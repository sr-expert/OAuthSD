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
define('LOG_FILE', __DIR__.'/../oauth/tmp/oauth.log');    // dans oauth/tmp/

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

/**
* It must be considered that the Oauth server is as secure as the least secure flow that it implements.
* Opening the possibility that the server responds to various requests 
* (such as implicit and hybrid flows) without controlling the client application 
* can be considered a security breach.
*/
define('ALLOW_IMPLICIT', true);

/** USE_JWT_ACCESS_TOKENS
* If sert to true, access token is issued as JWT. Is it pertinent with OpenID Connect ?
*/
define('USE_JWT_ACCESS_TOKENS', false);

/** Login/password options */
define ('PSWD_LENGTH', 9);      // Password exact length
define ('LOGIN_MIN_LENGTH',6);  // Login min length
define ('FORCE_EMAIL', FALSE);  // If TRUE, login should be an e-mail adress. If FALSE, login may be a pseudo or an email.

/** [dnc6] Allow  "jku" (JWK Set URL) claim in JWT header.
'jku' claim will pass OIDC_SERVER_URL . '/oidc/jwks.json',
*/ 
define('JKU_IN_JWT_HEADER', true);

/** [dnc6] Allow  "jwk" (JWK URL) claim in JWT header. 
* 'jwk' claim will pass OIDC_SERVER_URL . '/oidc/jwks/' . $payload['kid'] . '.json'
*/ 
define('JWK_IN_JWT_HEADER', false);   // Allow JWK or JKU, not both. In case both are true, JWK has precedence.


/** [dnc14] Check client IP if registered.
* client_ip, if static, may be defined when registering client application.
* If defined, client_ip is compared to $_SERVER['REMOTE_ADDR'].
* Checking client's IP is not OIDC standard, meanwhile not opposite to it.
* It is strongly advised to enforce this check when enabling SLI.
* This parameter act only on token controller.  
*/
define('CHECK_CLIENT_IP', false);

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
