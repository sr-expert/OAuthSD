<?php
/*
userinfo.php

UserInfo Controller for OpenId Connect protocol with OAuth2 Server

2016/12/29 
Author : B.Degoy DnC
Copyright (c) 2017 DnC 
licence GPL V3
  
*/

// include our OAuth2 Server object
define('PRIVATE', true);
require __DIR__.'/includes/server.php';

$request = OAuth2\Request::createFromGlobals();

$server->handleUserInfoRequest($request)->send();
  