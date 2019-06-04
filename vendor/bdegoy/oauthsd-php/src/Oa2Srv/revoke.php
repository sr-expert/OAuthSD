<?php
/*
revoke.php

Revoke Controller for OAuth2 Server
Implementation follows the Draft RFC7009 OAuth 2.0 Token Revocation.

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/
  
// include our OAuth2 Server object
define('PRIVATE', true);
require_once __DIR__ . '/includes/server.php';        

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// Handle a token revocation for an OAuth2.0 Token and send the response to the client
$server->handleRevokeRequest( $request, $response );

