<?php
/**
* login.php
2FA with Google Authenticator
[dnc43]

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2019 DnC  
All rights reserved

/**
* We use PHPGangsta https://github.com/PHPGangsta/GoogleAuthenticator
* Inspired by : 
* http://hazardedit.com/2017/11/02/implementing-totp-google-authenticator-php/
* https://medium.com/@richb_/easy-two-factor-authentication-2fa-with-google-authenticator-php-108388a1ea23
*/

if ( !defined('__AUTHORIZE') ) die();

// Autoloading by Composer
require_once '../vendor/autoload.php';

// Prepare cosmetic data
$thecss = ( empty($data['css'])? '' : htmlspecialchars($data['css']) );
$thetexte1 = ( empty($data['texte1'])? '' : htmlspecialchars($data['texte1']) );   //[dnc16]
$thetexte2 = ( empty($data['texte2'])? '' : htmlspecialchars($data['texte2']) );   //[dnc16] 

$form_id = '2fa';  

if ( isset($_GET['error']) AND (!empty($error = $_GET['error'])) ) {
    unset($_GET['error']);
}

$texte1 = _('Please do 2fa') ;

if ( empty($lang) ) $lang = 'fr';

if ( file_exists('my.css') ) { //[dnc37]
    $style = '<link rel="stylesheet" type="text/css" href="my.css">" .';
    if ( !empty($thecss) ) $style .= $thecss;
} else {
    $style = '
    <style>
    body {font-family: "Century Gothic", Helvetica, Arial, sans-serif !important;}
    #container {margin-left: auto; margin-right: auto; max-width: 360px; padding: 1.5em; border: solid 1px grey; border-radius: 10px; box-shadow: 10px 5px 5px silver;}
    .head-title {text-align: center; background-color: gray; color: white; padding: 0.5em;}
    .error {text_align: center; color : red; background-color : white; padding: 6px;}
    .bouton {margin:15px; background-color: #999; color: white; box-shadow:1px 1px 1px #666; cursor:pointer;}
    .tfacode {text-align: center;}
    #champ_tfacode {width: 64px; margin-top: 6px; padding: 3px; box-shadow: 3px 3px 3px silver;}
    #btn_submit {float: right;}
    #submit {width:100px; font-size: 1.1em;}
    #nocode {clear: both; margin-bottom: 0.5em;}
    #qrcode {text-align: center;}
    #bottom {color : grey; font-size: .8em;}
    #bottom a {color : grey;}
    
    ' . $thecss . '
    </style>
    ';     
}

$ga = new PHPGangsta_GoogleAuthenticator();

if ( !is_null($ga) ) {
    // Get secret Key
    $secretfile = './includes/secret.txt';
    $secret = @file_get_contents($secretfile);
    if ( $secret === false ) {
        $secret = $ga->createSecret();
        file_put_contents($secretfile, $secret);
    }   
    $_SESSION["tfasecret"] = $secret;
    
    // Website Title refers to OIDC Server
    $websiteTitle = TFA_VISIBLE_APPNAME;
    // Generate QR Code  
    $qrCodeUrl = $ga->getQRCodeGoogleUrl($websiteTitle, $secret);

    echo ('
        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'. $lang . '" lang="'. $lang . '" dir="ltr">
        <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . $texte1 . '</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
        <meta http-equiv="Lang" content="'. $lang .' ">
        <meta http-equiv="Expires" content="Fri, Jan 01 1900 00:00:00 GMT">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Cache-Control" content="no-cache">
        <meta name="robots" content="noindex, nofollow"> 
        ' . $style .   //[dnc37]    
        '<div id="container" class="2fa gangsta">
            <div id="2fa" class="login">
                <h3 class="head-title">' . $texte1 . '</h3> 
    ');

    if ( !empty($error) ) {
        $errormsg = '<div class="error">' . $error . '</div>';
        echo $errormsg;
    }

    echo ('
                <form id="'. $form_id .'" name="'. $form_id .'" method="post" action ="/oidc/identification/gangsta/login_return.php">
                    <input type="hidden" name="return_from" value="'. $form_id .'">
                    <input type="hidden" name="response_type" value="' . $response_type . '">
                    <input type="hidden" name="client_id" value="' . $client_id . '">
                    <input type="hidden" name="scope" value="' . $scope . '">
                    <input type="hidden" name="state" value="' . $state . '">
                    <input type="hidden" name="lang" value="' . $lang . '">
    ');
    if ( !empty($request_uri = @$_GET['request_uri']) ) {   //???
        echo ('
                    <input type="hidden" name="request_uri" value="' . $request_uri . '">
        ');
    }
    if ( !empty($redirect_uri = @$_GET['redirect_uri']) ) {   
        echo ('
                    <input type="hidden" name="redirect_uri" value="' . $redirect_uri . '">
        ');
    }
    echo (' 
                    <div class="editer tfacode">
                        <label for="champ_tfacode">' . _('tfacode') . ' ' . $websiteTitle . ' : </label>
                        <input class="champ_tfacode" id="champ_tfacode" type="text" name="tfacode"/>
                    </div>
                    <div id="btn_submit" class="bouton"><input type="submit" id="submit" value="' . _('Submit') .'" /></div>
                    
                </form>

                <div id="nocode">' . 
                    _('You do not have code') 
                . ': </div>
                <div id="qrcode"><img src="' . $qrCodeUrl . '" /></div>
                
                <div class="bottom">' .
                    _('You do not know 2FAGA') . ': <a href="/?page=about_google_authenticator&id_client=' . $client_id . '&lang=' . $lang . '">' . _('Learn about 2FAGA') . '</a>
                . </div>
            </div>
            
        </div> 
        '
    ); 


} else {
    // couldn't continue
    //TODO

} 

