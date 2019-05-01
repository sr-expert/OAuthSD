<?php
function formulaires_restauration_cfg_charger_dist(){
	$configs = array();

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
			$configs[$_config] = _T("sarkaspip_config:$item");
		}
	}

	$dir_cfg = sous_repertoire(_DIR_TMP,"sarkaspip");
	$dir_cfg = sous_repertoire($dir_cfg,"config");
	$sauvegardes = preg_files($dir_cfg, implode('|', array_flip($configs)));
	$options = '';
	$erreur = '';
	if (!$sauvegardes) {
		$erreur = _T('sarkaspip_config:cfg_msg_aucune_sauvegarde');
	}
	else {
		$groupe = '';
		foreach ($sauvegardes as $_fichier) {
			$nom = basename($_fichier);
			$dirs = explode('/', dirname($_fichier));
			$dir = end($dirs);
			if ($dir != $groupe) {
				if ($options) $options .= '</optgroup>';
				$options .= '<optgroup style="font-weight: strong;" label="'.$configs[$dir].'">';
				$groupe = $dir;
			}
			$options .= '<option value="' . $_fichier . '">' . $nom . '</option>';
		}
		if ($options) $options .= '</optgroup>';
	}

	$valeurs = array('_fichiers_sauvegardes' => $options, '_erreur_sauvegarde' => $erreur);

	return $valeurs;
}


function formulaires_restauration_cfg_traiter_dist(){
	$retour=array();
	
	$fichier = _request('config_a_restaurer');
	lire_fichier($fichier, $contenu);

	include_spip('inc/config');
	$dirs = explode('/', dirname($fichier));
	$config = end($dirs);
	$ok = ecrire_config("sarkaspip/$config", unserialize($contenu));
	
	if (!$ok)
		$retour['message_nok'] = _T('sarkaspip_config:cfg_msg_fichier_restauration_nok');
	else
		$retour['message_ok'] = _T('sarkaspip_config:cfg_msg_fichier_restauration_ok', array('nom_fichier' => $fichier));

	return $retour;
}

?>
