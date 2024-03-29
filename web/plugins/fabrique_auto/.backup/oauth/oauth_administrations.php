<?php
/**
 * Fichier gérant l'installation et désinstallation du plugin OAuth 2.0
 *
 * @plugin     OAuth 2.0
 * @copyright  2018
 * @author     DnC
 * @licence    GNU/GPL
 * @package    SPIP\Oauth\Installation
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Fonction d'installation et de mise à jour du plugin OAuth 2.0.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @param string $version_cible
 *     Version du schéma de données dans ce plugin (déclaré dans paquet.xml)
 * @return void
**/
function oauth_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	$maj['create'] = array(array('maj_tables', array('spip_users', 'spip_clients')));

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}


/**
 * Fonction de désinstallation du plugin OAuth 2.0.
 *
 * @param string $nom_meta_base_version
 *     Nom de la meta informant de la version du schéma de données du plugin installé dans SPIP
 * @return void
**/
function oauth_vider_tables($nom_meta_base_version) {

	sql_drop_table("spip_users");
	sql_drop_table("spip_clients");

	# Nettoyer les liens courants (le génie optimiser_base_disparus se chargera de nettoyer toutes les tables de liens), 
	sql_delete("spip_documents_liens",       sql_in("objet", array('user', 'client')));
	sql_delete("spip_mots_liens",            sql_in("objet", array('user', 'client')));
	sql_delete("spip_auteurs_liens",         sql_in("objet", array('user', 'client')));
	# Nettoyer les versionnages et forums
	sql_delete("spip_versions",              sql_in("objet", array('user', 'client')));
	sql_delete("spip_versions_fragments",    sql_in("objet", array('user', 'client')));
	sql_delete("spip_forum",                 sql_in("objet", array('user', 'client')));

	effacer_meta($nom_meta_base_version);
}