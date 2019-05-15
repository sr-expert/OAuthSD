<?php
/* 
grant.php
Page d'approbation pour authorize.php
Ce code est inclus par Authorize.
Le formulaire rappellera Authorize.

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/                   

if ( !defined('__AUTHORIZE') ) die();

// Prepare data
$thecss = ( empty($data['css'])? '' : htmlspecialchars($data['css']) );
$thetexte1 = ( empty($data['texte1'])? '' : htmlspecialchars($data['texte1']) );   //[dnc16]
$thetexte2 = ( empty($data['texte2'])? '' : htmlspecialchars($data['texte2']) );   //[dnc16] 

$form_id = 'grant';

// Build html scopes list
$count = 0;
$scopes_html = '<ul>';
foreach ( $scopes_to_grant as $thescope ) {
    $stmt = $cnx->prepare("SELECT * FROM spip_scopes WHERE scope=:thescope");
    $stmt->execute(compact('thescope'));
    $data = $stmt->fetch(\PDO::FETCH_ASSOC);
    $thedefinition = $data['scope_description'];  
    $scopes_html .= '<li>';
    $scopes_html .= '<span class="scopetitle">' . $thescope . ' : </span>&nbsp;<span class="scopedefinition">' . $thedefinition . '</span>';
    $scopes_html .= '</li>';
    $count += 1;
}
$scopes_html .= '</ul>';

// Prompt user for consent
if ( $count ) {
    $scopes_html = _('Requested scopes') . ' : <br />' . $scopes_html;
} else {
    $scopes_html = '';
}

$explain = '';
if ( !empty($theurl) AND !empty($thesite) ) {
    $explain = '<span class="explain">' . _("More about the application") . ' ' . $theclient . _(' of ') . $thename. ' : <a href="' . $theurl . '">' . $thesite . '</a></span>';
}  

if ( !empty($thetexte1) ) {   //[dnc16]
    $texte1 = $thetexte1;
} else {
    $texte1 = sprintf(_('The application %s of %s asks for your consent.'), $theclient, $thename);
}

if ( !empty($thetexte2) ) {   //[dnc16]
    $texte2 = $thetexte2;
} else {
    $texte2 = sprintf(_('To protect your data, %s makes use of <a href="https://oa.dnc.global">OAuth Server by DnC</a>.'), $thename);
}

if ( empty($lang) ) $lang = 'fr';

if ( file_exists('my.css') ) { //[dnc37]
    $style = '<link rel="stylesheet" type="text/css" href="my.css">" .';
    if ( !empty($thecss) ) $style .= $thecss;
} else {
    $style = '
    <style>
    body {
    font-family: "Century Gothic", Helvetica, Arial, sans-serif !important;
    }
    #container {
    margin-left: auto;
    margin-right: auto;
    max-width: 360px;
    padding: 1.5em;
    }
    #oauth {
    color: white;
    padding: 1.5em;
    border-width: 3px;
    border-color: #15a589;
    background-color: #18bc9c;
    }
    #oauth a {
    color : white;
    }
    h3.login {
    text-align: center;
    }
    #btn_submit {float: right;}
    #submit {
    margin:15px;
    background-color: #999;
    color: white;
    box-shadow:1px 1px 1px #666;
    cursor:pointer;
    }
    #bottom {
    color : grey;
    font-size: .8em;
    }
    #bottom a {
    color : grey;
    }
    .error {
    text_align: center;
    color : red;
    background-color : white;
    padding: 6px;
    }

    ' . $thecss . '
    </style>
    ';     
}

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
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta name="robots" content="noindex, nofollow"> 
    ' . $style .   //[dnc37]
    '</head>
    <body>
');

// Display error if any
if ( isset($_GET['error']) AND (!empty($error = $_GET['error'])) ) {
    unset($_GET['error']);
}
if ( !empty($error) ) {
    $errormsg = '<div class="error">' . $error . '</div><br/><br/>';
    echo $errormsg;
}

// Display Grant Form
echo('   
    <div id="container">

    <div id="oauth" class="grant">

    <h3 class="oauth grant">'  
    . $texte1 .  //[dnc16]
    '</h3> 

    <div id="scopes">' . $scopes_html . '</div>

    <form id="'. $form_id .'" name="'. $form_id .'" method="post">
    <input type="hidden" name="return_from" value="'. $form_id .'">

    <input type="hidden" name="state" value="' . $_GET['state'] . '">
    <input type="hidden" name="client_id" value="' . $_GET['client_id'] . '">
    <input type="hidden" name="just_granted_scopes" value="' . $being_granted_scopes . '">
    <input type="hidden" name="redirect_uri" value="' . @$_GET['redirect_uri'] . '">

    <br />
    <input type="checkbox" id="grant" name="grant"><label for="checkbox">' . _('I agree') . '</label>

    <div id="btn_submit" class="bouton"><button type="submit" >' . _('Submit') . '</button></div>

    </form>

    <br />

    </div>
    <div id="bottom">
    <p>
    ' . $explain . '
    </p>   
    <p>'
    . $texte2 . //[dnc16]
    '</p>
    </div>
    </div>
    </body>
    </html> 
    '
); 
