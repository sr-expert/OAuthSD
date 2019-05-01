<?php
/*
OAuthSD
Utilitaires pour prtg.

Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
Tous droits réservés
*/

//TODO : appuyer PRTG sur log.
define ('CURRENT_OIDC_FILE', '/home/oadnc/public_html/oidc/prtg/current_oidc.txt');   //CONFIG
define ('PRTG_OIDC_FILE', '/home/oadnc/public_html/oidc/prtg/prtg_oidc.txt');  //CONFIG

/**
* Update oidc raw values
* 
* @param mixed $file
* @param mixed $values
*/
function oidc_increment($value) {
    $values = oidc_read_values( CURRENT_OIDC_FILE );
    $values[$value] +=1;         //TODO: bug potentiel : overflow
    oidc_write_values( CURRENT_OIDC_FILE, $values);
}

function oidc_read_values($file) {
    $values = json_decode(@file_get_contents($file), true);
    if ( is_array( $values ) ) {
        return $values;
    } else {
        return array(
            'time' => time(),
            'total_requests' => 0,
            'good_requests' => 0,
            'authentications' => 0,        
        ); 
    }
}

function oidc_write_values($file, $values) {
    @file_put_contents($file,json_encode($values));   // Attention! le dossier prtg doit exister
}

