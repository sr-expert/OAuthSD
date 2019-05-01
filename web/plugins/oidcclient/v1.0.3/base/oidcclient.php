<?php
/***************************************************************************\
*  SPIP, Systeme de publication pour l'internet                           *
*                                                                         *
*  Copyright (c) 2001-2016                                                *
*  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
*                                                                         *
*  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
*  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/
/**
 * Plugin OpenID Connect client pour SPIP
 * Auteur : B. Degoy DnC     
 * d'après plugin OpenID pour SPIP
 * Licence GPL (c) 2007-2012 Edouard Lafargue, Mathieu Marcillaud, Cedric Morin, Fil
 * 
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Ajouter des champs a la table auteurs
 * @param array $tables_principales
 * @return array
 */
 
 /*
 ALTER TABLE `spip_auteurs` 
 ADD `oidc` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_general_ci NULL AFTER `composition_lock`, 
 ADD UNIQUE `oidc` (`oidc`);
 Noter que seul l'index unique assure l'unicité du login OIDC.
 */
function oidcclient_declarer_tables_principales($tables_principales){
	// Extension de la table auteurs
	$tables_principales['spip_auteurs']['field']['oidc'] = "VARCHAR(64) DEFAULT '' NULL";
    $tables_principales['spip_auteurs']['key']['UNIQUE oidc'] = "oidc";    //TODO: vérifier le bon fonctionnement
		
	return $tables_principales;
}

?>
