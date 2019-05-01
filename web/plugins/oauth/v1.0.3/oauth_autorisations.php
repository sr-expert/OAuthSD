<?php
/**
 * Définit les autorisations du plugin OAuth 2.0
 *
 * @plugin     OAuth 2.0
 * @copyright  2016
 * @author     DnC
 * @licence    GNU/GPL
 * @package    SPIP\Oauth\Autorisations
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Fonction d'appel pour le pipeline
 * @pipeline autoriser */
function oauth_autoriser(){}


// -----------------
// Objet users




/**
 * Autorisation de créer (user)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_user_creer_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], array('0minirezo', '1comite')); 
}

/**
 * Autorisation de voir (user)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_user_voir_dist($faire, $type, $id, $qui, $opt) {
	return ($qui['statut'] == '0minirezo' and !$qui['restreint'])
		or ($auteurs = oauth_auteurs_objet('user', $id) and in_array($qui['id_auteur'], $auteurs));
}

/**
 * Autorisation de modifier (user)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_user_modifier_dist($faire, $type, $id, $qui, $opt) {
	return ($qui['statut'] == '0minirezo' and !$qui['restreint'])
		or ($auteurs = oauth_auteurs_objet('user', $id) and in_array($qui['id_auteur'], $auteurs));
}

/**
 * Autorisation de supprimer (user)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_user_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return ($qui['statut'] == '0minirezo' and !$qui['restreint'])
		or ($auteurs = oauth_auteurs_objet('user', $id) and in_array($qui['id_auteur'], $auteurs));
}

// -----------------
// Objet clients


/**
 * Autorisation de voir un élément de menu (clients)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_clients_menu_dist($faire, $type, $id, $qui, $opt){
	return true;
} 


/**
 * Autorisation de voir le bouton d'accès rapide de création (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_clientcreer_menu_dist($faire, $type, $id, $qui, $opt){
	return autoriser('creer', 'client', '', $qui, $opt);
} 

/**
 * Autorisation de créer (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_client_creer_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], array('0minirezo', '1comite')); 
}

/**
 * Autorisation de voir (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_client_voir_dist($faire, $type, $id, $qui, $opt) {
	return ($qui['statut'] == '0minirezo' and !$qui['restreint'])
		or ($auteurs = oauth_auteurs_objet('client', $id) and in_array($qui['id_auteur'], $auteurs));
}

/**
 * Autorisation de modifier (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_client_modifier_dist($faire, $type, $id, $qui, $opt) {
	return ($qui['statut'] == '0minirezo' and !$qui['restreint'])
		or ($auteurs = oauth_auteurs_objet('client', $id) and in_array($qui['id_auteur'], $auteurs));
}

/**
 * Autorisation de supprimer (client)
 *
 * @param  string $faire Action demandée
 * @param  string $type  Type d'objet sur lequel appliquer l'action
 * @param  int    $id    Identifiant de l'objet
 * @param  array  $qui   Description de l'auteur demandant l'autorisation
 * @param  array  $opt   Options de cette autorisation
 * @return bool          true s'il a le droit, false sinon
**/
function autoriser_client_supprimer_dist($faire, $type, $id, $qui, $opt) {
	return ($qui['statut'] == '0minirezo' and !$qui['restreint'])
		or ($auteurs = oauth_auteurs_objet('client', $id) and in_array($qui['id_auteur'], $auteurs));
}


/**
 * Lister les auteurs liés à un objet
 *
 * @param int $objet Type de l'objet
 * @param int $id_objet Identifiant de l'objet
 * @return array        Liste des id_auteur trouvés
 */
function oauth_auteurs_objet($objet, $id_objet) {
	$auteurs = sql_allfetsel('id_auteur', 'spip_auteurs_liens', array('objet = ' . sql_quote($objet), 'id_objet = ' . intval($id_objet)));
	if (is_array($auteurs)) {
		return array_map('reset', $auteurs);
	}
	return array();
}