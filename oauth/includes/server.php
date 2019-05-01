<?php
/*
server.php

Bootstrap for OAuth2 Server
Create and configure our OAuth2 Server object. This will be used by all the endpoints in our application.

See : http://bshaffer.github.io/oauth2-server-php-docs/cookbook/

Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
Tous droits réservés
 
*/


if ( !defined('PRIVATE') ) die;      

// Autoloading by Composer
require __DIR__ . '/../../vendor/autoload.php';
OAuth2\Autoloader::register(); 

// Server configuration
require_once __DIR__ . '/../includes/configure.php';

if (!defined('PARAM_TOKEN_NAME') ) define('PARAM_TOKEN_NAME', 'access_token');

$storage = new OAuth2\Storage\Pdo( $connection, $storage_config );

// Initialise the OAuth2 server class

// Set Server configuration, including OpenID Connect
$config = array(
    'enforce_state' => true,
    'allow_implicit' => true,
    'use_jwt_access_tokens' => true,
);

// Create server
$server = new OAuth2\Server($storage, $config);

// Configure available scopes
$defaultScope = 'basic';
$supportedScopes = array(
  'basic',
  'profile',
  'email',
  'address',
  'phone', 
);
$memory = new OAuth2\Storage\Memory(array(
  'default_scope' => $defaultScope,
  'supported_scopes' => $supportedScopes
));
$scopeUtil = new OAuth2\Scope($memory);
// Set scopes
$server->setScopeUtil($scopeUtil);


// Add the "Client Credentials" grant type (it is the simplest of the grant types)
$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));

// Add the "Authorization Code" grant type (this is where the oauth magic happens)
$server->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));

// Add the "User Credentials" grant type
$server->addGrantType(new OAuth2\GrantType\UserCredentials($storage));

// Refresh Token grant type
$server->addGrantType(new OAuth2\GrantType\RefreshToken($storage));

// JWT Bearer grant type
// specify the audience 
$audience = OIDC_SERVER_URL;
$grantType = new OAuth2\GrantType\JwtBearer($storage, $audience);
// add the grant type to your OAuth server
$server->addGrantType($grantType);
