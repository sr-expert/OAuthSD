<?php
/* implicit2.php
Test élémentaire du serveur OIDC avec le flux "Implicit".
see : https://bshaffer.github.io/oauth2-server-php-docs/grant-types/client-credentials/

Auteur : Bertrand Degoy 
Copyright (c) 2019 DnC  
licence GPL
*/

$client_id = 'oa_dnc_global';
$client_secret = 'Bydf5x!LmXjo';

$server = 'oa.dnc.global';
$authorization_endpoint = 'https://' . $server . '/authorize';
$token_endpoint = 'https://' . $server . '/token';
$introspection_endpoint = 'https://' . $server . '/introspect'; 
$userinfo_endpoint = 'https://' . $server . '/userinfo';

define('PRIVATE', true);
require_once __DIR__.'/../../commons/configure_oidc.php'; 
require_once OIDCSRV_ROOT_PATH . 'includes/server.php';    


$data = array(
    'response_type' => 'id_token token',
    'client_id'  => $client_id,
    'scope' => 'openid profile',
    'state'     => 'gsfgrfgqgqsf',
    'nonce' => 'kfjghsfklgqsfkgqsdfu',
); 

$authorization_endpoint .= '?' . http_build_query($data);
header('Location: ' . $authorization_endpoint);
exit();


