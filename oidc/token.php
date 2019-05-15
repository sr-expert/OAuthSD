<?php
/**
* token.php
* 
* Token Controller for OAuth2 Server
* This is the URI which returns an OpenID Token to the client
* See : http://bshaffer.github.io/oauth2-server-php-docs/cookbook/
*
* OauthSD project
* This code is not an open source!
* You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
* You can only use one coded copy provided you have a particular license from DnC.
* Auteur : Bertrand Degoy 
* Copyright (c) 2016-2018 DnC  
* All rights reserved
*/

DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG 

// include our OAuth2 Server object
define('PRIVATE', true);
require_once __DIR__.'/includes/server.php';
require_once __DIR__.'/includes/log.php';    

// Handle a request for an OAuth2.0 Access Token and send the response to the client
//$server->handleTokenRequest(OAuth2\Request::createFromGlobals())->send();

$request = OAuth2\Request::createFromGlobals();      
$response = new OAuth2\Response();

$message = "";

if ( CHECK_CLIENT_IP AND @$_POST['grant_type'] !== "urn:ietf:params:oauth:grant-type:jwt-bearer" ) {   //[dnc14'']

    $cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);
    
    log_info("Token", "Begin", null, null, 3, 0, $cnx);  //[dnc27a] 

    //[dnc14] Check client IP if feasible                
    if ( $code = @$_POST['code'] ) {       
        // Authorization Code : get client_id from authorization_codes
        $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE authorization_code=:code', $storage_config['code_table']));    
        $stmt->execute(compact('code'));
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $client_id = ( empty($data['client_id'])? null : trim(htmlspecialchars($data['client_id'])) );
        $user_id = $data['user_id'];
        $message = "Checking client IP for code = " . $code;

    } else if ( 
    ('client_credentials' == @$_POST['grant_type'] OR 'password' == @$_POST['grant_type'])  
    AND (bool)@$_SERVER['PHP_AUTH_USER']
    ) {
        // User or Client Credentials with credentials in header : get client_id from HTTP Basic Authentication
        $client_id = $_SERVER['PHP_AUTH_USER'];
        $user_id = null;
        $message = "Checking IP of client from HTTP Basic Authentication";

    } else if ( 'client_credentials' == @$_POST['grant_type'] AND (bool)@$_POST['client_id'] ) {
        // Client Credentials with credentials in POST body : get client_id from body
        $client_id = $_POST['client_id'];
        $user_id = null;
        $message = "Checking IP of client from POST body";     
    }

    if ( !empty($client_id) ) {              
        // Get IP from clients
        $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE client_id=:client_id', $storage_config['client_table']));    //*****
        $stmt->execute(compact('client_id'));
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $theip = ( empty($data['client_ip'])? null : trim(htmlspecialchars($data['client_ip'])) );
        if ( ! is_null($theip) ) {
            // client's IP registered : check with referer's IP
            $remote = (string)$_SERVER['REMOTE_ADDR'];
            if ( strpos($theip, $remote) === false ) {     // client_ip may be multiple (LAN and WAN IPs, or client application sharing same registration)
                log_error("Token" , $message . ". Remote Adress = " . $remote . " not allowed for client = " . $client_id . ", waiting = " . $theip, $client_id, null, 301, 100, $cnx);
                if ( DEBUG ) {
                    $response->setError(403, 'forbidden', 'Remote Adress not allowed for client');
                } else {
                    $response->setError(403, 'forbidden');
                    sleep(10); // penalize skiddie
                }
                $response->send();    
                die();
            } else {
                log_info("Token" , $message . " Ok. IP = " . $theip, $client_id, $user_id, 302, 0, $cnx);     
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

    } else {
        log_error("Token" , $message . " but client unknown", null, null, 305, 100, $cnx);
        if ( DEBUG ) {
            $response->setError(403, 'forbidden', 'Checking client IP enabled, but client unknown');
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();    
        die();
    }
} 


$server->handleTokenRequest($request)->send();
