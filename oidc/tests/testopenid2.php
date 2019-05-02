<?php
/*
testopenid2.php

Test de OpenID Connect
Inscrivez sur ce serveur une application de test,
Renseignez les constantes ci-dessous en conséquence,
Lancez ce script : http://oa.dnc.global/oidc/testopenid2.php

Author : 
Bertrand Degoy https://degoy.com
Credits :
bschaffer https://github.com/bshaffer/oauth2-server-php
 
Licence : MIT licence
Copyright (c) 2016 - DnC
 

*/

ini_set('display_errors', 1);

$client_id = 'testopenid';
$client_secret = 'thesecret';
//$redirect_uri = '';

$authorization_endpoint = 'https://oa.dnc.global/authorize';
$token_endpoint = 'https://oa.dnc.global/token';
$userinfo_endpoint = 'https://oa.dnc.global/userinfo';


if (isset($_GET['error']))
{
    exit("Error: {$_GET['error']}. Description: {$_GET['error_description']}");
}
else if (isset($_GET['code']) && isset($_GET['state']))
{
    // Step 2. Token request

    $code = $_GET['code'];
    echo "Authorization Code is {$code}\n\n";

    $data = array(
        'grant_type' => 'authorization_code',
        'code' => $code,
    );

    $h = curl_init($token_endpoint);
    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($h, CURLOPT_TIMEOUT, 10);
    curl_setopt($h, CURLOPT_USERPWD, "{$client_id}:{$client_secret}");
    curl_setopt($h, CURLOPT_POST, true);
    curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data));
    //curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);

    $res = curl_exec($h);
    if (!$res)
        exit(curl_error($h));

    curl_close($h);
    $res = json_decode($res, true);

    echo "Token Response:\n";
    print_r($res);
    echo "\n";

    // Here you should decode JWT token and check sign using server's public key
    // $payload = Jwt::decode($response['id_token'], $this->serverPublicKey);

    // If Token Response is valid goto step 3
    // Step 3. Get UserInfo
    $access_token = $res['access_token'];
    $headr = array();
    $headr[] = 'Authorization: Bearer ' . $access_token;

    /* Méthode Auth Header  Ne fonctionne pas !
    $h = curl_init();
    curl_setopt($h, CURLOPT_URL, $userinfo_endpoint); 
    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($h, CURLOPT_TIMEOUT, 10);
    curl_setopt($h, CURLOPT_HTTPHEADER, $headr);
    //curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
    //*/

    //* Méthode Post   Fonctionne
    $data2 = array(
    'access_token' => $access_token,
    );
    $h = curl_init($userinfo_endpoint);
    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($h, CURLOPT_TIMEOUT, 10);
    curl_setopt($h, CURLOPT_POST, true);
    curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));     // semble facultatif : curl le fait pour nous?
    curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data2));
    //*/

    $res = curl_exec($h);
    if (!$res)
        exit(curl_error($h));

    $http_code = curl_getinfo( $h, CURLINFO_HTTP_CODE );    //dgy

    curl_close($h);
    $res = json_decode($res, true);

    echo "UserInfo Response:\n";
    print_r($res);
}
else
{
    // Step 1. Authorization Code request

    $data = array(
        'response_type' => 'code',
        'client_id' => $client_id,
        'state' => 'xyz',
        'scope' => 'openid profile',    
    );

    $authorization_endpoint .= '?' . http_build_query($data);
    header('Location: ' . $authorization_endpoint);
    exit();
}
?>