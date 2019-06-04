<?php
/* authorization_code.php
Test élémentaire du serveur OIDC avec le flux Authorization Code.

Auteur : B.Shaffer 
see : http://bshaffer.github.io/oauth2-server-php-docs/overview/openid-connect/
licence GPL
*/

define('PRIVATE', true);
require_once __DIR__.'/../../commons/configure_oidc.php'; 
require_once OIDCSRV_ROOT_PATH . 'includes/server.php';     

// create a request object to mimic an authorization code request
$request = new OAuth2\Request(array(
    'client_id'     => 'oa_dnc_global',
    'redirect_uri'  => 'https://oa.dnc.global/?action=auth',
    'response_type' => 'code',
    'scope'         => 'openid',
    'state'         => 'gsfgrfgqgqsf',
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

