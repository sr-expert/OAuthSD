<?php

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_zdoc_ajouter_page($id_rubrique_precedente=null) {
	if (is_null($id_rubrique_precedente)){
		$securiser_action = charger_fonction('securiser_action', 'inc');
		$id_rubrique_precedente = $securiser_action();
	}

	$set = array(
		'id_parent'=>0,
		'titre' => 'Nouvelle page',
	);

	$numero = 1; // par defaut en tete
	if ($row = sql_fetsel('*','spip_rubriques','id_rubrique='.$id_rubrique_precedente)){
		$set['id_parent'] = $row['id_parent'];
		include_spip('inc/filtres');
		$numero = recuperer_numero($row['titre'])+1;
	}
	$set['titre'] = $numero.". ".$set['titre'];

	include_spip("action/editer_rubrique");
	$id_rubrique = rubrique_inserer($set['id_parent']);
	rubrique_modifier($id_rubrique,$set);

	// renumeroter les pages
	include_spip("inc/numeroter");
	numero_numeroter_rubrique($set['id_parent'],'rubrique');

	// ajouter automatiquement le premier chapitre
	$zdoc_ajouter_chapitre = charger_fonction("zdoc_ajouter_chapitre","action");
	$zdoc_ajouter_chapitre($id_rubrique);

}

?>