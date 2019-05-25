<?php
/* commons/configure.php
Configuration values common to oauth and oidc servers.

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2019 DnC  
All rights reserved
*/

// Error reporting (this is a demo, after all!)
ini_set('display_errors',1);error_reporting(E_ALL);
//ini_set('display_errors',0);error_reporting(0);
define('DEBUG',true); // DEBUG

define('OIDC_SERVER_DOMAIN', 'oa.dnc.global');     // oa.dnc.global, oidc.intergros.com
define('OIDC_SERVER_URL', 'https://' . OIDC_SERVER_DOMAIN );     // https://oa.dnc.global, https://oidc.intergros.com  //[dnc45a]


//[dnc9] Client ID du serveur vu comme une application cliente. 
define('SERVER_CLIENT_ID', 'oadncserver');

//[dnc9] session storage on server 
define('SLI_SESSION_DIR', '/home/oadnc/sessions_oauthsd'); // best place is above web root

/** Database
* $dsn is the Data Source Name for your database, for exmaple "mysql:dbname=my_oauth2_db;host=localhost"
*/
//* MySQL
$connection = array(
'dsn' => 'mysql:dbname=dnc_oauth2;host=localhost',   
'username' => 'dnc_user_oa', 
'password' => 'oaY10CWrB9!'
); //*/
/* PostgreSQL
$connection = array(
    'dsn' => 'pgsql:dbname=oidcdnc_server;host=localhost;port=5432', 
    'username' => 'oidcdnc_admin', 
    'password' => 'oidcY10CWrB9!'
); //*/  

