<?php
// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

// Important : redéfinir toutes les autorisations du plugin http abstrait !

// Ecraser aussi l'appel du pipeline ? Ou le laisser transparent ?

// Voir l'index, contenant à priori les collections disponibles
function autoriser_get_index($faire, $quoi, $id, $qui, $options){
    // toujours autoriser
    return true;
}

// Voir une liste d'objets par HTTP 
function autoriser_get_collection($faire, $quoi, $id, $qui, $options){

    if ( API_HTTP_TYPES_AUTORISES !== '' ) {    //*****
        // Vérifier que le type est autorisé
        if ( strpos( API_HTTP_TYPES_AUTORISES, $quoi ) === false ) 
            return false;   
    }

    if ( $qui['statut'] == '0minirezo' ) {
        // Toujours autoriser un administrateur
        return true;
    } else {
        switch ($quoi) {
            case 'credential' :
            case 'oidclog' :
                /* effectuer seulement des vérifications simples
                return simple_authorize();
                break; //*/   
            case 'auteur' :
            case 'client' :
            case 'user' :
                // vérifier l'autorisation
                return _autoriser($faire, $quoi, $id, $qui, $options);
                break;  
            default :
                // interdit 
                return false;           
        }
    }
}

// Voir un objet par HTTP : 
function autoriser_get_ressource($faire, $quoi, $id, $qui, $options){

    if ( API_HTTP_TYPES_AUTORISES !== '' ) {         //*****
        // Vérifier que le type est autorisé
        if ( strpos( API_HTTP_TYPES_AUTORISES, $quoi ) === false ) 
            return false;   
    }

    if ( $qui['statut'] == '0minirezo' ) {
        // Toujours autoriser un administrateur
        return true;
    } else {
        switch ($quoi) { 
            case 'oidclog' :  
                //* effectuer seulement des vérifications simples
                return simple_authorize();
                break; //*/   
            case 'credential' :
            case 'auteur' :
            case 'client' :
            case 'user' :
                // vérifier l'autorisation
                return _autoriser($faire, $quoi, $id, $qui, $options);
                break;  
            default :
                // on adopte les autorisations de l'utilisateur local sur l'objet 
                return autoriser('voir', $quoi, $id, $qui, $options);           
        }
    }
}


// Ajouter un objet par HTTP
function autoriser_post_collection($faire, $quoi, $id, $qui, $options){

    if ( API_HTTP_TYPES_AUTORISES !== '' ) {    //*****
        // Vérifier que le type est autorisé
        if ( strpos( API_HTTP_TYPES_AUTORISES, $quoi ) === false ) 
            return false;   
    }

    if ( $qui['statut'] == '0minirezo' ) {
        // Toujours autoriser un administrateur
        return true;
    } else {
        switch ($quoi) {
            case 'auteur' :
            case 'client' :
            case 'user' :
                // vérifier l'autorisation
                return _autoriser($faire, $quoi, $id, $qui, $options);
                break;  
            default :
                // interdit 
                return false;           
        }
    }
}

// Modifier un objet par HTTP : comme POST. 
function autoriser_put_ressource($faire, $quoi, $id, $qui, $options){
    return autoriser_post_collection('post_collection', $quoi, $id, $qui, $options);
}

// Supprimer un objet par HTTP : comme POST.
function autoriser_delete_ressource($faire, $quoi, $id, $qui, $options){
    return autoriser_post_collection('post_collection', $quoi, $id, $qui, $options);
}

// Ajouter/Modifier/Supprimer un objet par HTTP
function _autoriser_ecriture($faire, $quoi, $id, $qui, $options){

    if ( API_HTTP_TYPES_AUTORISES !== '' ) {  //*****
        // Vérifier que le type est autorisé
        if ( strpos( API_HTTP_TYPES_AUTORISES, $quoi ) === false ) 
            return false;   
    }

    if ( $qui['statut'] == '0minirezo' ) {
        // Toujours autoriser un administrateur
        return true;
    } else {
        switch ($quoi) {
            case 'user' :
                // Autoriser la mise à jour des utilisateurs finaux sous conditions
                return _autoriser($faire, $quoi, $id, $qui, $options);
                break;  
            default :
                // on interdit le reste 
                return false;           
        }
    }
}

function _autoriser($faire, $quoi, $id, $qui, $options) {

    if ( API_HTTP_TYPES_AUTORISES !== '' ) {
        // Vérifier que le type est autorisé
        if ( strpos( API_HTTP_TYPES_AUTORISES, $quoi ) === false ) 
            return false;   
    }

    if ( API_HTTP_AUTHENTICATE ) {
        // Utiliser l'authentification
        if ( empty( $token = $_REQUEST['code'] ) ) {
            // pas de jeton
            return false;
        } else {
            // Vérifier le jeton passé dans la requête
            return ( oauth_authorize($token) !== false );
        }

    } else {
        // sinon, on effectue quelques vérifications simples
        return simple_authorize();
    }   

}

/**
* Autorisation simple 
*/
function simple_authorize () {
    // on effectue quelques vérifications simples
    if ( ! empty(API_HTTP_CLIENT_IP) ) {
        // Si API_HTTP_CLIENT_IP, vérifier l'identité avec l'IP du client
        if ( $_SERVER['REMOTE_ADDR'] != API_HTTP_CLIENT_IP ) return false;    
    }
    if ( ! empty(API_HTTP_CLIENT_HOST) ) {
        // Si API_HTTP_CLIENT_HOST, vérifier l'identité avec le domaine du client
        if ( $_SERVER['HTTP_HOST'] != API_HTTP_CLIENT_HOST ) return false;    
    }
    return true;
}

/*
* Autorisation avec OAuth Server by DnC
* Validation du jeton d'identité avec OpenID Connect JWT Introspection.
* Voir : https://oa.dnc.global/-JSON-Web-Token-JWT-18-.html
* Auteur : Bertrand degoy
* Copyright (c) 2016-2019 DnC

* @param mixed $idtoken Jeton d'identité à valider
* Return : True or False
*/

/**
* Validation du jeton d'identité avec OpenID Connect Introspection.
*
* @param mixed $token Jeton d'accès ou d'identité à valider
* Return : True or False

*/
function oauth_authorize($token) {

    
    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG
    
    $Ok = false;

    include_spip('inc/distant'); 

    if ( substr_count( $token, '.' ) === 2 ) { 

        /* Nous avons probablement un jeton JWT
        Validation du jeton d'identité avec OpenID Connect JWT Introspection.
        Voir : https://oa.dnc.global/-JSON-Web-Token-JWT-18-.html */

        /* méthode GET (pas très bonne)
        $url = AUTHENTICATION_SERVER_URL . "introspect?token=" . $token; 
        $response = recuperer_url($url);
        if ( (int)$response['status'] === 200 ) {  
            $jwt = json_decode($response['page'], true); //*/
        
        // méthode Bearer (meilleure)
        $h = curl_init(AUTHENTICATION_SERVER_URL . 'introspect');
        curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($h, CURLOPT_TIMEOUT, 10);
        curl_setopt($h, CURLOPT_HTTPHEADER, 
            array('Authorization: Bearer ' 
                . $token));
        $response = curl_exec($h);      
        if ( (int)curl_getinfo($h)['http_code'] === 200 ) {  
            $jwt = json_decode($response, true);  //*/
            
            if ( $jwt['active'] == true ) {
                $subject = $jwt['sub'];
            }
            if ( ! empty(API_HTTP_CLIENT_ID) ) {
                // Si API_HTTP_CLIENT_ID est défini, vérifier l'identité avec sub transmis par le jeton d'identité
                if ( strpos(API_HTTP_CLIENT_ID, $subject) === false  ) $subject = false;  // API_HTTP_CLIENT_ID est une liste d'IDs  
            } 
        }
        $Ok = (!empty($subject));   

    } else {  //[dnc27f]

        // Nous avons probablement un jeton d'accès
        $sanitized_token = preg_replace('/[^a-f0-9"\']/', '', $token);
        if ( !empty($sanitized_token) AND $sanitized_token === $token ) {

            // Interroger la fonction d'introspection de OAuth 2.0
            $url = AUTHENTICATION_SERVER_URL . "oauth/introspect?token=" . $token; // nous sommmes en local
            $response = recuperer_url($url);  

            if ( (int)$response['status'] === 200 )         
                $Ok = true;
        }

    }
    
    if ( $Ok AND isset($_SERVER["HTTP_REFERER"]) ) {
        $urlParts = parse_url($_SERVER["HTTP_REFERER"]);
        if ( $urlParts['host'] !== $_SERVER["HTTP_HOST"] ) {
            // CORS : autoriser l'origine
            $apphost = $urlParts['scheme'] . "://" . $urlParts['host'];
            include_spip('inc/headers');    
            header('Access-Control-Allow-Origin', $apphost);
        }
    }

    return $Ok;

}    
