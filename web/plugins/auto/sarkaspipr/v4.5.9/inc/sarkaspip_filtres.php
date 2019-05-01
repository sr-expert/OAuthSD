<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

// TODO : conditionner a l'existence du plugin tickets
include_spip("noisettes/afaire/filtres");

function sarkaspip_detecter_plugin_newsletter(){
	$f = find_in_path("newsletter/subscribe.php");
	if (!$f) return "";
	$d = dirname(dirname($f));
	$base = "";
	if (strncmp($d,_DIR_PLUGINS_DIST,strlen(_DIR_PLUGINS_DIST))==0)
		$base = _DIR_PLUGINS_DIST;
	elseif (strncmp($d,_DIR_PLUGINS,strlen(_DIR_PLUGINS))==0)
		$base = _DIR_PLUGINS;
	if (!$base) return "";

	$d = substr($d,strlen($base));
	$get_infos = charger_fonction("get_infos","plugins");
	if (!$infos = $get_infos($d,false,$base))
		return "";

	return $infos['prefix'];
}

/**
 * Tester si on doit rediriger une rubrique vers son article orphelin
 * - si reglage active dans la configuration
 * - si la rubrique ne contient qu'un article, aucune sous-rubrique ni documents
 *
 * @param $id_rubrique
 * @return string|int
 */
function sarkaspip_test_si_redirection_article_solitaire($id_rubrique){
	include_spip("inc/config");
	$serveur = '';

	// si reglage pas active, renvoyer rien (pas de redirection)
	if (!lire_config('sarkaspip/rubrique/court_circuit',0))
		return "";

	$trouver_table = charger_fonction('trouver_table', 'base');
	include_spip('public/compiler');
	include_spip('public/composer');
	// si plus d'un article publie, pas de redirection (on prend les 2 premiers, permet d'avoir l'id de l'article unique si besoin
	// il faut passer par une boucle compilateur pour avoir les conditions de statut publie
	if (!$desc = $trouver_table("spip_articles", $serveur))
		return "";
	$id_table_objet = "id_article";
	$id_table = $table_objet = "articles";
	$boucle = new Boucle();
	$boucle->show = $desc;
	$boucle->nom = 'articles_publies';
	$boucle->id_boucle = $id_table;
	$boucle->id_table = $id_table;
	$boucle->sql_serveur = $serveur;
	$boucle->select[] = $id_table_objet;
	$boucle->from[$table_objet] = "spip_articles";
	$boucle->where[] = $id_table.".id_rubrique=".intval($id_rubrique);
	$boucle->limit = "0,2";
	instituer_boucle($boucle, false);
	$res = calculer_select($boucle->select,$boucle->from,$boucle->from_type,$boucle->where,$boucle->join,$boucle->group,$boucle->order,$boucle->limit,$boucle->having,$table_objet,$id_table,$serveur);
	$a = array();
	while ($row = sql_fetch($res))
		$a[] = $row;
	if (count($a)!=1)
		return "";

	// si une sous-rubrique publie, pas de redirection
	// il faut passer par une boucle compilateur pour avoir les conditions de statut publie
	if (!$desc = $trouver_table("spip_rubriques", $serveur))
		return "";
	$id_table_objet = "id_rubrique";
	$id_table = $table_objet = "rubriques";
	$boucle = new Boucle();
	$boucle->show = $desc;
	$boucle->nom = 'sousrubriques_publies';
	$boucle->id_boucle = $id_table;
	$boucle->id_table = $id_table;
	$boucle->sql_serveur = $serveur;
	$boucle->select[] = $id_table_objet;
	$boucle->from[$table_objet] = "spip_rubriques";
	$boucle->where[] = $id_table.".id_parent=".intval($id_rubrique);
	$boucle->limit = "0,1";
	instituer_boucle($boucle, false);
	$res = calculer_select($boucle->select,$boucle->from,$boucle->from_type,$boucle->where,$boucle->join,$boucle->group,$boucle->order,$boucle->limit,$boucle->having,$table_objet,$id_table,$serveur);
	if (sql_fetch($res))
		return "";

	if (sql_countsel("spip_documents_liens","objet=".sql_quote('rubrique')." AND id_objet=".intval($id_rubrique)))
		return "";

	return intval(reset(reset($a)));
}

/**
 * Surcharge du filtre pagination pour utiliser le modele par defaut issu de la configuration de SarkaSpip
 */
function filtre_pagination($total, $nom, $position, $pas, $liste = true, $modele='', $connect='', $env=array()) {

	if (!$modele){
		if (!function_exists('lire_config'))
			include_spip('inc/config');
		$modele = lire_config('sarkaspip/modeles/modele_pagination','page');
	}
	return filtre_pagination_dist($total, $nom, $position, $pas, $liste, $modele, $connect, $env);
}

/**
 * Afficher la pagination ou non en fonction de la configuration de position et de seuil
 * et en fonction du nombre d'items affichés dans la liste ainsi que de la position (haut|top|debut)/(bas|bottom|fin)
 * @param int $nb_items
 * @param string $ou
 * @return string
 */
function affiche_pagination($nb_items, $ou='bottom'){
	if (!function_exists('lire_config'))
		include_spip('inc/config');

	$position = lire_config('sarkaspip/modeles/position_pagination','2');
	if ($position==3) return ' ';
	if ($position==1 AND !in_array($ou,array("bottom","bas","fin"))) return ' ';
	if ($position==2 AND !in_array($ou,array("top","haut","debut"))) return ' ';

	$seuil = lire_config('sarkaspip/modeles/seuil_double_pagination','');
	if (intval($seuil) AND $nb_items>$seuil)
		return ' ';

	return '';
}

// =======================================================================================================================================
// Filtre : typo_couleur
// =======================================================================================================================================
// Auteur : Smellup, inspire du filtre original de A. Pierard
// Fonction : permettant de modifier la couleur du texte ou de l'introduction d'un article
// Utilisation :                  
// 	- pour le redacteur : [rouge]xxxxxx[/rouge]
// 	- pour le webmaster : [(#TEXTE|typo_couleur)]
// =======================================================================================================================================
//
function typo_couleur($texte) {
	static $recherche = null;
	static $remplace = null;

	// vite si rien a faire
	if (strpos($texte,"[/")===false)
		return $texte;

	// Variables personnalisables par l'utilisateur
	// --> Activation (oui) ou desactivation (non) de la fonction
	$typo_couleur_active = 'oui';

	if (is_null($recherche)){
		// --> Couleurs transposees en classes CSS stylables
		$couleurs = array('noir','blanc','rouge','vert','bleu','jaune','gris','marron','violet','rose','orange');
		foreach ($couleurs as $c){
			$recherche[$c] = "/(\[$c\])(.*?)(\[\/$c\])/";
			$remplace[$c] = "<span class=\"$c\">\\2</span>";
		}
	}

	if ($typo_couleur_active == 'non') {
		$supprime = "\\2";
		$texte = preg_replace($recherche, $supprime, $texte);
	}
	else {
		$texte = preg_replace($recherche, $remplace, $texte);
	}
	return $texte;
}

// =======================================================================================================================================
// Filtre : libelle_statut
// =======================================================================================================================================
// Auteur: Smellup
// Fonction : Retourne le libelle multilangue du statut d'un auteur
// =======================================================================================================================================
//
function libelle_statut($statut) {
	return _T('sarkaspip:statut_'.$statut);
}
// FIN du Filtre : libelle_statut

function statut_forum($id_article) {

	$id = intval($id_article);
	$statut = 'non_autorise';

	// Forum active ou pas ?
	$accepter = 'non';
	$select = array('t1.accepter_forum');
	$from = array('spip_articles AS t1');
	$where = array('t1.id_article='.sql_quote($id));
	$result = sql_select($select, $from, $where);
	if ($row = sql_fetch($result)) 
		$accepter = $row['accepter_forum'];

	// Nombre messages de forum de l'article
	$from = array('spip_forum AS t1');
	$where = array('t1.id_objet='.sql_quote($id), 't1.objet='.sql_quote('article'));
	$nb = sql_countsel($from, $where);
	// Nombre de tickets termines pour le jalon
	if ($nb >= 1)
		$statut = ($accepter == 'non') ? 'ferme' : 'ouvert';
	else
		if ($accepter != 'non') $statut = 'ouvert';
	return $statut;
}
// FIN du Filtre : statut_forum


// =======================================================================================================================================
// Filtre : nettoyer_titre_sujet
// =======================================================================================================================================
// Auteur: Smellup 
// Fonction : Restaure le titre exact du sujet en supprimant le préfixe éventuel
// =======================================================================================================================================
//
function nettoyer_titre_sujet($titre, $resolu='') {
	$titre_nettoye = trim(preg_replace(',^\[(annonce|epingle)\](&nbsp;)*,UimsS', '', $titre));
	$titre_nettoye = trim(preg_replace(',_(verrouille|resolu)_,UimsS', '', $titre_nettoye));
	if ($resolu) $titre_nettoye = _T('sarkaspip:titre_sujet_resolu', array('titre' => $titre_nettoye)); 
	return $titre_nettoye;
}
// FIN du Filtre : nettoyer_titre_sujet



// =======================================================================================================================================
// Filtre : afficher_env
// =======================================================================================================================================
// Auteur: Smellup
// Fonction : Affiche la meta de configuration demandee sous un format lisible
//            Cette fonction existe déjà dans le plugin Dev sous le nom bel_env
// =======================================================================================================================================
//
// Cette fonction existe dans le plugin Dev.
// Si ce plugin est déjà actif on ne la redéfinit pas !
function afficher_env($env) {
	$env = str_replace(array('&quot;', '&#039;'), array('"', '\''), $env);
	if (is_array($env_tab = @unserialize($env))) {
		$env = $env_tab;
	}
	if (!is_array($env)) {
		return '';
	}
	$style = " style='border:1px solid #ddd;'";
	$res = "<table style='border-collapse:collapse;'>\n";
	foreach ($env as $nom => $val) {
		if (is_array($val) || is_array(@unserialize($val))) {
			$val = bel_env($val);
		}
		else {
			$val = entites_html($val);
		}
		$res .= "<tr>\n<td$style><strong>". entites_html($nom).
				"&nbsp;:&nbsp;</strong></td><td$style>" .$val. "</td>\n</tr>\n";
	}
	$res .= "</table>";
	return $res;
}

?>