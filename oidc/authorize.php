<?php
/*
authorize.php

Authorize Controller for OAuth2 OIDC Server

See : http://bshaffer.github.io/oauth2-server-php-docs/cookbook/

Test: http://oa.dnc.global/oidc/authorize.php?response_type=code&scope=openid&client_id=testclient&state=xyz

[dnc9] 2018/12/02 - version prenant en charge Single Login Identification (SLI)

Note à propos de la sécurité :
Si on autorise un pseudo comme login, on enregistre en session ce login. il est 
très facile de le deviner et de générer une requête avec.
Le fait de forcer un e-mail comme login fait que le système génère un $userid
aléatoire de 64 caractères, pratiquement impossible à deviner.
Même dans ce cas, on reste exposé au vol de cookie.

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

//DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

$trace = null;
$client_id = null; 
$sub = null;
$cnx = null;
$is_authorized = false;

define('__AUTHORIZE',1);    // don't modify

define('PRIVATE', true);
require_once __DIR__.'/includes/utils.php';

// include our OAuth2 Server object (configuration included)
require_once __DIR__.'/includes/server.php';

// Count requests for PRTG 
if( PRTG ) {  
    require_once __DIR__.'/prtg/prtg_utils.php';
    if( PRTG_TOTAL_REQUESTS ) {
        oidc_increment('total_requests');   
    }
}       

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// Quick and easy, most skiddies will die there!            
if ( NEEDS_OPENID_SCOPE AND strpos($request->query('scope'), 'openid') === False ) {
    log_error("Authorize" ,"Invalid Request : missing openid scope", $request->query('client_id'), $request->query('user_id'), 101, 100, null);  
    if ( DEBUG ) { 
        $response->setError(400,'Bad Request', 'Invalid Request : missing openid scope');
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddie
    }
    $response->send();
    die();
}

// Connect to database
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

if ( empty($password) AND empty($grant) AND empty($return_from) ) {
    // Log begin of Authorize processing
    log_info("Authorize", "Begin", $request->query('client_id'), $request->query('user_id'), 1, 0, $cnx);  //[dnc27a]
}

/* Do more testing to validate the authorize request.
Note this will test allowed scopes, thus immediatly invalidating a request with 
SLI cookie made by a client for which 'sli' scope has not been registered.
If Ok, will set scope, state, client_id, redirect_uri, response_type
Else will return false.
*/  
if ( ! $server->validateAuthorizeRequest($request, $response)) { 
    log_error("Authorize", $response->getParameter('error','Invalid_request') . ', ' . @$response->getParameter('error_description'), @$request->query('client_id'), @$request->query('user_id'), 102, 100, $cnx);     
    // respond with error 
    if ( DEBUG) {
        //[dnc25] Let underlying code set error details $response->setError(403, 'invalid_request *1');  
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddie
    }
    $response->send();
    die();
}

// Get parameters from request.    
$controller = $server->getAuthorizeController();
$response_type = $controller->getResponseType();
$scope = $controller->getScope();
$client_id = $controller->getClientId();   // human readable public client identificator 
$prompt = $request->query('prompt'); //[dnc32]   

// Validate state parameter exists (OAuthSD enforce state claim).
$state = $controller->getState();
if (!$state) {
    log_error("Authorize" ,"The state parameter is required", $request->query('client_id'), $request->query('user_id'), 103, 100, $cnx);    
    // respond with error 
    if ( DEBUG) {
        $response->setError(400,'Bad Request', 'The state parameter is required');
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddie
    }
    $response->send();  
    die();
}

// The state will be used as session_id
if ( !is_valid_session_id($state) ) {
    log_error("Authorize" ,"The state parameter is invalid", $request->query('client_id'), $request->query('user_id'), 180, 100, $cnx);    
    // respond with error 
    if ( DEBUG) {
        $response->setError(400,'Bad Request', 'The state parameter is invalid');
    } else {
        $response->setError(400,'Bad Request');
        sleep(10); // penalize skiddie
    }
    $response->send();  
    die();   
}

//[dnc9'] Compute a session UFP with state
$ufp = compute_user_fingerprint( $state );  //[ufp]

// Start session
$void = that_session_start('oauthsd', $state, SLI_SESSION_DIR); //[dnc34]  //TODO: en cas d'erreur?

// Some parameters from dialog
$password = htmlspecialchars(@$_REQUEST["password"]);
$grant = htmlspecialchars(@$_REQUEST["grant"]);
$return_from = htmlspecialchars(@$_REQUEST["return_from"]);

// Some more checks if returning from dialog
if ( !empty($password) OR !empty($grant) OR !empty($return_from) ) {

    // Verify state or die
    if ( $state !== decrypt(@$_SESSION['state']) ) {   //[dnc21] [dnc33] 
        log_error("Authorize" ,"Return from " . $return_from . " : wrong state", $client_id, $sub, 170, 100, $cnx);
        // Destroy all session data
        destroy_all_session_data();
        // die with error 
        if ( DEBUG) {
            $response->setError(403, 'forbidden', 'Return from ' . $return_from . ' form with wrong state');
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();
        die();
    }

    // Verify UFP or die
    if ( $ufp !== decrypt(@$_SESSION['ufp']) ) {
        log_error("Authorize" ,"Return from " . $return_from . " : wrong ufp", $client_id, $sub, 171, 100, $cnx);
        // Destroy all session data
        destroy_all_session_data();
        // die with error 
        if ( DEBUG) {
            $response->setError(403, 'forbidden', 'Return from ' . $return_from . ' form with wrong ufp');
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();
        die(); 

    }
}

// Store state and UFP in session 
$_SESSION['state'] = encrypt($state);  //[dnc21] state from query, not sliID  [dnc33]
$_SESSION['ufp'] = encrypt($ufp); 

//[dnc9] Process SLI cookie if exists
$slidata = null;
$enable_sli = false;
if ( ENABLE_SLI AND $slidata_crypted = @$_COOKIE['sli'] ) {
    // We have a Single Login Identification cookie
    // is SLI allowed by client?
    $requested_scopes = $request->query('scope'); 
    if ( strpos($requested_scopes, 'sli') !== false ) { 
        // decrypt, get SLI data
        $jslidata = private_decode( $slidata_crypted );
        if ( ! empty($jslidata) ) { 
            // SLI cookie has been decrypted
            $slidata = json_decode($jslidata, true);
            // adopt SLI state (state at last login time). This will propagate session, making a "multidomain session" for the final user identity.
            $sliID = $slidata['sliID'];
            $enable_sli = true;
        } else {
            // forged SLI cookie : exit
            log_error("Authorize" ,"Forged SLI cookie", $client_id, $request->query('user_id'), 104, 100, $cnx);
            // respond with error    
            if ( DEBUG) {
                $response->setError(400,'Bad Request', 'Forged SLI cookie');
            } else {
                $response->setError(400,'Bad Request');
                sleep(10); // penalize skiddie
            }
            $response->send();
            die();
        }
    } else {
        // SLI not granted by application : use state
        $sliID = $state;
    }
} else {
    // SLI not enabled by server, or no valid SLI cookie : use state
    $sliID = $state;
}

//[dnc8][dnc9] SLI checks
if ( $enable_sli AND is_array($slidata['ufp']) ) {       //ufp
    if ( 
    $ufp != $slidata['ufp']                     // check SLI cookie client's fingerprint
    OR @$_SESSION['sub'] != $slidata['sub'] )   // check SLI cookie subject (end user's username) 
    {
        $enable_sli = false;
        log_error("Authorize" ,"SLI checks failed", $client_id, $sub, 106, 100, $cnx);
        // Destroy all session data
        destroy_all_session_data();
        // and respond with error 
        if ( DEBUG) {
            $response->setError(403, 'forbidden', 'SLI checks failed');
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();
        die();            
    }
}

$nonce = @$request->query('nonce');      
if ( CHECK_NONCE_AS_UFP AND $nonce ) {     // Checking nonce as UFP is not OICD standard.  
    //[dnc8] Calculate Users's Fingerprint with state from query 
    $ufp = compute_user_fingerprint( $state );       //ufp      
    // Compare actual UFP with nonce
    if ( $nonce !== $ufp ) {
        log_error("Authorize" ,"Invalid Nonce", $client_id, $sub, 105, 100, $cnx);
        // Destroy all session data
        destroy_all_session_data();
        // and respond with error 
        if ( DEBUG) {
            $response->setError(403, 'forbidden', 'Invalid Nonce');
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();
        die();        
    }
}

// At this point, we have a good request
if( PRTG ) oidc_increment('good_requests');  

// Client/Application data
$_SESSION['client_id'] = $client_id;    // Store client_id in user identity session
$stmt = $cnx->prepare(sprintf("SELECT a.*, c.* FROM spip_auteurs a, spip_auteurs_liens al, %s c WHERE a.id_auteur=al.id_auteur AND al.objet='client' AND al.id_objet=c.id_client AND c.client_id=:client_id", $storage_config['client_table']));    //*****
$stmt->execute(compact('client_id'));
$data = $stmt->fetch(\PDO::FETCH_ASSOC);

/** Define subject (unique ID of end user), 
* May be imposed by client or passed in query.
* May be null at this step.
*/
$sub = null; 
// look for unique user_id from client registration. Has priority over any.
$sub = htmlspecialchars($data['user_id']);   
if ( empty($sub) ) {
    // if not defined in client registration, passed in query? 
    if ( !empty($request->query('user_id')) ) $sub = $request->query('user_id');  //[dnc29']
}
if ( empty($sub) ) {   //[dnc29']
    // if still empty, defined in session ?
    if (!empty(@$_SESSION['sub']) ) $sub = $_SESSION['sub']; 
}
if ( $enable_sli AND !empty($slidata['sub']) ) {     //[dnc9] SLI
    // SLI is active,
    if ( !empty($sub) ) {
        // if sub is given we should have the same SLI sub.
        if ( $slidata['sub'] !== $sub ) {   
            $enable_sli = false;     
        }
    } else {   
        // Adopt SLI user. Not in specification, but transparent.
        $sub = $slidata['sub'];     
    }
}
// At this step, $sub may still be null and should be determined later with login.
// Store authenticated subject in session 
$_SESSION['sub'] = $sub;


if ( empty($password) AND empty($grant) AND empty($return_from) ) { 

    //////////////////////  Prepare authorisation forms  ///////////////////////
    $theclient = ( empty($data['client_id'])? 'Unk' : htmlspecialchars($data['client_id']) );
    $id_client = (int)($data['id_client']);   // client unique ID in table clients
    $thename = ( empty($data['nom'])? 'Unk' : htmlspecialchars($data['nom']) );
    $thesite = ( empty($data['nom_site'])? '' : htmlspecialchars($data['nom_site']) );
    $theurl = ( empty($data['url_site'])? '' : htmlspecialchars($data['url_site']) );
    $thesecret = $data['client_secret'];   //???
    $scopes = explode(' ', $scope);

    log_info("Authorize" ,"Begin authentification for client = " . $client_id, $client_id, $sub, 110, 0, $cnx);    

    /** See if subject is (was) connected
    * Note : subject is considered as connected if there is a valid (not expired) 
    * access_token for subject and client.
    * If subject just changed for a new client, we should test her connexion to previous 
    * client found in SLI cookie data.
    */
    $isconnected = false;
    $client_has_changed = true; // if not SLI, everything is like having a new client each time 

    if ($enable_sli AND ($previous_client = $slidata['client_id']) AND !empty($sub) ) {    

        //[dnc9] If we have a SLI cookie, test if subject is coming from another client
        $client_has_changed = (bool)($previous_client != $client_id);

        if ( $client_has_changed ) {  
            /* If subject is coming from another client, check she was connected to that previous client.
            By the way, we verify that previous client exists and is valid for this subject.
            */
            $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE user_id=:sub AND client_id=:previous_client ORDER BY expires DESC', $storage_config['access_token_table']));    //*****
            $stmt->execute(compact('previous_client', 'sub'));
            $authdata = $stmt->fetch(\PDO::FETCH_ASSOC);
            // verify access token not expired for more than SRA_REPARATION_TIME + 1mn
            $isconnected = (bool)( strtotime($authdata['expires']) + SRA_REPARATION_TIME + 60 > time() );   // le fuseau horaire du serveur doit être Z (UTC)!        
            if ( ! $isconnected ) {
                log_info("Authorize" ,"SLI : client has changed, user not connected", $client_id, $sub, 111, 1, $cnx);
                //[dnc29] Destroy all session data silently
                destroy_all_session_data();
                // continue to authorization
            }

        } else {  
            // client has not changed, test subject connexion to current client.
            if ( ! $isconnected AND ! empty($sub) ) {
                // if sub is defined, verify wheter subject is (was) already connected or not
                $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE user_id=:sub AND client_id=:client_id ORDER BY expires DESC', $storage_config['access_token_table']));    //*****
                $stmt->execute(compact('client_id', 'sub'));
                $authdata = $stmt->fetch(\PDO::FETCH_ASSOC);
                //* verify access token not expired for more than SRA_REPARATION_TIME  + 1mn
                $isconnected = (bool)( strtotime($authdata['expires']) + SRA_REPARATION_TIME + 60 > time() );   // le fuseau horaire du serveur doit être Z (UTC)!
            } 

        }
        if ( ! $isconnected ) $sub = null;  // do'nt display user in login form if she is not connected
        // Store authenticated subject in session 
        $_SESSION['sub'] = $isconnected ? $sub : null;
    }


    /* Start processing Authorization.
    Login/grant form is generated at authentification time by this server, not by the application. 
    Thus, the Authorization Code Flow maintains the whole authentication process within the server.
    It should be remembered, since many newbies keep on thinking that it is done by the application, thus 
    considering other flows like the Implicit Grant as safe (what they are NOT).
    */

    /* Manage prompt parameter : what to display or not.
    see openid.net/specs/openid-connect-core-1_0.html § 3.1.2.3
    see AuthorizeController::setNotAuthorizedResponse
    */

    if ( strpos($prompt, 'none') !== false ) {

        ////////////////////////  prompt == none  ////////////////////////////// 

        /* Never display any form  
        Disregard any other value in prompt list. OIDC specs says we should return an error.
        We will return immediately to caller, avoiding the redirection of Authorization Code Flow.
        */    

        // test subject for present connexion to current client.       
        if ( ! empty($client_id) AND ! empty($sub) ) {
            // if sub is defined, verify wheter subject is connected or not
            $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE user_id=:sub AND client_id=:client_id ORDER BY expires DESC', $storage_config['access_token_table']));    
            $stmt->execute(compact('client_id', 'sub'));
            $authdata = $stmt->fetch(\PDO::FETCH_ASSOC);
            //* verify access token not expired
            $timeleft = strtotime($authdata['expires']) - time();   //[dnc28d]   le fuseau horaire du serveur doit être Z (UTC)!
            $isconnectednow = (bool)( $timeleft > 0 );   
        } else 
            $isconnectednow = false;

        //[dnc36] If CORS request, process it and respond to user-agent right now. 
        if ( cors_allow_known_client( $client_id, $response, $cnx ) ) {
            // answer directly to user-agent
            if ( ! $isconnectednow ) {
                // user is not connected, answer with code 401.
                $response->setError(401, 'Unauthorized');
            } // else subject is already connected, answer with code 200 and time left.
            $response->addParameters(array('timeleft'=>$timeleft)); //[dnc28d]
            $response->send();
            die(); 

        } else {
            // Return autorisation code to client
            if ( $isconnectednow ) {
                // Ok if subject is already connected,
                log_info("Authorize" ,"Prompt none : user is connected - client = " . $client_id . " sub = " . $sub, $client_id, $sub, 112, 0, $cnx);     
                $is_authorized = true;
            } else { 
                // Redirect with error 
                log_info("Authorize" ,"Prompt none : user is'nt connected", $client_id, $sub, 113, 0, $cnx);  
                $is_authorized = false;
            }
        } 

    } else {

        /////////////////  prompt empty  ////////////
        /*  If prompt is empty, if SLI is enabled and if subject is seen connected 
        by SLI, we will connect user without prompting her for login. 
        Else we will ask her to login.
        */

        if ( empty($prompt) ) {
            
            //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG  
            
            //[dnc9]
            if ( $enable_sli ) {   

                // When SLI cookie verified and SLI allowed by client
                if ( $isconnected ) {
                    // if subject is (was) already connected, extend SLI cookie. This makes SRA too !
                    // Refresh SLI Cookie
                    $cookiedata = array(    
                        'sliID' => $slidata['sliID'],       // sliID is state at last login time
                        'sub' => $slidata['sub'],           // value at last login time
                        'client_id' => $client_id,          // client_id might change
                        'ufp' => $slidata['ufp'],           // user fingerprint at last login time    //ufp
                        'authtime' => time(),               // check server time zone is UTC !
                    );
                    
                    $sub = $slidata['sub'];
                    // Store authenticated subject in session 
                    $_SESSION['sub'] = $sub;

                    // Send encoded SLI cookie to user-agent in server's domain
                    $jcookiedata = json_encode($cookiedata);
                    send_private_encoded_cookie('sli', $jcookiedata, SLI_COOKIE_LIFETIME);
                    
                    // Continue with consent ?     
                    $continue_with_consent = false;       //[dnc24]
                    if ( $prompt == 'consent' OR (empty($prompt) AND PROMPT_DEFAULT_TO_CONSENT) ) { //[dnc24a] process consent if prompt is undefined. 
                        $scopes = explode(' ', $request->query('scope'));
                        if ( count(scopes_to_grant($scopes, $client_id)) ) {      
                            // There are scope(s) left to be granted
                            $continue_with_consent = true;
                        }   
                    }   

                    /*[dnc36b] If CORS request, this is SRA : respond directly to user-agent with authorization code.
                    */ 
                    if ( cors_allow_known_client( $client_id, $response, $cnx ) ) {
                        // directly return autorisation code to client
                        $continue_with_consent = false;
                    }           

                    if ( DEBUG ) {       
                        $trace .= '----- SRA : SLI Cookie refreshed -----' . "<br />";
                        $trace .= 'cookie data : ' . print_r($cookiedata,true) . "<br /><br />";
                        $response->addParameters(array('trace' => urlencode($trace)));     
                    }

                    if (  $continue_with_consent == false ) {
                        // No consent needed, return autorisation code to client
                        log_success("Authorize" ,"SLI (prompt = empty, none or login ) : SLI successful, cookie refreshed - client = " . $client_id . " sub = " . $sub, $client_id, $sub, 120, -10, $cnx);   
                        if( PRTG ) oidc_increment('authentications');
                        $is_authorized = true;
                    }

                } else {  // user is not connected

                    /** Destroy SLI cookie
                    * Note : In case of SLI, subject is considered as connected if there is a   
                    * valid access_token for subject and client. If we just created SLI cookie 
                    * after a successful authorization above, and if Token controller failed 
                    * for any reason, access_token is missing and SLI cookie is not safe 
                    * therefore should be destroyed.
                    */
                    discard_cookie('sli');

                    if ( REAUTHENTICATE_NO_ROUNDTRIP ) {  //[dnc10]

                        // Re-authenticate at server's : continue with prompt = login
                        log_info("Authorize" ,"SLI (prompt none) : user was not connected, re-authenticate at server's, continue with prompt=login", $client_id, $sub, 121, 0, $cnx); 
                        if ( empty($prompt) OR strpos($prompt, 'consent') !== false OR empty($prompt) AND PROMPT_DEFAULT_TO_CONSENT ) {  //[dnc24a] //[dnc32]
                            $continue_with_consent = true;
                        }
                        $prompt = 'login';

                    } else {

                        // Redirect to client, it knows what to do, hu?
                        log_info("Authorize" ,"SLI (prompt empty) : user was not connected, return to client", $client_id, $sub, 122, 0, $cnx); 
                        $is_authorized = false;    
                    } 
                    
                    $sub = null;
                    // Store  no authenticated subject in session 
                    $_SESSION['sub'] = null;   
                } 
                // From there we go to "End with redirection". Access and ID Token will be re-initiated if $is_authorized is True.

            } else {

                // Not SLI, continue with login 
                log_info("Authorize" ,"Prompt empty, SLI not enabled, continue with prompt=login", $client_id, $sub, 123, 1, $cnx);
                if ( empty($prompt) OR strpos($prompt, 'consent') !== false OR empty($prompt) AND PROMPT_DEFAULT_TO_CONSENT ) {  //[dnc24a] //[dnc32]
                    $continue_with_consent = true;
                }
                $prompt = 'login';

            }
        } 

        ///////////// prompt == 'login' or == 'login consent' //////////////////

        if ( strpos($prompt, 'login') !== false ) {  

            //[dnc24] prompt == 'login consent' : remember there will be scopes to grant after login
            if ( strpos($prompt, 'consent') !== false ) {
                $scopes_to_grant = scopes_to_grant($scopes, $client_id);
                $_SESSION['scopes_to_grant'] = implode(' ', $scopes_to_grant); 
            } else {
                unset($_SESSION['scopes_to_grant']);
            }

            // Prompt user for (re)authentication 
            log_info("Authorize" ,"Display login form", $client_id, $sub, 130, 1, $cnx); 
            exit(
                // Display login form
                include './identification/' . LOGIN_FORM . '/login.php' 
            );

        } // end prompt contains login

        
        /////// prompt == consent only or $continue_with_consent == true ///////

        if ( $prompt == 'consent' OR $continue_with_consent == true ) {  //[dnc24]

            // Requested scope(s) left to be granted
            $scopes = explode(' ', $request->query('scope'));
            $scopes_to_grant = scopes_to_grant($scopes, $client_id); 

            if ( $count = count($scopes_to_grant) ) {
                // log
                $being_granted_scopes = implode(' ',$scopes_to_grant);
                log_info("Authorize" ,"Display grant form, scopes = " . $being_granted_scopes, $client_id, $sub, 140, 0, $cnx);
                // Display grant form, will redirect back to this script. 
                exit(     
                    include "./identification/grant/grant.php" 
                );

            } // else no scope left to be granted

        } // end prompt == consent only

    } // end prompt = empty or Null 

} // end prepare authorisation forms



if ( ! empty($password) OR 'login' == $return_from ) {

    ////////////////////////  Return from login form  //////////////////////////////

    $answer = unserialize(decrypt(@$_GET['answer']));
    if ( $answer ) {

        $is_authorized = @$answer['is_authorized'];
        $error = rtrim(@$answer['error'],'%');  // Remove random-length trail of '%'
        $sub = @$answer['sub'];
        $redo = @$answer['redo'];
        
        // Store authenticated subject in session 
        $_SESSION['sub'] = $is_authorized ? $sub : null;

    } else {
        // missing answer or decrypt() failed : forged answer, destroy all session data
        destroy_all_session_data();
        // die with error 
        if ( DEBUG) {
            $response->setError(403, 'forbidden', 'Return from login form with forged data');
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();
        die();    
    }

    if ( $error ) { // Error raised at identification step

        $is_authorized = false;

        if ( $redo AND LOGIN_NO_ROUNDTRIP ) {

            // Retry at server's
            if ( ($attempts = @$_SESSION["attempts"]) < ALLOWED_ATTEMPTS ) {

                log_info("Authorize" ,"Redo : " . $error . ' Attempts left = ' . ALLOWED_ATTEMPTS - $attempts, $client_id, $sub, 151, 1, $cnx);
                $_SESSION["attempts"] = $attempts + 1;
                sleep(1); // penalize 
                $data = array(
                    'response_type' => $response_type, // comes from request
                    'client_id' => $client_id, // comes from request
                    'scope' => $scope,  // comes from request  
                    'state' => decrypt($_SESSION['state']),  // the unforged one  [dnc33]
                    'error' => $error. '. ' . _('Please retry') . '.',
                );
                $redirect_uri = htmlEntities(@$_GET['redirect_uri']);          
                if ( !empty($redirect_uri) ) {
                    $data['redirect_uri'] = $redirect_uri;    
                }

                // Return to Authorize
                $authorization_endpoint = OIDC_SERVER_URL . '/authorize';
                $authorization_endpoint .= '?' . http_build_query($data);
                header('Location: ' . $authorization_endpoint);
                exit();

            } else {
                // Attempts count exceeded
                log_error("Authorize" ,"Redo returns error to client : " . $message, $client_id, $sub, 152, 10, $cnx);
                // Destroy all session data ???
                destroy_all_session_data();
                // Return to client with error
                $server->handleAuthorizeRequest($request, $response, false, null);
                if ( DEBUG ) {      
                    $trace .= '----- Redo -----' . "<br />";
                    $trace .= 'Redo returns error to client : ' . $message . "<br /><br />";
                    $response->addParameters(array('trace' => urlencode($trace)));     
                }  
                $response->send();
                die();
            }

        } else {
            // Redirect with error
            log_error("Authorize" ,$error, $client_id, $sub, 150, 10, $cnx);
        }   
    }
    
    if ( $is_authorized AND LOGIN_WITH_TFA ) {
        //[dnc43] Prompt user for Two Factors Authentication
        log_info("Authorize" ,"Display TFA form", $client_id, $sub, 190, 1, $cnx);
        $client_id = $_GET['client_id'];
        exit(
            // Display login form
            include './identification/' . TFA_PROVIDER . '/login.php'
        );    
        
    }

    if ( ENABLE_SLI ) {
        //[dnc9] Create (or destroy) SLI cookie  

        if ( $is_authorized ) {

            $client_id = @$_SESSION['client_id'];  // get it from session, safer than from form 
            if ( !empty($client_id) ) {
                // Create SLI Cookie
                $cookiedata = array(    
                    'sliID' => $sliID,   // sliID is the value of state at creation time
                    'sub' => $sub,       // value whose credential have been checked
                    'client_id' => $client_id,
                    'ufp' => compute_user_fingerprint($state), // user's fingerprint
                    'authtime' => time(),
                );
                // send encoded SLI cookie to user-agent in server's domain
                $jcookiedata = json_encode($cookiedata);
                send_private_encoded_cookie('sli', $jcookiedata, SLI_COOKIE_LIFETIME);

                log_info("Authorize" ,"SLI successful : cookie created - client = " . $client_id . " sub = " . $sub, $client_id, $sub, 156, -10, $cnx);   

                if ( DEBUG ) {    
                    $trace .= '----- New SLI Cookie -----' . "<br />";
                    $trace .= 'cookie data : ' . print_r($cookiedata,true) . "<br /><br />";    
                }

            } else {
                log_error("Authorize" ,"SLI error : null client ID : cookie destroyed", $client_id, $sub, 157, 100, $cnx);
                // Destroy all session data
                destroy_all_session_data();
                // die with error
                if ( DEBUG) {
                    $response->setError(400,'Bad Request', 'SLI error : null client ID');
                    $trace .= '----- SLI Cookie destroyed (case 1) -----' . "<br /><br />";    
                } else {
                    $response->setError(400,'Bad Request');
                    sleep(10); // penalize skiddie
                }
                $response->send();
                die(); 
            }

        } else {

            log_info("Authorize" ,"SLI : client was not connected : cookie destroyed", $client_id, $sub, 158, 10, $cnx);
            // Destroy all session data
            destroy_all_session_data();
            // die with error 

            if ( DEBUG ) {
                $trace .= '----- SLI Cookie destroyed (case 2) -----' . "<br /><br />";   
            }
        }

    } // Note that, if we have a SLI cookie with client not allowing SLI, we keep the cookie for other clients.

    if ( $is_authorized ) {
        log_success("Authorize" ,"Success - client = " . $client_id . " sub = " . $sub, $client_id, $sub, 159, -10, $cnx);    
        // PRTG
        if( PRTG ) oidc_increment('authentications');             
    }

} // End Return from Login form

if ( '2fa' == $return_from ) {

    ////////////////////////[dnc43]  Return from Two Factors Authentication  //////////////////////////////
    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG
    $answer = unserialize(decrypt(@$_GET['answer']));
    if ( $answer ) {
        $is_authorized = @$answer['is_authorized'];
        $error = rtrim(@$answer['error'],'%');  // Remove random-length trail of '%'
        $redo = @$answer['redo'];

    } else {
        // missing answer or decrypt() failed : forged answer, destroy all session data
        destroy_all_session_data();
        // die with error 
        if ( DEBUG) {
            $response->setError(403, 'forbidden', 'Return from TFA form with forged data');
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();
        die();    
    }
    
    if ( $error ) { // Error raised at TFA step

        $is_authorized = false;

        if ( $redo AND LOGIN_NO_ROUNDTRIP ) {

            // Retry at server's
            if ( ($attempts = @$_SESSION["tfa_attempts"]) < ALLOWED_ATTEMPTS ) {

                log_info("Authorize" ,"TFA Redo : " . $error . ' Attempts left = ' . ALLOWED_ATTEMPTS - $attempts, $client_id, $sub, 193, 1, $cnx);
                $_SESSION["tfa_attempts"] = $attempts + 1;
                sleep(1); // penalize 
                $data = array(
                    'response_type' => $response_type, // comes from request
                    'client_id' => $client_id, // comes from request
                    'scope' => $scope,  // comes from request  
                    'state' => decrypt($_SESSION['state']),  // the unforged one  [dnc33]
                    'error' => $error. '. ' . _('Please retry') . '.',
                );
                $redirect_uri = htmlEntities(@$_GET['redirect_uri']);          
                if ( !empty($redirect_uri) ) {
                    $data['redirect_uri'] = $redirect_uri;    
                }

                // Return to Authorize
                $authorization_endpoint = OIDC_SERVER_URL . '/authorize';
                $authorization_endpoint .= '?' . http_build_query($data);
                header('Location: ' . $authorization_endpoint);
                exit();

            } else {
                // Attempts count exceeded
                log_error("Authorize" ,"TFA Redo returns error to client : " . $message, $client_id, $sub, 194, 10, $cnx);
                // Destroy all session data ???
                destroy_all_session_data();
                // Return to client with error
                $server->handleAuthorizeRequest($request, $response, false, null);
                if ( DEBUG ) {      
                    $trace .= '----- TFA Redo -----' . "<br />";
                    $trace .= 'TFA Redo returns error to client : ' . $message . "<br /><br />";
                    $response->addParameters(array('trace' => urlencode($trace)));     
                }  
                $response->send();
                die();
            }

        } else {
            // Redirect with error
            log_error("Authorize" ,$error, $client_id, $sub, 195, 10, $cnx);
        }   
    }
    
    if ( $is_authorized ) {
        log_success("Authorize" ,"TFA success - client = " . $client_id . " sub = " . $sub, $client_id, $sub, 190, -10, $cnx);                    
    }

} // End Return from TFA form
    
    
    
if ( ! empty($grant) OR 'grant' == $return_from ) { 

    /////////////////////////  Return from Grant form  /////////////////////////////
    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG
    
    if ( ($sub = @$_SESSION['sub'])  ) {         
        // End-user should have been authentified before Grant
        
        $is_authorized = ( htmlspecialchars(@$_POST['grant']) == 'on' );   // user should accept all grant requests in one piece (or not)

        $just_granted_scopes = htmlspecialchars(@$_POST['just_granted_scopes']); // string of just granted scopes

        if ( $is_authorized ) {
            //[dnc24] Granted : update granted scopes in session data
            $already_granted_scopes = unserialize(@$_SESSION['granted_scopes']);   
            //$already_granted_scopes = explode(' ', @$_COOKIE['granted_scopes']);   // array of client_id, already granted scope for the client
            if (@$already_granted_scopes[$client_id]) { 
                if ( $just_granted_scopes ) {
                    $just_granted_scopes_array = explode(' ', $just_granted_scopes);
                    foreach ( $just_granted_scopes_array as $thescope) {
                        if ( strpos($already_granted_scopes[$client_id], $thescope) === false ) {
                            // add this scope
                            $granted_scopes[$client_id] .= ' ' . $thescope;
                        }
                    }
                }
            } else {
                // We are the first
                $already_granted_scopes[$client_id] = $just_granted_scopes;    
            }
            $_SESSION['granted_scopes'] = serialize($already_granted_scopes);
            //setcookie('granted_scopes', implode(' ', $already_granted_scopes), time() + SLI_COOKIE_LIFETIME, '/', OIDC_SERVER_DOMAIN, true, true);
            // Granted
            log_info("Authorize" ,"Return from Grant : " . $just_granted_scopes . ' granted', $client_id, $sub, 161, 0, $cnx);

        } else {    
            // Not granted. Client application knows what to do ;)
            log_error("Authorize" ,"Return from Grant : " . $just_granted_scopes . ' not granted', $client_id, $sub, 162, 1, $cnx);
        }

    } else {
        log_error("Authorize" ,"Return from Grant : user was not authentified", $client_id, $sub, 163, 10, $cnx);
        $is_authorized = false;

    }

} // End Return from Grant form


//////////////  End with redirection  ///////////////////

$server->handleAuthorizeRequest($request, $response, $is_authorized, $sub);

if ( DEBUG ) $response->addParameters(array('trace' => urlencode($trace)));

$scopes = explode(' ', $request->query('scope'));
if ( ! empty($scopes_to_grant = scopes_to_grant($scopes, $client_id)) AND $is_authorized AND $prompt !== 'none' ) { 
    // There are scope(s) left to be granted, re-enter Authorize to process them
    $response->addParameters(array(        
        'response_type' => $response_type,
        'client_id' => $client_id,
        'state' => $state,   
        'scope' => $scope,
        'prompt' => 'consent',
    ));
    $response->setRedirect(302, '/oidc/authorize.php');
} 

// Store authenticated subject in session 
$_SESSION['sub'] = $is_authorized ? $sub : null; 

// redirect to client with authorization or error
$response->send();
die();



////////////////////////////////  Utilities  ///////////////////////////////////

/** [dnc24]
* Après avoir éliminé les scopes réservés, la fonction examine si les scopes 
* demandés ont déjà été consentis au cours de la session de l'utilisateur final.
* Elle retourne un array des scopes restant éventuellement à traiter, ou null. 
* 
* @param mixed $scopes : array des scopes demandés
* @param mixed $client_id : ID de l'application cliente courante
* @return : array of scopes or null 
*/
function scopes_to_grant($scopes, $client_id) {

    // Eliminate reserved scopes
    /* //[dnc31] $Scope = new \OAuth2\Scope();     //*****
    $reservedscopes = $Scope->getReservedScopes();    */
    $reservedscopes = array('openid', 'offline_access', 'sli', 'kerberos', 'privileges'); //[dnc31]
    $scopes_to_retain = array();
    foreach ( $scopes as $thescope) {
        if ( ! in_array($thescope, $reservedscopes) ) {
            // keep this scope
            $scopes_to_retain[] = $thescope;
        }
    }

    if ( DONT_PROMPT_FOR_ALREADY_GRANTED_SCOPE ) {  //[dnc24b]

        /* Do we have new scope(s) to consent with ?
        Make array of client_id => already granted scope for the client    
        */
        $scopes_to_grant = array();
        $already_granted_scopes = unserialize(@$_SESSION['granted_scopes']);
        //$already_granted_scopes = explode(' ', @$_COOKIE['granted_scopes']);   
        if ( @$already_granted_scopes[$client_id]) {
            foreach ( $scopes_to_retain as $thescope) {
                if ( strpos($already_granted_scopes[$client_id], $thescope) === false ) {
                    // this scope needs to be granted
                    $scopes_to_grant[] = $thescope;
                }
            }
        } else {
            // all non-reserved scopes need to be granted
            $scopes_to_grant = $scopes_to_retain;
        }

    } else {
        // all non-reserved scopes need to be granted
        $scopes_to_grant = $scopes_to_retain;
    }

    return ( count($scopes_to_grant)? $scopes_to_grant : null );
}

/** [dnc36b]
* Detect a CORS request (simple request, no preflight) 
* and allow known client. 
* See: https://zinoui.com/blog/cross-domain-ajax-request 
* 
* @param mixed $client_id
* @param mixed $stmt
*/
function cors_allow_known_client( $client_id, $response, $cnx ) {

    if ( !is_null($httporigin = @$_SERVER['HTTP_ORIGIN']) OR @$_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" ) {
        global $storage_config;              
        $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE client_id=:client_id', $storage_config['client_table']));    
        $stmt->execute(compact('client_id'));
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);
        $client_domain = parse_url($data['redirect_uri'], PHP_URL_HOST);
        $response->setHttpHeader('Access-Control-Allow-Headers','x-requested-with');
        $response->setHttpHeader('Access-Control-Allow-Origin','https://' . $client_domain);
        $response->setHttpHeader('Vary','Origin');
        return true;

    } else {
        // not a CORS request
        return false;
    }
}
