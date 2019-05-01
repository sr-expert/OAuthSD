<?php
/*
buildkey.php

Construit une entrée du fichiers JWK pour un client donné.

Voir : https://tools.ietf.org/html/draft-ietf-jose-json-web-key-41

2018/11/08
Author : B.Degoy DnC
Copyright (c) 2018 DnC 
Tous droits réservés

*/

/* [dnc5] Attention! Les clés doivent être encadrées par 
-----BEGIN PUBLIC KEY----- et -----END PUBLIC KEY-----. 
Un LF peut être présent après -----BEGIN PUBLIC KEY----- et avant -----END PUBLIC KEY----- mais c'est plus sûr sans ???
Et pas de CR à la Windows!
Des espaces peuvent être insérés (?) ainsi que des = à la fin.
Exemple :
-----BEGIN PUBLIC KEY-----
MIGeMA0GCSqGSIb3DQEBAQUAA4GMADCBiAKBgGgptoyDkZQKfwBnwp7GFpONsV1R5aD7BdoO2/wsDM8nWOQNUOHcOIvnMZXKAWhdzp5OXNNjCBJUAUghxtC6fnc/FgWCEzkIWWBSK+L+21KdxU8aX5rpksskRcDN9mgK/mYN3Uhkuv+UJAf7UsIs/O9G8koak+qDr1aB7oYLcaHfAgMBAAE=
-----END PUBLIC KEY-----
*/

ini_set('display_errors', 0);

// include our OAuth2 Server object
define('PRIVATE', true);
chdir('/home/dnc/oa/oidc/');        //CONFIG
require_once './includes/server.php';
require_once './includes/utils.php';

$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

$aresult = buildkey( $cnx );
$jresult = json_encode( $aresult );

$fp = fopen('./jwks.json', 'w');                  
fwrite($fp, $jresult); 
fclose($fp); //*/

//

function buildkey( $cnx ) {

    // Public key of one client  //TODO: use Storage Object
    $stmt = $cnx->prepare(sprintf("SELECT pk.* FROM %s pk, %s c WHERE c.client_id = pk.client_id AND c.statut='publie'", $storage_config['public_key_table'], $storage_config['client_table']));    //*****
    $stmt->execute();

    //$publickeys = "";
    $publickeys = array();

    while ( $keyinfo = $stmt->fetch(\PDO::FETCH_ASSOC) ) {

        if (  $keyinfo['client_id'] == 'testidc' ) {        // Indiquer ici le client pour lequel on génère le jwks

            $private = $keyinfo['private_key'];
            $public = $keyinfo['public_key'];

            $kid = md5($public);   // calculé sur la valeur enregistrée sur le serveur.

            // RSA : Calculer le module et l'exposant
            $data = openssl_pkey_get_private($private);
            $data = openssl_pkey_get_details($data);
            $key = $data['key'];
            $modulus = $data['rsa']['n'];
            $exponent = $data['rsa']['e'];

            $akey = array(
                'kid' => $kid,
                'kty' => 'RSA',
                'alg' => $keyinfo['encryption_algorithm'],
                'use' => 'sig',
                'e' => urlSafeB64Encode($exponent),     
                'n' => urlSafeB64Encode($modulus),
            );

            $publickeys[] = $akey;
            //$publickeys = $akey;

        }
    }

    $aresult = array('keys' => $publickeys);

    return $aresult;
}

