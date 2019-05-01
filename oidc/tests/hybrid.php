<?php
/* Test élémentaire du serveur OIDC avec le flux Hybrid.

Seul 'response_type' => 'code id_token' fonctionne.

*/

define('PRIVATE', true);
require_once __DIR__.'/../../oidc/includes/configure.php'; 
require_once __DIR__.'/../../oidc/includes/server.php';     

// create a request object to mimic an authorization code request
$request = new OAuth2\Request(array(
    'client_id'     => 'oa_dnc_global',
    'redirect_uri'  => 'https://oa.dnc.global/?action=auth',
    'response_type' => 'code token',
    'scope'         => 'openid',
    'state'         => 'gsfgrfgqgqsf',
));
$response = new OAuth2\Response();
$server->handleAuthorizeRequest($request, $response, true);

// parse the returned URL to get the authorization code
$parts = parse_url($response->getHttpHeader('Location'));
parse_str($parts['query'], $query);

var_export($parts);

