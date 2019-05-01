<?php
function formulaires_configurer_sarkaspip_plugins_traiter() {

	// On simule le traitement normal du cvt configurer
	include_spip('inc/cvt_configurer');
	$args = func_get_args();
	$trace = cvtconf_formulaires_configurer_enregistre('configurer_sarkaspip_plugins', $args);

	// Post traitement de configuration des plugins concernes
	include_spip('inc/config');
	if (lire_config('sarkaspip/plugins/config_boutonstexte') == 'sarkaspip') {
		ecrire_config('boutonstexte/', array(	'selector'=>_SARKASPIP_CONFIG_BOUTONSTEXTE_SELECTOR,
												'txtOnly' => _SARKASPIP_CONFIG_BOUTONSTEXTE_TXTONLY));
	}
	else
		effacer_config('boutonstexte');

	if (lire_config('sarkaspip/plugins/config_mediabox') == 'sarkaspip') {
		ecrire_config('mediabox/', array(	'active' => _SARKASPIP_CONFIG_MEDIABOX_ACTIF,
											'traiter_toutes_images'=>_SARKASPIP_CONFIG_MEDIABOX_TOUT,
											'selecteur_commun'=>_SARKASPIP_CONFIG_MEDIABOX_IMAGE,
											'selecteur_galerie'=>_SARKASPIP_CONFIG_MEDIABOX_GALERIE,
											'skin'=>_SARKASPIP_CONFIG_MEDIABOX_SKIN));
	}
	else
		effacer_config('mediabox');

	if (lire_config('sarkaspip/plugins/config_socialtags') == 'sarkaspip') {
		ecrire_config('socialtags/', array(	'jsselector'=>_SARKASPIP_CONFIG_SOCIALTAGS_SELECTOR,
											'tags' => explode(':', _SARKASPIP_CONFIG_SOCIALTAGS_TAGS)));
	}
	else
		effacer_config('socialtags');

	return array('message_ok'=>_T('config_info_enregistree').$trace,'editable'=>true);
}
?>
