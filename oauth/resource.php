<?php
/*
resource.php

Resource Controller for OAuth2 Server

See : http://bshaffer.github.io/oauth2-server-php-docs/cookbook/

Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
Tous droits réservés
 
*/

// include our OAuth2 Server object
define('PRIVATE', true);
require_once __DIR__.'/includes/server.php';        

// Handle a request to a resource and authenticate the access token

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

$ResourceController = $server->getResourceController();

//if (!$ResourceController->verifyResourceRequest($request, $response, $scope) ) {
if (!$ResourceController->verifyResourceRequest($request, $response) ) {
    $response->send();
    die;
} 

//$token = $ResourceController->getAccessTokenData($request, $response);
$token = $ResourceController->getToken();

/* //[dnc8] Extension ufp (dispositif à garder secret!)    //ufp
if ( $ufp = $request->query['ufp']) {
    // Une déclaration User Fingerprint (ufp) figure dans la requête
    if ( $token['ufp'] != $ufp ) {
        // répondre comme pour un jeton invalide afin de ne pas dévoiler le dispositif
        $response->setError(401, "invalid_token","The access token provided is invalid"); 
        $response->send();
        die;
    }        
}  //*/ 

// User info. Transmettre ou non des données personnelles ?
//TODO: pour transmettre les informations personnelles, agir selon une variable de configuration, un scope etc.
$user_id = $token['user_id'];
$sanitized_user_id = preg_replace('/[^A-Za-z0-9"\']/', '', $user_id);
$userinfo = array();
if ( $sanitized_user_id == $user_id ) {
    $cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);
    //$stmt = $cnx->prepare($sql = "SELECT * FROM spip_users WHERE username=:user_id");
    $stmt = $cnx->prepare($sql = "SELECT id_user, verified, zoneinfo, locale, postal_code, country, updated_time, created_time, statut FROM spip_users WHERE username=:user_id");
    $stmt->execute(compact('user_id'));
    $userinfo = $stmt->fetch(\PDO::FETCH_ASSOC);
    unset($userinfo['password']);
}

// Retourner les données au format JSON dans le corps de la réponse    
$aresult =  array(
    'success' => true, 
    'client_id' => $token['client_id'],
    'user_id' => $token['user_id'],
    'expires' => $token["expires"],
    'scope' => $token['scope']
);

if ( is_array($userinfo)) $aresult = array_merge( $aresult, $userinfo ); 

echo json_encode( $aresult );

?>