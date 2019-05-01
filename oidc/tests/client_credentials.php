<?php
/* client_credentials.php
Test élémentaire du serveur OIDC avec le flux "Client Credentials".
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
require_once __DIR__.'/../../oidc/includes/configure.php'; 
require_once __DIR__.'/../../oidc/includes/server.php';
     
/* Credentials dans le corps de la requête :
$data = array(
    'grant_type'     => 'client_credentials',
    'client_id' => $client_id,
    'client_secret' => $client_secret,
    'state'         => 'gsfgrfgqgqsf',
); 
$h = curl_init($token_endpoint);
curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
curl_setopt($h, CURLOPT_TIMEOUT, 10);
curl_setopt($h, CURLOPT_POST, true);
curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data));
//curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
//*/

//* Credentials avec HTTP Basic Authentication :
$data = array(
    'grant_type'     => 'client_credentials',
    'state'         => 'gsfgrfgqgqsf',
); 
$h = curl_init($token_endpoint);
curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
curl_setopt($h, CURLOPT_TIMEOUT, 10);
curl_setopt($h, CURLOPT_POST, true);
curl_setopt($h, CURLOPT_USERPWD, "{$client_id}:{$client_secret}");
curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data));
//*/


$res = curl_exec($h);

if ( is_array(json_decode($res, true) ) ) {

    curl_close($h);
    $res = json_decode($res, true);

}

var_export($res);

