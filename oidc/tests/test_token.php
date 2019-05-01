<?php
/* test_token.php
Test élémentaire du serveur OIDC : appel direct au code token

Auteur : Bertrand Degoy 
Copyright (c) 2019 DnC  
licence GPL
*/

define('PRIVATE', true);
require_once __DIR__.'/../../oidc/includes/configure.php'; 
require_once __DIR__.'/../../oidc/includes/server.php';     

// create a request object to mimic a token request
$request = new OAuth2\Request(array(
    'grant_type' => 'authorization_code',
    'code'  => $_GET['code'],
    'redirect_uri' => $_GET['redirect_uri'],
));
$response = new OAuth2\Response();
$server->handleTokenRequest($request);



