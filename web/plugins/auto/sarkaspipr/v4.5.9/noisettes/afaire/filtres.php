<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined("_ECRIRE_INC_VERSION")) return;


// =======================================================================================================================================
// Filtre : afaire_liste_par_jalon
// =======================================================================================================================================
// Auteur: Smellup
// Fonction : Retourne les blocs d'affichage des tickets par jalon dans la page afaire
// =======================================================================================================================================
//
function afaire_liste_par_jalon($jalons) {
	$page = NULL;
	if (($jalons) && defined('_SARKASPIP_AFAIRE_JALONS_AFFICHES')) {
		$liste = explode(":", $jalons);
		$i =0;
		foreach($liste as $_jalon) {
			$i += 1;
			$page .= recuperer_fond('noisettes/afaire/jalon',
				array('jalon' => $_jalon, 'ancre' => 'ancre_jalon_'.strval($i)));
		}
	}
	return $page;
}
// FIN du Filtre : afaire_liste_par_jalon

// =======================================================================================================================================
// Filtre : afaire_tdm_par_jalon
// =======================================================================================================================================
// Auteur: Smellup
// Fonction : Retourne les blocs d'affichage des tickets par jalon dans la page afaire
// =======================================================================================================================================
//
function afaire_tdm_par_jalon($jalons) {
	$page = NULL;
	if (($jalons) && defined('_SARKASPIP_AFAIRE_JALONS_AFFICHES')) {
		$liste = explode(":", $jalons);
		$i =0;
		foreach($liste as $_jalon) {
			$i += 1;
			$nb = afaire_compteur_jalon($_jalon);
			$nb_str = ($nb == 0) ? _T('sarkaspip:0_ticket') : (($nb == 1) ? strval($nb).' '._T('sarkaspip:1_ticket') : strval($nb).' '._T('sarkaspip:n_tickets'));
			$page .= '<li class="item"><a href="#ancre_jalon_'.strval($i).'" title="'._T('sarkaspip:afaire_aller_jalon').'">'
				._T('sarkaspip:afaire_colonne_jalon').'&nbsp;&#171;&nbsp;'.$_jalon.'&nbsp;&#187;, '.$nb_str
				.'</a></li>';
		}
	}
	$nb = afaire_compteur_jalon();
	if ($nb > 0) {
		$nb_str = ($nb == 1) ? strval($nb).' '._T('sarkaspip:1_ticket') : strval($nb).' '._T('sarkaspip:n_tickets');
		$page .= '<li class="item"><a href="#ancre_jalon_non_planifie" title="'._T('sarkaspip:afaire_aller_jalon').'">&#171;&nbsp;'
			._T('sarkaspip:afaire_non_planifies').'&nbsp;&#187;, '.$nb_str
			.'</a></li>';
	}
	return $page;
}
// FIN du Filtre : afaire_tdm_par_jalon

// =======================================================================================================================================
// Filtre : afaire_compteur_jalon
// =======================================================================================================================================
// Auteur: Smellup
// Fonction : Retourne le nombre de tickets pour le jalon ou pour le jalon et le statut choisis
// =======================================================================================================================================
//
function afaire_compteur_jalon($jalon='', $statut='') {
	$valeur = 0;
	// Nombre total de tickets pour le jalon
	$where = array('jalon='.sql_quote($jalon));
	if ($statut)
		$where[] = 'statut='.sql_quote($statut);
	$valeur = sql_countsel('spip_tickets', $where);
	return $valeur;
}
// FIN du Filtre : afaire_compteur_jalon

// =======================================================================================================================================
// Filtre : afaire_avancement_jalon
// =======================================================================================================================================
// Auteur: Smellup
// Fonction : Retourne le pourcetage de tickets termines sur le nombre de tickets total du jalon
// =======================================================================================================================================
//
function afaire_avancement_jalon($jalon='') {
	$valeur = 0;
	// Nombre total de tickets pour le jalon
	$where = array('jalon='.sql_quote($jalon));
	$n1 = sql_countsel('spip_tickets', $where);
	// Nombre de tickets termines pour le jalon
	if ($n1 > 0) {
		$where[] = sql_in('statut', array('resolu','ferme'));
		$n2 = sql_countsel('spip_tickets', $where);
		$valeur = floor($n2*100/$n1);
	}
	return $valeur;
}
// FIN du Filtre : afaire_avancement_jalon

// =======================================================================================================================================
// Filtre : afaire_ticket_existe
// =======================================================================================================================================
// Auteur: Smellup
// Fonction : Retourne l'info qu'au moins un ticket a ete cree
// =======================================================================================================================================
//
function afaire_ticket_existe() {
	$existe = false;
	// Test si la table existe
	$trouver_table = charger_fonction('trouver_table','base');
	if ($trouver_table('spip_tickets')){
		// Nombre total de tickets
		if (sql_countsel('spip_tickets'))
			$existe = true;
	}
	return $existe ? ' ':'';
}
// FIN du Filtre : afaire_ticket_existe

// =======================================================================================================================================
// Filtre : statut_forum
// =======================================================================================================================================
// Auteur: Smellup
// Fonction : Retourne le statut d'un forum cad non autorise, ouvert, ferme
// =======================================================================================================================================
//