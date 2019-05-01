<?php
/*
The form in login.php calls this as action goal.
Calling back Authorize controller the normal way allow to trigger all security 
checks.
Sensible informations ( $sub, $state, $is_authorized ) are returned crypted, thus 
protecting Authorize against forgery. 
*/

// Autoloading by Composer
require_once '../../../vendor/autoload.php';
OAuth2\Autoloader::register(); 

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

// A few easy cheks
$response_type = htmlEntities(@$_POST['response_type'], ENT_QUOTES);
$scope = htmlEntities(@$_POST['scope'], ENT_QUOTES);
$client_id = htmlEntities(@$_POST['client_id'], ENT_QUOTES);
if (
'https://' . $_SERVER['HTTP_HOST'] !== OIDC_SERVER_URL
OR empty($response_type)
OR empty($scope)
OR empty($client_id)   
OR empty(@$_REQUEST['return_from'])
) {
    // Destroy all session data and die silently
    destroy_all_session_data();
    $response->setError(403, 'forbidden');
    sleep(10); // penalize skiddie
    $response->send();
    die();
}

//Set oauthsd session
$state = $_POST['state'];   //***** 
that_session_start('oauthsd', $state, SLI_SESSION_DIR);  //[dnc34]

// Connect to database
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

// Verify state or die
if ( $state !== decrypt(@$_SESSION['state']) ) {   //[dnc21] [dnc33] 
    log_error("Authorize" ,"Return from login : wrong state", 'unk', $login, 190, 200, $cnx);
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
    log_error("Authorize" ,"Return from login : wrong UFP", 'unk', $login, 191, 200, $cnx);
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

/* All scripts processing identification should begin with the above code.
Following code is particular to GhostKeys.
*/

/* All errors above where possibly attacks, so we killed process and returned directly to client.
From there on, we will return to Authorize.
*/ 
// if sub was pre-defined, check identical login
if ( ! empty( $sub = @$_SESSION['sub']) ) {
    if ( $sub != $login ) {
        log_error("Authorize" ,"Incoherent user login", $client_id, $login, 192, 200, $cnx);
        // Destroy all session data
        destroy_all_session_data();
        // die with error 
        if ( DEBUG) {
            $response->setError(403, 'forbidden', 'Incoherent user login');
        } else {
            $response->setError(403, 'forbidden');
            sleep(10); // penalize skiddie
        }
        $response->send();
        die();
    }
}

/* Validate login or die
The login may be a RFC822 email or a pseudo.
If a pseudo, it should be made of chars and figures with no special char and 
the length should be LOGIN_MIN_LENGTH chars or more.
*/
if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
    // Convert email to login : search email in users and return username as $login.
    $stmt = $cnx->prepare(sprintf('SELECT username FROM %s WHERE email=:login', $storage_config['user_table']));    //*****
    $stmt->execute(compact('login'));
    $data = $stmt->fetch(\PDO::FETCH_ASSOC);
    $sub = $data['username']; 

} else {

    $sub = $login;

    if ( !FORCE_EMAIL ) {

        // If login is not an e-mail address, is it a well-formed pseudo ?
        $sanitized_userid = preg_replace('/[^A-Za-z0-9"\']/', '', $login);
        // Validate or die
        if ( $sanitized_userid != $login OR strlen($login) < LOGIN_MIN_LENGTH ) {
            $error = "Empty, too short or malformed_identifier";
            $redo = true;
        } 

    } else {
        // We want an e-mail, nothing else
        $error = "malformed_email";
        $redo = true;
    }

}

if ( ! $redo ) {
    // Is user's statut 'publie' ? Hello Spip ;)
    $stmt = $cnx->prepare(sprintf("SELECT id_user FROM %s WHERE statut='publie' AND username=:sub", $storage_config['user_table']));    //*****
    $stmt->execute(compact('sub'));
    $data = $stmt->fetch(\PDO::FETCH_ASSOC);

    if ( !is_null($data) ) { 

        // Authenticate user's credentials
        $password = htmlEntities(@$_POST['password'], ENT_QUOTES); 
        $storage = new OAuth2\Storage\Pdo( $connection, $storage_config );   // Initialize database storage 
        $is_authorized = $storage->checkUserCredentials($sub, $password);

        if ( $is_authorized == false ) { 
            // Invalid user, retry at server's
            $error = "Login error";
            $redo = true;
        }

    } else {
        // return to client with error
        $error = "Unknown or invalid User";
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
    'sub' => @$sub,
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
    'return_from' => 'login',
    'answer' => $answer,   // crypted payload.
);

// Return to Authorize
$authorization_endpoint = OIDC_SERVER_URL . '/authorize';
$authorization_endpoint .= '?' . http_build_query($data);
header('Location: ' . $authorization_endpoint);
exit();
