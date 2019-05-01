<?php
/*
keys.php

Keys Controller for OAuth2 Server
Fourniture des clés publiques des client.

Voir : https://tools.ietf.org/html/draft-ietf-jose-json-web-key-41

2018/11/04
Author : B.Degoy DnC
Copyright (c) 2017 DnC 
Tous droits réservés
 
*/

ini_set('display_errors', 0);

// include our OAuth2 Server object
define('PRIVATE', true);
require_once __DIR__.'/includes/server.php';
require_once __DIR__.'/includes/buildkeys.php';  

$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

$aresult = buildkeys( $cnx );

// Send as JSON array
header("HTTP/1.0 200 OK");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header('Content-Type: application/json');
echo json_encode( $aresult );
  