<?php 
/* test_JWTBearer.php

Usage :  https://oa.dnc.global/oidc/tests/test_JWTBearer.php

Author : 
Bertrand Degoy https://degoy.com
Credits :
bschaffer https://github.com/bshaffer/oauth2-server-php
Licence : MIT licence
*/

ini_set('display_errors', 1);

// Autoloading by Composer
require __DIR__ . '/../../vendor/autoload.php';
OAuth2\Autoloader::register(); 

///// Configuration /////

$client_id = 'testopenid';   // iss
$user_id = 'bebert';  // subject

$server = 'oa.dnc.global';
$token_endpoint = 'https://' . $server . '/token';

define('PRIVATE', true);
require_once __DIR__.'/../../oidc/../../commons/configure_oidc.php';         
require_once __DIR__.'/../../oidc/includes/utils.php';

//*** End of configuration ***

// Connect to database
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);
// Get private key
$stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE client_id=:client_id', $storage_config['public_key_table']));    //*****
$stmt->execute(compact('client_id'));
$keyinfo = $stmt->fetch(\PDO::FETCH_ASSOC);
if ( empty($keyinfo) ) {
    $response->setError(403, 'forbidden'); 
    $response->send();
    die;
}
$private_key = $keyinfo['private_key'];

$grant_type  = 'urn:ietf:params:oauth:grant-type:jwt-bearer';

$jwt = generateJWT($private_key, $client_id, $user_id, 'https://' . $server);

passthru("curl " . $token_endpoint . " -d 'grant_type=$grant_type&assertion=$jwt'");

/**
* Generate a JWT
*
* @param $privateKey The private key to use to sign the token
* @param $iss The issuer, usually the client_id
* @param $sub The subject, usually a user_id
* @param $aud The audience, usually the URI for the oauth server
* @param $exp The expiration date. If the current time is greater than the exp, the JWT is invalid
* @param $nbf The "not before" time. If the current time is less than the nbf, the JWT is invalid
* @param $jti The "jwt token identifier", or nonce for this JWT
*
* @return string
*/
function generateJWT($privateKey, $iss, $sub, $aud, $exp = null, $nbf = null, $jti = null)
{
    if (!$exp) {
        $exp = time() + 1000;
    }

    $params = array(
        'iss' => $iss,
        'sub' => $sub,
        'aud' => $aud,
        'exp' => $exp,
        'iat' => time(),
    );

    if ($nbf) {
        $params['nbf'] = $nbf;
    }

    if ($jti) {
        $params['jti'] = $jti;
    }

    $jwtUtil = new OAuth2\Encryption\Jwt();

    return $jwtUtil->encode($params, $privateKey, 'RS256');
}
