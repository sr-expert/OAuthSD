<?php
/*
testopenid.php

Verbose Test of OpenID Connect Authorization Code Flow

This code is meant for testing, not for production !

For the authentification step, if you don't want to create an account 
on OAuthSD, use this credentials : 
login = bebert 
password = 012345678

Author : 
Bertrand Degoy https://degoy.com
Credits :
bschaffer https://github.com/bshaffer/oauth2-server-php
Licence : MIT licence

*/

///// Configuration /////

/* Client's Public Key 
Warning : no double-quote, no CRLF, no indent!
Copy/Paste key from OAuthSD server
2018/11/25 
*/
define ('PUBLIC_KEY', '-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA2dCHybdu/1pDk5BHSxMA
nMGygm8lm6s2hnru3GPH2JyMxrd92dC7TljI21P+egsnmjsUzaS1IWZPvIFvEOwO
5wP8gFyNm8fFkXSmrAEHXLFJ6WufF+f5Fg3pU5GDPjT5Z7ccWab5NM9w7ec433J1
XtePh2QjUbibu4rpwYh8ADODAJkyIRMhhYXqK0n8GgojkcgEZ172sB/NdbcNALPy
Qg0lMd3/AKxavTSSm9LslEyP+ZwBvTENzhoeQV2V7ZQ5xIZs6VBgrnsnYbgfbdQ+
Dbk2FRbtB2+g4rKbN04JheaZyFORseoigVJ6asQ5lUS/3cMIUj2C+VBj4xAyXp00
TMH8GtiGIQkVYjBd/Lsza11YwBOA8YYvDnTs/kzy9CqHjETdIHUlNqeaFbHSYTST
rUl9QxBN+JAVGs9YY9MWoiVsGex4MsTwf3PanKIlavKXeFSwppwMMvmdt+yrGraH
UKv5QP5NMbOu6/BghbuQZP4MoUnRxQxt8PN2e5M2b358C3tctgRQhRGBWaYw8B5J
/drz5VA8s14NkG162lBW7PLYhLqm8u2hpqIlOCVndwW2W+bCkXrfjj3jBHe4yauQ
vyQWcv3KaBV2HsUoY2sCAaC5nB46SV0UkAycX8xyqOsGJA64m2S+ntOQkB9R2x2y
4DjfJHTRTe2uXsaiYFahoLECAwEAAQ==
-----END PUBLIC KEY-----');
$public_key = str_replace("\r\n", "\n", PUBLIC_KEY);

$client_id = 'testopenid';
$client_secret = 'thesecret';

$authorization_endpoint = 'https://oa.dnc.global/authorize';
$token_endpoint = 'https://oa.dnc.global/token';
$introspection_endpoint = 'https://oa.dnc.global/introspect';
$userinfo_endpoint = 'https://oa.dnc.global/userinfo';

//*** End of configuration ***

ini_set('display_errors', 1);

// Set session
session_save_path('/home/oadnc/sessions_oauthsd');
session_name('oauthsd'); 
if ( isset($_GET['state']) ) session_id($_GET['state']);
session_start();

$continue = true;   

if ( !isset($_GET['error']) ) {

    if ( !empty( $code = $_GET['code']) ) {

        // Return from OIDC Step 1 (Authorization Code request)

        $state = $_GET['state'];
        if ( empty( $state ) ) {
            // Missing State
            exit("oidcclient - Authorization error : missing State");
            $continue = false;
        }

        // Check state
        if ( $continue AND ( $state != decrypt($_SESSION['state']) ) ) {
            // Wrong State
            exit("oidcclient - Authorization error : incoherent State");
            $continue = false;
        }

        if ( $continue ) {
            
            ///// OIDC Step 2. Token request

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

            $res1 = curl_exec($h);
            $errorc1 = curl_error($h);
            curl_close($h);
        }

        if ( $continue AND ( ! empty( $errorc1 ) ) ) {                            
            // Curl error during Token request
            exit('oidcclient - Token request Curl error : ' . $errorc1);                         
        }

        if ( $continue AND ( ! is_array(json_decode($res1, true) ) ) ) {
            exit('oidcclient - Introspection request result not JSON, malformed or empty');
            $continue = false;
        }

        $res2 = json_decode($res1, true);

        if  ( $continue AND ( ! empty($res2['error']) ) ) {
            // Token request error
            exit('oidcclient - Token request error : ' . $res2['error'] . ' : ' 
                . $res2['error_description']);
            $continue = false;
        }

        $access_token = $res2['access_token'];
        $id_token = $res2['id_token'];  //JWT


        ///// OIDC step 3. Validate signed JWT token using client's public key
        
        if ( $continue ) {
            $payload = decode_jwt($id_token, $public_key, 'RS256');
            if (  empty($payload) ) {
                // Invalid id_token 
                exit('testopenid - Error : Invalid ID Token');
                $continue = false;
            }    
        }

        if  ( $continue AND ( ! empty($payload['error'] ) ) ) {
            // JWT is inactive
            exit('testopenid - Error : ' . $payload['error']);
            $continue = false; 
        }

        if ( $continue AND ( ! empty( $payload['nonce'] ) ) ) {

            // Check nonce invariant trough session
            if ( $_SESSION['nonce'] != $payload['nonce'] ) {
                exit('testopenid - Wrong nonce');  
            } 

            //[dnc8] Check nonce as Client Footprint
            $cfp = compute_client_footprint($state);
            if ( $cfp != $payload['nonce'] ) {
                exit('testopenid - Forged nonce');
                $continue = false;     
            }   
        }

        if (  $continue AND ( ! $payload['active'] == 'true' ) ) {
            exit('testopenid - Invalid JWT');
            $continue = false;       
        }

        // If ID Token is valid continue with step 4

        ///// OIDC Step 4. Get UserInfo

        if ( $continue ) {
            $data2 = array(
                'access_token' => $access_token,
            );
            $h = curl_init($userinfo_endpoint);
            curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($h, CURLOPT_TIMEOUT, 10);
            curl_setopt($h, CURLOPT_POST, true);
            curl_setopt($h, CURLOPT_HTTPHEADER, 
                array('Content-Type: application/x-www-form-urlencoded')
            );    
            curl_setopt($h, CURLOPT_POSTFIELDS, 
                http_build_query($data2));

            $res4 = curl_exec($h);
            $errorc3 = curl_error($h);
            curl_close($h);

        }

        if ( $continue AND ( ! empty( $errorc3 ) ) ) {
            // Curl error during Introspection request
            exit('oidcclient - UserInfo request Curl error : ' . $errorc3);
        }

        if (  $continue AND ( ! is_array(json_decode($res4, true) ) ) ) {
            // script error ?
            exit('oidcclient - UserInfo result not JSON, malformed or empty');
            $continue = false;     
        }

        $userinfo = json_decode($res4, true);

        if  (  $continue AND ( ! empty($userinfo['error'] ) ) ) {
            // Token request error
            exit('oidcclient - UserInfo Request error : ' . $userinfo['error'] . ' : '
                . $res['error_description']);
            $continue = false;
        }

        if (  $continue AND ( ! $payload['sub'] == $userinfo['sub'] ) ) {
            // User of ID Token doesn't match UserInfo's one
            exit('oidcclient - UserInfo user mismatch, got : ' . $userinfo['sub']);
            $continue = false;
        }

        if ( $continue ) {
            // Everithing Ok !
            exit("oidcclient - UserInfo Response:\n" . print_r($userinfo, true)); 
        }

    } else {

        // Step 1. Authorization Code request

        /* Generate state.

        */
        @session_regenerate_id();
        $state = session_id();
        $_SESSION['state'] = encrypt($state);

        $data = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'scope' => 'openid profile',
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

function urlSafeB64Decode($b64) {
    $b64 = str_replace(array('-', '_'),
        array('+', '/'),
        $b64);
    return base64_decode($b64);
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
?>