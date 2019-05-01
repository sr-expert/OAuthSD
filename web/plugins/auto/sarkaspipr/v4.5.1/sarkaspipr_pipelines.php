<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Post-propre : appliquer le filtre des couleurs typo_couleur automatiquement
 * @param string $texte
 * @return mixed
 */
function sarkaspipr_post_propre($texte){
	$texte = typo_couleur($texte);
	return $texte;
}

/**
 * Pipeline "mes_fichiers_a_sauver" permettant de rajouter des fichiers a sauvegarder dans le plugin Mes Fichiers 2
 *
 * @param $flux
 * @return array
 */
function sarkaspipr_mes_fichiers_a_sauver($flux){
	$tmp_fonds = defined('_DIR_TMP') ? _DIR_TMP.'fonds/': _DIR_RACINE.'tmp/fonds/';
	$tmp_styles = defined('_DIR_TMP') ? _DIR_TMP.'cfg/': _DIR_RACINE.'tmp/cfg/';

	// le repertoire des images de fonds pour les styles
	if (@is_dir($tmp_fonds))
		$flux[] = $tmp_fonds;
	// le repertoire sauvegardes du cfg des styles
	if (@is_dir($tmp_styles))
		$flux[] = $tmp_styles;

	spip_log('*** sarkaspip_mes_fichiers_a_sauver ***');
	spip_log($flux);
	return $flux;
}

// -- Fonction d'affichage des noisettes
function sarkaspipr_afficher_noisettes($define, $flux, $ajax=true){
	$noisettes = explode(':', $define);
	foreach ($noisettes as $_fond) {
		if (find_in_path($_fond.'.html')) {
			$contexte = $ajax ? array_merge($flux['args'], array('ajax' => true)) : $flux['args'];
			$html = recuperer_fond($_fond, $contexte);
			$flux['data'] .= $html;
		}
		else 
			$flux['data'] .= '<div class="noisette avertissement" style="margin-top: 0; font-size: 0.95em">' . _T('sarkaspip:msg_fichier_introuvable', array('fichier' => $_fond . '.html')) . '</div>';
	}
	return $flux;
}
// -- Fonction d'insertion en debut de colonne extra
function sarkaspipr_personnaliser_colonne_extra_debut($flux){
	if (defined('_PERSO_COLONNE_EXTRA_DEBUT'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_COLONNE_EXTRA_DEBUT, $flux, true);
	return $flux;
}
// -- Fonction d'insertion en fin de colonne extra
function sarkaspipr_personnaliser_colonne_extra_fin($flux){
	if (defined('_PERSO_COLONNE_EXTRA_FIN')) 
		$flux = sarkaspipr_afficher_noisettes(_PERSO_COLONNE_EXTRA_FIN, $flux, true);
	return $flux;
}
// -- Fonction d'insertion en debut de colonne navigation
function sarkaspipr_personnaliser_colonne_navigation_debut($flux){
	if (defined('_PERSO_COLONNE_NAVIGATION_DEBUT'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_COLONNE_NAVIGATION_DEBUT, $flux, true);
	return $flux;
}
// -- Fonction d'insertion en fin de colonne navigation
function sarkaspipr_personnaliser_colonne_navigation_fin($flux){
	if (defined('_PERSO_COLONNE_NAVIGATION_FIN'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_COLONNE_NAVIGATION_FIN, $flux, true);
	return $flux;
}
// -- Fonction d'insertion en fin de menu des pages speciales
function sarkaspipr_personnaliser_menu_pages_speciales_fin($flux){
	if (defined('_PERSO_MENU_PAGES_SPECIALES_FIN'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_MENU_PAGES_SPECIALES_FIN, $flux, false);
	return $flux;
}
// -- Fonction d'insertion en debut de bandeau haut
function sarkaspipr_personnaliser_bandeau_haut_debut($flux){
	if (defined('_PERSO_BANDEAU_HAUT_DEBUT'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_BANDEAU_HAUT_DEBUT, $flux, false);
	return $flux;
}
// -- Fonction d'insertion en fin de bandeau haut
function sarkaspipr_personnaliser_bandeau_haut_fin($flux){
	if (defined('_PERSO_BANDEAU_HAUT_FIN'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_BANDEAU_HAUT_FIN, $flux, false);
	return $flux;
}
// -- Fonction d'insertion en debut de bandeau bas
function sarkaspipr_personnaliser_bandeau_bas_debut($flux){
	if (defined('_PERSO_BANDEAU_BAS_DEBUT'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_BANDEAU_BAS_DEBUT, $flux, false);
	return $flux;
}
// -- Fonction d'insertion en fin de bandeau bas
function sarkaspipr_personnaliser_bandeau_bas_fin($flux){
	if (defined('_PERSO_BANDEAU_BAS_FIN'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_BANDEAU_BAS_FIN, $flux, false);
	return $flux;
}
// -- Fonction d'insertion en debut de pied
function sarkaspipr_personnaliser_pied_debut($flux){
	if (defined('_PERSO_PIED_DEBUT'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_PIED_DEBUT, $flux, false);
	return $flux;
}
// -- Fonction d'insertion en fin de pied
function sarkaspipr_personnaliser_pied_fin($flux){
	if (defined('_PERSO_PIED_FIN'))
		$flux = sarkaspipr_afficher_noisettes(_PERSO_PIED_FIN, $flux, false);
	return $flux;
}
?>
