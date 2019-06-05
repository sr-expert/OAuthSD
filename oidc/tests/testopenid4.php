<?php
/*
testopenid4.php

Verbose Test of OpenID Connect JWT Introspection

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

$client_id = 'testopenid4';
$client_secret = 'Hgfd5vW!OpZq';

$authorization_endpoint = 'https://oa.dnc.global/authorize';
$token_endpoint = 'https://oa.dnc.global/token';
$introspection_endpoint = 'https://oa.dnc.global/introspect'; 
$userinfo_endpoint = 'https://oa.dnc.global/userinfo';

//*** End of configuration ***

require_once OIDCSRV_ROOT_PATH . 'includes/utils.php'; 

ini_set('display_errors', 1);

// Set session
//*
session_save_path('/home/oadnc/sessions_oauthsd');          
session_name('oauthsd'); 
session_start();

$continue = true;   

if ( !isset($_GET['error']) ) {

    if ( !empty( $code = $_GET['code']) ) {

        // Return from OIDC Step 1 (Authorization Code request)

        $state = $_GET['state'];
        if ( empty( $state ) ) {
            // Missing State
            exit("testopenid - Authorization error : missing State");
            $continue = false;
        }

        // Check state
        if ( $continue AND ( $state != decrypt(@$_SESSION['state'])) ) {   //[dnc21]
            // Wrong State
            exit("testopenid - Authorization error : incoherent State");
            $continue = false;
        }

        if ( $continue ) {
            ///// OIDC Step 2. Token request

            $data = array(
                'grant_type' => 'authorization_code',
                'code' => $code,
            );
            $h0 = curl_init($token_endpoint);
            curl_setopt($h0, CURLOPT_RETURNTRANSFER, true);
            //curl_setopt($h0, CURLOPT_FORBID_REUSE, true);
            //curl_setopt($h0, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($h0, CURLOPT_TIMEOUT, 10);
            curl_setopt($h0, CURLOPT_USERPWD, "{$client_id}:{$client_secret}");
            curl_setopt($h0, CURLOPT_POST, true);
            curl_setopt($h0, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($h0, CURLOPT_POSTFIELDS, http_build_query($data));
            //curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);

            $res1 = curl_exec($h0);
            $errorc1 = curl_error($h0);
            curl_close($h0);
        }

        if ( $continue AND ( ! empty( $errorc1 ) ) ) {                            
            // Curl error during Token request
            exit('testopenid - Token request Curl error : ' . $errorc1);                         
        }

        if ( $continue AND ( ! is_array(json_decode($res1, true) ) ) ) {
            exit('testopenid - Tokens JSON malformed or empty');
            $continue = false;
        }

        $res2 = json_decode($res1, true);

        if  ( $continue AND ( ! empty($res2['error']) ) ) {
            // Token request error
            exit('testopenid - Token request error : ' . $res2['error'] . ' : ' 
                . $res2['error_description']);
            $continue = false;
        }

        $access_token = $res2['access_token'];
        $id_token = $res2['id_token'];  //JWT


        ///// OIDC step 3. Validate signed JWT token using introspection
        if ( $continue ) {
            $data1 = array(
                'token' => $id_token,
                'state' => $state,          // state is mandatory with OAuthSD
            );

            $h = curl_init($introspection_endpoint);
            curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($h, CURLOPT_TIMEOUT, 10);
            curl_setopt($h, CURLOPT_POST, true);
            curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));   
            curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data1));

            $n = 0;
            do {
                $res3 = curl_exec($h);
                $errorc2 = curl_error($h);             
                $n += 1;
                sleep(1);
            } while ( empty($res3) AND $n < 3 );
            curl_close($h);

        }

        if ( $continue AND ( ! empty( $errorc2 ) ) ) {
            // Curl error during Introspection request
            exit('testopenid - Introspection request Curl error : ' . $errorc2);
        }

        if (  $continue AND ( ! is_array(json_decode($res3, true) ) ) ) {
            // Invalid id_token 
            exit('testopenid - Error : Invalid ID Token');
            $continue = false;
        }    

        // L'introspection retourne un JWT validé et décodé
        $decoded_jwt = json_decode($res3, true);

        if  ( $continue AND ( ! empty($decoded_jwt['error'] ) ) ) {
            // JWT is inactive
            exit('testopenid - Error : ' . $decoded_jwt['error']);
            $continue = false; 
        }

        if ( $continue AND ( ! empty( $decoded_jwt['nonce'] ) ) ) {

            // Check nonce invariant trough session
            if ( $_SESSION['nonce'] != $decoded_jwt['nonce'] ) {
                exit('testopenid - Wrong nonce');  
            } 

            //[dnc8] Check nonce as Client Footprint
            $cfp = compute_client_footprint($state);
            if ( $cfp != $decoded_jwt['nonce'] ) {
                exit('testopenid - Forged nonce');
                $continue = false;     
            }   
        }

        if (  $continue AND ( ! $decoded_jwt['active'] == 'true' ) ) {
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
            exit('testopenid - UserInfo request Curl error : ' . $errorc3);
        }

        if (  $continue AND ( ! is_array(json_decode($res4, true) ) ) ) {
            // script error ?
            exit('testopenid - UserInfo result not JSON, malformed or empty');
            $continue = false;     
        }

        $userinfo = json_decode($res4, true);

        if  (  $continue AND ( ! empty($userinfo['error'] ) ) ) {
            // Token request error
            exit('testopenid - UserInfo Request error : ' . $userinfo['error'] . ' : '
                . $res['error_description']);
            $continue = false;
        }

        if (  $continue AND ( ! $decoded_jwt['sub'] == $userinfo['sub'] ) ) {
            // User of ID Token doesn't match UserInfo's one
            exit('testopenid - UserInfo user mismatch, got : ' . $userinfo['sub']);
            $continue = false;
        }

        if ( $continue ) {
            // Everithing Ok !
            exit("testopenid - UserInfo Response:\n" . print_r($userinfo, true)); 
        }

    } else {

        // Step 1. Authorization Code request

        // Generate state.
        @session_regenerate_id();
        $state = session_id();
        $_SESSION['state'] = encrypt($state);  //[dnc21]

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
