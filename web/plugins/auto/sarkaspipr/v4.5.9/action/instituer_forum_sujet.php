<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

function action_instituer_forum_sujet_dist() {
	static $statuts_sujet = array(
							'verrou_on' => 'verrouille', 'verrou_off' => 'verrouille', 
							'resolu_on' => 'resolu', 'resolu_off' => 'resolu');

	$securiser_action = charger_fonction('securiser_action', 'inc');
	$args = $securiser_action();

	list($objet, $id_forum, $action) = explode('/',$args);
	if ((!$action) OR (!array_key_exists($action, $statuts_sujet))) return;

	if ($id_forum = intval($id_forum)) {
		$titre = sql_getfetsel('titre', 'spip_forum', 'id_forum=' . sql_quote($id_forum));
		$pattern = '_' . $statuts_sujet[$action] . '_';

		// On supprime systematique le statut demande. Ainsi si l'action demandee est d'enlever un
		// statut c'est fait sinon on evite de rajouter le meme statut
		$titre_modifie = trim(preg_replace(",$pattern,UimsS", '', $titre));
		// Si l'action demandee est de positionner un nouveau statut on le rajoute au titre
		if (($action == 'verrou_on') OR ($action == 'resolu_on')) {
			$titre_modifie .= $pattern;
		}

		sql_updateq('spip_forum', array('titre' => $titre_modifie), 'id_forum =' . sql_quote($id_forum));

		// Invalider les pages comportant ce forum
		include_spip('inc/invalideur');
		suivre_invalideur("id='id_forum/$id_forum'");
	}
}

?>