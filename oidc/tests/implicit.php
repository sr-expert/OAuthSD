<?php
/* implicit.php
Test élémentaire du serveur OIDC avec le flux Implicit.
see : https://bshaffer.github.io/oauth2-server-php-docs/grant-types/implicit/

Auteur : Bertrand Degoy 
Copyright (c) 2019 DnC  
licence GPL
*/

define('PRIVATE', true);
require_once __DIR__.'/../../commons/configure_oidc.php'; 
require_once OIDCSRV_ROOT_PATH . 'includes/server.php';        

// create a request object to mimic an authorization code request
$request = new OAuth2\Request(array(
    'response_type' => 'id_token token',
    'client_id'  => 'oa_dnc_global',
    'scope' => 'openid profile',
    'state'     => 'gsfgrfgqgqsf',
    'nonce' => 'kfjghsfklgqsfkgqsdfu',
));
$response = new OAuth2\Response();
$server->handleAuthorizeRequest($request, $response, true);

// parse the returned URL to get the authorization code
$parts = parse_url($response->getHttpHeader('Location'));
parse_str($parts['query'], $query);

var_export($parts);

