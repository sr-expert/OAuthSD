<?php
/* test_token.php
Test élémentaire du serveur OIDC : appel direct au code token

Auteur : Bertrand Degoy 
Copyright (c) 2019 DnC  
licence GPL
*/

define('PRIVATE', true);
require_once __DIR__.'/../../commons/configure_oidc.php'; 
require_once OIDCSRV_ROOT_PATH . 'includes/server.php';         

// create a request object to mimic a token request
$request = new OAuth2\Request(array(
    'grant_type' => 'authorization_code',
    'code'  => $_GET['code'],
    'redirect_uri' => $_GET['redirect_uri'],
));
$response = new OAuth2\Response();
$server->handleTokenRequest($request);



