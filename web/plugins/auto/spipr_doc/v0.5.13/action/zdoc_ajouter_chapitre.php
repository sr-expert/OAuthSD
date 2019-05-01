<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_zdoc_ajouter_chapitre($arg=null) {
	if (is_null($arg)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$arg = $securiser_action();
	}

	$id_article_precedent = 0;
	$arg = explode("-",$arg);
	$id_rubrique = array_shift($arg);
	if (count($arg))
		$id_article_precedent = array_shift($arg);

	$set = array(
		'id_rubrique'=>$id_rubrique,
		'titre' => 'Nouveau Chapitre',
		'texte' => '{texte de ce chapitre}',
		'statut' => 'publie'
	);

	$numero = 1; // par defaut en tete
	if ($row = sql_fetsel('*','spip_articles','id_article='.$id_article_precedent)){
		include_spip('inc/filtres');
		$numero = recuperer_numero($row['titre'])+1;
	}
	$set['titre'] = $numero.". ".$set['titre'];

	include_spip("action/editer_article");
	$id_article = article_inserer($set['id_rubrique']);
	article_modifier($id_article,$set);

	// renumeroter les chapitres
	include_spip("inc/numeroter");
	numero_numeroter_rubrique($set['id_rubrique'],'article');


	$GLOBALS['redirect'] = generer_url_entite($id_article,'article');
}

?>