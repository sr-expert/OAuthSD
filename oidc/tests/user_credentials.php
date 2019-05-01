<?php
/* user_credentials.php
Test élémentaire du serveur OIDC avec le flux "User Credentials Grant" (password) 
ou encore "Resource Owner Password Credentials Grant".

see : https://bshaffer.github.io/oauth2-server-php-docs/grant-types/user-credentials/

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
require_once __DIR__.'/../../oidc/includes/configure.php'; 
require_once __DIR__.'/../../oidc/includes/server.php';
     

$data = array(
    'grant_type'     => 'password',
    'username' => 'bebert',
    'password' => '012345678',
    'state'         => 'gsfgrfgqgqsf',
    'scope' => 'openid profile sli',
);

$h = curl_init($token_endpoint);
curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
curl_setopt($h, CURLOPT_TIMEOUT, 10);
curl_setopt($h, CURLOPT_USERPWD, "{$client_id}:{$client_secret}");
curl_setopt($h, CURLOPT_POST, true);
curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data));

$res = curl_exec($h);

if ( is_array(json_decode($res, true) ) ) {

    curl_close($h);
    $res = json_decode($res, true);

}

var_export($res);

