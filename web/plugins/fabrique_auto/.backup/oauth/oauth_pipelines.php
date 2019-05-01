<?php
/**
 * Utilisations de pipelines par OAuth 2.0
 *
 * @plugin     OAuth 2.0
 * @copyright  2018
 * @author     DnC
 * @licence    GNU/GPL
 * @package    SPIP\Oauth\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) return;



/**
 * Ajout de contenu sur certaines pages,
 * notamment des formulaires de liaisons entre objets
 *
 * @pipeline affiche_milieu
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function oauth_affiche_milieu($flux) {
	$texte = "";
	$e = trouver_objet_exec($flux['args']['exec']);

	// auteurs sur les users, clients
	if (!$e['edition'] AND in_array($e['type'], array('user', 'client'))) {
		$texte .= recuperer_fond('prive/objets/editer/liens', array(
			'table_source' => 'auteurs',
			'objet' => $e['type'],
			'id_objet' => $flux['args'][$e['id_table_objet']]
		));
	}



	if ($texte) {
		if ($p=strpos($flux['data'],"<!--affiche_milieu-->"))
			$flux['data'] = substr_replace($flux['data'],$texte,$p,0);
		else
			$flux['data'] .= $texte;
	}

	return $flux;
}


/**
 * Ajout de liste sur la vue d'un auteur
 *
 * @pipeline affiche_auteurs_interventions
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function oauth_affiche_auteurs_interventions($flux) {
	if ($id_auteur = intval($flux['args']['id_auteur'])) {

		$flux['data'] .= recuperer_fond('prive/objets/liste/clients', array(
			'id_auteur' => $id_auteur,
			'titre' => _T('client:info_clients_auteur')
		), array('ajax' => true));

	}
	return $flux;
}



/**
 * Optimiser la base de données 
 * 
 * Supprime les objets à la poubelle.
 * Supprime les objets à la poubelle.
 *
 * @pipeline optimiser_base_disparus
 * @param  array $flux Données du pipeline
 * @return array       Données du pipeline
 */
function oauth_optimiser_base_disparus($flux){

	sql_delete("spip_users", "statut='poubelle' AND maj < " . $flux['args']['date']);

	sql_delete("spip_clients", "statut='poubelle' AND maj < " . $flux['args']['date']);

	return $flux;
}