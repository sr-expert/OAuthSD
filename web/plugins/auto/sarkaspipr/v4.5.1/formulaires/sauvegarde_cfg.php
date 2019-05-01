<?php
function formulaires_sauvegarde_cfg_charger_dist() {

	$options = '';

	$pages_cfg = array();
	$sections = explode('|',_SARKASPIP_PAGES_CONFIG);
	foreach ($sections as $_section){
		$_section = explode("!",$_section);
		$_section = end($_section);
		$pages_cfg = array_merge($pages_cfg, array_map('trim',explode(":",$_section)));
	}

	foreach ($pages_cfg as $_config) {
		if ($_config != 'maintenance') {
			$item = "sarkaspip_{$_config}";
			$options .= '<option value="' . $_config . '">' . _T("sarkaspip:$item") . '</option>';
		}
	}

	$valeurs = array('_configurations' => $options);

	return $valeurs;
}


function formulaires_sauvegarde_cfg_traiter_dist() {
	$retour=array();
	
	$configs = array();
	$mode = _request('config_a_sauvegarder');
	if ($mode !== '--')
		$configs = array($mode);

	$dir_cfg = sous_repertoire(_DIR_TMP,"sarkaspip");
	$dir_cfg = sous_repertoire($dir_cfg,"config");
	$ok = sauvegarder_configuration($configs, $dir_cfg);
	
	if (!$ok)
		$retour['message_nok'] = _T('sarkaspip_config:cfg_msg_fichier_sauvegarde_nok');
	elseif ($mode !== '--')
		$retour['message_ok'] = _T('sarkaspip_config:cfg_msg_fichier_sauvegarde_ok');
	else
		$retour['message_ok'] = _T('sarkaspip_config:cfg_msg_fichiers_sauvegardes_ok');
	return $retour;
}


/**
 * Cree les sauvegardes d'une liste de fonds dans le repertoire temporaire tmp/cfg/
 *
 * @param $configs
 * @param $ou
 * @return bool
 */
function sauvegarder_configuration($configs, $ou) {
	include_spip('inc/config');

	// si pas de fond precise, on prend toutes les configs de la meta
	if (!$configs)
		$configs = array_keys(lire_config("sarkaspip"));

	$dir = $ou;
	foreach ($configs as $_config) {
		$dir = sous_repertoire($ou, $_config);
		$nom = $_config . "_" . date("Ymd_Hi") . ".txt";
		$fichier = $dir . $nom;
		$ok = ecrire_fichier($fichier, serialize(lire_config("sarkaspip/$_config")));
	}

	return $ok;
}

?>