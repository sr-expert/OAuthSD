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
function http_collectionjson_clients_get_collection_dist($request, $response) {

    include_spip('inc/autoriser');

    $json = array();

    $contexte = $request->query->all();

    if ( empty($count = intval($contexte['count'])) ) $count = API_DEFAULT_LENGTH;
    
    if ( empty(intval($contexte['offset'])) ) {
        /**
        * https://.../http.api/collectionjson/clients/
        * Les clients étant triés par date décroissante, retourne les 20 premiers evenements, 
        * https://.../http.api/collectionjson/clients/?count=<nombre> 
        * Les clients étant triés par date décroissante, retourne count evenements depuis le rang offset, 
        */
        $limit = "0," . intval($count);
        $where = null;   
    }

    else if ( !empty($offset = intval($contexte['offset'])) ) {  
        /**
        * https://.../http.api/collectionjson/clients/?offset=<rang>&count=<nombre> 
        * Les clients étant triés par date décroissante, retourne count evenements depuis le rang offset, 
        */
        $limit = $offset . ", " . $count;
        $where = null;   

    } else { // Si on ne comprend pas la requête,
        /* On utilise la fonction d'erreur générique pour renvoyer dans le bon format.
        $fonction_erreur = charger_fonction('erreur', "http/collectionjson/");
        $response = $fonction_erreur(415, $request, $response);
        return $response; */

        /** 
        * https://.../http.api/collectionjson/clients/
        * retourne les API_DEFAULT_LENGTH derniers évènements.
        */
        $where = null;
        $limit = API_DEFAULT_LENGTH;
    }

    if ( is_null($limit)) $limit = API_MAX_ITEMS_RETURNED ;  

    $champs = get_public_fields('clients');  // string des champs non sensibles
    $lignes = sql_allfetsel( $champs, 'spip_clients', $where, null, 'id_client', $limit);
    
    $json = array(
        'collection' => array(
            'version' => '1.0',
            'href' => url_absolue(self('&')),
            'items' => $items,       // les champs sont un array, ex: text = items[i].data[j].name + '=' + items[i].data[j].value + "<br />";
            'objects' => $lignes,    // les champs sont un objet, ex : text = objects[i].titre;
        ),
    );  

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
function http_collectionjson_users_post_collection_dist($request, $response){

    include_spip('inc/session');   // ???
    include_spip('inc/autoriser');
    include_spip('inc/filtres');
    include_spip('inc/verifier');
    $verifier = charger_fonction('verifier', 'inc/');

    $erreurs = array();

    // Saisir les données de la requête  (format collectionjson)
    if ( $contenu = $request->getContent()
    and $json = json_decode($contenu, true)
    and is_array($json)
    and isset($json['collection']['items'][0]['data'])
    and $data = $json['collection']['items'][0]['data']
    and is_array($data)
    ) {
        // Pour chaque champ envoyé, 
        foreach ($data as $champ) {
            // Seulement pour les champs acceptés pour l'enregistrement d'un client final
            if (
            isset($champ['name'])
            and isset($champ['value'])
            ) {
                $value = trim(urldecode($champ['value']));    // Les données auront été urlencode-ées. 
                /* Effectuer quelques vérifications. On suppose que les vérifications d'usage 
                ont été faites par l'application cliente. Mais il faut garder à l'esprit que ce 
                pourrait être un pourriciel qui a posté les données ! On refait donc ici les vérifications.*/
                switch ( $champ['name'] ) {     
                    case 'client_id' :
                        $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad client_id',
                                'code' => 415);

                        } else {
                            $ze_requete[$champ['name']] = $good;
                        } 
                        break;
                    case 'client_secret' :
                        $good = str_replace(' ', '', textebrut($value));   // Ce doit être une chaine simple
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad client secret',
                                'code' => 415);

                        } else {
                            $ze_requete[$champ['name']] = $good;
                        } 
                        break;
                    case 'redirect_uri' :
                        $erreursmsg = $verifier($value,'url');
                        if ( empty($value) OR !empty($erreursmsg) ) {    // Null interdit  
                            $erreurs[] = array(
                                'title' => $champ['name'] . ' : ' . $erreursmsg,
                                'code' => 415);
                        } else {
                            $ze_requete[$champ['name']] = $value;
                        }
                        break;
                    case 'grant_types' :
                        $good = textebrut($value);   // Ce doit être des mots simples //TODO: lister les valeurs permises
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad grant type(s)',
                                'code' => 415);

                        } else {
                            $ze_requete[$champ['name']] = $good;
                        } 
                    case 'scope' :
                        $good = textebrut($value);   // Ce doit être des mots simples //TODO: lister les valeurs permises
                        if ( empty($good) OR $value !== $good ) {  // Null interdit
                            $erreurs[] = array(
                                'title' => 'Bad scope(s)',
                                'code' => 415);

                        } else {
                            $ze_requete[$champ['name']] = $good;
                        } 
                    case 'date_publication' :               // déprécié ?
                    case 'updated_time' : 
                    case 'created_time' :
                        if ( !empty($value) ) {        // Null possible
                            $dateTime = new DateTime($value); 
                            if ( empty(@$dateTime->getTimestamp()) ) {    // ???
                                $erreurs[] = array(
                                    'title' => $champ['name'] . ' : wrong date format',
                                    'code' => 415);
                            } else {
                                $ze_requete[$champ['name']] = $value;
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
                                $ze_requete[$champ['name']] = $good;
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
                            $ze_requete[$champ['name']] = $good;
                        }
                    case 'texte1' :
                    case 'texte2' :
                        // autres champs de texte facultatifs 
                        $good = textebrut($value);   // Ce doit être une chaine simple ou nulle
                        if ( $value !== $good ) {
                            $erreurs[] = array(
                                'title' => 'Bad ' . $champ['name'],
                                'code' => 415);
                        } else {
                            $ze_requete[$champ['name']] = $good;
                        } 
                        break;
                    case 'css' :  // trop dangereux !
                    case 'maj' :
                    case 'composition' : // ???
                    case 'composition_lock' :   
                        // ignorer ces champs
                        break; 
                    default :
                        // erreur : champ inconnu
                        $erreurs[] = array(
                            'title' => 'Unknown ' . $champ['name'],
                            'code' => 415);
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

            // Vérifier l'unicité du champs client_id
            $erreur = '';
            $row = sql_fetsel("client_id", "spip_clients", "client_id=" . sql_quote($ze_requete['client_id']));
            if ( !empty($row) ) $erreur = "Duplicate client_id ";

            if ( empty($erreur) ) {
                // Ok : créer le client
                $Ok = sql_insertq('spip_clients', $ze_requete);

                if ( $Ok ) {
                    // C'est Ok, on renvoie 201
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

            } else {
                // erreur duplication
                $response->setStatusCode(415);
                $json_reponse = array(
                    'collection' => array(
                        'version' => '1.0',
                        'href' => url_absolue(self('&')),
                        'error' => array(
                            'title' => _T('erreur'),
                            'code' => 415,
                        ),
                        'errors' => array(array(
                            'title' => $erreur,
                            'code' => 415))
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

    $response->headers->set('Content-Type', 'application/json');
    $response->setCharset('utf-8');
    return $response;
}
