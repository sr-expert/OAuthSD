<?php
if (!defined("_ECRIRE_INC_VERSION")) return;

function zvide_ieconfig_metas($table){
	$table['zvide']['titre'] = _T('zvide:configurer_zvide');
	$table['zvide']['icone'] = 'entete-16.png';
	$table['zvide']['metas_serialize'] = 'zvide';
	return $table;
}

?>