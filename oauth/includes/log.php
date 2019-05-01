<?php
/* log.php

Auteur : B. Degoy bertrand@degoy.com
Copyright (c) 2018 DnC https://degoy.com
All rights reserved

*/

//define('LOG_LEVEL', 3 ); // 3 = error + info + success, 2 = error + info, 1 = error only  

if ( !defined('PRIVATE') ) return;

function log_info( $where, $str = '') {
    // simple information
    if ( LOG_LEVEL > 2 ) {   
        __log("Information", $where, $str);
    }
}

function log_success( $where, $str = '') {
    // log du succès
    if ( LOG_LEVEL > 1 ) {   
        __log("Success", $where, $str);
    }
}

function log_error( $where, $str = '') {
    // log de l'erreur
    if ( LOG_LEVEL > 0 ) {
        __log("Error", $where, $str); 
    }      
}

function __log( $title, $where, $str = '') {
    // log de l'erreur   
    $when = date("Y-m-d H:i:s");
    $erreur = $when . ' - ' . $title . ' : ' . $where . ' : ' . $str . "\n\n";
    @file_put_contents(LOG_FILE, $erreur, FILE_APPEND);
}
