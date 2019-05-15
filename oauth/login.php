<?php 
/* 
login.php
Page de login pour authorize.php

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

if ( !defined('__AUTHORIZE') ) die();

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
    background-color: #18bc9c !important;
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

    <br />
    Identifiez-vous : <br />
    <br />
    <div class="editer login">
    <label for="champ_login">' . $login_label . ' : </label>
    <input id="champ_login" type="text" name="login" />
    </div>

    <div class="editer password">
    <label for="champ_password">Password:</label>
    <input id="champ_password" type="password" name="password" readonly="readonly" />
    <div id="btn_reset" class="bouton"><input type="reset" name="reset" id="reset" value=" Reset " /></div>
    </div>

    <div class="editer ghostkeys">
    <img src="../ghostkeys/gkeys.php" usemap="#map" border="0" />
    <map name="map" id="map">
    <area shape="rect" coords="0,0,30,30" href="#" onclick="javascript : bloc (\'F\')" />
    <area shape="rect" coords="0,30,30,60" href="#" onclick="javascript : bloc (\'O\')" />
    <area shape="rect" coords="0,60,30,90" href="#" onclick="javascript : bloc (\'I\')" />
    <area shape="rect" coords="0,90,30,120" href="#" onclick="javascript : bloc (\'D\')" />
    <area shape="rect" coords="30,0,60,30" href="#" onclick="javascript : bloc (\'E\')" />
    <area shape="rect" coords="30,30,60,60" href="#" onclick="javascript : bloc (\'A\')" />
    <area shape="rect" coords="30,60,60,90" href="#" onclick="javascript : bloc (\'G\')" />
    <area shape="rect" coords="30,90,60,120" href="#" onclick="javascript : bloc (\'H\')" />
    <area shape="rect" coords="60,0,90,30" href="#" onclick="javascript : bloc (\'C\')" />
    <area shape="rect" coords="60,30,90,60" href="#" onclick="javascript : bloc (\'M\')" />
    <area shape="rect" coords="60,60,90,90" href="#" onclick="javascript : bloc (\'P\')" />
    <area shape="rect" coords="60,90,90,120" href="#" onclick="javascript : bloc (\'L\')" />
    <area shape="rect" coords="90,0,120,30" href="#" onclick="javascript : bloc (\'J\')" />
    <area shape="rect" coords="90,30,120,60" href="#" onclick="javascript : bloc (\'N\')" />
    <area shape="rect" coords="90,60,120,90" href="#" onclick="javascript : bloc (\'B\')" />
    <area shape="rect" coords="90,90,120,120" href="#" onclick="javascript : bloc (\'K\')" />
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