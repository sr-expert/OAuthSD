<?php
/**
* Déclarations relatives à  la base de données
*
* @plugin     OAuth 2.0
* @copyright  2018
* @author     DnC
* @licence    GNU/GPL
* @package    SPIP\Oauth\Pipelines
*/

if (!defined('_ECRIRE_INC_VERSION')) return;


/**
* DÃ©claration des alias de tables et filtres automatiques de champs
*
* @pipeline declarer_tables_interfaces
* @param array $interfaces
*     DÃ©clarations d'interface pour le compilateur
* @return array
*     DÃ©clarations d'interface pour le compilateur
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
*     Description complÃ©tÃ©e des tables
*/
function oauth_declarer_tables_objets_sql($tables) {

    $tables['spip_users'] = array(
        'type' => 'user',
        'principale' => "oui",
        'field'=> array(
            'id_user'           => 'bigint(21) NOT NULL',
            'username'          => 'varchar(255) NOT NULL',
            'password'          => 'varchar(2000) NOT NULL',
            'given_name'        => 'varchar(255) NULL',
            'middle_name'       => 'varchar(255) NULL',
            'family_name'       => 'varchar(255) NULL',
            'nickname'          => 'varchar(255) NULL',
            'profil'             => 'varchar(255) DEFAULT NULL',
            'picture'           => 'varchar(255) NULL',
            'website'           => 'varchar(255) NULL',
            'email'             => 'varchar(256) NOT NULL',
            'verified'          => 'tinyint(1) NULL',
            'gender'           => 'varchar(16) NULL',
            'birthday'         => 'varchar(64) NULL',
            'zoneinfo'           => 'varchar(64) NULL',
            'locale'           => 'varchar(16) NULL',
            'phone_number'      => 'varchar(64) NULL',
            'street_address'           => 'varchar(255) NULL',
            'locality'           => 'varchar(63) NULL',
            'region'           => 'varchar(63) NULL',
            'postal_code'           => 'varchar(31) NULL',
            'country'           => 'varchar(63) NULL',
            'updated_time'   => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"',
            'created_time'   => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"',
            'composition'        => 'varchar(255) NOT NULL DEFAULT ""',
            'composition_lock'   => 'tinyint(1) NOT NULL DEFAULT "0"',
            'profile'            => 'text NULL',
            'comment'            => 'text NULL',
            'date_publication'   => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"',  
            'statut'             => 'varchar(20)  DEFAULT "0" NOT NULL', 
            'maj'                => 'TIMESTAMP'
        ),
        'key' => array(
            'PRIMARY KEY'        => 'id_user',
            'KEY email'         => 'email', 
            'KEY statut'         => 'statut',
            'KEY username'         => 'username',  
        ),
        'titre' => '"" AS titre, "" AS lang',
        'date' => 'updated_time',
        'champs_editables'  => array('username', 'password', 'given_name', 'middle_name','family_name', 'nickname', 'profile', 'picture', 'website', 'email', 'gender', 'birthday', 'zoneinfo', 'locale', 'phone_number', 'street_address', 'locality', 'region', 'postal_code', 'country', 'comment', 'scope'),
        'champs_versionnes' => array('profile', 'comment'),
        'rechercher_champs' => array('given_name', 'middle_name','family_name', 'nickname', 'street_address', 'locality', 'region', 'postal_code', 'country','comment'),
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
            'client_secret'      => 'varchar(80) NOT NULL',
            'redirect_uri'       => 'varchar(2000) NOT NULL',
            'grant_types'        => 'varchar(80) NOT NULL',
            'scope'              => 'varchar(100) NOT NULL',
            'user_id'            => 'varchar(80) NOT NULL',
            'client_id'          => 'varchar(64) NULL',  
            'client_ip'          => 'varchar(256) NOT NULL DEFAULT ""',   //[dnc14]
            'css'                => 'text',
            'date_publication'   => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"', 
            'statut'             => 'varchar(20)  DEFAULT "0" NOT NULL', 
            'maj'                => 'TIMESTAMP',
            'texte1'             => 'varchar(256) NULL',   //[dnc16]
            'texte2'             => 'varchar(256) NULL',   //[dnc16]

        ),
        'key' => array(
            'PRIMARY KEY'        => 'id_client',
            'KEY statut'         => 'statut',
            'KEY client_id'         => 'client_id',  
        ),
        'titre' => '"" AS titre, "" AS lang',
        'date' => 'date_publication',
        'champs_editables'  => array('client_secret', 'redirect_uri', 'grant_types', 'scope', 'user_id', 'client_id', 'client_ip', 'css','texte1', 'texte2'), //[dnc14][dnc16]
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

        $tables['spip_oidc_logs'] = array(      //[dnc27e]
            'type' => 'oidc_log',
            'principale' => "oui",
            'field'=> array(
                'id_oidc_logs'       => 'bigint(20) NOT NULL',
                'remote_addr'      => 'varchar(255) NOT NULL',
                'state'       => 'varchar(255) NULL',
                'client_id'          => 'varchar(255) NULL',
                'user_id'            => 'varchar(255) NOT NULL',  
                'datetime'          => 'datetime NOT NULL DEFAULT "0000-00-00 00:00:00"',
                'origin'                => 'varchar(255) NOT NULL',
                'message'   => 'text NULL', 
                'level'             => 'tinyint(4) NOT NULL', 
                'weight'                => 'smallint(6) NULL',
                'errnum'             => 'smallint(6) NULL',

            ),
            'key' => array(
                'PRIMARY KEY'        => 'id_oidc_log',
                'KEY state'         => 'state',
                'KEY remote_addr'         => 'remote_addr',  
            ),
            'titre' => '"" AS titre, "" AS lang',
            'date' => 'datetime',
            'champs_editables'  => array('id_oidc_log', 'remote_addr', 'state', 'client_id', 'user_id', 'datetime', 'origin', 'message', 'level', 'weight', 'errnum'), 
            'champs_versionnes' => array(),
            'rechercher_champs' => array(),
            'tables_jointures'  => array(),
            'statut_textes_instituer' => array(
                'prepa'    => 'texte_statut_en_cours_redaction',
                'prop'     => 'texte_statut_propose_evaluation',
                'publie'   => 'texte_statut_publie',
                'refuse'   => 'texte_statut_refuse',
                'poubelle' => 'texte_statut_poubelle',
            )
        )    

    );

    return $tables;
}