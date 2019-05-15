<?php
/*
userinfo.php
UserInfo Controller for OpenId Connect protocol with OAuth2 Server

2016/12/29 

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
require __DIR__.'/includes/server.php';

$request = OAuth2\Request::createFromGlobals();

$server->handleUserInfoRequest($request)->send();
  