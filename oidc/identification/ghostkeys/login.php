<?php 
/* Page de login simple (sans grant) pour authorize.php
Utilise Ghost Keys

Auteur : Bertrand Degoy 
Copyright (c) 2016-2019 DnC  
Tous droits réservés
*/

if ( !defined('__AUTHORIZE') ) die();

// Prepare data
$thetexte1 = ( empty($data['texte1'])? '' : htmlspecialchars($data['texte1']) );   //[dnc16]
$thetexte2 = ( empty($data['texte2'])? '' : htmlspecialchars($data['texte2']) );   //[dnc16] 

$form_id = 'login';  

$explain = '';
if ( !empty($theurl) AND !empty($thesite) ) {
    $explain = '<span class="explain">' . _("More about the application") . ' ' . $theclient . _(' of ') . $thename. ' : <a href="' . $theurl . '">' . $thesite . '</a></span>';
}    

if ( FORCE_EMAIL ) {
    $login_label = _('E-mail');
} else {
    $login_label = _('E-mail or login');
}

// Prepare password encoding
$tab = array(1=>'A', 2=>'B', 3=>'C', 4=>'D', 5=>'E', 6=>'F', 7=>'G', 8=>'H', 9=>'I',
    10=>'J', 11=>'K', 12=>'L', 13=>'M', 14=>'N', 15=>'O', 16=>'P');   // index => char  
shuffle($tab);
$antitab = array_flip($tab);   // char => index

$_SESSION["antitab"] = $antitab; 

if ( isset($_GET['error']) AND (!empty($error = $_GET['error'])) ) {
    unset($_GET['error']);
}

if ( !empty($thetexte1) ) {   //[dnc16]
    $texte1 = $thetexte1;
} else {
    $texte1 = _('Please log in ') . $theclient . _(' of ') . $thename;
}

if ( !empty($thetexte2) ) {   //[dnc16]
    $texte2 = $thetexte2;
} else {
    $texte2 = sprintf(_('To protect your data, %s makes use of <a href="https://oa.dnc.global">OAuth Server by DnC</a>.'), $thename);
}

if ( empty($lang) ) $lang = 'fr';

// Styles

$thecss = ( empty($data['css'])? '' : htmlspecialchars($data['css']) );

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
    h3.login, .ghostkeys {
    text-align: center;
    }
    .ghostkeys {
    height: 120px
    }
    #champ_login {
    width: 240px;
    }
    div.password {
    margin-top: 10px;
    margin-bottom: 10px;
    }
    #champ_password {
    background-color: #18bc9c;
    border: none;}
    .nologin {
    clear: both;
    margin-top : 9px;
    }
    #btn_submit {float: right;}
    #reset, #submit {
    margin:15px;
    background-color: #999;
    color: white;
    box-shadow:1px 1px 1px #666;
    cursor:pointer;
    }
    #reset {
    width:70px;
    }
    #submit {
    width:100px;
    font-size: 1.1em;
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
        '<script>
        <!--
        function bloc ( lettre ) {
        if ( document.login.password.value.length < ' . PSWD_LENGTH . ' ) document.login.password.value = document.login.password.value + lettre;
        }
        -->
        </script>
        <div id="container">

        <div id="oauth" class="login">
        <h3 class="oauth login">'  
        . $texte1 .  //[dnc16]
        '</h3> 

        <form id="'. $form_id .'" name="'. $form_id .'" method="post" action ="/oidc/identification/ghostkeys/login_return.php">
        <input type="hidden" name="return_from" value="'. $form_id .'">

        <input type="hidden" name="response_type" value="' . $response_type . '">
        <input type="hidden" name="client_id" value="' . $client_id . '">
        <input type="hidden" name="scope" value="' . $scope . '">
        <input type="hidden" name="state" value="' . $state . '">

        <br />
    '); 

    if ( !empty($error) ) {
        $errormsg = '<div class="error">' . $error . '</div><br/><br/>';
        echo $errormsg;
    }

    if ( !empty($request_uri = @$_GET['request_uri']) ) {   
    echo ('
        <input type="hidden" name="request_uri" value="' . $request_uri . '">
    ');     //*****   //???
}

if ( !empty($sub) ) {
    // login (username) pre-defined
    echo('
        <input type="hidden" name="login" value="' . $sub . '">
        <div class="editer login">' .
        _('Please enter password for : ') . $sub .'
        <br /><br />
        </div>
    ');
} else {
    // end-user is asked for login (username)
    echo('
        <div class="editer login">
        <label for="champ_login">' . $login_label . ' : </label>
        <input class="champ_login" id="champ_login" type="text" name="login"' . ( !empty($sub) ? ' value="' . $sub . '"' : '' ) .' />
        </div>
    '); 
}

echo(' 
    <div class="editer password">
    <label for="champ_password">' . _('Password') . ':</label>
    <input id="champ_password" type="password" name="password" readonly="readonly" />

    </div>

    <div class="editer ghostkeys">
    <img src="/oidc/identification/ghostkeys/gkeys.php?state=' . $state . '" usemap="#map" border="0" />
    <map name="map" id="map">
    <area shape="rect" coords="0,0,30,30" href="#" onclick="javascript :bloc(\'' . $tab[0] . '\')" />
    <area shape="rect" coords="0,30,30,60" href="#" onclick="javascript :bloc(\'' . $tab[1] . '\')" />
    <area shape="rect" coords="0,60,30,90" href="#" onclick="javascript :bloc(\'' . $tab[2] . '\')" />
    <area shape="rect" coords="0,90,30,120" href="#" onclick="javascript :bloc(\'' . $tab[3] . '\')" />
    <area shape="rect" coords="30,0,60,30" href="#" onclick="javascript :bloc(\'' . $tab[4] . '\')" />
    <area shape="rect" coords="30,30,60,60" href="#" onclick="javascript :bloc(\'' . $tab[5] . '\')" />
    <area shape="rect" coords="30,60,60,90" href="#" onclick="javascript :bloc(\'' . $tab[6] . '\')" />
    <area shape="rect" coords="30,90,60,120" href="#" onclick="javascript :bloc(\'' . $tab[7] . '\')" />
    <area shape="rect" coords="60,0,90,30" href="#" onclick="javascript :bloc(\'' . $tab[8] . '\')" />
    <area shape="rect" coords="60,30,90,60" href="#" onclick="javascript :bloc(\'' . $tab[9] . '\')" />
    <area shape="rect" coords="60,60,90,90" href="#" onclick="javascript :bloc(\'' . $tab[10] . '\')" />
    <area shape="rect" coords="60,90,90,120" href="#" onclick="javascript :bloc(\'' . $tab[11] . '\')" />
    <area shape="rect" coords="90,0,120,30" href="#" onclick="javascript :bloc(\'' . $tab[12] . '\')" />
    <area shape="rect" coords="90,30,120,60" href="#" onclick="javascript :bloc(\'' . $tab[13] . '\')" />
    <area shape="rect" coords="90,60,120,90" href="#" onclick="javascript :bloc(\'' . $tab[14] . '\')" />
    <area shape="rect" coords="90,90,120,120" href="#" onclick="javascript :bloc(\'' . $tab[15] . '\')" />
    </map>
    </div>
    <div id="btn_reset" class="bouton"><input type="reset" name="reset" id="reset" value="' . _('Reset') .'" /></div>
    <div id="btn_submit" class="bouton"><input type="submit" id="submit" value="' . _('Submit') .'" /></div>

    </form>

    <br />
    <div class="nologin">' .
    _('You do not have identifiers ?') . ' <a href="/?page=editer_user_ext&id_client=' . $id_client . '&client=' . $theclient . '&nom=' . $thename . '&pswdl=' . PSWD_LENGTH . '&lang=' . $lang .'">' . _('Please sign on') . '</a>.     
    </div>

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
    '
); 