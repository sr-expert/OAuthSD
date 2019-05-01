<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Fonction d'installation du plugin et de mise à jour.
**/
function sarkaspipr_upgrade($nom_meta_base_version, $version_cible){
	$maj = array();

	include_spip('inc/config');
	include_spip('base/abstract_sql');

	$secteur_forum = lire_config("sarkaspip/forum/rubrique_forum");

	# Premiere installation  creation des tables
	$maj['create'] = array(
		array('sql_updateq', 'spip_rubriques', array('composition' => 'forums'), 'id_rubrique= '.intval($secteur_forum)),
		array('sarkaspipr_upgrade_metas'),
	);

	$maj['0.1.1'] = array(
		array('sql_updateq', 'spip_rubriques', array('composition' => 'forums'), 'id_rubrique= '.intval($secteur_forum)),
	);
	$maj['0.1.4'] = array(
		array('sarkaspipr_upgrade_metas'),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function sarkaspipr_upgrade_metas(){

	include_spip("lire_config");
	foreach (array_keys($GLOBALS['meta']) as $k){
		if (strncmp($k,"sarkaspip_",10)==0){
			$casier = substr($k,10);
			$c = lire_config($k);
			ecrire_config("sarkaspip/$casier",$c);
			effacer_meta($k);
		}
	}

	// une fois le tableau transite, reaffecter quelques config qui ont change de place
	if (!lire_config("sarkaspip/pied/position_badges",""))
		ecrire_config("sarkaspip/pied/position_badges",lire_config("sarkaspip/noisettes/position_badges",1));
	effacer_config("sarkaspip/noisettes/position_badges");

	if (!lire_config("sarkaspip/rubrique/court_circuit",""))
		ecrire_config("sarkaspip/rubrique/court_circuit",lire_config("sarkaspip/menus/option_rubriques",0)==2);
	effacer_config("sarkaspip/menus/option_rubriques");

	// effacer les vieilles config qui ne serviront plus
	effacer_config("sarkaspip/agenda");
	effacer_config("sarkaspip/styles");
	effacer_config("sarkaspip/coins");
	effacer_config("sarkaspip/layout");
}

/**
 * Fonction de désinstallation du plugin.
**/
function sarkaspipr_vider_tables($nom_meta_base_version) {


	# suppression meta & config
	effacer_meta("sarkaspip");
	effacer_meta($nom_meta_base_version);

}

?>
