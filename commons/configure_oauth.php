<?php
/* configure_oauth.php
Configuration du serveur OAuthSD, protocole OAuth 2.0.

Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
Licence GPL3
*/

define('_GHOSTKEYS','1');  // don't modify

//***** CONFIGURATION *****

include_once(__DIR__. "/configure.php");    

// Log
define('LOG_LEVEL', 3 ); // 3 = error + info + success, 2 = error + info, null, 0 or 1 = error only     
// où trouver le fichier avec les fonctions log_error() et log_info ?
include_once(__DIR__ . '/log.php');    
// dans quel fichier inscrire les messages ?
define('LOG_FILE', __DIR__.'/../tmp/oauth.log');    // dans oauth/tmp/

// Storage configuration     
$storage_config = array(
    // tables 
    'client_table' => 'spip_clients',
    'access_token_table' => 'spip_access_tokens',
    'refresh_token_table' => 'spip_refresh_tokens',
    'code_table' => 'spip_authorization_codes',
    'user_table' => 'spip_users',
    'jwt_table'  => 'spip_jwt',
    'jti_table'  => 'spip_jti',
    'scope_table'  => 'spip_scopes',
    'public_key_table'  => 'spip_public_keys',
    'scope_table'  => 'spip_scopes',
    // lifetimes
    'id_lifetime'              => 3600,
    'access_lifetime'          => 3600,
    'always_issue_new_refresh_token' => true,
    'refresh_token_lifetime'         => 2419200,   // 28 days
);

//*****

//***** OPTIONS *****

/** NEEDS_OPENID_SCOPE      
* If true, all calls to Othaurize should have scope openid.
*/
define('NEEEDS_OPENID_SCOPE', false);

/**  CHECK_NONCE_AS_UFP
* Set to true to force checking nonce as a User FingerPrint (UFP). False is standard.
* If set to true, OAuthSD is expecting a UFP as nonce. If an ordinary nonce is 
* passed in Authorize request, such as a random string, OAuthSD will refuse authorization 
* and will return 'Forbidden'.
* If set to false, OAuthSD follows standard process checking nonce.
* Checking UFP as nonce can only be used within a corporate realm where client applications
* elaborates UFP and send it as nonce. Since UFP check happens right at the beginning 
* of the authorization process, it adds security against DDOS and Man-in-the-middle attacks.
* This parameter act only on Authorize. UFP is checked by OAuthSD at different steps. 
* [dnc8] 
*/
define('CHECK_NONCE_AS_UFP', false);       //ufp

/** [dnc9] Enable Single Login Identification? 
* Will be effictive only if client application was registered with scope 'sli'.
*/
define('ENABLE_SLI', true);

/** SLI cookie lifetime.
* SLI cookie is refreshed with this value each time authorize is called. 
* So it is the duration of cookie survival after last authorize request.
*/
define('SLI_COOKIE_LIFETIME', 3600);

/** [dnc14] Check client IP if registered.
* client_ip, if static, may be defined when registering client application.
* If defined, client_ip is compared to $_SERVER['REMOTE_ADDR'].
* Checking client's IP is not OIDC standard, meanwhile not opposite to it.
* It is strongly advised to enforce this check when enabling SLI.
* This parameter act only on token controller.  
*/
define('CHECK_CLIENT_IP', false);

/** PRTG
* True to allow prtg tracking.
* See /oidc/prtg/oauthsd_prtg.txt  
*/
define ('PRTG', true);
/**
* Allow traking of all requests?
* If true, all requests including skiddie's will be tracked.
* This induce server load and makes it sensitive to deny of service attacks.
* Keep it true, unless induced load proves to make DDOS attacks successful.  
* if false, tracking begins after request validation.
*/
define ('PRTG_TOTAL_REQUESTS', true);

/** [dnc6] Allow  "jku" (JWK Set URL) claim in JWT header.
'jku' claim will pass OIDC_SERVER_URL . '/oidc/jwks.json',
*/ 
define('JKU_IN_JWT_HEADER', true);

/** [dnc6] Allow  "jwk" (JWK URL) claim in JWT header. 
* 'jwk' claim will pass OIDC_SERVER_URL . '/oidc/jwks/' . $payload['kid'] . '.json'
*/ 
define('JWK_IN_JWT_HEADER', false);   // Allow JWK or JKU, not both. In case both are true, JWK has precedence.

/* Suported Scopes
*/
$defaultScope = 'basic';
$supportedScopes = array(
    'basic',
    'profile',
    'email',
    'address',
    'phone',
);

define('OAUTHSRV_PATH', __DIR__. "/../vendor/bdegoy/oauthsd-php/src/Oa2Srv/");