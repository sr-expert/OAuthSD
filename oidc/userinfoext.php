<?php
/*
userinfoext.php
Extended UserInfo Controller for OpenId Connect protocol with OAuth2 Server
2019/01/27 

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2019 DnC  
All rights reserved
*/

// include our OAuth2 Server object
define('PRIVATE', true);
require_once __DIR__.'/includes/server.php';
require_once __DIR__.'/includes/log.php';    

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

//$server->handleUserInfoRequest($request)->send();

if (!$server->verifyResourceRequest($request, $response, 'openid')) {                //TODO: tester
    $response->setError(403, 'forbidden');
    $response->send();
    die; 
    return;
}

$token = $this->getToken();

// Prepare data connection
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

log_info("UserInfoExt", "Begin", null, null, 5, 0, $cnx);  //[dnc27a]      

//[dnc22] Verify where we send the answer : check requester location.

if ( !empty( $client_id = $token["client_id"] ) ) { 

    if ( CHECK_CLIENT_IP OR CHECK_SAME_DOMAINS ) {              
        // Get IP from clients
        $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE client_id=:client_id', $storage_config['client_table']));    //*****
        $stmt->execute(compact('client_id'));
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
    } else {
        log_error("UserInfoExt" ,"Checking client IP enabled, but client unknown", 'unk', $token['user_id'], 501, 100, $cnx);  //[dnc27a]
        sleep(10); // penalize skiddie
        $response->setError(403,"forbidden");
        $response->send();    
        die();
    }

    if ( $data ) {

        if ( CHECK_CLIENT_IP ) { 
            //[dnc14'] Check client IP if feasible. Has no effect if client_id is not defined or client_ip not defined. 
            $cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);      
            $message = "Checking client IP";             
            // Get IP from clients
            $theip = ( empty($data['client_ip'])? null : trim(htmlspecialchars($data['client_ip'])) );
            if ( ! is_null($theip) ) {
                // client's IP registered : check with referer's IP
                $remote = (string)$_SERVER['REMOTE_ADDR'];
                if ( strpos($theip, $remote) === false ) {     // client_ip may be multiple (LAN and WAN IPs, or client application sharing same registration)
                    log_error("UserInfoExt" , $message . ". Remote Adress = " . $remote . " not allowed for client = " . $client_id . ", waiting = " . $theip, $client_id, $token['user_id'], 502, 100, $cnx);  //[dnc27a]
                    sleep(10); // penalize skiddie
                    $response->setError(403,"forbidden");
                    $response->send();    
                    die();
                } else {
                    log_success("UserInfoExt" , $message . " Ok. IP = " . $theip, $client_id, $token['user_id'], 505, -10, $cnx);
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

    } // else fail to fetch client data

} else {
    log_error("UserInfoExt" ,"Client is unknown", 'unk', $token['user_id'], 507, 100, $cnx);
    $response->setError(403, 'forbidden');
    $response->send();
    die;
}




$claims = $server->userClaimsStorage->getUserClaims($token['user_id'], $token['scope']);    

// The sub Claim MUST always be returned in the UserInfo Response.
// http://openid.net/specs/openid-connect-core-1_0.html#UserInfoResponse
$claims += array(
    'sub' => $token['user_id'],
);
$response->addParameters($claims);

$response->send();
