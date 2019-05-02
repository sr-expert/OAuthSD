<?php
/**
 * Plugin Inscription3 pour SPIP
 * © 2007-2010 - cmtmt, BoOz, kent1
 * Licence GPL v3
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Function déterminant les champs obligatoires de I3 en fonction de la configuration de CFG
 *
 * @return array Un array contenant l'ensemble des champs
 * @param int $id_auteur[optional] Dans le cas ou cette option est présente, on ne retourne que les champs autorisé à être modifiés dans la configuration
 */
function inc_inscription3_champs_obligatoires_dist($id_auteur=null,$form='editer_auteur') {
	$config_i3 = lire_config('inscription3');

	if(is_numeric($id_auteur)){
		$suffixe = '_fiche_mod';
	}

	$valeurs = array();
	$exceptions_des_champs_auteurs_elargis = pipeline('i3_exceptions_des_champs_auteurs_elargis',array());

	//charge les valeurs de chaque champs proposés dans le formulaire
	foreach ($config_i3 as $clef => $valeur) {

		/*  On retrouve donc les chaines de type champ_obligatoire
		 *  Remplissage de $valeurs[]
		 */
		//decoupe la clef sous le forme $resultat[0] = $resultat[1] ."_obligatoire"
		//?: permet de rechercher la chaine sans etre retournée dans les résultats
		if(preg_match("/_(nocreation)/i", $clef)){
			$fin_suffixe = $suffixe.'_nocreation';
		}else{
			$fin_suffixe = '';
		}
		preg_match('/^(.*)_obligatoire/i', $clef, $resultat);

		if ((!empty($resultat[0])) && ($config_i3[$resultat[1].$suffixe.$fin_suffixe] == 'on') && ($config_i3[$resultat[1].'_obligatoire'.$fin_suffixe] == 'on') && (!in_array($resultat[1],$exceptions_des_champs_auteurs_elargis))) {
			$valeurs[] = $resultat[1];
		}
	}
	if($form == 'inscription' && $config_i3['reglement'] == 'on'){
		$valeurs[] = 'reglement';
	}
	return $valeurs;
}
?>