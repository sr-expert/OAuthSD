<?php
/* 
Projet OauthSD

HTML Rest WS
CRUD sur le type users
Utilise le plugin Serveur HTTP abstrait

Copyright(c) 2019 DnC
Auteurs : Bertrand Degoy
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
function http_json_users_get_collection_dist($request, $response) {

    include_spip('inc/autoriser');

    $json = array();

    $contexte = $request->query->all();

    if ( empty($count = intval($contexte['count'])) ) $count = API_DEFAULT_LENGTH;   
    
    if ( empty(intval($contexte['offset'])) ) {
        /**
        * https://.../http.api/users/
        * Les utilisateurs étant triés par date décroissante, retourne les 20 premiers evenements, 
        * https://.../http.api/users/?count=<nombre> 
        * Les utilisateurs étant triés par date décroissante, retourne count evenements depuis le rang offset, 
        */
        $limit = "0," . intval($count);
        $where = null;   
    }

     else if ( !empty($offset = intval($contexte['offset'])) ) {  
        /**
        * https://.../http.api/json/users/?offset=<rang>&count=<nombre> 
        * Les users étant triés par date décroissante, retourne count utilisateurs depuis le rang offset, 
        */
        $limit = $offset . ", " . $count;
        $where = null;   

    } else { // Si on ne comprend pas la requête,
        /* On utilise la fonction d'erreur générique pour renvoyer dans le bon format.
        $fonction_erreur = charger_fonction('erreur', "http/collectionjson/");
        $response = $fonction_erreur(415, $request, $response);
        return $response; */

        /** 
        * https://.../http.api/json/users/
        * retourne les 100 derniers évènements.
        */
        $where = null;
        $limit = API_DEFAULT_LENGTH;
    }

    if ( is_null($limit)) $limit = API_MAX_ITEMS_RETURNED ;  

    $champs = get_public_fields('users');  // string des champs non sensibles
    $lignes = sql_allfetsel( $champs, 'spip_users', $where, null, 'id_user', $limit);

    $response->setStatusCode(200);
    $response->setCharset('utf-8');
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode($lignes));

    return $response;
}

/**
* Ajouter un auteur : suivant le cas, inscription ou ajout par un admin
*
* @param Request $request
* @param Response $response
* @return void
*/
function http_json_users_post_collection_dist($request, $response){

    include_spip('inc/session');   // ???
    include_spip('inc/autoriser');
    include_spip('inc/filtres');
    include_spip('inc/verifier');
    $verifier = charger_fonction('verifier', 'inc/');
    $fonction_erreur = charger_fonction('erreur', "http/json/");

    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

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
            if ( isset($name) AND isset($value) ) {
                $value = trim(urldecode($value));    // Les données auront été urlencode-ées. 
                /* Effectuer quelques vérifications. On suppose que les vérifications d'usage 
                ont été faites par l'application cliente. Mais il faut garder à l'esprit que ce 
                pourrait être un pourriciel qui a posté les données ! On refait donc ici les vérifications.*/
                switch ( $name ) {     
                    case 'username' :
                        $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad username',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        } 
                        break;
                    case 'email' :
                        $erreursmsg = $verifier($value,'email');
                        if ( empty($value) OR !empty($erreursmsg) ) {   // Null interdit
                            $erreurs[] = array(
                                'title' => 'email : ' . $erreursmsg,
                                'code' => 415);
                        } else {
                            $ze_requete[$name] = $value;
                        } 
                        break;
                    case 'password' :
                        $erreursmsg = $verifier($value,'entier');    // OAuthSD impose un nombre entier
                        if ( empty($value) OR !empty($erreursmsg) ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'password : ' . $erreursmsg,
                                'code' => 415);
                        } else {
                            // On enregistre le hash du password, pas le password
                            $pswdh = password_hash($good, PASSWORD_BCRYPT); // Toujours 60 chars
                            $ze_requete[$name] = $value;
                        } 
                        break;
                    case 'picture' :   // url ???
                    case 'website' :     
                        if ( !empty($value) ) {  // Null possible  
                            $erreursmsg = $verifier($value,'url');
                            if ( !empty($erreursmsg) ) {   
                                $erreurs[] = array(
                                    'title' => $name . ' : ' . $erreursmsg,
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break;
                    case 'verify' :
                        $ze_requete[$name] = (bool)$value;   // ???
                        break;
                    case 'birthday' :    
                        // date au format JJ/MM/AAAA
                        if ( !empty($value) ) {        // Null possible
                            $erreursmsg = $verifier($value,'date');    
                            if ( !empty($erreursmsg) ) {   
                                $erreurs[] = array(
                                    'title' => 'birthday : ' . $erreursmsg,
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break;
                    case 'phone_number' :
                        if ( !empty($value) ) {        // Null possible
                            $erreursmsg = $verifier($value,'telephone');      // ???
                            if ( !empty($erreursmsg) ) {   
                                $erreurs[] = array(
                                    'title' => 'phone_number : ' . $erreursmsg,
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break;
                    case 'postal_code' :
                        if ( !empty($value) ) {        // Null possible
                            $erreursmsg = $verifier($value,'code_postal');      // ???
                            if ( !empty($erreursmsg) ) {   
                                $erreurs[] = array(
                                    'title' => 'postal_code : ' . $erreursmsg,
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break; 
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
                    case 'given_name' :
                    case 'middle_name' :
                    case 'family_name' :
                    case 'nickname' :
                    case 'gender' : 
                    case 'profil' :
                    case 'zoneinfo' :
                    case 'locale' :
                    case 'street_address' :
                    case 'locality' :
                    case 'region' :
                    case 'country' :
                    case 'profile' :   // ???   
                    case 'comment' :
                    case 'scope' :     
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
                    case 'statut' :
                    case 'maj' :
                    case 'composition' : // ???
                    case 'composition_lock' :   
                        // ignorer ces champs
                        break; 
                    default :
                        // erreur : champ inconnu
                        $erreurs[] = array(
                            'title' => 'Unknown ' . $name,
                            'code' => 415);
                }
            }
        } // foreach

        // Vérifier la présence des champs requis
        if ( empty($ze_requete['username']) OR empty($ze_requete['password']) ) {
            $erreurs[] = array(
                'title' => 'Missing field(s)',
                'code' => 415);
        }

        // Vérifier l'unicité des champs username (et email)
        $row = array();
        $row = sql_fetsel("id_user", "spip_users", "username=" . sql_quote($ze_requete['username']));
        if ( !empty($row['id_user']) )  $erreurs[] = array(
            'title' => 'Duplicate username',
            'code' => 415);
        if ( !empty($ze_requete['email']) ) { 
            // Si l'email est fourni, vérifier son unicité
            $row = sql_fetsel("id_user", "spip_users", "email=" . sql_quote($ze_requete['email']));
            if ( !empty($row['id_user']) )  $erreurs[] = array(
                'title' => 'Duplicate email',
                'code' => 415);
        }

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

            // Ok : créer l'utilisateur
            $id_user = sql_insertq('spip_users', $ze_requete);

            if ( $id_user ) {
                // C'est Ok, on renvoie 201 et le n° d'enregistrement                    
                $response->setContent(json_encode(array(
                    'id_user' => $id_user,
                )));
                $response->setStatusCode(201);

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
function http_json_users_put_ressource_dist($request, $response){

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
            'title' => 'No Ressource given',
            'code' => 415);

    if ( is_array($data) AND empty($erreurs) ) {
        // Pour chaque champ envoyé, 
        foreach ($data as $name => $value ) {
            // Seulement pour les champs acceptés pour l'enregistrement d'un client final
            if ( isset($name) AND isset($value) ) {
                $value = trim(urldecode($value));    // Les données auront été urlencode-ées. 
                /* Effectuer quelques vérifications. On suppose que les vérifications d'usage 
                ont été faites par l'application cliente. Mais il faut garder à l'esprit que ce 
                pourrait être un pourriciel qui a posté les données ! On refait donc ici les vérifications.*/
                switch ( $name ) {     
                    case 'username' :
                        $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad username',
                                'code' => 415);

                        } else {
                            $ze_requete[$name] = $good;
                        } 
                        break;
                    case 'email' :
                        $erreursmsg = $verifier($value,'email');
                        if ( empty($value) OR !empty($erreursmsg) ) {   // Null interdit
                            $erreurs[] = array(
                                'title' => 'email : ' . $erreursmsg,
                                'code' => 415);
                        } else {
                            $ze_requete[$name] = $value;
                        } 
                        break;
                    case 'password' :
                        $erreursmsg = $verifier($value,'entier');    // OAuthSD impose un nombre entier
                        if ( empty($value) OR !empty($erreursmsg) ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'password : ' . $erreursmsg,
                                'code' => 415);
                        } else {
                            // On enregistre le hash du password, pas le password
                            //$pswdh = password_hash($good, PASSWORD_BCRYPT); // Toujours 60 chars
                            $pswdh = sha1($good); // Format attendu par le serveur OAuthSD
                            $ze_requete[$name] = $pswdh;
                        } 
                        break;
                    case 'picture' :   // url ???
                    case 'website' :     
                        if ( !empty($value) ) {  // Null possible  
                            $erreursmsg = $verifier($value,'url');
                            if ( !empty($erreursmsg) ) {   
                                $erreurs[] = array(
                                    'title' => $name . ' : ' . $erreursmsg,
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break;
                    case 'verify' :
                        $ze_requete[$name] = (bool)$value;   // ???
                        break;
                    case 'birthday' :    
                        // date au format JJ/MM/AAAA
                        if ( !empty($value) ) {        // Null possible
                            $erreursmsg = $verifier($value,'date');    
                            if ( !empty($erreursmsg) ) {   
                                $erreurs[] = array(
                                    'title' => 'birthday : ' . $erreursmsg,
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break;
                    case 'phone_number' :
                        if ( !empty($value) ) {        // Null possible
                            $erreursmsg = $verifier($value,'telephone');      // ???
                            if ( !empty($erreursmsg) ) {   
                                $erreurs[] = array(
                                    'title' => 'phone_number : ' . $erreursmsg,
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break;
                    case 'postal_code' :
                        if ( !empty($value) ) {        // Null possible
                            $erreursmsg = $verifier($value,'code_postal');      // ???
                            if ( !empty($erreursmsg) ) {   
                                $erreurs[] = array(
                                    'title' => 'postal_code : ' . $erreursmsg,
                                    'code' => 415);
                            } else {
                                $ze_requete[$name] = $value;
                            }
                        } 
                        break; 
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
                    case 'given_name' :
                    case 'middle_name' :
                    case 'family_name' :
                    case 'nickname' :
                    case 'gender' : 
                    case 'profil' :
                    case 'zoneinfo' :
                    case 'locale' :
                    case 'street_address' :
                    case 'locality' :
                    case 'region' :
                    case 'country' :
                    case 'profile' :   // ???   
                    case 'comment' :
                    case 'scope' :     
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
                    case 'id_user' :
                    case 'statut' :  //???
                    case 'maj' :
                    case 'composition' : // ???
                    case 'composition_lock' :   
                        // ignorer ces champs
                        break; 
                    default :
                        // erreur : champ inconnu
                        $erreurs[] = array(
                            'title' => 'Unknown ' . $name,
                            'code' => 415);
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
            $Ok = sql_updateq('spip_users', $ze_requete, "id_user=" . sql_quote($ressource));

            if ( $Ok ) {   
                // C'est Ok, on renvoie le n° d'enregistrement (la ressource). Donc PUT répond comme POST.   
                $response->setStatusCode(204);  // plutôt que 200 ???
                $response->headers->set('Content-Type', 'application/json');                  
                $response->setContent(json_encode(array(
                    'id_user' => $ressource,
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
function http_json_users_delete_ressource_dist($request, $response) {

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
        $Ok = sql_delete('spip_users', 'id_user =' . sql_quote($ressource));    
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




