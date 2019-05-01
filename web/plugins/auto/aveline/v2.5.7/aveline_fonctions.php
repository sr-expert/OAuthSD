<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

// Nécessaire pour le formulaire volant
if (!defined('_FORUM_AUTORISER_POST_ID_FORUM')) {
	define('_FORUM_AUTORISER_POST_ID_FORUM', true);
}

// Filtre pour afficher les statistiques d'un mot-clé
// [(#ID_MOT|statistiques_mot{#ID_GROUPE})] // passer #ID_GROUPE si possible (evite une requete)
function filtre_statistiques_mot_dist($id_mot, $id_groupe = '')
{
	include_spip('inc/mots');
	include_spip('inc/filtres');
	include_spip('base/abstract_sql');

	$id_mot = intval($id_mot);
	if (!$id_groupe) {
		$id_groupe = sql_getfetsel('id_groupe', 'spip_mots', 'id_mot='.sql_quote($id_mot));
	}
	$texte_lie = filtrer('objets_associes_mot', $id_mot, $id_groupe);
	$texte_lie = implode($texte_lie, ', ');

	return $texte_lie;
}

// Critère compteur_publie
// Provient de http://contrib.spip.net/Classer-les-articles-par-nombre-de-commentaires

function critere_compteur_publie($idb, &$boucles, $crit)
{
	$op = '';
	$boucle = &$boucles[$idb];
	$params = $crit->param;
	$type = array_shift($params);
	$type = $type[0]->texte;
	if (preg_match(',^(\w+)([<>=])([0-9]+)$,', $type, $r)) {
		$type = $r[1];
		$op = $r[2];
		$op_val = $r[3];
	}
	// champ que l'on doit compter
	$type_id = 'compt.'.id_table_objet($type);

	$type_requete = $boucle->type_requete;
	$id_table = $boucle->id_table.'.'.$boucle->primary;
	$boucle->select[] = 'COUNT('.$type_id.') AS compteur_'.$type;
	$boucle->from['compt'] = table_objet_sql($type);
	$boucle->from_type['compt'] = 'LEFT';
	// On passe par cette jointure pour que les articles avec 0 commentaires soient comptés
	// Merci notation !
	// on verifie que la table dispose d'un champ sur notre table
	// sinon on tente  objet id_objet
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($type);
	if (isset($desc['field'][ $boucle->primary ])) {
		# spip_forum du temps de id_article en vrai colonne
		# LEFT JOIN spip_forum AS compt ON ( compt.id_article = articles.id_article AND compt.statut='publie')
		$boucle->join['compt'] = array(
			"'$boucle->id_table'",
			"'$boucle->primary'",
			"'$boucle->primary'",
			"'compt.statut='.sql_quote('publie')",
		);
	} elseif (isset($desc['field']['objet']) and isset($desc['field']['id_objet'])) {
		# spip_forum spip 3
		# LEFT JOIN spip_forum AS compt ON ( compt.id_objet = articles.id_article AND compt.objet='article' AND compt.statut='publie')
		$objet = objet_type($boucle->primary);
		$boucle->join['compt'] = array(
			"'$boucle->id_table'",
			"'id_objet'",
			"'$boucle->primary'",
			"'compt.objet='.sql_quote('$objet').' AND compt.statut='.sql_quote('publie')",
		);
	} else {
		// bug...
		return array('aveline:zbug_erreur_critere', array('critere' => 'compteur_publie'));
	}
	$boucle->group[] = $id_table;
	if ($op) {
		$boucle->having[] = array("'".$op."'", "'compteur_".$type."'", $op_val);
	}
}

// On préfixe avec AVELINE pour éviter conflit avec d'autres plugins
// comme afficher_objet qui définit sont propre #COMPTEUR_ARTICLES

function balise_AVELINE_COMPTEUR_FORUM_dist($p)
{
	$p->code = '$Pile[$SP][\'compteur_forum\']';
	$p->interdire_scripts = false;

	return $p;
}

function balise_AVELINE_COMPTEUR_ARTICLES_dist($p)
{
	$p->code = '$Pile[$SP][\'compteur_articles\']';
	$p->interdire_scripts = false;

	return $p;
}
// Balise #ME
// Source : http://contrib.spip.net/me-Moi-and-myself

/***
 * (c)James 2006, Licence GNU/GPL
 * |me compare un id_auteur, par exemple,
 * d'une boucle FORUMS avec les auteurs d'un article
 * et renvoie la valeur booleenne true (vrai) si on trouve
 *  une correspondance
 * utilisation:
 * <div id="forum#ID_FORUM"[(#ID_ARTICLE|me{#ID_AUTEUR}|?{' ', ''})class="me"]>
 ***/
function me($id_article, $id_auteur, $sioui = true, $sinon = false)
{
	static $deja = false;
	static $auteurs = array();
	// id_article peut arriver avec 'article/8' (ou rubrique/3 et on sort)
	if (strpbrk($id_article, '/')) {
		list($objet, $id_article) = explode('/', $id_article);
		if ($objet != 'article') {
			return $sinon;
		}
	}
	if (!$deja) {
		$auteurs = sql_allfetsel('id_auteur', 'spip_auteurs_liens', array(
			'objet='.sql_quote('article'),
			'id_objet='.sql_quote($id_article), ));
		$auteurs = array_map('array_shift', $auteurs);
		$auteurs = array_map('intval', $auteurs);
		$deja = true;
	}

	return in_array($id_auteur, $auteurs) ? $sioui : $sinon;
}

function balise_ME($p)
{
	$b = index_boucle($p);
	if ($b === '') {
		$msg = array('spip:zbug_champ_hors_boucle',
				array('champ' => '#ME'),
			  );
		erreur_squelette($msg, $p);
		$p->code = "''";

		return $p;
	}

	// retrouver la description de la table
	$boucle = &$p->boucles[$b];
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($boucle->id_table);
	// s'il n'y a pas de champ id_article ,
	// chercher id_objet, objet
	if (isset($desc['field']['id_article'])) {
		// ancienne table spip_forum
		$p->code = 'me('.
			champ_sql('id_article', $p).', '.
			champ_sql('id_auteur', $p).', '.
			"'me', '')";
	} elseif (isset($desc['field']['objet']) and isset($desc['field']['id_objet'])) {
		// nouvelle table spip_forum
		$p->code = 'me('.
			champ_sql('objet', $p)." . '/' . ".champ_sql('id_objet', $p).', '.
			champ_sql('id_auteur', $p).', '.
			"'me', '')";
	} else {
		$msg = array('aveline:zbug_erreur_champ',
			array('champ' => '#ME'),
		);
		erreur_squelette($msg, $p);
	}

	return $p;
}

// #AVELINE_PAGINATION
// S'appelle dans une noisette ainsi [<p class="pagination">(#AVELINE_PAGINATION{'debut'})</p>] ou [<p class="pagination">(#AVELINE_PAGINATION{'fin'})</p>]
// Le YAML de la noisette doit contenir - 'inclure:inc-yaml/pagination.yaml'

function balise_AVELINE_PAGINATION_dist($p)
{
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];

	$pos = interprete_argument_balise(1, $p);

	$connect = $p->boucles[$b]->sql_serveur;
	$pas = $p->boucles[$b]->total_parties;
	$f_pagination = chercher_filtre('pagination');
	$type = $p->boucles[$b]->modificateur['debut_nom'];
	$modif = ($type[0] !== "'") ? "'debut'.$type"
	  : ("'debut".substr($type, 1));

	if ($pos == "'debut'") {
		$p->code = "(\$Pile[0]['selection']=='pagination' && (\$Pile[0]['position_pagination']=='debut' || \$Pile[0]['position_pagination']=='deux')) ? ".sprintf(CODE_PAGINATION, $f_pagination, $b, $type, $modif, $pas, true, "\$Pile[0]['style_pagination']", _q($connect), '')." : ''";
	} else {
		$p->code = "(\$Pile[0]['selection']=='pagination' && (\$Pile[0]['position_pagination']=='fin' || \$Pile[0]['position_pagination']=='deux')) ? ".sprintf(CODE_PAGINATION, $f_pagination, $b, $type, $modif, $pas, true, "\$Pile[0]['style_pagination']", _q($connect), '')." : ''";
	}

	return $p;
}

// Critère aveline_pagination
// Le YAML de la noisette doit contenir - 'inclure:inc-yaml/pagination.yaml'
// Ajouter {aveline_pagination} à la boucle

function critere_aveline_pagination_dist($idb, &$boucles, $crit)
{
	$boucle = &$boucles[$idb];
	// definition de la taille de la page
	$pas = "((@\$Pile[0]['selection']=='pagination') ? @\$Pile[0]['pas_pagination'] : ((@\$Pile[0]['selection']=='limite') ? @\$Pile[0]['limite'] : 1000000))";
	// On ajoute id_noisette à la variable de pagination
	$type = !isset($crit->param[0][1]) ? "'$idb'.'_'.\$Pile[0]['id_noisette']" : calculer_liste(array($crit->param[0][1]), array(), $boucles, $boucle->id_parent);
	$debut = ($type[0] !== "'") ? "'debut'.$type"
	  : ("'debut".substr($type, 1));

	$boucle->modificateur['debut_nom'] = $type;
	$partie =
		 // tester si le numero de page demande est de la forme '@yyy'
		 'isset($Pile[0]['.$debut.']) ? $Pile[0]['.$debut.'] : _request('.$debut.");\n"
		."\tif(substr(\$debut_boucle, 0, 1)=='@'){\n"
		."\t\t".'$debut_boucle = @$Pile[0]['.$debut.'] = quete_debut_pagination(\''.$boucle->primary.'\', @$Pile[0][\'@'.$boucle->primary.'\'] = substr($debut_boucle, 1), '.$pas.', $result, '._q($boucle->sql_serveur).');'."\n"
		."\t\t".'if (!sql_seek($result, 0, '._q($boucle->sql_serveur).")){\n"
		."\t\t\t".'@sql_free($result, '._q($boucle->sql_serveur).");\n"
		."\t\t\t".'$result = calculer_select($select, $from, $type, $where, $join, $groupby, $orderby, $limit, $having, $table, $id, $connect);'."\n"
		."\t\t}\n"
		."\t}\n"
		."\t".'$debut_boucle = intval($debut_boucle)';

	$boucle->total_parties = $pas;
	calculer_parties($boucles, $idb, $partie, 'p+');
	// ajouter la cle primaire dans le select pour pouvoir gerer la pagination referencee par @id
	// sauf si pas de primaire, ou si primaire composee
	// dans ce cas, on ne sait pas gerer une pagination indirecte
	$t = $boucle->id_table.'.'.$boucle->primary;
	if ($boucle->primary
		and !preg_match('/[,\s]/', $boucle->primary)
		and !in_array($t, $boucle->select)) {
		$boucle->select[] = $t;
	}
}

// Si le plugin notation n'est pas actif, on définit un critère {notation} ne faisant rien
// pour ne pas avoir d'erreur avec les boucles appelant ce critère
// on définit également moyenne (égal alors à id)
if (!defined('_DIR_PLUGIN_NOTATION')) {
	function critere_notation_dist($idb, &$boucles, $crit)
	{
		$boucle = &$boucles[$idb];
		$table = $boucle->id_table;
		$id = $boucle->primary;
		$boucle->select[] = "$table.$id AS moyenne";
	}
}

// #AVELINE_CHOIX_TRI
// Le YAML de la noisette doit contenir - 'inclure:inc-yaml/choix_tri-objet.yaml'
// Appel : #AVELINE_CHOIX_TRI{objet,debut_ou_fin}
// S'utilise en conjonction avec le critère tri de Bonux
// Les possibilités de tri pour chaque objet sont définis directement dans le code de la balise
// pour récupérer les variables d'environnement adéquates.

function balise_AVELINE_CHOIX_TRI_dist($p)
{
	$b = $p->nom_boucle ? $p->nom_boucle : $p->descr['id_mere'];

	// s'il n'y a pas de nom de boucle, on ne peut pas trier
	if ($b === '') {
		erreur_squelette(
			_T('spip:zbug_champ_hors_boucle',
				array('champ' => '#AVELINE_CHOIX_TRI')
			), $p->id_boucle);
		$p->code = "''";

		return $p;
	}
	$boucle = $p->boucles[$b];

	// s'il n'y a pas de tri_champ, c'est qu'on se trouve
	// dans un boucle recursive ou qu'on a oublie le critere {tri}
	if (!isset($boucle->modificateur['tri_champ'])) {
		erreur_squelette(
			_T('aveline:zbug_tri_sans_critere',
				array('champ' => '#AVELINE_CHOIX_TRI')
			), $p->id_boucle);
		$p->code = "''";

		return $p;
	}

	$suffixe = $boucle->modificateur['tri_nom'];
	$objet = interprete_argument_balise(1, $p);
	$pos = interprete_argument_balise(2, $p);
	$tri_actuel = $boucle->modificateur['tri_champ'];
	$sens_actuel = $boucle->modificateur['tri_sens'];

	// Définir les choix possibles
	$choix = 'array()';
	if ($objet == "'article'") {
		$choix = "array(
			array('affiche' => @\$Pile[0]['choix_tri_titre'], 'tri' => 'titre', 'sens' => 1, 'libelle' => _T('avelinepublic:par_titre')),
			array('affiche' => @\$Pile[0]['choix_tri_rang'], 'tri' => 'num titre', 'sens' => 1, 'libelle' => _T('avelinepublic:par_rang')),
			array('affiche' => @\$Pile[0]['choix_tri_popularite'], 'tri' => 'popularite', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_populaires')),
			array('affiche' => @\$Pile[0]['choix_tri_date'], 'tri' => 'date', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_recents')),
			array('affiche' => @\$Pile[0]['choix_tri_anciens'], 'tri' => 'date', 'sens' => 1, 'libelle' => _T('avelinepublic:les_plus_anciens')),
			array('affiche' => @\$Pile[0]['choix_tri_date_modif'], 'tri' => 'date_modif', 'sens' => -1, 'libelle' => _T('avelinepublic:modifies_recemment')),
			array('affiche' => @\$Pile[0]['choix_tri_commentes'], 'tri' => 'compteur_forum', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_commentes')),
			array('affiche' => @\$Pile[0]['choix_tri_visistes'], 'tri' => 'visites', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_visites')),
			array('affiche' => @\$Pile[0]['choix_tri_note'], 'tri' => 'moyenne', 'sens' => -1, 'libelle' => _T('avelinepublic:les_mieux_notes')),
			array('affiche' => @\$Pile[0]['recherche'], 'tri' => 'points', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_pertinents'))
		)";
	}
	if ($objet == "'breve'") {
		$choix = "array(
			array('affiche' => @\$Pile[0]['choix_tri_titre'], 'tri' => 'titre', 'sens' => 1, 'libelle' => _T('avelinepublic:par_titre')),
			array('affiche' => @\$Pile[0]['choix_tri_rang'], 'tri' => 'num titre', 'sens' => 1, 'libelle' => _T('avelinepublic:par_rang')),
			array('affiche' => @\$Pile[0]['choix_tri_date'], 'tri' => 'date_heure', 'sens' => -1, 'libelle' => _T('avelinepublic:b_les_plus_recentes')),
			array('affiche' => @\$Pile[0]['choix_tri_anciens'], 'tri' => 'date_heure', 'sens' => 1, 'libelle' => _T('avelinepublic:b_les_plus_anciennes')),
			array('affiche' => @\$Pile[0]['choix_tri_commentes'], 'tri' => 'compteur_forum', 'sens' => -1, 'libelle' => _T('avelinepublic:b_les_plus_commentees')),
			array('affiche' => @\$Pile[0]['recherche'], 'tri' => 'points', 'sens' => -1, 'libelle' => _T('avelinepublic:b_les_plus_pertinentes'))
		)";
	}
	if ($objet == "'auteur'") {
		$choix = "array(
			array('affiche' => @\$Pile[0]['choix_tri_nom'], 'tri' => 'nom', 'sens' => 1, 'libelle' => _T('avelinepublic:par_nom')),
			array('affiche' => @\$Pile[0]['choix_tri_nb_articles'], 'tri' => 'compteur_articles', 'sens' => -1, 'libelle' => _T('avelinepublic:par_nb_articles')),
			array('affiche' => @\$Pile[0]['recherche'], 'tri' => 'points', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_pertinents'))
		)";
	}
	if ($objet == "'rubrique'") {
		$choix = "array(
			array('affiche' => @\$Pile[0]['choix_tri_titre'], 'tri' => 'titre', 'sens' => 1, 'libelle' => _T('avelinepublic:par_titre')),
			array('affiche' => @\$Pile[0]['choix_tri_commentes'], 'tri' => 'compteur_forum', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_commentes')),
			array('affiche' => @\$Pile[0]['choix_tri_date_heure'], 'tri' => 'date_heure', 'sens' => -1, 'libelle' => _T('avelinepublic:modifiees_recemment')),
			array('affiche' => @\$Pile[0]['choix_tri_note'], 'tri' => 'moyenne', 'sens' => -1, 'libelle' => _T('avelinepublic:les_mieux_notes')),
			array('affiche' => @\$Pile[0]['recherche'], 'tri' => 'points', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_pertinents'))
		)";
	}
	if ($objet == "'evenement'") {
		$choix = "array(
			array('affiche' => @\$Pile[0]['choix_tri_date'], 'tri' => 'date_debut', 'sens' => -1, 'libelle' => _T('avelinepublic:par_date_decroissante')),
			array('affiche' => @\$Pile[0]['choix_tri_anciens'], 'tri' => 'date_debut', 'sens' => 1, 'libelle' => _T('avelinepublic:par_date_croissante')),
			array('affiche' => @\$Pile[0]['choix_tri_titre'], 'tri' => 'titre', 'sens' => 1, 'libelle' => _T('avelinepublic:par_titre')),
			array('affiche' => @\$Pile[0]['recherche'], 'tri' => 'points', 'sens' => -1, 'libelle' => _T('avelinepublic:les_plus_pertinents'))
		)";
	}

	$p->code = "calculer_balise_AVELINE_CHOIX_TRI($suffixe, $choix, $pos, $tri_actuel, $sens_actuel, @\$Pile[0]['choix_tri'], @\$Pile[0]['position_choix_tri'])";

	return $p;
}

function calculer_balise_AVELINE_CHOIX_TRI($suffixe, $choix, $pos, $tri_actuel, $sens_actuel, $choix_tri, $position_choix_tri)
{
	// Doit-on afficher les tri perso ?
	if (!$choix_tri || ($pos == 'debut' && $position_choix_tri == 'fin') || ($pos == 'fin' && $position_choix_tri == 'debut')) {
		return '';
	}

	$retour = array();
	foreach ($choix as $c) {
		// Cas où on demande la note moyenne et que notation n'est pas activé
		if ($c['tri'] == 'moyenne' && !defined('_DIR_PLUGIN_NOTATION')) {
			$c['affiche'] = '';
		}
		if ($c['affiche']) {
			$lien = parametre_url(self(), 'tri'.$suffixe, $c['tri']);
			$lien = parametre_url($lien, 'sens'.$suffixe, $c['sens']);
			$retour[] = lien_ou_expose($lien, $c['libelle'], $c['tri'] == $tri_actuel && $c['sens'] == $sens_actuel);
		}
	}

	return implode(' <span class="sep separateur">|</span> ', $retour);
}

// Surcharge du critère tri pour ajouter id_noisette aux variables de personnalisation du tri
/**
 * {tri [champ_par_defaut][,sens_par_defaut][,nom_variable]}
 * champ_par_defaut : un champ de la table sql
 * sens_par_defaut : -1 ou inverse pour decroissant, 1 ou direct pour croissant
 * nom_variable : nom de la variable utilisee (par defaut tri_nomboucle).
 *
 * {tri titre}
 * {tri titre,inverse}
 * {tri titre,-1}
 * {tri titre,-1,truc}
 *
 * @param unknown_type $idb
 * @param unknown_type $boucles
 * @param unknown_type $crit
 */
function critere_tri($idb, &$boucles, $crit)
{
	$boucle = &$boucles[$idb];

	// definition du champ par defaut
	$_champ_defaut = !isset($crit->param[0][0]) ? "''"
		: calculer_liste(array($crit->param[0][0]), array(), $boucles, $boucle->id_parent);
	$_sens_defaut = !isset($crit->param[1][0]) ? '1'
		: calculer_liste(array($crit->param[1][0]), array(), $boucles, $boucle->id_parent);
	// On ajoute _id_noisette à la variable de tri
	$_variable = !isset($crit->param[2][0]) ? "'$idb'.'_'.@\$Pile[0]['id_noisette']"
		: calculer_liste(array($crit->param[2][0]), array(), $boucles, $boucle->id_parent);

	$_tri = "((\$t=(isset(\$Pile[0]['tri'.$_variable]))?\$Pile[0]['tri'.$_variable]:$_champ_defaut)?tri_protege_champ(\$t):'')";

	$_sens_defaut = "(is_array(\$s=$_sens_defaut)?(isset(\$s[\$st=$_tri])?\$s[\$st]:reset(\$s)):\$s)";
	$_sens = "((intval(\$t=(isset(\$Pile[0]['sens'.$_variable]))?\$Pile[0]['sens'.$_variable]:$_sens_defaut)==-1 OR \$t=='inverse')?-1:1)";

	$boucle->modificateur['tri_champ'] = $_tri;
	$boucle->modificateur['tri_sens'] = $_sens;
	$boucle->modificateur['tri_nom'] = $_variable;
	// faut il inserer un test sur l'existence de $tri parmi les champs de la table ?
	// evite des erreurs sql, mais peut empecher des tri sur jointure ...
	$boucle->hash .= "
	\$senstri = '';
	\$tri = $_tri;
	if (\$tri){
		\$senstri = $_sens;
		\$senstri = (\$senstri<0)?' DESC':'';
	};
	";
	$boucle->select[] = '".tri_champ_select($tri)."';
	$boucle->order[] = "tri_champ_order(\$tri,\$command['from']).\$senstri";
}

// Critère aveline_branche
// Le YAML de la noisette doit contenir - 'inclure:inc-yaml/branche-objet.yaml'
// Ajouter {aveline_branche} à la boucle
function critere_aveline_branche_dist($idb, &$boucles, $crit)
{
	$boucle = &$boucles[$idb];

	$id_article = calculer_argument_precedent($idb, 'id_article', $boucles);
	$id_syndic = calculer_argument_precedent($idb, 'id_syndic', $boucles);
	$id_rubrique = calculer_argument_precedent($idb, 'id_rubrique', $boucles);
	$id_secteur = calculer_argument_precedent($idb, 'id_secteur', $boucles);

	//Trouver une jointure
	$desc = $boucle->show;
	//Seulement si necessaire
	if (!array_key_exists('id_rubrique', $desc['field'])) {
		$cle_rubrique = trouver_jointure_champ('id_rubrique', $boucle);
	} else {
		$cle_rubrique = $boucle->id_table;
	}

	$table = $boucle->id_table;
	$primary = $boucle->primary;

	$boucle->where[] = "aveline_calcul_branche($id_article, $id_syndic, $id_rubrique, $id_secteur, '$cle_rubrique', '$table', '$primary', @\$Pile[0]['branche'], @\$Pile[0]['rubrique_specifique'], @\$Pile[0]['branche_specifique'], @\$Pile[0]['secteur_specifique'], @\$Pile[0]['article_specifique'], @\$Pile[0]['site_specifique'], @\$Pile[0]['filtre_rub'], @\$Pile[0]['filtre_art'])";
}

function aveline_calcul_branche($id_article, $id_syndic, $id_rubrique, $id_secteur, $cle_rubrique, $table, $primary, $branche, $rubrique_specifique, $branche_specifique, $secteur_specifique, $article_specifique, $site_specifique, $filtre_rub, $filtre_art)
{
	if ($filtre_rub) {
		$branche = 'branche_specifique';
		$branche_specifique = 'rubrique|'.$filtre_rub;
	}
	if ($filtre_art) {
		$branche = 'article_specifique';
		$article_specifique = 'article|'.$filtre_art;
	}
	if ($branche == 'meme_rubrique_indirects' and !defined('_DIR_PLUGIN_POLYHIER')) {
		$branche = '';
	}
	if ($branche == 'meme_rubrique_complete' and !defined('_DIR_PLUGIN_POLYHIER')) {
		$branche = 'meme_rubrique';
	}
	if ($branche == 'rubrique_specifique_indirects' and !defined('_DIR_PLUGIN_POLYHIER')) {
		$branche = '';
	}
	if ($branche == 'rubrique_specifique_complete' and !defined('_DIR_PLUGIN_POLYHIER')) {
		$branche = 'rubrique_specifique';
	}
	if ($branche == 'branche_complete' and !defined('_DIR_PLUGIN_POLYHIER')) {
		$branche = 'branche_actuelle';
	}
	if ($branche == 'branche_specifique_complete' and !defined('_DIR_PLUGIN_POLYHIER')) {
		$branche = 'branche_specifique';
	}
	$objet = objet_type($table);
	switch ($table) {
		case 'articles':
			$cle_secteur = $table;
			$champ_secteur = 'id_secteur';
			break;
		case 'breves':
			$cle_secteur = $table;
			$champ_secteur = 'id_rubrique';
	}
	switch ($branche) {
		case 'meme_article':
			return $id_article ? array('=', "$table.id_article", $id_article) : array();
			break;
		case 'article_specifique':
			return $article_specifique ? sql_in("$table.id_article", picker_selected($article_specifique, 'article')) : array();
			break;
		case 'meme_site':
			return $id_syndic ? array('=', "$table.id_syndic", $id_syndic) : array();
			break;
		case 'site_specifique':
			return $site_specifique ? sql_in("$table.id_syndic", $site_specifique) : array();
			break;
		case 'meme_rubrique':
			return $id_rubrique ? array('=', "$cle_rubrique.id_rubrique", $id_rubrique) : array();
			break;
		case 'meme_rubrique_complete':
			$where1 = array('=',"$cle_rubrique.id_rubrique",$id_rubrique); // Enfants directs
			$sous = sql_allfetsel('rl.id_objet', 'spip_rubriques_liens as rl', sql_in('rl.id_parent', $id_rubrique)." AND objet='$objet'"); // Enfants indirects
			$sous = array_map('reset', $sous);
			$where2 = sql_in($table.'.'.$primary, $sous);

			return $id_rubrique ? array('OR', $where1, $where2) : array();
			break;
		case 'meme_rubrique_indirects':
			$sous = sql_allfetsel('rl.id_objet', 'spip_rubriques_liens as rl', sql_in('rl.id_parent', $id_rubrique)." AND objet='$objet'"); // Enfants indirects
			$sous = array_map('reset', $sous);
			$where = sql_in($table.'.'.$primary, $sous);

			return $id_rubrique ? $where : array();
			break;
		case 'rubrique_specifique':
			return $rubrique_specifique ? sql_in("$cle_rubrique.id_rubrique", picker_selected($rubrique_specifique, 'rubrique')) : array();
			break;
		case 'rubrique_specifique_indirects':
			$r = picker_selected($rubrique_specifique, 'rubrique');
			$sous = sql_allfetsel('rl.id_objet', 'spip_rubriques_liens as rl', sql_in('rl.id_parent', $r)." AND objet='$objet'"); // Enfants indirects
			$sous = array_map('reset', $sous);
			$where = sql_in($table.'.'.$primary, $sous);

			return $rubrique_specifique ? $where : array();
			break;
		case 'rubrique_specifique_complete':
			$r = picker_selected($rubrique_specifique, 'rubrique');
			$where1 = sql_in("$cle_rubrique.id_rubrique", $r); // Enfants directs
			$sous = sql_allfetsel('rl.id_objet', 'spip_rubriques_liens as rl', sql_in('rl.id_parent', $r)." AND objet='$objet'"); // Enfants indirects
			$sous = array_map('reset', $sous);
			$where2 = sql_in($table.'.'.$primary, $sous);

			return $rubrique_specifique ? array('OR', $where1, $where2) : array();
			break;
		case 'branche_actuelle':
			return $id_rubrique ? sql_in("$cle_rubrique.id_rubrique", calcul_branche_in($id_rubrique)) : array();
			break;
		case 'branche_complete':
			$b = calcul_branche_polyhier_in($id_rubrique);
			$where1 = sql_in("$cle_rubrique.id_rubrique", $b);
			$sous = sql_allfetsel('rl.id_objet', 'spip_rubriques_liens as rl', sql_in('rl.id_parent', $b)." AND objet='$objet'");
			$sous = array_map('reset', $sous);
			$where2 = sql_in($table.'.'.$primary, $sous);

			return $id_rubrique ? array('OR', $where1, $where2) : array();
			break;
		case 'branche_specifique':
			return $branche_specifique ? sql_in("$cle_rubrique.id_rubrique", calcul_branche_in(picker_selected($branche_specifique, 'rubrique'))) : array();
			break;
		case 'branche_specifique_complete':
			$b = calcul_branche_polyhier_in(picker_selected($branche_specifique, 'rubrique'));
			$where1 = sql_in("$cle_rubrique.id_rubrique", $b);
			$sous = sql_allfetsel('rl.id_objet', 'spip_rubriques_liens as rl', sql_in('rl.id_parent', $b)." AND objet='$objet'");
			$sous = array_map('reset', $sous);
			$where2 = sql_in($table.'.'.$primary, $sous);

			return $branche_specifique ? array('OR', $where1, $where2) : array();
			break;
		case 'meme_secteur':
			return $id_secteur ? array('=', "$cle_secteur.$champ_secteur", $id_secteur) : array();
			break;
		case 'secteur_specifique':
			return $secteur_specifique ? sql_in("$cle_secteur.$champ_secteur", $secteur_specifique) : array();
			break;
		default:
			return array();
	}
}

// Critère aveline_lang
// Le YAML de la noisette doit contenir - 'inclure:inc-yaml/restreindre_langue.yaml''
// Ajouter {aveline_lang} à la boucle
// N'appliquer qu'à des tables ayant un champ 'lang'
function critere_aveline_lang_dist($idb, &$boucles, $crit)
{
	$boucle = &$boucles[$idb];

	//Trouver une jointure (pour les évènements par exemple)
	$desc = $boucle->show;
	//Seulement si necessaire
	if (!array_key_exists('lang', $desc['field'])) {
		$id_table = trouver_jointure_champ('lang', $boucle);
	} else {
		$id_table = $boucle->id_table;
	}

	$boucle->where[] = "aveline_calcul_lang('$id_table', @\$Pile[0]['restreindre_langue'], @\$Pile[0]['lang'])";
}

function aveline_calcul_lang($id_table, $restreindre_langue, $lang)
{
	if ($restreindre_langue) {
		return array('=', "$id_table.lang", sql_quote($lang));
	} else {
		return array();
	}
}

// Critère aveline_exclure_objet_encours
// Le YAML de la noisette doit contenir - 'inclure:inc-yaml/exclure_objet_en_cours-objet.yaml''
// Ajouter {aveline_exclure_objet_encours} à la boucle
function critere_aveline_exclure_objet_encours_dist($idb, &$boucles, $crit)
{
	$boucle = &$boucles[$idb];
	$id_table = $boucle->id_table;
	$id_objet = $boucle->primary;

	$boucle->where[] = "aveline_calcul_exclure_objet('$id_table', '$id_objet', @\$Pile[0]['$id_objet'], @\$Pile[0]['exclure_objet_en_cours'])";
}

function aveline_calcul_exclure_objet($id_table, $id_objet, $id_en_cours, $exclure_objet_en_cours)
{
	if ($exclure_objet_en_cours) {
		return array('!=', "$id_table.$id_objet", intval($id_en_cours));
	} else {
		return array();
	}
}

/**
 * Retourne le champ date d'une table.
 *
 * @param string $type
 *                     Nom de la boucle (ex: articles)
 *                     Generalement $boucle->type_requete
 *
 * @return string
 *                Champ date, sinon ''
 **/
function aveline_retrouver_champ_date($type)
{
	static $dates = array();
	if (isset($dates[$type])) {
		return $dates[$type];
	}

	// retrouver le champ date
	$trouver_table = charger_fonction('trouver_table', 'base');
	$desc = $trouver_table($type);
	// dans la description, sinon dans l'ancienne globale (deprecie)
	$date = isset($desc['date']) ?
		$desc['date'] :
		(isset($GLOBALS['table_date'][$type]) ?
			$GLOBALS['table_date'][$type] :
			'');

	return $dates[$type] = $date;
}

// Critère aveline_selecteurs_archives_mois et aveline_selecteurs_archives_annees
// Utilisée pour les sélecteurs d'archives
// Balise disponible #NB_ARCHIVES
function critere_aveline_selecteur_archives_mois_dist($idb, &$boucles, $crit)
{
	$boucle = &$boucles[$idb];
	if ($date = aveline_retrouver_champ_date($boucle->type_requete)) {
		$champ_date = $boucle->id_table.'.'.$date;
		$id_objet = $boucle->id_table.'.'.$boucle->primary;
		$boucle->select[] = "COUNT($id_objet) AS nb_archives";
		$boucle->group[] = "YEAR($champ_date)";
		$boucle->group[] = "MONTH($champ_date)";
	} else {
		// bug...
		return array('aveline:zbug_erreur_critere', array('critere' => 'aveline_selecteur_archives_mois'));
	}
}

function critere_aveline_selecteur_archives_annee_dist($idb, &$boucles, $crit)
{
	$boucle = &$boucles[$idb];
	if ($date = aveline_retrouver_champ_date($boucle->type_requete)) {
		$champ_date = $boucle->id_table.'.'.$date;
		$id_objet = $boucle->id_table.'.'.$boucle->primary;
		$boucle->select[] = "COUNT($id_objet) AS nb_archives";
		$boucle->group[] = "YEAR($champ_date)";
	} else {
		// bug...
		return array('aveline:zbug_erreur_critere', array('critere' => 'aveline_selecteur_archives_annee'));
	}
}

/** Balise #NB_ARCHIVES associee aux criteres aveline_selecteur_archives_mois et aveline_selecteur_archives_annees */
function balise_NB_ARCHIVES_dist($p)
{
	$p->code = '$Pile[$SP][\'nb_archives\']';
	$p->interdire_scripts = false;

	return $p;
}

/**
 * Calculer l'initiale d'un nom ou d'un titre.
 *
 * @param <type> $nom
 *
 * @return <type>
 */
function aveline_initiale($nom)
{
	return spip_substr(trim(strtoupper($nom)), 0, 1);
}

/**
 * Afficher l'initiale pour la navigation par lettres
 * adptée du plugin afficher_objets.
 *
 * @staticvar string $memo
 *
 * @param <type> $url
 * @param <type> $initiale
 * @param <type> $compteur
 * @param <type> $debut
 * @param <type> $pas
 *
 * @return <type>
 */
function aveline_afficher_initiale($url, $initiale, $compteur, $debut, $pas)
{
	static $memo = null;
	$res = '';
	if (!$memo
		or (!$initiale and !$url)
		or ($initiale !== $memo['initiale'])
		) {
		$newcompt = intval(floor(($compteur - 1) / $pas) * $pas);
		if ($memo) {
			$on = (($memo['compteur'] <= $debut)
				and (
						$newcompt > $debut or ($newcompt == $debut and $newcompt == $memo['compteur'])
						));
			$res = lien_ou_expose($memo['url'], $memo['initiale'], $on, 'lien_pagination');
		}
		if ($initiale) {
			$memo = array('initiale' => $initiale,'url' => $url,'compteur' => $newcompt);
		}
	}

	return $res;
}

// Filtre d'affichage de date
// http://contrib.spip.net/Utilisation-des-filtres-de-date
function filtre_aveline_affdate_dist($date, $format = 'affdate')
{
	if ($format == null) {
		$f_affdate = chercher_filtre('affdate');

		return $f_affdate($date);
	}
	switch ($format) {
		case 'affdate':                // affiche la date sous forme de texte (1er juillet 2012)
		case 'affdate_jourcourt':    // affiche le numéro du jour et le nom du mois, si la date est dans l’année en cours (1er juillet),
											// si la date n’est pas dans l’année en cours, on rajoute l’année (1er juillet 2010)
		case 'affdate_court':        // affiche le numéro du jour et le nom du mois (si la date est dans l’année en cours) (1er juillet),
											// si la date n’est pas dans l’année en cours, on affiche le nom du mois et l’année (juillet 2010)
		case 'affdate_mois_annee': // affiche seulement le mois et l’année (juillet 2012)
			$f_affdate = chercher_filtre($format);

			return $f_affdate($date);
			break;
		case 'annee':                    // affiche uniquement l'annee (2012)
			$f_annee = chercher_filtre('annee');

			return $f_annee($date);
			break;
		case 'nom_jour_affdate':    // Idem affdate précédé du nom du jour (dimanche 1er juillet 2012)
			$f_affdate = chercher_filtre('affdate');
			$f_nom_jour = chercher_filtre('nom_jour');

			return $f_nom_jour($date).' '.$f_affdate($date);
			break;
		case 'numerique_slash':        // affiche la date sous forme numerique avec un slash séparateur (01/07/2012)
			$f_annee = chercher_filtre('affdate');

			return $f_annee($date, 'd/m/Y');
			break;
		case 'numerique_tiret':        // affiche la date sous forme numerique avec un tiret séparateur (01-07-2012)
			$f_annee = chercher_filtre('affdate');

			return $f_annee($date, 'd-m-Y');
			break;
		default:
			$f_affdate = chercher_filtre('affdate');

			return f_affdate($date);
			break;
	}
}
