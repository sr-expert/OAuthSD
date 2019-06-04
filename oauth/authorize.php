<?php
/*
authorize.php avec GhostKeys

Authorize Controller for OAuth2 Server

See : http://bshaffer.github.io/oauth2-server-php-docs/cookbook/

Test : oa.dnc.global/oauth/authorize.php?response_type=code&client_id=testclient&state=xyz

OauthSD project
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
Licence GPLv3
*/

include_once __DIR__. '/../commons/configure.php';
include_once OAUTHSRV_ROOT_PATH . 'authorize.php';
