<?php
/** cryptos.php
* Plugin OIDC pour SPIP
* 
* Ceci n'est pas un logiciel libre !
* L'utilisation de ce code nécessite une licence spécifique obtenue de DnC. Voyez le fichier licence.txt.
* 
* Auteur : B. Degoy DnC SARL bertrand@degoy.com    
* Copyright (c) 2018 DnC B.Degoy
* Tous droits réservés
*/

/**
* DnC livre le code ci-après codé avec IonCube. 
* Si vous obtenez par ailleurs ce code décodé, il s'agit d'une violation de la licence.
* Ne distribuez pas le fichier décodé. 
* Ne vous rendez pas complice d'un piratage informatique : détruisez ce code et prévenez DnC SARL.
*/
///// utils

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
* This function must be maintained identical with OAuthSD one.
* @param string $state : any string, session-stable, to make ufp change between session.
*/
function compute_user_fingerprint( $state = '' ) {

    $trace = 
    $state.   // pour avoir un ufp toujours différent à chaque session, même avec un même client
    @$_SERVER['REMOTE_ADDR'] . 
    @$_SERVER['HTTP_USER_AGENT'];

    $ufp = md5($trace);                    //ufp

    if ( $ufp !== False ) {  
        $ufp = urlsafeB64Encode($ufp);
    } // else erreur technique

    return $ufp;
} 

function getRandomBytes ( $byteLength = 32 ) {
    if (function_exists('openssl_random_pseudo_bytes')) {
        $randomBytes = openssl_random_pseudo_bytes($byteLength, $cryptoStrong);
        if ($cryptoStrong)
            return bin2hex($randomBytes);
    } else return False; 
}
