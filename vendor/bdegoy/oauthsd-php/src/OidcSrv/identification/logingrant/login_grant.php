<?php 
/* 
login_grant.php
Page de login + grant pour authorize.php

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

if ( !defined('__AUTHORIZE') ) die();

if ( isset($_GET['error']) AND (!empty($error = $_GET['error'])) ) {
    unset($_GET['error']);
}

echo ('
    <style>
    body {
    font-family: "Century Gothic", Helvetica, Arial, sans-serif !important;
    }
    #container {
    margin-left: auto;
    margin-right: auto;
    width:460px;
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
    #btn_reset, #btn_submit {float: right;}
    #reset, #submit {
    margin-left:5px;
    background-color: #999;
    color: white;
    box-shadow:1px 1px 1px #666;
    cursor:pointer;
    }
    #reset {
    width:100px;
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

    <script>
    <!--
    function bloc ( lettre ) {
    if ( document.login.password.value.length < ' . PSWD_LENGTH . ' ) document.login.password.value = document.login.password.value + lettre;
    }
    -->
    </script>
    <div id="container">

    <div id="oauth" class="login">

    <h3 class="oauth login">L\'application ' . $theclient . ' de ' . $thename . ' demande votre autorisation</h3>

    <div id="scopes">' . $scopes_html . '</div>

    <form id="login" name="login" method="post">

    <input type="hidden" name="state" value="' . $_GET['state'] . '">
    <input type="hidden" name="client_id" value="' . $_GET['client_id'] . '">

    <br />
');

if ( !empty($error) ) {
    $errormsg = '<div class="error">' . $error . '</div><br/><br/>';
    echo $errormsg;
}

if ( !empty($sub) ) {
    // login (username) pre-defined
    echo('
        <input type="hidden" name="login" value="' . $sub . '">
        <div class="editer login">
        Please enter password for : ' . $sub .'
        <br /><br />
        </div>
    ');
} else {
    // end-user is asked for login (username)
    echo('
        <div class="editer login">
        <label for="champ_login">' . $login_label . ' : </label>
        <input id="champ_login" type="text" name="login"' . ( !empty($sub) ? ' value="' . $sub . '"' : '' ) .' />
        </div>
    '); 
}

echo('
    <div class="editer password">
    <label for="champ_password">Password:</label>
    <input id="champ_password" type="password" name="password" readonly="readonly" />
    <div id="btn_reset" class="bouton"><input type="reset" name="reset" id="reset" value=" Reset " /></div>
    </div>

    <div class="editer ghostkeys">
    <img src="oidc/identification/password/ghostkeys/gkeys.php?scope=' . $_GET['scope'] . '&state=' . $_GET['state'] . '" usemap="#map" border="0" />
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

    <div id="btn_submit" class="bouton"><input type="submit" id="submit" value="Submit" /></div>

    </form>

    <br />
    <div class="nologin">
    Vous n\'avez pas d\'identifiants ? <a href="/article18&id_client=' . $id_client . '&client=' . $theclient . '&nom=' . $thename . '&pswdl=' . PSWD_LENGTH . '">Inscrivez-vous ici</a>.     
    </div>

    </div>
    <div id="bottom">
    <p>
    ' . $explain . '
    </p>   
    <p>
    Pour votre protection, '  . $thename . ' utilise <a href="http://oa.dnc.global">OAuth Server by DnC</a> : les mots de passe ne peuvent être interceptés au moment de leur saisie, ne circulent pas sur Internet, ne sont enregistrés nulle part !
    </p>
    </div>
    </div> 
    '
); 