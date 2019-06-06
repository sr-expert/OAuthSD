<?php
/* envoyer_mail_fonctions.php

status :
0 : non traité
1 : ignoré
2 : message envoyé

dnc26b,c,d

Auteur : B.Degoy http://degoy.com
Tous droits réservés
Copyright (c) 2017_2019 DnC

*/
    
// Envoyer mail.
function envmail( $destinataire, $sujet, $msg, $id_state, $level ) {
    
    $envoyer_mail = charger_fonction('envoyer_mail', 'inc/');
    
    if ( $level >= ALERTE_WEIGHT ) {
        // Envoie un message pour une alerte de niveau ALERTE_WEIGHT et plus
        $ok = $envoyer_mail($destinataire, $sujet, $msg);
        if ( $ok ) {
            // et marquer l'alerte comme traitée
            $ret = sql_update("spip_oidc_states", array('status'=>'2'), "state='" . $id_state . "'");
        }
    } else {
        // Marquer les alertes de niveau inférieur comme ignorées
        $ret = sql_update("spip_oidc_states", array('status'=>'1'), "state='" . $id_state . "'");
    }  
    
}

function envmail2( $destinataire, $sujet, $msg, $remote_addr, $level ) {
    
    $envoyer_mail = charger_fonction('envoyer_mail', 'inc/');
    
    if ( $level >= ALERTE_WEIGHT ) {
        // Envoie un message pour une alerte de niveau ALERTE_WEIGHT et plus
        $ok = $envoyer_mail($destinataire, $sujet, $msg);
        if ( $ok ) {
            // et marquer l'alerte comme traitée
            $ret = sql_update("spip_oidc_remote_addr", array('status'=>'2'), "remote_addr='" . $remote_addr . "'");
        }
    } else {
        // Marquer les alertes de niveau inférieur comme ignorées
        $ret = sql_update("spip_oidc_remote_addr", array('status'=>'1'), "remote_addr='" . $remote_addr . "'");
    }  
    
}
