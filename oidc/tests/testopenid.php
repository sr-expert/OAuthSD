<?php
/*
testopenid.php
See : http://bshaffer.github.io/oauth2-server-php-docs/overview/openid-connect/
*/

// Include our OAuth2 Server object
define('PRIVATE', true);
require_once __DIR__.'/../../commons/configure_oidc.php'; 
require_once OIDCSRV_ROOT_PATH . 'includes/server.php';    

// create a request object to mimic an authorization code request
/*
$request = new OAuth2\Request(array(
    'client_id'     => 'chemin_openid',           // ou testopenid
    'redirect_uri'  => 'http://chemindeleau.com/callback_openid.php',     // ou http://fake.com
    'response_type' => 'code',
    'scope'         => 'openid',
    'state'         => 'xyz',
));   */

$request = new OAuth2\Request(array(
    'client_id'     => 'oa_dnc_global',           // ou testopenid
    'redirect_uri'  => 'https://oa.dnc.global/?action=auth',     // ou http://fake.com
    'response_type' => 'code',
    'scope'         => 'openid',
    'state'         => '123xyz',
)); 

$response = new OAuth2\Response();
$server->handleAuthorizeRequest($request, $response, true);

// parse the returned URL to get the authorization code
$parts = parse_url($response->getHttpHeader('Location'));
parse_str($parts['query'], $query);

// pull the code from storage and verify an "id_token" was added
$code = $server->getStorage('authorization_code')
        ->getAuthorizationCode($query['code']);
var_export($code);



?>