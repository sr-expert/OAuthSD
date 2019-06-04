<?php
/*
authorize.php

Authorize Controller for OAuth2 OIDC Server

See : http://bshaffer.github.io/oauth2-server-php-docs/cookbook/

Test: http://oa.dnc.global/oidc/authorize.php?response_type=code&scope=openid&client_id=testclient&state=xyz

[dnc9] 2018/12/02 - version prenant en charge Single Login Identification (SLI)

OauthSD project
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
Licence GPLv3
*/

// Count requests for PRTG 
if( PRTG ) {  
    require_once __DIR__.'/../oidc/prtg/prtg_utils.php';
    if( PRTG_TOTAL_REQUESTS ) {
        oidc_increment('total_requests');   
    }
} 

include_once __DIR__. '/../commons/configure.php';
include_once OIDCSRV_ROOT_PATH . 'authorize.php';
