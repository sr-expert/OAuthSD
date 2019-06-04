<?php
/*
introspect.php
Introspection Controller for OpenID Connect

2017/01/03 - 2019/03/08

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

ini_set('display_errors', 0);

//DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG  

// include our OAuth2 Server object
// Following RFC 7662 introspect should utilize 'token'
define('PRIVATE', true);
require_once __DIR__. '/includes/server.php';
require_once __DIR__. '/includes/utils.php';        

// Handle a request to a resource
$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// No cache with token data
$response->addHttpHeaders(array('Cache-Control' => 'no-store', 'Pragma' => 'no-cache'));

// token parameter (ID JWT) is required. Allow quick death of skiddies.     
if ( empty ( $undecodedJWT = $request->request('token', $request->query('token')) ) ) {   
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

log_info("Introspect", "Begin", null, null, 2, 0, $cnx);  //[dnc27a]    

if ( empty ($undecodedJWT) ) {  //[dnc30]
    log_error("Introspect", "Invalid request : no token parameter", 'unk', 'unk', 201, 100, $cnx);  //[dnc27a]  
    if ( DEBUG) {
        $response->setError(400,'Bad Request', 'Missing parameter: "token" is required');
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
    log_error("Introspect", "Invalid token : malformed JWT", 'unk', 'unk', 202, 100, $cnx);  //[dnc27a] 
    if ( DEBUG) {
        $response->setError(400,'Bad Request', "malformed JWT");        //*****
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddie
    }
    $response->send();
    die;
}

if ( DEBUG ) {
    $fp = fopen('./debug.txt', 'a');                  
    fwrite($fp, '-------- introspect.php : 1 -----------' . "\n");
    fwrite($fp, 'Begin : ' . date("Y-m-d h:i:sa",time()) . "\n");
    fwrite($fp, 'JWT : ' . print_r($jwt, true) . "\n\n"); 
    fclose($fp); 
}

// Find public key of client 
$client_id = $jwt['aud'];
$stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE client_id=:client_id', $storage_config['public_key_table']));    //*****
$stmt->execute(compact('client_id'));
$keyinfo = $stmt->fetch(\PDO::FETCH_ASSOC);
if ( empty($keyinfo) ) {
    log_error("Introspect", "Invalid token : Bad Audience or Missing Client Keys", null, null, 203, 100, $cnx);  //[dnc27a] 
    if ( DEBUG) {
        $response->setError(400,'Bad Request', "Bad Audience or Missing Client Keys");  
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddiesleep(10); // penalize skiddie
    }
    $response->send();
    die;
}

/* Delete CRLF in keys (est-ce utile ???)
$keyinfo['public_key'] = str_replace("\r\n", "\n", $keyinfo['public_key']);
$keyinfo['private_key'] = str_replace("\r\n", "\n", $keyinfo['private_key']);
//*/

if ( DEBUG ) {
    $fp = fopen('./debug.txt', 'a');                  
    fwrite($fp, '-------- introspect.php : 2 -----------' . "\n");
    fwrite($fp, 'keyinfo : ' . print_r($keyinfo, true) . "\n\n"); 
    fclose($fp);
}

// Find user data from user ID
$user_id = $jwt['sub'];
$stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE username=:user_id', $storage_config['user_table']));    //*****
$stmt->execute(compact('user_id'));
$userinfo = $stmt->fetch(\PDO::FETCH_ASSOC);
if ( empty($userinfo) ) {
    log_error("Introspect", "Invalid token : Invalid subject", null, null, 204, 100, $cnx);  //[dnc27a]
    if ( DEBUG) {
        $response->setError(403, 'forbidden', "Invalid subject");
    } else {
        $response->setError(403, 'forbidden');
    }
    $response->send();
    die;
}

if ( DEBUG ) {
    $fp = fopen('./debug.txt', 'a');                  
    fwrite($fp, '-------- introspect.php : 3 -----------' . "\n");
    fwrite($fp, 'userinfo : ' . print_r($userinfo, true) . "\n\n"); 
    fclose($fp);
}

$error = "";
$weight = 0;    

// Validate signature of JWT token using client's public key
if ( ! $payload = $jwtUtil->decode($undecodedJWT, $keyinfo['public_key'], $keyinfo['encryption_algorithm']) ) {    //Includes signature verification
    $error = 'JWT decode error or bad signature';
    $weight = 500;
}


if ( DEBUG ) {
    $fp = fopen('./debug.txt', 'a');                  
    fwrite($fp, '-------- introspect.php : 4 -----------' . "\n");
    fwrite($fp, 'payload : ' . print_r($payload, true) . "\n\n"); 
    fclose($fp);
}

if ( !empty($payload) ) {

    // Verify validity of token
    if ( $payload['iss'] != OIDC_SERVER_URL ) {  //[dnc45a]
        $error = 'JWT has wrong iss ' ;
        $weight = 500;
    }

    if ( time() > $payload['exp'] ) {     // Vérifier que le serveur est en UTC !
        $error .= 'JWT has expired ';
        $weight = 10;    
    }

    if ( isset($payload['nbf']) AND time() < $payload['nbf'] ) {
        $error .= 'JWT is prematured ';
        $weight = 10;
    }

} 

if ( DEBUG ) {
    $fp = fopen('./debug.txt', 'a');                  
    fwrite($fp, '-------- introspect.php : 5 -----------' . "\n");
    if ( $error ) {
        fwrite($fp, 'token error : ' . $error . "\n\n");
    } else {
        fwrite($fp, 'token is verified' . "\n\n");
    } 
    fclose($fp);
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
                        log_error("Introspect" , "Checking requester IP. Remote Adress = " . $remote . " not allowed for client = " . $client_id . ", waiting = " . $theip, $client_id, null, 220, 100, $cnx);
                        if ( DEBUG) {
                            $response->setError(403,'forbidden', 'wrong requester IP');
                        } else {
                            $response->setError(403, 'forbidden');
                            sleep(10); // penalize skiddie
                        }
                        $response->send();    
                        die();
                    } else {
                        log_info("Introspect" , "Checking requester IP : Ok. IP = " . $theip, $client_id, $jwt['sub'], 221, 0, $cnx);    
                    }    
                }    

            }

            /*[dnc22] check same domain
        $registered_domain = $data['redirect_uri'];       
        if ( CHECK_SAME_DOMAINS 
        AND strpos($registered_domain, 'localhost') === false 
        AND strpos($registered_domain, '127.0.0.1') === false 
        AND !is_null($_SERVER['REQUEST_URI']) ) {                          //*****
            // Same domain : check that the request and redirect URI domains are identicals.
            $requester_domain = parse_url($_SERVER['REQUEST_URI'], PHP_URL_HOST);
            if ( strpos($registered_domain, $requester_domain) !== False ) {        
                log_info("Token" , "Checking requester domain : Ok. Domain = " . $requester_domain, $client_id, $user_id, 303, 0, $cnx);  

            } else {
                log_error("Token" , "Requester domain = " . $requester_domain . " not allowed for client = " . $client_id . ", waiting = " . $registered_domain, $client_id, 'Unk', 304, 100, $cnx);
                if ( ! DEBUG ) sleep(10); // penalize skiddie  
                $response->setError(403,"forbidden");
                $response->send();    
                die();    
            }  
        } //*/   

        }

    } else {
        log_error("Introspect" ,"Checking client location is requested by configuraton, but client is unknown", 'unk', $jwt['sub'], 225, 100, $cnx);
        if ( DEBUG) {
            $response->setError(403, 'forbidden', "Invalid audience"); 
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();
        die;
    }

    // Prepare payload    
    $aresult =  array(
        'active' => true, 
    );

    $aresult = array_merge($aresult,$payload); 

    if ( isset($payload['scope']) ) {
        $aresult['scope'] = $payload['scope'];    
    }   

    if ( isset($payload['nonce']) ) {
        $aresult['nonce'] = $payload['nonce'];    
    }

} else {  // error
    log_error("Introspect" , $error, @$jwt['aud'], @$jwt['sub'], 210, $weight, $cnx);
    $aresult =  array(
        'active' => false,
        'error' => $error,
    ); 
}

if ( DEBUG ) {
    $fp = fopen('./debug.txt', 'a');                  
    fwrite($fp, '-------- introspect.php : result -----------' . "\n");
    fwrite($fp, 'result : ' . print_r($aresult, true) . "\n\n"); 
    fclose($fp);
}

$jresult = json_encode( $aresult ); 

if ( DEBUG ) {
    $fp = fopen('./debug.txt', 'a');                  
    fwrite($fp, '-------- introspect.php : json result -----------' . "\n");
    fwrite($fp, 'jresult : ' . $jresult . "\n");
    fwrite($fp, 'End : ' . date("Y-m-d h:i:sa",time()). "\n\n"); 
    fclose($fp);
}   

// Send as JSON array
header("HTTP/1.0 200 OK");
header('Content-Type: application/json');
// Do'nt cache.          
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.
echo $jresult;
die();
