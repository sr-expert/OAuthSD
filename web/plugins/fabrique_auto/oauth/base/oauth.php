<?php
/**
 * Déclarations relatives à la base de données
 *
 * @plugin     OAuth 2.0
 * @copyright  2018
 * @author     DnC
 * @licence    GNU/GPL
 * @package    SPIP\Oauth\Pipelines
 */

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
 * Déclaration des alias de tables et filtres automatiques de champs
 *
 * @pipeline declarer_tables_interfaces
 * @param array $interfaces
 *     Déclarations d'interface pour le compilateur
 * @return array
 *     Déclarations d'interface pour le compilateur
 */
function oauth_declarer_tables_interfaces($interfaces) {

	$interfaces['table_des_tables']['users'] = 'users';
	$interfaces['table_des_tables']['clients'] = 'clients';

	return $interfaces;
}


/**
 * Déclaration des objets éditoriaux
 *
 * @pipeline declarer_tables_objets_sql
 * @param array $tables
 *     Description des tables
 * @return array
 *     Description complétée des tables
 */
function oauth_declarer_tables_objets_sql($tables) {

	$tables['spip_users'] = array(
		'type' => 'user',
		'principale' => "oui",
		'field'=> array(
			'id_user'            => 'bigint(21) NOT NULL',
			'username'           => 'varchar(255) DEFAULT NULL',
			'password'           => 'varchar(2000) DEFAULT NULL',
			'email'              => 'varchar(256) DEFAULT NULL',
			'given_name'         => 'varchar(255) DEFAULT NULL',
			'middle_name'        => 'varchar(255) DEFAULT NULL',
			'family_name'        => 'varchar(255) DEFAULT NULL',
			'nickname'           => 'varchar(255) DEFAULT NULL',
			'profile'            => 'text',
			'picture'            => 'varchar(255) DEFAULT NULL',
			'website'            => 'varchar(255) DEFAULT NULL',
			'verified'           => 'tinyint(1) DEFAULT NULL',
			'gender'             => 'varchar(16) DEFAULT NULL',
			'birthday'           => 'varchar(64) DEFAULT NULL',
			'zoneinfo'           => 'varchar(64) DEFAULT NULL',
			'locale'             => 'varchar(16) DEFAULT NULL',
			'phone_number'       => 'varchar(64) DEFAULT NULL',
			'street_address'     => 'varchar(255) DEFAULT NULL',
			'locality'           => 'varchar(63) DEFAULT NULL',
			'region'             => 'varchar(63) DEFAULT NULL',
			'postal_code'        => 'varchar(31) DEFAULT NULL',
			'country'            => 'varchar(63) DEFAULT NULL',
			'updated_time'       => 'datetime DEFAULT "0000-00-00 00:00:00"',
			'created_time'       => 'datetime DEFAULT NULL',
			'composition'        => 'varchar(255) NOT NULL DEFAULT ""',
			'composition_lock'   => 'tinyint(1) NOT NULL DEFAULT "0"',
			'date_publication'   => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"', 
			'statut'             => 'varchar(20)  DEFAULT "0" NOT NULL', 
			'maj'                => 'TIMESTAMP'
		),
		'key' => array(
			'PRIMARY KEY'        => 'id_user',
			'KEY statut'         => 'statut', 
		),
		'titre' => '"" AS titre, "" AS lang',
		'date' => 'date_publication',
		'champs_editables'  => array('username', 'password', 'email', 'profile'),
		'champs_versionnes' => array('profile'),
		'rechercher_champs' => array(),
		'tables_jointures'  => array(),
		'statut_textes_instituer' => array(
			'prepa'    => 'texte_statut_en_cours_redaction',
			'prop'     => 'texte_statut_propose_evaluation',
			'publie'   => 'texte_statut_publie',
			'refuse'   => 'texte_statut_refuse',
			'poubelle' => 'texte_statut_poubelle',
		),
		'statut'=> array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prop,prepa',
				'post_date' => 'date', 
				'exception' => array('statut','tout')
			)
		),
		'texte_changer_statut' => 'user:texte_changer_statut_user', 
		

	);

	$tables['spip_clients'] = array(
		'type' => 'client',
		'principale' => "oui",
		'field'=> array(
			'id_client'          => 'bigint(21) NOT NULL',
			'client_secret'      => 'varchar(80) DEFAULT NULL',
			'redirect_uri'       => 'varchar(2000) NOT NULL',
			'grant_types'        => 'varchar(80) DEFAULT NULL',
			'scope'              => 'varchar(100) DEFAULT NULL',
			'user_id'            => 'varchar(80) DEFAULT NULL',
			'client_id'          => 'varchar(256) NOT NULL DEFAULT ""',
			'css'                => 'text',
			'date_publication'   => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"', 
			'statut'             => 'varchar(20)  DEFAULT "0" NOT NULL', 
			'maj'                => 'TIMESTAMP'
		),
		'key' => array(
			'PRIMARY KEY'        => 'id_client',
			'KEY statut'         => 'statut', 
		),
		'titre' => '"" AS titre, "" AS lang',
		'date' => 'date_publication',
		'champs_editables'  => array('client_secret', 'redirect_uri', 'grant_types', 'scope', 'user_id', 'client_id', 'css'),
		'champs_versionnes' => array(),
		'rechercher_champs' => array(),
		'tables_jointures'  => array(),
		'statut_textes_instituer' => array(
			'prepa'    => 'texte_statut_en_cours_redaction',
			'prop'     => 'texte_statut_propose_evaluation',
			'publie'   => 'texte_statut_publie',
			'refuse'   => 'texte_statut_refuse',
			'poubelle' => 'texte_statut_poubelle',
		),
		'statut'=> array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prop,prepa',
				'post_date' => 'date', 
				'exception' => array('statut','tout')
			)
		),
		'texte_changer_statut' => 'client:texte_changer_statut_client', 
		

	);

	return $tables;
}