<?php
/**
* Plugin OIDC pour SPIP
* doc : https://oa.dnc.global
* 
* Auteur : B. Degoy DnC SARL bertrand@degoy.com    
* Copyright (c) 2018 DnC B.Degoy
* Tous droits réservés
*/

/**
* OIDC Step 1. Authorization Code request
*/
function oidc_step_1( $login ) {              

    include_spip('inc/session');
    include_spip('inc/cryptos');

    // We want to keep the OAuthSD session synchronized with client application session.
    if ( empty( $state = session_get('state') ) ) {
        if ( function_exists('session_create_id') ) {         
            $state = session_create_id();   // PHP 7
        } else {
            $state = getRandomBytes();          
        }
        $state = substr($state,2,16); //[dnc39]
        session_set('state', $state);
    }

    // Remember caller (where to redirect after Authorization dialog).
    $caller = $_GET['url'];
    if ( empty($caller) OR $caller == './' ) { 
        $caller = $_SERVER['REQUEST_URI'];
    }
    session_set('caller', $caller);

    // Get scopes
    include_spip('inc/config');
    $scopes = lire_config('oidcclient/cfg_oidcclient_scopes');

    //[dnc8] Send User's FingerPrint as nonce
    $ufp = compute_user_fingerprint($state);        //ufp
    session_set('nonce', $ufp);  //TODO: inutile? sécurité?

    // Goto Authorization Endpoint (server will show Authorization dialog)
    $data = array(
        'response_type' => 'code',
        'client_id' => OIDC_CLIENT_ID,     
        'user_id' => $login,    
        'scope' => $scopes,
        'state' => $state,
        'nonce' => $ufp,                      //ufp
    );
    if ( isset($_GET['prompt']) ) {
        $data['prompt'] = $_GET['prompt'];    
    }
    $authorization_endpoint = OIDC_AUTHORIZATION_ENDPOINT . '?' . http_build_query($data);
    header('Location: ' . $authorization_endpoint);
    die();

}

/** 
* OIDC steps after Authorization Code request
* Process token verification, Introspection, Userinfo requests and all verifications.
*/

function oidc_step_2() { 

    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG 

    include_spip('inc/oidcclient_configuration');
    include_spip('inc/session');
    include_spip('inc/cryptos'); 

    $login = "";
    $continue = true;
    $userinfo = array();
    $error = '';

    if ( empty( $auth_error = _request('error') ) ) {   

        if ( !empty( $code = _request('code') ) ) {

            // Return from OIDC Step 1 (Authorization Code request)

            $state = $_GET['state'];
            if ( empty( $state ) ) {
                // Missing State
                $error = 'Authorization error : missing State';
                spip_log("oidcclient - " . $error, _LOG_ERREUR);
                $continue = false;
            }

            // Check state
            if ( $continue AND ( $state != session_get('state') ) ) {
                // Wrong State
                $error = 'Authorization error : incoherent State';
                spip_log("oidcclient - " . $error, _LOG_CRITIQUE);
                $continue = false;
            }

            if ( $continue ) {
                ///// OIDC Step 2. Token request

                $data = array(
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                );
                $client_id = OIDC_CLIENT_ID;
                $client_secret = OIDC_CLIENT_SECRET;
                $h = curl_init(OIDC_TOKEN_ENDPOINT);
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
                $error = 'Token request Curl error : ' . $errorc1;
                spip_log('oidcclient - Token request Curl error : ' . $errorc1, _LOG_ERREUR);                         
            }

            if ( $continue AND ( ! is_array(json_decode($res1, true) ) ) ) {
                $error = 'Token request failed';      
                spip_log('oidcclient - ' . $error, _LOG_ERREUR);
                $continue = false;
            }

            $res2 = json_decode($res1, true);

            if  ( $continue AND ( ! empty($res2['error']) ) ) {
                // Token request error
                $error = 'Token request error : ' . $res2['error'] . ' : ' 
                . $res2['error_description'];
                spip_log('oidcclient - ' . $error, _LOG_CRITIQUE);
                $continue = false;
            }

            // Tokens
            $access_token = $res2['access_token'];
            $id_token = $res2['id_token'];  //JWT
            // Save access token in session                 //*****
            session_set('access_token', $access_token);

            ///// OIDC step 3. Validate signed JWT token using introspection
            if ( $continue ) {
                $data1 = array(
                    'token' => $id_token,
                    'state' => $state,          // state is mandatory with OAuthSD
                );

                $h = curl_init(OIDC_INTROSPECTION_ENDPOINT);
                curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($h, CURLOPT_TIMEOUT, 10);
                curl_setopt($h, CURLOPT_POST, true);   // Post Methode
                curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));     // sur une ligne !
                curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data1));
                //*/

                $res3 = curl_exec($h);
                $errorc2 = curl_error($h);
                curl_close($h);

            }

            if ( $continue AND ( ! empty( $errorc2 ) ) ) {
                // Curl error during Introspection request
                $error = 'Introspection request Curl error : ' . $errorc2;
                spip_log('oidcclient - ' . $error, _LOG_ERREUR);
            }

            if (  $continue AND ( ! is_array(json_decode($res3, true) ) ) ) {
                // Invalid id_token
                $error = 'Error : Introspection request failed';
                spip_log('oidcclient - ' . $error, _LOG_CRITIQUE);
                $continue = false;
            }    

            // L'introspection retourne un JWT validé et décodé
            $decoded_jwt = json_decode($res3, true);

            if  ( $continue AND ( ! empty($decoded_jwt['error'] ) ) ) {
                // JWT is inactive
                $error = 'Error : ' . $decoded_jwt['error'];
                spip_log('oidcclient - ' . $error, _LOG_CRITIQUE);
                $continue = false; 
            }

            if ( $continue AND ( ! empty( $decoded_jwt['nonce'] ) ) ) {

                // Check nonce invariant trough session
                if ( session_get('nonce') != $decoded_jwt['nonce'] ) {
                    $error = 'Wrong nonce';
                    spip_log('oidcclient - ' . $error, _LOG_CRITIQUE);  
                } 

                //[dnc8] Check nonce as User's FingerPrint
                If ( CHECK_NONCE_AS_UFP ) {               //ufp
                    $ufp = compute_user_fingerprint($state);
                    if ( $ufp != $decoded_jwt['nonce'] ) {
                        $error = 'Forged nonce';
                        spip_log('oidcclient - ' . $error, _LOG_ALERTE_ROUGE);
                        $continue = false;     
                    }
                }   
            }

            if (  $continue AND ( ! $decoded_jwt['active'] == 'true' ) ) {
                $error = 'Invalid JWT';
                spip_log('oidcclient - ' . $error, _LOG_CRITIQUE);
                $continue = false;       
            }

            // If ID Token is valid, save it in session and continue with step 4
            //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG
            if ( $continue ) {
                // Save access token in session
                session_set('id_token', $id_token);
            } else {
                // destroy existing id_token in session
                session_set('id_token');
            }

            ///// OIDC Step 4. Get UserInfo

            if ( $continue ) {
                $data2 = array(
                    'access_token' => $access_token,
                );
                $h = curl_init(OIDC_USERINFO_ENDPOINT);
                curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($h, CURLOPT_TIMEOUT, 10);
                curl_setopt($h, CURLOPT_POST, true);
                curl_setopt($h, CURLOPT_HTTPHEADER, 
                    array('Content-Type: application/x-www-form-urlencoded')
                );     // sur une ligne ! 
                curl_setopt($h, CURLOPT_POSTFIELDS, 
                    http_build_query($data2));

                $res4 = curl_exec($h);
                $errorc3 = curl_error($h);
                curl_close($h);

            }

            if ( $continue AND ( ! empty( $errorc3 ) ) ) {
                // Curl error during Introspection request
                $error = 'UserInfo request Curl error : ' . $errorc3;
                spip_log('oidcclient - ' . $error, _LOG_ERREUR);
            }

            if (  $continue AND ( ! is_array(json_decode($res4, true) ) ) ) {
                // script error ?
                $error = 'UserInfo result not JSON, malformed or empty';
                spip_log('oidcclient - ' . $error, _LOG_CRITIQUE);
                $continue = false;     
            }

            $userinfo = json_decode($res4, true);

            if  (  $continue AND ( ! empty($userinfo['error'] ) ) ) {
                // Token request error
                $error = 'UserInfo Request error : ' . $userinfo['error'] . ' : '
                . $res['error_description'];
                spip_log('oidcclient - ' . $error, _LOG_ERREUR);
                $continue = false;
            }

            if (  $continue AND ( ! $decoded_jwt['sub'] == $userinfo['sub'] ) ) {
                // User of ID Token doesn't match UserInfo's one
                $error = 'UserInfo user mismatch, got : ' . $userinfo['sub'];
                spip_log('oidcclient - ' . $error, _LOG_ALERTE_ROUGE);
                $continue = false;
            }

            if ( $continue ) {
                // Everithing Ok !
                $error = '';
                spip_log ("oidcclient - UserInfo Response:\n" . print_r($userinfo, true), _LOG_INFO);

            }

        } else {   
            // Code is missing
            $error = 'Code is missing';
            spip_log('oidcclient - ' . $error, _LOG_CRITIQUE);
            $continue = false;     

        }

        if ( $continue == false ) {
            // Pass error in userinfo
            $userinfo['error'] = $error;   
        }

        return $userinfo;   

    } else {
        // Authorize redirect with error when Not Authorize 

        switch ( $auth_error ) {
            case 'login_required':
                /* generated when calling Authorize whith prompt = 'none'
                and user_id not defined.
                Means : 'The user must log in'
                */
                $userinfo['error'] = $auth_error; 
                break;
            case 'interaction_required':
                /* generated when calling Authorize whith prompt = 'none'
                and user_id not defined.
                Means : 'The user must grant access to your application'.
                But this situation also occurs when polling Authorize to find out if user is connected.
                */
                $userinfo['error'] = $auth_error; 
                break;

            case 'consent_required':   
                /* generated when calling Authorize whith prompt different from 'none'.
                Means : 'The user denied access to your application'
                */
                $userinfo['error'] = $auth_error;  
                break;

            default :
                // Unknown case, may not occur?
                $userinfo['error'] = "unknown_error"; 
                break;
        }


    }

}
