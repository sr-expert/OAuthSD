<?php
/* 
Projet OAuthSD
API HTTP REST pour DataTables
server-side processing

Utilisé par evenements_stats, Tableau des événements avec DataTables
Voir :
https://datatables.net/manual/ajax#Loading-data
https://datatables.net/manual/server-side

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
function http_datatables_oidc_logs_get_collection_dist($request, $response) {

    $json = array();
    
    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG
    
    $contexte = $request->query->all();
    
    if ( 
        !empty( $draw = (int)$contexte['draw'] ) 
        //AND !empty( $length = (int)$contexte['length'] )
        //AND !is_array( $search = $contexte['search'] )
        //AND !is_array( $order = $contexte['order'] )
        //AND !is_array( $columns = $contexte['columns'] )
    ) {
        /**
        * Dans cette version, on ne prend pas en compte les paramètres search, 
        * context et columns qui seront fixes.
        * Si length = -1 on ne retourne rien.
        */
        $start = (int)$contexte['start'];
        if ( empty( $length = (int)$contexte['length'] ) ) $length = 100;      //???
        $limit = intval($start).", ".intval($length);
        $lignes = sql_allfetsel( '*', 'spip_oidc_logs', null, null, 'id_oidc_log', $limit);     // ou datetime DESC ???
    }    
    
    else   // Sinon on ne comprend pas la requête
    {
        // On utilise la fonction d'erreur générique pour renvoyer dans le bon format.
        $fonction_erreur = charger_fonction('erreur', "http/datatables/");
        $response = $fonction_erreur(415, $request, $response);
        return $response;
    }

    $items = array(); 
    foreach ($lignes as $index => $value) {       //TODO: limiter le nombre de lignes
        $items[] = array_values($value);    // retourner un array non associatif         
    }
    
    $nbrecords = sql_countsel('spip_oidc_logs');  //???
    
    $toreturn = array(
        'draw' => $draw,
        'recordsTotal' =>  $nbrecords,    //???
        'recordsFiltered' => $nbrecords,
        'data' => $items,    
    );
    
    $jsonreturned = json_encode($toreturn);
    
    $response->setStatusCode(200);
    $response->setCharset('utf-8');
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent($jsonreturned);  

    return $response;
}

