<?php
/*
testopenid.php

Verbose Test of OpenID Connect Authorization Code Flow

JWT is validated localy with hard-coded public key.

This code is meant for testing, not for production !

For the authentification step, if you don't want to create an account 
on OAuthSD, use this credentials : 
login = bebert 
password = 012345678

Hint : test with and without SLI.

Author : 
Bertrand Degoy https://degoy.com
Credits :
bschaffer https://github.com/bshaffer/oauth2-server-php
Licence : MIT licence

*/

///// Configuration /////

/* Client's Public Key 
Warning : no double-quote, no indent!
Copy/Paste key from OAuthSD server
2018/11/25 
*/
define ('PUBLIC_KEY', 
'-----BEGIN PUBLIC KEY-----
MIGeMA0GCSqGSIb3DQEBAQUAA4GMADCBiAKBgGgptoyDkZQKfwBnwp7GFpONsV1R5aD7BdoO2/wsDM8nWOQNUOHcOIvnMZXKAWhdzp5OXNNjCBJUAUghxtC6fnc/FgWCEzkIWWBSK+L+21KdxU8aX5rpksskRcDN9mgK/mYN3Uhkuv+UJAf7UsIs/O9G8koak+qDr1aB7oYLcaHfAgMBAAE=
-----END PUBLIC KEY-----
');
$public_key = str_replace("\r\n", "\n", PUBLIC_KEY);

$client_id = 'testopenid';
$client_secret = 'thesecret';

$authorization_endpoint = 'https://oa.dnc.global/authorize';
$token_endpoint = 'https://oa.dnc.global/token';
$userinfo_endpoint = 'https://oa.dnc.global/userinfo';

//*** End of configuration ***

require_once __DIR__.'/../../oidc/includes/utils.php';     //*****

ini_set('display_errors', 1);

// Set session
session_save_path('/home/oadnc/sessions_oauthsd');          
session_name('oauthsd'); 
session_start();    

if ( !isset($_GET['error']) ) {

    if ( isset($_GET['code']) ) {

        if ( isset($_GET['state']) ) {

            // Check state
            if ( $_GET['state'] == decrypt(@$_SESSION['state']) ) {    //[dnc21]
            
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
                curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
                curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data));
                //curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);

                $res = curl_exec($h);

                if ($res)  {

                    curl_close($h);
                    $res = json_decode($res, true);

                    if  ( empty($res['error'] ) ) {

                        // Validate signed JWT token using client's public key
                        if ( $payload = decode_jwt($res['id_token'], $public_key, 'RS256') ) {

                            // If Token Response is valid goto step 3
                            // Step 3. Get UserInfo
                            $access_token = $res['access_token'];

                            /* Auth Header Methode
                            $headr = array();
                            $headr[] = 'Authorization: Bearer ' . $access_token;
                            $h = curl_init();
                            curl_setopt($h, CURLOPT_URL, $userinfo_endpoint); 
                            curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($h, CURLOPT_TIMEOUT, 10);
                            curl_setopt($h, CURLOPT_HTTPHEADER, $headr);
                            //curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
                            //*/

                            //* Post Methode  
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

                            if ( $res ) {

                                curl_close($h);
                                $res = json_decode($res, true);

                                if  ( empty($res['error'] ) ) {

                                    // Check User ID
                                    if ( $payload['sub'] == $res['sub'] ) {

                                        // Everithing Ok !
                                        echo "UserInfo Response:\n";
                                        print_r($res);

                                    } else  
                                        // User of ID Token doesn't match UserInfo's one
                                        exit('User mismatch, got : ' . $res['sub']);

                                } else
                                    // Token request error
                                    exit ('UserInfo Request error : ' . $res['error'] . ' : ' . $res['error_description']);

                            } else {
                                // Curl error during UserInfo request
                                exit('UserInfo Request error : ' . curl_error($h));
                                curl_close($h);
                            } 

                        } else
                            // Invalid id_token 
                            exit('Error : Invalid ID Token');

                    } else {
                        // Token request error
                        exit ('Token request error : ' . $res['error'] . ' : ' . $res['error_description']);
                    }

                } else {
                    // Curl error during Token request
                    exit('Token Request error : ' . curl_error($h));
                    curl_close($h);
                }
            
            } else 
                // Wrong State
                exit("Authorization error : incoherent State");

        } else 
            // Missing State
            exit("Authorization error : missing State");
            
    } else {

        // Step 1. Authorization Code request

        // Generate state.
        @session_regenerate_id();
        $state = session_id();
        $_SESSION['state'] = encrypt($state);   //[dnc21]
        
        $data = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'scope' => 'openid profile sli',
            'state' => $state,    
        );

        // Send authorization request. Note state parameter in URL 
        $authorization_endpoint .= '?' . http_build_query($data);
        header('Location: ' . $authorization_endpoint);
        exit();
    }

} else {
    // Authorization error 
    exit("Authorization error : {$_GET['error']} : {$_GET['error_description']}");
}




// Vérification du JWT

/**
* @author    Brent Shaffer <bshafs at gmail dot com>
* @license   MIT License
*
* Sépare les composantes du jeton, détecte les erreurs de format, vérifie la signature et retourne la charge utile ou false en cas d'erreur.
*
* @param mixed $jwt : le jeton JWT
* @param mixed $key : la clé publique
* @param mixed $allowedAlgorithms : un array des codes d'algorithmes autorisés (sous ensemble de HS256, HS384 ou HS512, RS256, RS384 et RS512). Si ce paramètre est précisé, le jeton doit indiquer l'algorithme et celui-ci doit être compris dans l'array.
* @param mixed return : charge utile (tableau associatif) ou false.
*/

function decode_jwt($jwt, $key = null, $allowedAlgorithms = true) {
    if (!strpos($jwt, '.')) {
        return false;
    }

    $tks = explode('.', $jwt);

    if (count($tks) != 3) {
        return false;
    }

    list($headb64, $payloadb64, $cryptob64) = $tks;

    if (null === ($header = json_decode(urlSafeB64Decode($headb64), true))) {
        return false;
    }

    if (null === $payload = json_decode(urlSafeB64Decode($payloadb64), true)) {
        return false;
    }

    $sig = urlSafeB64Decode($cryptob64);

    if ((bool) $allowedAlgorithms) {
        if (!isset($header['alg'])) {
            return false;
        }

        // check if bool arg supplied here to maintain BC
        if (is_array($allowedAlgorithms) && !in_array($header['alg'], $allowedAlgorithms)) {
            return false;
        }

        if (!verifySignature($sig, "$headb64.$payloadb64", $key, $header['alg'])) {
            return false;
        }
    }

    return $payload;
}



function verifySignature($signature, $input, $key, $algo = 'RS256') {
    // use constants when possible, for HipHop support
    switch ($algo) {
        case 'RS256':
            return openssl_verify($input, $signature, $key, defined('OPENSSL_ALGO_SHA256') ? OPENSSL_ALGO_SHA256 : 'sha256')  === 1;

        case 'RS384':
            return @openssl_verify($input, $signature, $key, defined('OPENSSL_ALGO_SHA384') ? OPENSSL_ALGO_SHA384 : 'sha384') === 1;

        case 'RS512':
            return @openssl_verify($input, $signature, $key, defined('OPENSSL_ALGO_SHA512') ? OPENSSL_ALGO_SHA512 : 'sha512') === 1;

        default:
            //Unsupported or invalid signing algorithm
    }
}
