<?php

/**
 *  Fichier généré par la Fabrique de plugin v6
 *   le 2016-10-06 14:39:41
 *
 *  Ce fichier de sauvegarde peut servir à recréer
 *  votre plugin avec le plugin «Fabrique» qui a servi à le créer.
 *
 *  Bien évidemment, les modifications apportées ultérieurement
 *  par vos soins dans le code de ce plugin généré
 *  NE SERONT PAS connues du plugin «Fabrique» et ne pourront pas
 *  être recréées par lui !
 *
 *  La «Fabrique» ne pourra que régénerer le code de base du plugin
 *  avec les informations dont il dispose.
 *
**/

if (!defined("_ECRIRE_INC_VERSION")) return;

$data = array (
  'fabrique' => 
  array (
    'version' => 6,
  ),
  'paquet' => 
  array (
    'prefixe' => 'oauth',
    'nom' => 'OAuth 2.0',
    'slogan' => 'Helps building an OAuth 2.0 Server',
    'description' => '',
    'version' => '1.0.0',
    'auteur' => 'DnC',
    'auteur_lien' => 'http://degoy.com',
    'licence' => 'GNU/GPL',
    'categorie' => 'outil',
    'etat' => 'dev',
    'compatibilite' => '[3.1.3;3.1.*]',
    'documentation' => 'http://oa.dnc.global',
    'administrations' => 'on',
    'schema' => '1.0.0',
    'formulaire_config' => '',
    'formulaire_config_titre' => '',
    'fichiers' => 
    array (
      0 => 'autorisations',
      1 => 'fonctions',
      2 => 'options',
      3 => 'pipelines',
    ),
    'inserer' => 
    array (
      'paquet' => '',
      'administrations' => 
      array (
        'maj' => '',
        'desinstallation' => '',
        'fin' => '',
      ),
      'base' => 
      array (
        'tables' => 
        array (
          'fin' => '',
        ),
      ),
    ),
    'scripts' => 
    array (
      'pre_copie' => '',
      'post_creation' => '',
    ),
    'exemples' => '',
  ),
  'objets' => 
  array (
  ),
  'images' => 
  array (
    'paquet' => 
    array (
      'logo' => 
      array (
        0 => 
        array (
          'extension' => 'gif',
          'contenu' => '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',
        ),
      ),
    ),
    'objets' => 
    array (
    ),
  ),
);