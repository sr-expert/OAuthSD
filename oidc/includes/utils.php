<?php
/*  utils.php

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

function urlSafeB64Encode($data) {
    $b64 = base64_encode($data);
    $b64 = str_replace(array('+', '/', "\r", "\n", '='),
        array('-', '_'),
        $b64);
    return $b64;
}

function urlSafeB64Decode($b64)           
{
    $b64 = str_replace(array('-', '_'),
        array('+', '/'),
        $b64);
    return base64_decode($b64);
}

/** [dnc8]
* Compute User's FingerPrint. DnC specific, to keep secret !
* @param string $state : any string, session-stable, to make ufp change between session.
*/
function compute_user_fingerprint( $state = '' ) {

    $trace = $state.   // pour avoir un ufp toujours différent à chaque session, même avec un même client
    @$_SERVER['REMOTE_ADDR'] . 
    @$_SERVER['HTTP_USER_AGENT'];

    $ufp = md5($trace);                  //ufp

    if ( $ufp !== False ) {  
        $ufp = urlsafeB64Encode($ufp);
    } // else erreur technique

    return $ufp;
} 


//[dnc9]
function send_private_encoded_cookie( $cookie_name, $jcookiedata, $lifetime=3600 ) {  
    //* Encode cookie json data 
    $encoded = private_encode($jcookiedata); 
    // Send cookie to user-agent in server's domain
    setcookie($cookie_name, $encoded, time() + $lifetime, '/', OIDC_SERVER_DOMAIN, true, true);     
}

function private_encode( $decoded ) {  
    //* Encode data 
    return base64_encode(encrypt($decoded)); //[dnc9'] 
}

//[dnc9]
function private_decode( $encoded ) {
    //* Decode data  
    return decrypt(base64_decode($encoded)); //[dnc9']
}

/** [dnc34]
* that_session_start() est utilisé par le controleur Authorize pour créer une session 
* à partir de l'identifiant de session qui lui est passé.    
* @param mixed $name
* @param mixed $id
* @param mixed $dir
*/

function that_session_start($name, $id = null, $dir=null) {
    
    session_set_cookie_params(0, '/', OIDC_SERVER_DOMAIN, true, true);
    
    if ( ! empty($dir) ) {
        session_save_path($dir);
    }
    
    session_name($name);
    
    if ( !is_null($id) ) session_id($id);
    
    return session_start();    
}

/** [dnc34]
* new_session_start() est utilisé par les tests locaux pour créer une nouvelle session. 
*  https://stackoverflow.com/questions/24964699/php-how-can-i-create-multiple-sessions mod dgy
* @param mixed $name
* @param mixed $dir
* @returns
*/
function new_session_start($name, $dir=null) {
    
    session_set_cookie_params(0, '/', OIDC_SERVER_DOMAIN, true, true);
    
    if ( ! empty($dir) ) {
        session_save_path($dir);
    }
    session_name($name);
    
    if(!isset($_COOKIE[$name])) {
        if ( function_exists('session_create_id') ) {         
            $_COOKIE[$name] = session_create_id();   // PHP 7
        } else {
            $_COOKIE[$name] = getRandomBytes();      // probabilité de collision : 10**-98    
        }
    }
    
    session_id($_COOKIE[$name]);
    session_start();
    session_regenerate_id(true);

    return $_COOKIE[$name] = session_id();

}
// Voir également : https://blog.teamtreehouse.com/how-to-create-bulletproof-sessions


/**
* Destroy current session and all cookies for the current domain (including SLI Cookie).
* Must be used before leaving each time we have a doubt about the end user's identity.
*/
function destroy_all_session_data() {

    $params = session_get_cookie_params();
    // Destroy current session
    setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
    @session_destroy();
    session_write_close();

    /** Destroy all other cookies on server domain
    * give them a blank value and set them to expire in the past.
    */
    if (!empty($_COOKIE)) {
        foreach($_COOKIE as $name => $cookie) {
            if ( $name !== 'DBGSESSID') {   // keep this one
                discard_cookie($name);
            }
        }
    } 
    
}

function discard_cookie($name) {
    @setcookie($name, '', time()-1000, '/', OIDC_SERVER_DOMAIN, true, true);
    @setcookie($name, '', time()-1000, '/', OIDC_SERVER_DOMAIN, true, true);
    unset($_COOKIE[$name]);
}

function is_valid_session_id($session_id) {
    return preg_match('/^[-,a-zA-Z0-9]{1,128}$/', $session_id) > 0;
}


/* [dnc9'] crypto.php  2018/03/26
Utilise les fonctions de la bibliothèque PHP OpenSSL pour le cryptage et le décryptage symétrique.
Voir : http://php.net/manual/fr/book.openssl.php
[33az]
*/

//require_once DIR_FS_CATALOG . 'ext/options/includes/functions/keys.inc';
define('KEY', "85861b51b063f867fb0e43b3dc85831d24181e465efd05f3e8d25818d8125fe8"); 
define('HKEY',"35cffafe002333fca221f57a85c4b43c490f708107ed7bff56f01e06d0272b66");


/**
* Cryptage avec Open SSL
* 
* @var string $plaintext message à crypter
* @param string $key : la clé (sans laquelle ciphertext ne pourra être décrypté).
* @return mixed  : URL Safe B64 encoded encrypted text.
*/
function encrypt( $plaintext, $key = null ) {
    if ( is_null( $key ) ) $key = KEY;
    $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");  // ou AES-256-CBC ? 
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, HKEY, $as_binary=true);   
    $ciphertext = urlSafeB64Encode( $iv.$hmac.$ciphertext_raw );
    return $ciphertext;
}

/**
* Décryptage avec Open SSL
* 
* @param string $ciphertext : chaîne à décrypter.
* @param string $key : la clé avec laquelle la chaîne a été cryptée. 
* @return mixed
*/
function decrypt( $ciphertext, $key = KEY ) {
    if ( is_null( $key ) ) $key = KEY;
    $c = urlSafeB64Decode($ciphertext);
    $ivlen = openssl_cipher_iv_length($cipher="AES-128-CBC");
    $iv = substr($c, 0, $ivlen);
    $hmac = substr($c, $ivlen, $sha2len=32);
    $ciphertext_raw = substr($c, $ivlen+$sha2len);
    $original_plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, HKEY, $as_binary=true);    
    if (@hash_equals($hmac, $calcmac)) { //PHP 5.6+ timing attack safe comparison
        return $original_plaintext;
    } else {
        return '';
    }
}

/**
* Retourne un hash pour $raw, avec un éventuel salt.
* 
* @param mixed $raw
* @param mixed $salt
* @param mixed $key défaut : HKEY
*/
function hmac( $raw, $salt = "", $key = HKEY ) {
    return base64_encode( hash_hmac('sha256', $raw . $salt, $key, $as_binary=true) );
}


/**
* Cette fonction peut être exécutée manuellement pour générer les clés KEY et HKEY.
* 
* @param mixed $byteLength. 16 à 64 ? défaut : 32 . En réalité donne le double !
*/

function getRandomBytes ( $byteLength = 32 ) {
    if (function_exists('openssl_random_pseudo_bytes')) {
        $randomBytes = openssl_random_pseudo_bytes($byteLength, $cryptoStrong);
        if ($cryptoStrong)
            return bin2hex($randomBytes);
    } else return False; 
}

// fin crypto.php
