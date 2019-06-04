<?php
/*
server.php

Bootstrap for OAuth2 Server with OpenID Connect
Create and configure our OAuth2 Server object for OpenID Connect. 
This will be used by all the endpoints in our application.

See : 
http://bshaffer.github.io/oauth2-server-php-docs/cookbook/
https://github.com/bshaffer/oauth2-demo-php/blob/master/src/OAuth2Demo/Server/Server.php

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

if ( !defined('PRIVATE') ) die;      

// Autoloading by Composer
require_once __DIR__ . '/../../../../../autoload.php';
OAuth2\Autoloader::register();

//[dnc35] i18n define global system locale
require_once __DIR__ . '/../../../../../../locale/i18n_setup.php';

// Overrides
require_once __DIR__ . '/../../../../../../commons/configure_oidc.php';

// Initialize database storage
$storage = new OAuth2\Storage\Pdo( $connection, $storage_config );

// Create array of supported grant types
$grantTypes = array(
    'authorization_code' => new OAuth2\OpenID\GrantType\AuthorizationCode($storage),
    'user_credentials'   => new OAuth2\GrantType\UserCredentials($storage),
    'client_credentials'   => new OAuth2\GrantType\ClientCredentials($storage),
    'refresh_token'      => new OAuth2\GrantType\RefreshToken($storage, array(
        'always_issue_new_refresh_token' => true,
    )),
);

// Server configuration, including OpenID Connect, Implicit Flow et JWT Bearer
$server_config = array(
    'enforce_state' => true,    // Always true with OAuthSD.
    'allow_implicit' => ALLOW_IMPLICIT,
    'use_openid_connect' => true,
    'issuer' => OIDC_SERVER_URL,  //[dnc45a]
    //'use_jwt_access_tokens' => true,    // Attention : nécessite d'augmenter le champ access_token.    TODO: ???
    // lifetimes
    'id_lifetime'              => ID_TOKEN_LIFETIME,
    'access_lifetime'          => ACCESS_TOKEN_LIFETIME,
    'always_issue_new_refresh_token' => true,
    'refresh_token_lifetime'         => REFRESH_TOKEN_LIFETIME, 
);

// Instantiate the OAuth server.
$server = new OAuth2\Server($storage, $server_config, $grantTypes);

// Add JWT Bearer grant type          
$audience = OIDC_SERVER_URL;   // specify the audience 
$grantType = new OAuth2\GrantType\JwtBearer($storage, $audience);
// add the grant type to the OAuth server
$server->addGrantType($grantType); 

// Initialize Scopes utility.
$memory = new OAuth2\Storage\Memory(array(
    'default_scope' => $defaultScope,
    'supported_scopes' => $supportedScopes
));
$scopeUtil = new OAuth2\Scope($memory);
// Set scopes.
$server->setScopeUtil($scopeUtil);

// Verify timezone is UTC
$date = new DateTime();
$timeZone = $date->getTimezone();
if ( $timeZone->getName() !== 'UTC' ) die ("Fatal error : Server time zone should be UTC");
