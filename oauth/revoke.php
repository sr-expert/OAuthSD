<?php
/*
revoke.php

Revoke Controller for OAuth2 Server
Implementation follows the Draft RFC7009 OAuth 2.0 Token Revocation.
 
*/
  
// include our OAuth2 Server object
define('PRIVATE', true);
require_once __DIR__.'/includes/server.php';        

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

// Handle a token revocation for an OAuth2.0 Token and send the response to the client
$server->handleRevokeRequest( $request, $response );
  
?>
