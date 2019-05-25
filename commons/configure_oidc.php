<?php
/* configure.php
Configuration du serveur OAuthSD.

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2019 DnC  
All rights reserved
*/


///// CONFIGURATION /////

include_once(__DIR__. "/configure.php"); 

/* Log des événements. Les événements sont considérés en fonction de leur niveau. 
* Les événements retenus sont enregistrés en base de données (table oidc_logs) et, 
* éventuellement, dans un fichier.
*/
define('LOG_LEVEL', 3 ); // 3 = error + info + success, 2 = error + info, null, 0 or 1 = error only     
// où trouver le fichier avec les fonctions log_error() et log_info ?
include_once(__DIR__ . '/log.php');    
/* Dans quel fichier inscrire les messages ?
* En fonction de la charge, il peut être utile de désigner un disque SSD ou même de 
* ne pas enregistrer les messages dans un fichier en indiquant une valeur nulle (empty).
*/
define('LOG_FILE', __DIR__.'/../tmp/oidc.log');    // dans oidc/tmp/

///// LIFETIMES /////



/** ..._TOKEN_LIFETIME
* In most situations, ID Token and SLI Cookie lifetimes should be set equal to Access Token lifetime.
* These lifetimes should be longer than the local session duration defined by the client application.
* With token lifetimes set to 1 hour, typical local session duration will be set around 1/2 hour.
*
* A simple method is to set a base lifetime equal to the largest local session time of clients and 
* deduce the rest.
*/
define('BASE_LIFETIME', 3600);
define ('ACCESS_TOKEN_LIFETIME', 2 * BASE_LIFETIME);
define ('ID_TOKEN_LIFETIME', 2 * BASE_LIFETIME);

/** REFRESH_TOKEN_LIFETIME
* Refresh Token may be used to programmatically extend Acces token Lifetime. 
* It is useful for server to server communication. 
*/
define ('REFRESH_TOKEN_LIFETIME', 2592000); // 30 jours

///// OPTIONS /////

/** NEEDS_OPENID_SCOPE      
* If true, all calls to Authorize should have scope openid.
* Setting this to true block Oauth 2.0 native controllers.
*/
define('NEEDS_OPENID_SCOPE', false);     

/**  CHECK_NONCE_AS_UFP
* Set to true to force checking nonce as a User FingerPrint (UFP). False is standard.
* If set to true, OAuthSD is expecting a UFP as nonce. If an ordinary nonce is 
* passed in Authorize request, such a random string, OAuthSD will refuse authorization 
* and will return 'Forbidden'.
* If set to false, OAuthSD follows standard process checking nonce.
* Checking UFP as nonce can only be used within a corporate realm where client applications
* elaborates UFP and send it as nonce. Since UFP check happends right at the beginning 
* of the authorization process, it helps mitigate DDOS and Man-in-the-middle attacks.
* This parameter act only on Authorize. UFP is checked by OAuthSD at different steps. 
* [dnc8]
*/
define('CHECK_NONCE_AS_UFP', false);       //ufp

/** [dnc9] Enable Single Login Identification? 
* Will be effective only if client application was registered with scope 'sli'.
*/
define('ENABLE_SLI', true);

/** [dnc48] Force Single Login Identification? 
* Will enforce SLI even with missing scope 'sli'.
* Has priority over ENABLE_SLI.
* Usefull for testing, avoid in production.
*/
define('FORCE_SLI', true);

/** SLI cookie lifetime.
* SLI cookie is refreshed with this value each time authorize is successful, including SRA. 
* So it is the duration of cookie survival after last successful authorize request.
*/
define('SLI_COOKIE_LIFETIME', 3 * ACCESS_TOKEN_LIFETIME);

/** SRA reparation time.
* When SLI is enabled, and if Access Token has expired, it may be convenient to 
* still allow Silent Re-Authentication (SRA) after a certain time. Thus, end user 
* who has not explicitly logout may come back to work with no need to re-authenticate.
* Note that it has no effect on Access Token, ID Token nor SLI Cookie lifetimes.
*/
define('SRA_REPARATION_TIME', 2 * BASE_LIFETIME);

/**[dnc10]
What to do if prompt not defined (or Null) and user is not connected?
*/
define('REAUTHENTICATE_NO_ROUNDTRIP', true); // True to make server manage re-authentication. false is standard compliant.

/**[dnc10] What to do if prompt == login and user is not connected (Acces Token has expired) ?
* Spec said we should return to client with error. 
* It is safer and better (no roundtrip) to manage the situation at server.
*/
define('LOGIN_NO_ROUNDTRIP', true); // True to make server manage re-authentication. false is standard compliant.

/** In case of repeating login at server, how many attemps are allowed to end-user ? */
define('ALLOWED_ATTEMPTS', 5);

/**[dnc9] If SLI fails, shall we go back to client? */
define('LOGIN_AFTER_SLI_FAILS', true); // authorize controller is responsible for prompting again.

/** Login form option. May be 'ghostkeys', 'password' ... */
define('LOGIN_FORM','ghostkeys'); 

/** Login/password options */
define ('PSWD_LENGTH', 9);      // Password exact length
define ('LOGIN_MIN_LENGTH',6);  // Login min length
define ('FORCE_EMAIL', FALSE);  // If TRUE, login should be an e-mail adress. If FALSE, login may be a pseudo or an email.

/** [dnc14] Check requester IP if registered.
* client_ip, if static, may be defined when registering client application.
* If defined, client_ip is compared to $_SERVER['REMOTE_ADDR'].
* Checking client's IP is not OIDC standard, meanwhile not opposite to it.
* It is strongly advised to enforce this check when enabling SLI.
* This check is not faisible on authorize controller. 
*/
define('CHECK_CLIENT_IP', true);     // Keep this to true, or have a good reason !

/** [dnc22] Check that the requester and redirect URI domains are identicals.
* This check is not feasible on authorize controller. 
*/
define('CHECK_SAME_DOMAINS', false);  //TODO: Bug! Keep to false until further notice!

/** PRTG
* True to allow prtg tracking.
* See /oidc/prtg/oauthsd_prtg.txt  
*/
define ('PRTG', false);

/**
* Allow traking of all requests?
* If true, all requests including skiddie's will be tracked.
* This induce server load and makes it sensitive to deny of service attacks.
* Keep it true, unless induced load proves to make DDOS attacks successful.  
* if false, tracking begins after request validation.
*/
define ('PRTG_TOTAL_REQUESTS', true);

/** [dnc6] Allow  "jku" (JWK Set URL) claim in JWT header.
'ku' claim will pass OIDC_SERVER_URL . '/oidc/jwks.json',
*/ 
define('JKU_IN_JWT_HEADER', true);

/** [dnc6] Allow  "jwk" (JWK URL) claim in JWT header. 
* 'jwk' claim will pass OIDC_SERVER_URL . '/oidc/jwks/' . $payload['kid'] . '.json'
*/ 
define('JWK_IN_JWT_HEADER', false);   // Allow JWK or JKU, not both. In case both are true, JWK has precedence.

/** [dnc24a]
* If prompt is empty (not defined), process consent. 
*/
define('PROMPT_DEFAULT_TO_CONSENT', true);

/** [dnc24b]
* If this is set to True, end-user is not asked again for consent if she has already 
* given her consent for the scope, in the same client application and during the same session.  
*/
define('DONT_PROMPT_FOR_ALREADY_GRANTED_SCOPE', true);

/** [dnc43] Two Factors Authentication.
* TFA may be presented to end user if scope 'tfa' is present in the authorization request.
* If this is set to true, TFA will always be presented to end user after login form.
*/
define('LOGIN_WITH_TFA', false);
/** [dnc43] Designate wich TFA provider will be used.
* Enter name of directory in /oidc/identification/ where are TFA scripts.
*/
define('TFA_PROVIDER', 'gangsta');  // 'gangsta', ... (nothing else for now).
/** Nom du serveur à passer à getQRCodeGoogleUrl. 
* Sera affiché sous le QR-Code.
*/
define('TFA_VISIBLE_APPNAME','OAuthSD Server');

/** [dnc51] Two Factors Authentication with OVH SMS API
* 2FA SMS may be presented to end user if scope 'sms' is present in the authorization request.
* If this is set to true, 2FA SMS will always be presented to end user after login form.
*/
define('LOGIN_WITH_SMS', false);

/** Obtain credential from :
* https://api.ovh.com/createToken/index.cgi?GET=/sms&GET=/sms/*&PUT=/sms/*&DELETE=/sms/*&POST=/sms/
*/
define('OVHSMSAPI_APPLICATIONKEY','Dfh5WUMbon91xtC1');
define('OVHSMSAPI_APPLICATIONSECRET','4P2sPbiQygc6JMtXB1F3phAfs2wmD8ca');
define('OVHSMSAPI_CONSUMER_KEY','YXBD9wjslgIpMx5DKQIgoLvA9L2vqb33');

/** OVH SMS API End point.
*/
define('OVHSMSAPI_ENDPOINT','ovh-eu');


//// NO CHANGE NEEDED ////

// Storage configuration. 
$storage_config = array(
    // tables : traduit les noms de table abstraits en noms de table réels dans la base de données.
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
    'oidc_log_table' => 'spip_oidc_logs', 
    'oidc_stat_table' => 'spip_oidc_stats', 
    'oidc_state_table' => 'spip_oidc_states', 
    'oidc_remote_addr_table' => 'spip_oidc_remote_addr',  
);

// Suported Scopes
$defaultScope = 'basic';
$supportedScopes = array(
    'basic',
    'profile',
    'email',
    'address',
    'phone',
);

// Reserved scopes
$reservedscopes = array(
'openid', 
'offline_access', 
'sli',       //[dnc9] est-ce utile ici ?
'kerberos',  //[dnc12]
'privileges', //[dnc19]
'tfa' //[dnc43b]
); //[dnc31]
$supportedScopes = array_merge($supportedScopes,$reservedscopes);
