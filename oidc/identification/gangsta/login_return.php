<?php
/*
login_return.php
Return from login with Google Authenticator
[dnc43]

The form in login.php calls this as action goal.
Calling back Authorize controller the normal way allow to trigger all security 
checks.
Sensible informations ( $sub, $state, $is_authorized ) are returned crypted, thus 
protecting Authorize against forgery.

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved 
*/

//DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

// Autoloading by Composer
require_once '../../../vendor/autoload.php';
// We use oauth2-server-php https://bshaffer.github.io/oauth2-server-php-docs/
OAuth2\Autoloader::register();
// We use PHPGangsta https://github.com/PHPGangsta/GoogleAuthenticator

// Server configuration
require_once '../../includes/configure.php';
require_once '../../includes/utils.php';

// Some initialization
$error = null;
$redo = false;
$is_authorized = false;
$sub = 'Unk';

// We need a response object to send to client in case of error
$response = new \OAuth2\Response();

$login = htmlEntities(@$_POST['login'], ENT_QUOTES);
$form_id = htmlEntities(@$_POST['return_from'], ENT_QUOTES);

// A few easy cheks
$tfacode = htmlEntities(@$_POST['tfacode'], ENT_QUOTES);
$response_type = htmlEntities(@$_POST['response_type'], ENT_QUOTES);
$scope = htmlEntities(@$_POST['scope'], ENT_QUOTES);
$client_id = htmlEntities(@$_POST['client_id'], ENT_QUOTES);
if ( 
'https://' . $_SERVER['HTTP_HOST'] !== OIDC_SERVER_URL
OR empty($tfacode)
OR empty($form_id)
OR empty($response_type)
OR empty($scope)
OR empty($client_id)
) {
    // Destroy all session data and die silently
    destroy_all_session_data();
    $response->setError(403, 'forbidden');
    if ( ! DEBUG ) sleep(10); // penalize skiddie       
    $response->send();
    die();
}

//Set oauthsd session
$state = $_POST['state'];   
that_session_start('oauthsd', $state, SLI_SESSION_DIR);  //[dnc34]

// Connect to database
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

// Verify state or die
if ( $state !== decrypt(@$_SESSION['state']) ) {   //[dnc21] [dnc33] 
    log_error("Authorize" ,"Return from TFA : wrong state", 'unk', $login, 191, 200, $cnx);
    // Destroy all session data
    destroy_all_session_data();
    // die with error 
    if ( DEBUG) {
        $response->setError(403, 'forbidden', 'Return from login form with wrong state');
    } else {
        $response->setError(403, 'forbidden');
        sleep(10); // penalize skiddie
    }
    $response->send();
    die();
}

// Verify UFP
$ufp = compute_user_fingerprint( $state );
if ( $ufp !== decrypt(@$_SESSION['ufp']) ) {
    log_error("Authorize" ,"Return from TFA : wrong UFP", 'unk', $login, 192, 200, $cnx);
    // Destroy all session data
    destroy_all_session_data();
    // die with error 
    if ( DEBUG) {
        $response->setError(403, 'forbidden', 'Return from login form with wrong UFP');
    } else {
        $response->setError(403, 'forbidden');
        sleep(10); // penalize skiddie
    }
    $response->send();
    die();

}

/* All scripts processing identification should begin with the above code or similar one.
Following code is particular to Gangsta TFA.
*/

/* All errors above where possibly attacks, so we killed process and returned directly to supposed client.
From there on, we will return to Authorize.
*/ 

/* Validate TFA 
*/
$ga = new PHPGangsta_GoogleAuthenticator();

if ( !is_null($ga) ) { 

    $secret = $_SESSION["tfasecret"];
    $result = $ga->verifyCode($secret, $tfacode, 2);       // Third parameter is time tolerance = N x 30s.
    
    if ( $result == true) {
        // Valid TFA
        $is_authorized = true;
    
    } else {
     // Invalid TFA, retry at server's
        $error = _('TFA error');
        $redo = true;
    }
}

/* 
All scripts processing identification should end with following code.
*/

/* Prepare crypted payload. 
No parameter outside crypted payload should give an information about failure or success 
of identification process. 
*/
$answer = array(
    'state' => @$state,
    'is_authorized' => ( $is_authorized ? true: false),
);
if ( $redo ) $answer['redo'] = $redo;
// Makes error message length random to hide failure or success.
$answer['error'] = $error . str_repeat('%', rand(5,40));  // add random-length trail of '%'

// Our function encrypt() is URL-safe.
$answer = encrypt(serialize($answer));

/* Prepare URL parameters.
Calling back Authorize controller the normal way allow to trigger all security 
checks.
Scripts processing identification should be transparent to 'response_type', 'client_id', 
'scope' and 'state'.
'return_from' should be unique for each identification process and usually takes 
the name of the script file.
*/

$data = array(
    'response_type' => $response_type,
    'client_id' => $client_id,
    'scope' => $scope,    
    'state' => $state,
    'return_from' => $form_id,
    'answer' => $answer,   // crypted payload.
);
$redirect_uri = htmlEntities(@$_POST['redirect_uri']); 
if ( !empty($redirect_uri) ) {
    // if an after-login uri is defined, return it              
    $data['redirect_uri'] = $redirect_uri;    
}
if ( !empty( $sub = @$_SESSION['sub']) ) {            
    // if sub was pre-defined, return it too
    $data['user_id'] = $sub; 
}

// Return to Authorize
$authorization_endpoint = OIDC_SERVER_URL . '/authorize';
$authorization_endpoint .= '?' . http_build_query($data);
header('Location: ' . $authorization_endpoint);
exit();
