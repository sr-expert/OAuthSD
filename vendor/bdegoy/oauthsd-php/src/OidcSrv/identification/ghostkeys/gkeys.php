<?php
/*
Build GhostKeys for OAuthSD
oidc verion with SLI session

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

//DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

define('PRIVATE', true);
include_once('../../includes/server.php');
include_once('../../includes/utils.php');

// Set headers
header ("Content-type: image/png");
// Do'nt cache. 
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies. 

// Set session in order to pass tableau back to caller. 
$state = $_GET['state'];      
$void = that_session_start('oauthsd', $state, SLI_SESSION_DIR);  //[dnc34]

// check state
if ( $state !== decrypt(@$_SESSION['state']) ) exit(); // Something bad happened.     //[dnc21][dnc33] 

// Generate random grid
$tableau = array (0,1,2,3,4,5,6,7,8,9,'','','','','','');
shuffle($tableau);
$_SESSION["tableau"] = $tableau;

// Make grid. 
$image = imagecreate(120,120);
$background_color = imagecolorallocate($image, 255,255,255);
$noir = imagecolorallocate($image, 40, 40, 40);
$argent = imagecolorallocate($image, 200, 200, 200);

putenv('GDFONTPATH=' . realpath('.'));
$font = "arial";

imagettftext($image, 16, 0, 9, 22, $noir, $font, $tableau[0]);
imagettftext($image, 16, 0, 9, 52, $noir, $font, $tableau[1]);
imagettftext($image, 16, 0, 9, 82, $noir, $font, $tableau[2]);
imagettftext($image, 16, 0, 9, 112, $noir, $font, $tableau[3]);
imagettftext($image, 16, 0, 39, 22, $noir, $font, $tableau[4]);
imagettftext($image, 16, 0, 39, 52, $noir, $font, $tableau[5]);
imagettftext($image, 16, 0, 39, 82, $noir, $font, $tableau[6]);
imagettftext($image, 16, 0, 39, 112, $noir, $font, $tableau[7]);
imagettftext($image, 16, 0, 69, 22, $noir, $font, $tableau[8]);
imagettftext($image, 16, 0, 69, 52, $noir, $font, $tableau[9]);
imagettftext($image, 16, 0, 69, 82, $noir, $font, $tableau[10]);
imagettftext($image, 16, 0, 69, 112, $noir, $font, $tableau[11]);
imagettftext($image, 16, 0, 99, 22, $noir, $font, $tableau[12]);
imagettftext($image, 16, 0, 99, 52, $noir, $font, $tableau[13]);
imagettftext($image, 16, 0, 99, 82, $noir, $font, $tableau[14]);
imagettftext($image, 16, 0, 99, 112, $noir, $font, $tableau[15]);
imageline ($image, 0, 30, 120, 30, $argent);
imageline ($image, 0, 60, 120, 60, $argent);
imageline ($image, 0, 90, 120, 90, $argent);
imageline ($image, 30, 0, 30, 120, $argent);
imageline ($image, 60, 0, 60, 120, $argent);
imageline ($image, 90, 0, 90, 120, $argent);

imagepng($image);
imagedestroy($image);