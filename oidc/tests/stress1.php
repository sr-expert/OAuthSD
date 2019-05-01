<?php
/** Test élémentaire du serveur OIDC
* Ce test boucle sur lui même en appelant Authorize.
* Il n'y a pas plus sauvage!
* 
* Usage : https://.../oidc/tests/stress1.php 
* Adresse de retour : https://.../oidc/tests/stress1.php
* 
*/

$client_id = 'stress1';
$client_secret = 'qsDr43!Ml@';

$server = 'oa.dnc.global';
$authorization_endpoint = 'https://' . $server . '/authorize';
$token_endpoint = 'https://' . $server . '/token';
$introspection_endpoint = 'https://' . $server . '/introspect'; 
$userinfo_endpoint = 'https://' . $server . '/userinfo';

define('PRIVATE', true);
require_once __DIR__.'/../../oidc/includes/configure.php';      
require_once __DIR__.'/../../oidc/includes/utils.php';

//*** End of configuration ***

ini_set('display_errors', 1);

// Set session
session_start();

if ( isset($_GET['code']) ) {
    // Return from Authorization Code request
    // Step 2. Token request

    $code = $_GET['code'];

    $data = array(
        'grant_type' => 'authorization_code',
        'code' => $code,
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

    unset($_GET);

}

// Step 1. Authorization Code request
$state = @$_SESSION['state'];
$state += 1;
$_SESSION['state'] = $state; 
$data = array(
    'response_type' => 'code',
    'client_id' => $client_id,
    'scope' => 'openid sli',        // granted scopes, must be in available scopes
    'state' => $state, 
);
$authorization_endpoint .= '?' . http_build_query($data);
header('Location: ' . $authorization_endpoint);
exit();






