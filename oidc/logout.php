<?php
/* logout.php
[dnc9] Single Log Out (SLO)

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2018 DnC  
All rights reserved
*/

//DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

// La demande de déconnexion centralisée (Single LogOut, SLO) commence comme l'introspection.

// include our OAuth2 Server object
define('PARAM_TOKEN_NAME', 'token'); // Following RFC 7662 introspect should utilize 'token'
define('PRIVATE', true);
require_once __DIR__.'/includes/server.php';
require_once __DIR__.'/includes/utils.php';        

// Handle a request to a resource
$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// No cache
$response->addHttpHeaders(array('Cache-Control' => 'no-store', 'Pragma' => 'no-cache'));

//[dnc30] ID token is required. Allow quick death of skiddies.
$token = $request->request('token', $request->query('token'));
if ( empty( $token ) ) $token = $request->request('id_token_hint', $request->query('id_token_hint')); //[dnc41]    
if ( empty ( $undecodedJWT = $token ) ) { 
    // is JWT in header?  
    $undecodedJWT = substr($request->headers['AUTHORIZATION'], 7);    //[dnc30]
    if ( empty($undecodedJWT) AND ! DEBUG ) {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddie
        $response->send();
        die;
    }
}

// Prepare data connection
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

log_info("Logout", "Begin", null, null, 4, 0, $cnx);  //[dnc27a]

// Get the JWT
if ( empty ($undecodedJWT) ) {  //[dnc30] 
    log_error("Logout", "Invalid request : no token parameter", 'unk', 'unk', 601, 100, $cnx);  //[dnc27a]  
    if ( DEBUG) {
        $response->setError(400,'Bad Request', 'Missing parameter: "token" or "id_token_hint" is required');   //[dnc41]
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddie
    }
    $response->send();
    die;
} 

// Decode the JWT
$jwtUtil = new \OAuth2\Encryption\Jwt();
$jwt = $jwtUtil->decode($undecodedJWT, null, false);
if (!$jwt) {
    log_error("Logout", "Invalid request : malformed JWT", 'unk', 'unk', 602, 10, $cnx);  //[dnc27a]  
    if ( DEBUG) {
        $response->setError(400,'Bad Request', 'malformed JWT');
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddie
    }
    $response->send();
    die;
}

// Verify ID Token

// Find public key of client 
$client_id = $jwt['aud'];
$stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE client_id=:client_id', $storage_config['public_key_table']));    //*****
$stmt->execute(compact('client_id'));
$keyinfo = $stmt->fetch(\PDO::FETCH_ASSOC);
if ( empty($keyinfo) ) {
    log_error("Logout", "Invalid token : Bad Audience or Missing Client Keys", null, null, 603, 100, $cnx);  //[dnc27a] 
    if ( DEBUG) {
        $response->setError(400,'Bad Request', "Bad Audience or Missing Client Keys");  
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddiesleep(10); // penalize skiddie
    }
    $response->send();
    die;
}

$error = "";
$weight = 0;    

// Validate signature of JWT token using client's public key
if ( ! $payload = $jwtUtil->decode($undecodedJWT, $keyinfo['public_key'], $keyinfo['encryption_algorithm']) ) {    //Includes signature verification
    $error = 'JWT decode error or bad signature';
    $weight = 500;
}

if ( !empty($payload) ) {
    // Verify validity of token

    if ( $payload['iss'] != $_SERVER['HTTP_HOST']) {  //[dnc45]
        $error = 'JWT has wrong iss ' ;
        $weight = 500;
    }
    // We do'nt make any more check, logout can be done with an old JWT.
} 

if ( ! $error ) {

    //[dnc22] Verify where we send the answer : check requester location.

    if ( !empty( $client_id = $jwt['aud'] ) ) { 

        if ( CHECK_CLIENT_IP OR CHECK_SAME_DOMAINS ) {
            // Get Client data               
            $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE client_id=:client_id', $storage_config['client_table']));   
            $stmt->execute(compact('client_id'));
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        if ( $data ) {

            if ( CHECK_CLIENT_IP ) {   
                //[dnc14'] Check client IP if feasible. Has no effect if aud is not defined or client_ip not defined. 
                $theip = ( empty($data['client_ip'])? null : trim(htmlspecialchars($data['client_ip'])) );
                if ( ! is_null($theip) ) {
                    // client's IP registered : check with requester IP
                    $remote = (string)$_SERVER['REMOTE_ADDR'];
                    if ( strpos($theip, $remote) === false ) {     // client_ip may be multiple (LAN and WAN IPs, or client application sharing same registration)
                        $error = "Checking requester IP. Remote Adress = " . $remote . " not allowed for client = " . $client_id . ", waiting = " . $theip;
                        $weight = 500;
                    }    
                }
            }

            /*[dnc22] check same domain. Has no effect for localhost
            $registered_domain = $data['redirect_uri'];       
            if ( CHECK_SAME_DOMAINS 
            AND strpos($registered_domain, 'localhost') === false 
            AND strpos($registered_domain, '127.0.0.1') === false) {
                // Same domain : check that the request and redirect URI domains are identicals.
                $requester_domain = $_SERVER['HTTP_HOST'];
                if ( strpos($registered_domain, $requester_domain) === False ) {        
                    $error = "Requester domain = " . $requester_domain . " not allowed for client = " . $client_id . ", waiting = " . $registered_domain;
                    $weight = 500;
                }  
            } //*/   

        }

        // Effacer tous les jetons d'accès du sujet
        $sub = $jwt['sub']; 
        $stmt = $cnx->prepare(sprintf('DELETE FROM %s WHERE user_id=:sub', $storage_config['access_token_table']));   
        $stmt->execute(compact('sub'));
        $res = $stmt->execute();
        // détruire les données de session et les cookies
        destroy_all_session_data();
        // Pour le(s) cookie(s) SLI présents sur les autres agents, ils seront détruits lors d'une tentative de reconnexion.

    } else {
        $error = "client is unknown";
        $weight = 500;
    }

} 

if ( $error ) {  // error
    log_error("Logout" , $error, @$jwt['aud'], @$jwt['sub'], 610, $weight, $cnx);
    if ( DEBUG) {
        $response->setError(403, 'forbidden', $error); 
    } else {
        $response->setError(403, 'forbidden');
        sleep(10); // penalize skiddie
    }
    $response->send();
    die;

} else {
    // Send HTTP code 200
    header("HTTP/1.0 200 OK");
    header('Content-Type: application/json');
    // Do'nt cache.          
    header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
    header("Pragma: no-cache"); // HTTP 1.0.
    header("Expires: 0"); // Proxies.
    die();
}
