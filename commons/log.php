<?php
/* log.php

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

//define('LOG_LEVEL', 3 ); // 3 = error + info + success, 2 = error + info, 1 = error only  

if ( !defined('PRIVATE') ) return;

function log_info( $where, $str = '', $client_id, $user_id, $errnum, $weight, $cnx) {                      
    // simple information
    if ( LOG_LEVEL > 2 ) {   
        __log("Information", $where, $str,  $client_id, $user_id, 1, $errnum, $weight, $cnx);
    }
}

function log_success( $where, $str = '', $client_id, $user_id, $errnum, $weight, $cnx) {
    // log du succès
    if ( LOG_LEVEL > 1 ) {   
        __log("Success", $where, $str,  $client_id, $user_id, 2, $errnum, $weight, $cnx);
    }
}

function log_error( $where, $str = '', $client_id, $user_id, $errnum, $weight, $cnx) {
    // log de l'événement
    if ( LOG_LEVEL > 0 ) {
        __log("Error", $where, $str,  $client_id, $user_id, 3, $errnum, $weight, $cnx); 
    }      
}

function __log( $title, $origin, $str = '', $client_id = 'unk', $user_id = 'unk', $level = 0, $errnum = 0, $weight = 1, $cnx = null) {       

    global $storage_config;

    if ( empty($client_id)) $client_id = 'Unk';
    if ( empty($user_id)) $user_id = 'Unk';

    require_once __DIR__.'/../oidc/includes/utils.php'; 

    $state = decrypt(@$_SESSION['state']); //[dnc21] will be used to chain messages in log view  [dnc33]
    $remote_addr = (string)$_SERVER['REMOTE_ADDR'];  // id.

    if ( !is_null(@$_SERVER['HTTP_ORIGIN']) OR @$_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" ) {  //[dnc36]
        // specify errnum if Cross Origin Request.
        $errnum += 10000;
    }

    //[dnc40] HIDS
    require_once __DIR__ . '/hids.php';
    if ( function_exists('hids') ) {   
        // Détecter les tentatives d'intrusion
        $arglist = hids($title, $origin, $str, $client_id, $user_id, $level, $errnum, $weight, $cnx);
        $title = $arglist[0];
        $origin = $arglist[1];
        $str = $arglist[2];
        $client_id = $arglist[3];
        $user_id = $arglist[4];
        $level = $arglist[5];
        $errnum = $arglist[6]; 
        $weight = $arglist[7];
    }

    // Log de l'événement   
    //$date = date_create('now',new DateTimeZone('GMT'));
    $t = microtime(true);
    $micro = sprintf("%06d",($t - floor($t)) * 1000000);
    $datetime = date('Y-m-d H:i:s.'.$micro, $t);//[dnc38]  
    $erreur = $datetime . ' - ' . $remote_addr . ' - ' . $title . ' : ' . $origin . ' : ' . $str . "\n\n";
    if ( defined('LOG_FILE') AND !empty(LOG_FILE) ) {
        @file_put_contents(LOG_FILE, $erreur, FILE_APPEND);
    }

    // Enregistrer dans la table oidc_logs   
    if ( is_object($cnx) ) {              
        $stmt = $cnx->prepare(sprintf("INSERT INTO %s SET state=:state, remote_addr=:remote_addr, client_id=:client_id, user_id=:user_id, datetime=:datetime, origin=:origin, message=:erreur, level=:level, errnum=:errnum, weight=:weight", $storage_config['oidc_log_table']));    
        $stmt->execute(compact('state', 'remote_addr', 'client_id', 'user_id', 'datetime', 'origin', 'erreur', 'level', 'errnum', 'weight'));    
    }

    /*[dnc26b] Si on a un state, mettre à jour la table oidc_states.
    Comme state est éphémère, nous n'aurons pas à gérer la répétion des alertes. 
    */
    if ( ! empty($state) ) {
        $stmt = $cnx->prepare(sprintf('INSERT INTO %s (state, total_weight, status) VALUES (:state, :weight, 0) ON DUPLICATE KEY UPDATE total_weight=total_weight+:weight', $storage_config['oidc_state_table']));    
        $stmt->execute(compact('state', 'weight'));           
    }

    /*[dnc26c] Si on a une IP, mettre à jour la table oidc_remote_addr.
    Les IP étant stables, il faut empêcher une "bonne" IP d'accumuler les poids négatifs.
    Pour cela, le champ total_weight est UNSIGNED et l'erreur de MySQL est ignorée.
    */
    if ( ! empty($remote_addr) ) {
        $stmt = $cnx->prepare(sprintf("INSERT INTO %s (remote_addr, total_weight, status) VALUES (:remote_addr, :weight, 0) ON DUPLICATE KEY UPDATE total_weight=total_weight+:weight", $storage_config['oidc_remote_addr_table']));    
        @$stmt->execute(compact('remote_addr', 'weight'));           
    }



}
