<?php

/* 
grant.php
Page d'approbation pour authorize.php

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
');

if ( !empty($error) ) {
    $errormsg = '<div class="error">' . $error . '</div><br/><br/>';
    echo $errormsg;
}

echo('   
    <div id="container">

    <div id="oauth" class="grant">

    <h3 class="oauth login">L\'application ' . $theclient . ' de ' . $thename . ' demande votre autorisation</h3>

    <div id="scopes">' . $scopes_html . '</div>

    <form id="grant" name="grant" method="post">

    <input type="hidden" name="state" value="' . $_GET['state'] . '">
    <input type="hidden" name="client_id" value="' . $_GET['client_id'] . '">
    <input type="hidden" name="grant" value="1">

    <br />

    <div id="btn_cancel" class="bouton"><button type="button" >Cancel</button></div>

    <div id="btn_submit" class="bouton"><button type="submit" >I agree</button></div>


    </form>

    <br />

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

