<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

// =======================================================================================================================================
// Balise : #VERSION_SQUELETTE
// =======================================================================================================================================
// Auteur: SarkASmeL
// Fonction : affiche la version utilise du squelette variable globale $version_squelette
// =======================================================================================================================================
//
function balise_VERSION_SQUELETTE($p) {
	$p->code = 'calcul_version_squelette()';
	$p->interdire_scripts = false;
	return $p;
}

function calcul_version_squelette() {

	$version = NULL;

	$informer = chercher_filtre('info_plugin');
	$version = $informer('sarkaspipr', 'version');

	$revision = version_svn_courante(_DIR_PLUGIN_SARKASPIPR);
	if ($revision > 0)
		$version .= ' ['.strval($revision).']';
	else if ($revision < 0)
		$version .= ' SVN&nbsp;['.strval(abs($revision)).']';

	return $version;
}

// =======================================================================================================================================
// Balise : #VERSION_PHP
// =======================================================================================================================================
// Auteur: SarkASmeL
// Fonction : affiche la version du PHP utilisee
// =======================================================================================================================================
//
function balise_VERSION_PHP($p) {
	$p->code = 'phpversion()';
	$p->interdire_scripts = false;
	return $p;
}

// =======================================================================================================================================
// Balise : #VISITEURS_CONNECTES
// =======================================================================================================================================
// Auteur: SarkASmeL (base sur le plugin Nombre de visiteurs connectes)
// Fonction : affiche le nombre de visiteurs en cours de connection sur le site
// Parametre: aucun
// =======================================================================================================================================
//
function balise_VISITEURS_CONNECTES($p) {

	$p->code = 'calcul_visiteurs_connectes()';
	$p->statut = 'php';
	return $p;
}

function calcul_visiteurs_connectes() {
	$nb = count(preg_files(_DIR_TMP.'visites/','.'));
	return $nb;
}

// =======================================================================================================================================
// Balise : #VISITES_SITE
// =======================================================================================================================================
// Auteur: SarkASmeL
// Fonction : affiche le nombre de visites sur le site pour le jour courant, la veille ou depuis le debut
// Parametre: aujourdhui, hier, depuis_debut (ou vide)
// =======================================================================================================================================
//
function balise_VISITES_SITE($p) {

	$jour = interprete_argument_balise(1,$p);
	$jour = isset($jour) ? str_replace('\'', '"', $jour) : '"depuis_debut"';

	$p->code = 'calcul_visites_site('.$jour.')';
	$p->statut = 'php';
	return $p;
}

function calcul_visites_site($j) {

	$visites = 0;
	
	if ( $j == 'aujourdhui' ) {
		$auj = date('Y-m-d',strtotime(date('Y-m-d')));
		$select = array('visites');
		$from = array('spip_visites');
		$where = array("date=".sql_quote($auj));
		$result = sql_select($select, $from, $where);
		if ($row = sql_fetch($result)) {
			$visites = $row['visites'];
		}
	}
	else if ( $j == 'hier' ) {
		$hier = date('Y-m-d',strtotime(date('Y-m-d')) - 3600*24);
		$select = array('visites');
		$from = array('spip_visites');
		$where = array("date=".sql_quote($hier));
		$result = sql_select($select, $from, $where);
		if ($row = sql_fetch($result)) {
			$visites = $row['visites'];
		}
	}
	else {
		$select = array('SUM(visites) AS total_absolu');
		$from = array('spip_visites');
		$result = sql_select($select, $from);
		if ($row = sql_fetch($result)) {
			$visites = $row['total_absolu'];
			if ($visites == NULL) $visites=0;
		}
	}
	return $visites;
}

// =======================================================================================================================================
// Balise : #AUJOURDHUI
// =======================================================================================================================================
// Auteur: SarkASmeL
// Fonction : retourne la date du jour independamment du contexte d'appel
// =======================================================================================================================================
//
function balise_AUJOURDHUI($p) {

	$p->code = 'date("Y-m-d H:i")';
	$p->statut = 'php';
	return $p;
}

// =======================================================================================================================================
// Balise : #RACINE_SPECIALISEE et BRANCHE_SPECIALISEE
// =======================================================================================================================================
// Auteur: SarkASmeL
// Fonction : retourne la valeur de l'ID de la rubrique demandee ou de toutes les rubriques specialisees sous forme de regex
//            Pour creer une nouvelle rubrique specialisee il suffit de rajouter un mot dans le tableau des mots reserves ($mots_reserves)
// =======================================================================================================================================
//
function balise_RACINE_SPECIALISEE($p) {

	$mot_rubrique = interprete_argument_balise(1,$p);
	$mot_rubrique = isset($mot_rubrique) ? str_replace('\'', '"', $mot_rubrique) : '""';
	$critere = interprete_argument_balise(2,$p);
	$critere = isset($critere) ? str_replace('\'', '"', $critere) : '"in"';
	$mode = "'secteur'";

	$p->code = 'calcul_rubrique_specialisee('.strtolower($mot_rubrique).','.$mode.','.$critere.')';
	$p->interdire_scripts = false;
	return $p;
}

function balise_BRANCHE_SPECIALISEE($p) {

	$mot_rubrique = interprete_argument_balise(1,$p);
	$mot_rubrique = isset($mot_rubrique) ? str_replace('\'', '"', $mot_rubrique) : '""';
	$critere = interprete_argument_balise(2,$p);
	$critere = isset($critere) ? str_replace('\'', '"', $critere) : '"in"';
	$mode = "'branche'";

	$p->code = 'calcul_rubrique_specialisee('.strtolower($mot_rubrique).','.$mode.','.$critere.')';
	$p->interdire_scripts = false;
	return $p;
}

function calcul_rubrique_specialisee($mot_rubrique, $mode, $critere) {

	// On calcule la liste des mots reserves SarkaSPIP + definis par l'utilisateur
	$mots_reserves = explode(':', _SARKASPIP_MOT_SECTEURS_SPECIALISES);
    if (defined('_PERSO_MOT_SECTEURS_SPECIALISES'))
    	if (_PERSO_MOT_SECTEURS_SPECIALISES != '')
	    	$mots_reserves = array_merge($mots_reserves, explode(':', _PERSO_MOT_SECTEURS_SPECIALISES));
	$types_reserves = explode(':', _SARKASPIP_TYPE_SECTEURS_SPECIALISES);
    if (defined('_PERSO_TYPE_SECTEURS_SPECIALISES'))
    	if (_PERSO_TYPE_SECTEURS_SPECIALISES != '')
	    	$types_reserves = array_merge($types_reserves, explode(':', _PERSO_TYPE_SECTEURS_SPECIALISES));
	$fonds_reserves = explode(':', _SARKASPIP_FOND_SECTEURS_SPECIALISES);
    if (defined('_PERSO_FOND_SECTEURS_SPECIALISES'))
    	if (_PERSO_FOND_SECTEURS_SPECIALISES != '')
	    	$fonds_reserves = array_merge($fonds_reserves, explode(':', _PERSO_FOND_SECTEURS_SPECIALISES));

	// Determination de la liste des mots cles associes aux secteurs specialises demandes par la balise
	$id = NULL;
	$mots = explode(':', $mot_rubrique);
	if ($critere == "not_in") {
		$mots = array_diff($mots_reserves, $mots);
		sort($mots);
	}
 	if (!$mots[0]) $mots = $mots_reserves;
 	// Si on est en en mode secteur (ie. balise #RACINE_SPECIALISEE) et qu'on demande un seul secteur specialise
 	// on renvoie une valeur; sinon on renvoie toujours une regexp
	$comparaison_valeur = (($mode == 'secteur') && ($mots[0] == $mot_rubrique)) ? true : false;
	// Calcul de la balise
	reset($mots_reserves);
	while (list($cle, $valeur) = each($mots_reserves)) {
		if ( in_array($valeur, $mots)) {
			if ($id != NULL) $id .= '|';
			$id .= strval(calcul_rubrique($valeur, $types_reserves[$cle], $fonds_reserves[$cle], $mode));
		}
	}
	if (!$comparaison_valeur) $id = '^('.$id.')$';
	
	return $id;
}

function calcul_rubrique($mot, $type, $fond, $mode='rubrique') {

	$id_rubrique = 0;
	if (!$mot)
		return $id_rubrique;

	// On recupere le secteur de base soit via la methode du mot-cle, soit par la config
	if ($type == 'motcle') {
		$select = array('id_rubrique');
		$from = array('spip_mots_rubriques AS t1', 'spip_mots AS t2', 'spip_groupes_mots AS t3');
		$where = array('t3.titre='.sql_quote('squelette_habillage'),
					   't3.id_groupe=t2.id_groupe',
					   't2.titre='.sql_quote($mot),
					   't2.id_mot=t1.id_mot');
		$result = sql_select($select, $from, $where);
		if ($row = sql_fetch($result)) {
			$id_rubrique = $row['id_rubrique'];
		}
	}
	else if ($type == 'config') {
		include_spip('inc/config');
		$valeur = lire_config($fond.'/rubrique_'.$mot);
		if (($valeur != NULL) && ($valeur > 0)) $id_rubrique = $valeur;
	}
	
	// Si on est en mode branche on retourne les rubriques de la branche, sinon uniquement le secteur recupere precedemment
	if (( $id_rubrique != 0) && ($mode == 'branche')) {
		$select = array('id_rubrique');
		$from = array('spip_rubriques AS t1');
		$where = array('t1.id_secteur='.sql_quote($id_rubrique));
		$result = sql_select($select, $from, $where);
		$secteur = $id_rubrique;
		while ($row = sql_fetch($result)) {
			if ($row['id_rubrique'] != $secteur) $id_rubrique .= '|'.$row['id_rubrique'];
		}
	}
	
	return $id_rubrique;
}

?>
