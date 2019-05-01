<?php
/* 
Projet OauthSD

HTML Rest WS
CRUD sur le type clients
Utilise le plugin Serveur HTTP abstrait

Copyright(c) 2019 DnC
clients : Bertrand Degoy
Licence : GPL 3

*/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

/**
* get collection
* traite les paramètres offset et count.
* @param Request $request
* @param Response $response
* @return Response $response
*/
function http_json_clients_get_collection_dist($request, $response) {

    include_spip('inc/autoriser');

    $json = array();

    $contexte = $request->query->all();

    if ( empty($count = intval($contexte['count'])) ) $count = API_DEFAULT_LENGTH;
    
    if ( empty(intval($contexte['offset'])) ) {
        /**
        * https://.../http.api/json/clients/
        * Les clients étant triés par date décroissante, retourne les 20 premiers evenements, 
        * https://.../http.api/json/clients/?count=<nombre> 
        * Les clients étant triés par date décroissante, retourne count evenements depuis le rang offset, 
        */
        $limit = "0," . intval($count);
        $where = null;   
    }

    else if ( !empty($offset = intval($contexte['offset'])) ) {   
        /**
        * https://.../http.api/json/clients/?offset=<rang>&count=<nombre> 
        * Les clients étant triés par date décroissante, retourne count evenements depuis le rang offset, 
        */
        $limit = $offset . ", " . $count;
        $where = null;   

       } else { // Si on ne comprend pas la requête,
        /* On utilise la fonction d'erreur générique pour renvoyer dans le bon format.
        $fonction_erreur = charger_fonction('erreur', "http/json/");
        $response = $fonction_erreur(415, $request, $response);
        return $response; */

        /** 
        * https://.../http.api/json/clients/
        * retourne les API_DEFAULT_LENGTH derniers évènements.
        */
        $where = null;
        $limit = API_DEFAULT_LENGTH;
    }

    if ( is_null($limit)) $limit = API_MAX_ITEMS_RETURNED ;

    $champs = get_public_fields('clients');  // string des champs non sensibles
    $lignes = sql_allfetsel( $champs, 'spip_clients', $where, null, 'id_client', $limit);

    $response->setStatusCode(200);
    $response->setCharset('utf-8');
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode($lignes));

    return $response;
}

/**
* Ajouter un client : suivant le cas, inscription ou ajout par un admin
*
* @param Request $request
* @param Response $response
* @return void
*/
function http_json_clients_post_collection_dist($request, $response){

    include_spip('inc/session');   // ???
    include_spip('inc/autoriser');
    include_spip('inc/filtres');
    include_spip('inc/verifier');
    $verifier = charger_fonction('verifier', 'inc/');
    $fonction_erreur = charger_fonction('erreur', "http/json/");


    $erreurs = array();

    // Saisir les données de la requête, trois formats possibles
    if ( $_SERVER['CONTENT_TYPE'] == 'application/json' ) {  // format application/json
        $content = file_get_contents("php://input");  //read the HTTP body
        $data = json_decode($content, true);
    } else if ( $_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded' ) { 
        if ( ! is_array($data = json_decode(urldecode($_POST['data']),true) ) ) // format urlencoded data application/x-www-form-urlencoded 
            $data = $_POST; // format application/x-www-form-urlencoded
    }

    if ( is_array($data) ) {
        // Pour chaque champ envoyé, 
        foreach ($data as $name => $value ) {
            // Seulement pour les champs acceptés pour l'enregistrement d'un client final
            if ( isset($name) and isset($value) ) {
                $value = trim($value);   
                /* Effectuer quelques vérifications. On suppose que les vérifications d'usage 
                ont été faites par l'application cliente. Mais il faut garder à l'esprit que ce 
                pourrait être un pourriciel qui a posté les données ! On refait donc ici les vérifications.*/
                switch ( $name ) {     
                    case 'client_id' :
                        $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad client_id',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        } 
                        break;
                    case 'client_secret' :
                        $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                        if ( empty($good) OR $value !== $good OR strlen($good) < 8 ) {  // Null interdit, longueur suffisante
                            $erreurs[] = array(
                                'title' => 'Bad client secret',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        } 
                        break;
                    case 'redirect_uri' :
                        $erreursmsg = $verifier($value,'url');
                        if ( empty($value) OR !empty($erreursmsg) ) {    // Null interdit  
                            $erreurs[] = array(
                                'title' => $name . ' : ' . $erreursmsg,
                                'code' => 415);
                        } else {
                            $ze_requete[$name] = $value;
                        }
                        break;
                    case 'grant_types' :
                        $good = textebrut($value);   // Ce doit être des mots simples //TODO: lister les valeurs permises
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad grant type(s)',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        }
                        break; 
                    case 'scope' :
                        $good = textebrut($value);   // Ce doit être des mots simples //TODO: lister les valeurs permises
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad scope(s)',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        }
                        break; 
                    case 'date_publication' :               // déprécié ?
                    case 'updated_time' : 
                    case 'created_time' :
                        if ( !empty($value) ) {        // Null possible
                            $dateTime = new DateTime($value); 
                            if ( empty(@$dateTime->getTimestamp()) ) {    // ???
                                $erreurs[] = array(
                                    'title' => $name . ' : wrong date format',
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break;
                    case 'user_id' :
                        if ( !empty($value) ) {        // Null possible
                            $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                            if ( $value !== $good ) { 
                                $erreurs[] = array(
                                    'title' => 'Bad user_id',
                                    'code' => 415);

                            } else {
                                $ze_requete[$name] = $good;
                            }
                        } 
                        break;
                    case 'client_ip' :
                        $good = textebrut($value);   // Ce doit être des mots simples 
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad client IP',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        }
                        break;
                    case 'texte1' :
                    case 'texte2' :
                        // autres champs de texte facultatifs 
                        $good = textebrut($value);   // Ce doit être une chaine simple ou nulle
                        if ( $value !== $good ) {
                            $erreurs[] = array(
                                'title' => 'Bad ' . $name,
                                'code' => 415);
                        } else {
                            $ze_requete[$name] = $good;
                        } 
                        break;
                    case 'css' :  // trop dangereux !
                    case 'maj' :
                    case 'composition' : // ???
                    case 'composition_lock' :
                    case 'DBGSESSID' : // debugger
                        // ignorer ces champs
                        break; 
                    default :
                        // erreur : champ inconnu
                        $erreurs[] = array(
                            'title' => 'Unknown ' . $name,
                            'code' => 415);
                        break;
                }
            }
        } // foreach 

        // Vérifier la présence des champs requis
        if ( 
        empty($ze_requete['client_id']) 
        OR empty($ze_requete['client_secret']) 
        OR empty($ze_requete['redirect_uri']) 
        OR empty($ze_requete['grant_types']) 
        ) {
            $erreurs[] = array(
                'title' => 'Missing field(s)',
                'code' => 415);
        }

        // Vérifier l'unicité du champs client_id
        $row = array();
        $row = sql_fetsel("client_id", "spip_clients", 'client_id=' . sql_quote($ze_requete['client_id']));   //*****
        if ( !empty($row['client_id']) )  $erreurs[] = array(
            'title' => 'Duplicate client_id',
            'code' => 415);

        // S'il y a des erreurs, on génère une réponse d'erreur
        if (count($erreurs)) {
            $response->setStatusCode(415);
            $json_reponse = array(
                'collection' => array(
                    'version' => '1.0',
                    'href' => url_absolue(self('&')),
                    'error' => array(
                        'title' => _T('erreur'),
                        'code' => 415,
                    ),
                    'errors' => $erreurs,
                ),
            );
            $response->setContent(json_encode($json_reponse));  

        } else { // Sinon on continue le traitement

            // Ok : créer le client
            $id_client = sql_insertq('spip_clients', $ze_requete);

            if ( $id_client ) {
                // C'est Ok, on renvoie 201 et le n° d'enregistrement
                $response->setStatusCode(201);                         
                $response->setContent(json_encode(array(
                    'client_id' => $ze_requete['client_id'],
                    'id_client' => $id_client,
                )));
                // Il faut également créer et enrégistrer la paire de clé publique/privée    //*****
                include_spip('inc/pkeys');
                $void = create_and_save_pkeys( $id_client, $ze_requete['client_id'] );
                //TODO : gérer l'erreur     

            } else {
                // Erreur SQL
                $response->setStatusCode(500);
                $json_reponse = array(
                    'collection' => array(
                        'version' => '1.0',
                        'href' => url_absolue(self('&')),
                        'error' => array(
                            'title' => _T('erreur'),
                            'code' => 500,
                        ),
                        'errors' => array(array(
                            'title' => 'Erreur SQL ' . sql_errno() . ' ' . sql_error(),
                            'code' => 500))
                    ),
                );
                $response->setContent(json_encode($json_reponse));

            }
        }

    } else {  // Données de la requête mal formattées ou nulles
        // On utilise la fonction d'erreur générique pour renvoyer dans le bon format
        $response = $fonction_erreur(415, $request, $response);
    }

    $response->headers->set('Content-Type', 'application/json');
    $response->setCharset('utf-8'); 
    return $response;
}

/**
* Mettre à jour un client : suivant le cas, inscription ou ajout ???
*
* @param Request $request
* @param Response $response
* @return void
*/
function http_json_clients_put_ressource_dist($request, $response) {

    include_spip('inc/session');   // ???
    include_spip('inc/autoriser');
    include_spip('inc/filtres');
    include_spip('inc/verifier');
    $verifier = charger_fonction('verifier', 'inc/');
    $fonction_erreur = charger_fonction('erreur', "http/json/");

    $erreurs = array();

    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

    // Saisir les données de la requête, trois formats possibles
    if ( $_SERVER['CONTENT_TYPE'] == 'application/json' ) {  // format application/json
        $content = file_get_contents("php://input");  //read the HTTP body
        $data = json_decode($content, true);
    } else if ( $_SERVER['CONTENT_TYPE'] == 'application/x-www-form-urlencoded' ) { 
        if ( ! is_array($data = json_decode(urldecode($_POST['data']),true) ) ) // format urlencoded data application/x-www-form-urlencoded 
            $data = $_POST; // format application/x-www-form-urlencoded
    }

    // Saisir la ressource (obligatoire pour PUT, mais l'erreur aura été générée avant par api_http)
    $arg = $request->query->get('arg');
    list($format, $collection, $ressource) = explode('/', $arg);
    if ( empty($ressource) ) 
        $erreurs[] = array(
            'title' => 'Existing client_id',
            'code' => 415);

    if ( is_array($data) AND empty($erreurs) ) {
        // Pour chaque champ envoyé, 
        foreach ($data as $name => $value ) {
            // Seulement pour les champs acceptés pour l'enregistrement d'un client final
            if ( isset($name) and isset($value) ) {
                $value = trim($value);   
                /* Effectuer quelques vérifications. On suppose que les vérifications d'usage 
                ont été faites par l'application cliente. Mais il faut garder à l'esprit que ce 
                pourrait être un pourriciel qui a posté les données ! On refait donc ici les vérifications.*/
                switch ( $name ) {     
                    case 'client_id' :
                        $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad client_id',
                                'code' => 415);
                        } else {
                            // Vérifier l'unicité du champs client_id
                            $row = array();
                            $row = sql_fetsel("id_client", "spip_clients", "client_id=" . sql_quote($good));
                            if ( !empty($row['id_client']) AND ($row['id_client'] !== $ressource) )  {
                                $erreurs[] = array(
                                    'title' => 'Existing client_id',
                                    'code' => 415);
                                break;
                            }
                            // Ok
                            $ze_requete[$name] = $good;
                        } 
                        break;
                    case 'client_secret' :
                        $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                        if ( empty($good) OR $value !== $good OR strlen($good) < 8 ) {  // Null interdit, longueur suffisante
                            $erreurs[] = array(
                                'title' => 'Bad client secret',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        } 
                        break;
                    case 'redirect_uri' :
                        $erreursmsg = $verifier($value,'url');
                        if ( empty($value) OR !empty($erreursmsg) ) {    // Null interdit  
                            $erreurs[] = array(
                                'title' => $name . ' : ' . $erreursmsg,
                                'code' => 415);
                        } else {
                            $ze_requete[$name] = $value;
                        }
                        break;
                    case 'grant_types' :
                        $good = textebrut($value);   // Ce doit être des mots simples //TODO: lister les valeurs permises
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad grant type(s)',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        }
                        break; 
                    case 'scope' :
                        $good = textebrut($value);   // Ce doit être des mots simples //TODO: lister les valeurs permises
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad scope(s)',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        }
                        break; 
                    case 'date_publication' :               // déprécié ?
                    case 'updated_time' : 
                    case 'created_time' :
                        if ( !empty($value) ) {        // Null possible
                            $dateTime = new DateTime($value); 
                            if ( empty(@$dateTime->getTimestamp()) ) {    // ???
                                $erreurs[] = array(
                                    'title' => $name . ' : wrong date format',
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break;
                    case 'user_id' :
                        if ( !empty($value) ) {        // Null possible
                            $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                            if ( $value !== $good ) { 
                                $erreurs[] = array(
                                    'title' => 'Bad user_id',
                                    'code' => 415);

                            } else {
                                $ze_requete[$name] = $good;
                            }
                        } 
                        break;
                    case 'client_ip' :
                        $good = textebrut($value);   // Ce doit être des mots simples 
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad client IP',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        }
                        break;
                    case 'texte1' :
                    case 'texte2' :
                        // autres champs de texte facultatifs 
                        $good = textebrut($value);   // Ce doit être une chaine simple ou nulle
                        if ( $value !== $good ) {
                            $erreurs[] = array(
                                'title' => 'Bad ' . $name,
                                'code' => 415);
                        } else {
                            $ze_requete[$name] = $good;
                        } 
                        break;
                    case 'id_client' :
                    case 'css' :  // trop dangereux !
                    case 'maj' :
                    case 'composition' : // ???
                    case 'composition_lock' :
                    case 'DBGSESSID' : // debugger
                        // ignorer ces champs
                        break; 
                    default :
                        // erreur : champ inconnu
                        $erreurs[] = array(
                            'title' => 'Unknown ' . $name,
                            'code' => 415);
                        break;
                }
            }
        } // foreach 

        // S'il y a des erreurs, on génère une réponse d'erreur
        if (count($erreurs)) {
            $response->setStatusCode(415);
            $response->headers->set('Content-Type', 'application/json');
            $response->setCharset('utf-8');
            $json_reponse = array(
                'collection' => array(
                    'version' => '1.0',
                    'href' => url_absolue(self('&')),
                    'error' => array(
                        'title' => _T('erreur'),
                        'code' => 415,
                    ),
                    'errors' => $erreurs,
                ),
            );
            $response->setContent(json_encode($json_reponse));  

        } else { // Sinon on continue le traitement

            // Ok : mettre à jour
            $Ok = sql_updateq('spip_clients', $ze_requete, "id_client=" . sql_quote($ressource));

            if ( $Ok ) {   
                // C'est Ok, on renvoie le n° d'enregistrement (la ressource). Donc PUT répond comme POST.   
                $response->setStatusCode(204);  // plutôt que 200 ???
                $response->headers->set('Content-Type', 'application/json');                  
                $response->setContent(json_encode(array(
                    'client_id' => $ze_requete['client_id'],
                    'id_client' => $ressource,
                )));

            } else {
                // Erreur SQL
                $response->setStatusCode(500);
                $response->headers->set('Content-Type', 'application/json');
                $response->setCharset('utf-8');
                $json_reponse = array(
                    'collection' => array(
                        'version' => '1.0',
                        'href' => url_absolue(self('&')),
                        'error' => array(
                            'title' => _T('erreur'),
                            'code' => 500,
                        ),
                        'errors' => array(array(
                            'title' => 'Erreur SQL ' . sql_errno() . ' ' . sql_error(),
                            'code' => 500))
                    ),
                );
                $response->setContent(json_encode($json_reponse));

            }
        }

    } else {  // Données de la requête mal formattées ou nulles
        // On utilise la fonction d'erreur générique pour renvoyer dans le bon format
        $fonction_erreur = charger_fonction('erreur', "http/collectionjson/");
        $response = $fonction_erreur(415, $request, $response);
    }

    return $response;
}

/**
* Delete Ressource
*
* @param Request $request
* @param Response $response
* @return void
*/
function http_json_clients_delete_ressource_dist($request, $response) {

    include_spip('inc/session');   // ???
    include_spip('inc/autoriser');
    include_spip('inc/filtres');
    include_spip('inc/verifier');
    $verifier = charger_fonction('verifier', 'inc/');   
    $fonction_erreur = charger_fonction('erreur', "http/json/");

    $erreurs = array();

    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

    // Saisir la ressource (obligatoire pour DELETE, mais l'erreur aura été générée avant par api_http)
    $arg = $request->query->get('arg');
    list($format, $collection, $ressource) = explode('/', $arg);
    if ( empty($ressource) )  {
        $response = $fonction_erreur(415, $request, $response);

    } else {  
        // Supprimer la resource indiquée
        $Ok = sql_delete('spip_clients', 'id_client = ' . sql_quote($ressource));
        // Supprimer la paire de clé publique/privée    //*****
        $void = sql_delete('spip_public_keys', 'id_client = ' . sql_quote($ressource));    
    }

    if ( $Ok != 1 ) {
        $response->setStatusCode(500);  // ??? 
        $response->setContent(json_encode(array(
            'error' => 'Delete failed',
        )));          
    } else {  
        $response->setStatusCode(204);  // plutôt que 200 ???
        $response->setContent(json_encode(array(
            'count' => 1,
        )));    
    }

    $response->headers->set('Content-Type', 'application/json');                  
    return $response;           
}

