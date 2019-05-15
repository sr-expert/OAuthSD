<?php 
/* Page d'erreur pour authorize.php

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

if ( defined('__AUTHORIZE') ) {

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
            #champ_login {
                width: 240px;
                }
            div.password {
                margin-top: 10px;
                }
            #champ_password {
                background-color: #18bc9c !important;
                border: none;}
            .nologin {margin-top : 9px;}
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
                
                <h3 class="oauth error">Erreur :</h3>'. $errormsg . '

                <div class="nologin">
                    Vous n\'avez pas d\'identifiants d\'accès ? <a href="/article18&id_client=' . $id_client . '&client=' . $theclient . '&nom=' . $thename . '&pswdl=' . PSWD_LENGTH . '">Inscrivez-vous ici</a>.     
                </div>

            </div>
            <div id="bottom">
                <p>
                    Pour votre protection, '  . $thename . ' utilise <a href="http://oa.dnc.global">OAuth Server by DnC</a> : les mots de passe ne peuvent être interceptés au moment de leur saisie, ne circulent pas sur Internet, ne sont enregistrés nulle part !
                </p>
            </div>
        </div> 
        '
    ); 
};
