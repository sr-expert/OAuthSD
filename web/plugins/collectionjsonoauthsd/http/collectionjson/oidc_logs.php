<?php
/* 
Projet OauthSD

HTML Rest WS : Type oidc_logs Table spip_oidc_logs
Utilise le plugin Serveur HTTP abstrait

Copyright(c) 2019 DnC
Auteurs : Bertrand Degoy
Licence : GPL 3

*/

//* Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
} //*/

/**
*
* @param Request $request
* @param Response $response
* @return Response $response
*/
function http_collectionjson_oidc_logs_get_collection_dist($request, $response) {

    include_spip('inc/autoriser');

    $json = array();

    $contexte = $request->query->all();
    
    if ( empty($count = intval($contexte['count'])) ) $count = API_DEFAULT_LENGTH; 
    
    if ( 
    !is_null($tmax = $contexte['tmax']) 
    ) {
        /**
        * https://.../http.api/<format>/oidc_logs/?ts=<timeserial>&count=<nombre>
        * On va centrer les événements retournés sur ts en tentant de répartir 
        * count/2 événements de part et d'autre de l'événement le plus proche de ts.
        * On ne prend pas en compte offset.
        */
        if ( ! is_null($contexte['count']) ) $count = $contexte['count'];     // sinon le défaut défini en tête
        // Déterminer l'ID de l'événement le plus proche du ts donné
        $datetime = date('Y-m-d H:i:s', $tmax);  
        $ligne = sql_fetsel( 'id_oidc_log', 'spip_oidc_logs', "datetime < " . sql_quote($datetime), null, 'datetime DESC', 1);
        $id = $ligne['id_oidc_log'];
        // Id min et max
        $idmin = $id - $count/2;
        $idmax = $id + $count/2; 
        $where = "id_oidc_log >= " . $idmin . " AND id_oidc_log <= " . $idmax;
    }

    else if (
    !is_null( $tmin = $contexte['tmin'] ) 
    AND !is_null( $tmax = $contexte['tmax'] )
    )  
    {
        /** 
        * https://.../http.api/<format>/oidc_logs/?tmin=<timeserial>&tmax=<timeserial>
        * retourne les évènements entre tmin et tmax.
        * On ne prend pas en compte offset et count.
        */
        $total = null;
        $dmin = date('Y-m-d H:i:s', $tmin); 
        $dmax = date('Y-m-d H:i:s', $tmax); 
        $where = "datetime >= " . $dmin . " AND datetime <= " . $dmax;
        $limit = API_MAX_ITEMS_RETURNED;
    }
    
     else if ( 
    !is_null($offset = $contexte['offset'])
    AND !is_null($count = $contexte['count'])
    ) {  
        /**
        * https://.../http.api/<format>/oidc_logs/?offset=<rang>&count=<nombre> 
        * Les événements étant triés par date décroissante, retourne count evenements depuis le rang offset, 
        */
        $limit = intval($offset).", ".intval($count);
        $where = null;   
    }
       
    else if ( !empty($offset = intval($contexte['offset'])) ) {  
        /**
        * https://.../http.api/<format>/oidc_logs/?count=<nombre> 
        * Les événements étant triés par date décroissante, retourne count evenements depuis le rang 0, 
        */
        $limit = "0," . intval($count);
        $where = null;   
    }
    
    else if ( empty(intval($contexte['offset'])) ) {
        /**
        * https://.../http.api/users/
        * Les événements étant triés par date décroissante, retourne les 100 derniers événements, 
        * https://.../http.api/users/?count=<nombre> 
        * Les événements étant triés par date décroissante, retourne count événements depuis le rang offset, 
        */
        $limit = "0," . intval($count);
        $where = null;   
    }

    else   // Sinon on ne comprend pas la requête
    {
        // On utilise la fonction d'erreur générique pour renvoyer dans le bon format.
        $fonction_erreur = charger_fonction('erreur', "http/collectionjson/");
        $response = $fonction_erreur(415, $request, $response);
        return $response;
    }

    $lignes = sql_allfetsel( '*', 'spip_oidc_logs', $where, null, 'datetime DESC', $limit);

    foreach ($lignes as $champs) {       //TODO: limiter le nombre de lignes
        $items[] = collectionjson_get_objet(null, null, $champs);
    }

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
    $response->setContent(json_encode($json));

    return $response;
}
