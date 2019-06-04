<?php
/*
authorize.php avec GhostKeys

Authorize Controller for OAuth2 Server

See : http://bshaffer.github.io/oauth2-server-php-docs/cookbook/

Test : oa.dnc.global/oauth/authorize.php?response_type=code&client_id=testclient&state=xyz

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/


define('__AUTHORIZE',1);

$antitab = array();

// Include our OAuth2 Server object  
define('PRIVATE', true);
require_once __DIR__ . '/includes/server.php';

// Set a session for each state = each instance of client application
session_save_path(SLI_SESSION_DIR);
session_name('oauthsd'); 
if ( isset($_GET['state']) ) session_id($_GET['state']);
session_start();

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// Validate the authorize request
if (!$server->validateAuthorizeRequest($request, $response)) {
    $response->send();
    die;
}

$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

// Prepare Client and Author data
$client_id = $request->query['client_id'];
$stmt = $cnx->prepare($sql = "SELECT a.*, c.* FROM spip_auteurs a, spip_auteurs_liens al, spip_clients c WHERE a.id_auteur=al.id_auteur AND al.objet='client' AND al.id_objet=c.id_client AND c.client_id=:client_id");
$stmt->execute(compact('client_id'));
$data = $stmt->fetch(\PDO::FETCH_ASSOC); 
$theclient = ( empty($data['client_id'])? 'Unk' : htmlspecialchars($data['client_id']) );
$id_client = (int)($data['id_client']);
$thename = ( empty($data['nom'])? 'Unk' : htmlspecialchars($data['nom']) );
$thesite = ( empty($data['nom_site'])? '' : htmlspecialchars($data['nom_site']) );
$theurl = ( empty($data['url_site'])? '' : htmlspecialchars($data['url_site']) );
$thecss = ( empty($data['css'])? '' : htmlspecialchars($data['css']) );
$thetexte1 = ( empty($data['texte1'])? '' : htmlspecialchars($data['texte1']) );   //[dnc16]
$thetexte2 = ( empty($data['texte2'])? '' : htmlspecialchars($data['texte2']) );   //[dnc16]

// pre-fill login with user_id if defined in client application
$uid = $data['user_id'];        
// user_id (username) may also be passed in request. Has priority over application. Not in specification ?
if ( !empty($request->query('uid')) ) $uid = $request->query('uid');

if (empty($_POST)) { 

    // Store state in session
    $_SESSION['state'] = $_GET['state'];

    // Display authorization form to End user    
    $explain = '';
    if ( !empty($theurl) AND !empty($thesite) ) {
        $explain = '<div class="explain">En savoir plus sur ' . $thename. ' : <a href="' . $theurl . '">' . $thesite . '</a></div>';
    }    

    if ( FORCE_EMAIL ) {
        $login_label = 'E-mail';
    } else {
        $login_label = 'E-mail ou pseudo';
    }

    // Build scopes list
    $count = 0;
    $stmt = $cnx->prepare($sql = "SELECT * FROM spip_clients WHERE client_id=:theclient");
    $stmt->execute(compact('theclient'));
    $data = $stmt->fetch(\PDO::FETCH_ASSOC);
    $scope_string = $data['scope'];  // string of client scopes
    $scope_array = explode(' ', $scope_string);
    $scopes_html = '<ul>';
    foreach ( $scope_array as $thescope ) {
        if ( $thescope !== 'openid' ) {  // à prendre dans la liste des scopes réservés, il y a une fon pour cela
            $stmt = $cnx->prepare($sql = "SELECT * FROM spip_scopes WHERE scope=:thescope");
            $stmt->execute(compact('thescope'));
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            $thedefinition = $data['scope_description'];  
            $scopes_html .= '<li>';
            $scopes_html .= '<span class="scopetitle">' . $thescope . ' : </span>&nbsp;<span class="scopedefinition">' . $thedefinition . '</span>';
            $scopes_html .= '</li>';
            $count += 1;
        }
    }
    $scopes_html .= '</ul>';

    if ( $count ) {
        $scopes_html = 'Étendue de la demande (Scopes) : <br />' . $scopes_html;
    } else {
        $scopes_html = '';
    }

    // Prepare password encoding
    $tab = array(1=>'A', 2=>'B', 3=>'C', 4=>'D', 5=>'E', 6=>'F', 7=>'G', 8=>'H', 9=>'I',
        10=>'J', 11=>'K', 12=>'L', 13=>'M', 14=>'N', 15=>'O', 16=>'P');   // index => char  
    shuffle($tab);
    $antitab = array_flip($tab);   // char => index
    $_SESSION["antitab"] = $antitab;

    exit(
        // Display login form
        include "./ui/login.php" 
    );
}

// Return from Form

// Security : verify state
$state = $_POST['state'];
if ( is_null(@$_SESSION['state']) OR $state !== @$_SESSION['state'] ) exit();       //*****

// Validate GhostKeys or die
$ghostkeys = $_POST["password"];
$sanitized_ghostkeys = preg_replace('/[^A-P"\']/', '', $ghostkeys);
if ( $sanitized_ghostkeys != $ghostkeys OR strlen($ghostkeys) != PSWD_LENGTH ) {
    // We talk to a browser : no response object
    $errormsg = 'Bad credentials'; 
    exit(                                         
        // Display error form
        include "./includes/error.php" 
    );
}

// Decode GhostKeys. 
$tableau = $_SESSION["tableau"];
$antitab = $_SESSION["antitab"];
$password = "";
for ($i=0; $i<strlen($ghostkeys); $i++){  
    // Adds each non Null decoded char of ghostkey to $password
    $password .= $tableau[strtr($ghostkeys[$i],$antitab)]; 
}

/* Validate login or die
The login may be a RFC822 email or a pseudo.
If a pseudo, it should be made of chars and figures with no special char and 
the length should be LOGIN_MIN_LENGTH chars or more.
*/
$userid = $_POST['login'];

if (filter_var($userid, FILTER_VALIDATE_EMAIL)) {
    // find user id from email using SPIP users table
    $stmt = $cnx->prepare($sql = "SELECT username FROM spip_users WHERE email=:userid");
    $stmt->execute(compact('userid'));
    $data = $stmt->fetch(\PDO::FETCH_ASSOC);
    $userid = $data['username']; 

} else {
    if ( !FORCE_EMAIL ) {
        // If login is not an e-mail address, check for pseudo
        $sanitized_userid = preg_replace('/[^A-Za-z0-9"\']/', '', $userid);
        // Validate
        if ( $sanitized_userid != $userid OR strlen($userid) < LOGIN_MIN_LENGTH ) {
            // We are speaking with a navigator : no Response
            $errormsg = 'Bad credentials'; 
            exit(                                         
                // Display error form
                include "./error.php" 
            );
        } else {
            // good pseudo
            $userid = $sanitized_userid;
        } 
    } else {
        // email was expected
        $errormsg = 'E-mail expected'; 
        exit(                                         
            // Display error form
            include "./error.php" 
        ); 
    }

}

/* Is user's statut 'publie' ?
$stmt = $cnx->prepare($sql = "SELECT id_user FROM spip_users WHERE statut='publie' AND username=:userid");
$stmt->execute(compact('userid'));
$data = $stmt->fetch(\PDO::FETCH_ASSOC);

if ( !is_null($data) ) { 
    // Authenticate user's credentials
    $is_authorized = $storage->checkUserCredentials($userid, $password);
} else {
    $is_authorized = false;    
} //*/

// Authenticate user's credentials
$is_authorized = $storage->checkUserCredentials($userid, $password);

// Give answer
$server->handleAuthorizeRequest($request, $response, $is_authorized, $userid);
$response->send();
