<?php
if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
include_spip('inc/editer');


function formulaires_supprimer_client_traiter_dist() {

    if ( !is_null(_request('id_client')) ) $id_client = intval(_request('id_client'));
    
    sql_delete('spip_clients',  'id_client=' . $id_client);
    sql_delete('spip_auteurs_liens',  'id_client=' . $id_client);
    
    unset($_GET['id_client']);
    
    return array('message_ok' => 'Client supprimé');

}
?>
