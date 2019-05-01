<?php
/*
introspect.php

Introspection Controller for OAuth2 Server

See : OAuth 2.0 Token Introspection https://tools.ietf.org/html/rfc7662 

Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
Tous droits réservés

*/

// include our OAuth2 Server object
define('PRIVATE', true);
define('PARAM_TOKEN_NAME', 'token'); // Following RFC 7662 introspect should utilize 'token'
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

// Success :
$token = $ResourceController->getToken();

$user_id = $token['user_id'];
$sanitized_user_id = preg_replace('/[^A-Za-z0-9"\']/', '', $user_id);

$userinfo = array();
if ( $sanitized_user_id == $user_id ) {
    $cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);
    $stmt = $cnx->prepare($sql = "SELECT * FROM spip_users WHERE username=:user_id");
    $stmt->execute(compact('user_id'));
    $userinfo = $stmt->fetch(\PDO::FETCH_ASSOC);
}

$extra = array();
if ( (bool)$userinfo['verified'] ) {
    $extra = array(
        'sub' => $userinfo['email']
    );    
}

// Retourner les données au format JSON dans le corps de la réponse    

$aresult =  array(
    'active' => true, 
    'scope' => json_encode($token['scope']),
    'client_id' => $token['client_id'],
    'username' => $userinfo['username'],
    'exp' => $token["expires"]
);

if ( is_array($extra)) $aresult = array_merge( $aresult, $extra ); 

echo json_encode( $aresult );   

?>