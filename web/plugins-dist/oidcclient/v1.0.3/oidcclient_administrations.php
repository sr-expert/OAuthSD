<?php
/*************************************************************************\
*  SPIP, Systeme de publication pour l'internet                           *
*                                                                         *
*  Copyright (c) 2001-2016                                                *
*  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
*                                                                         *
*  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
*  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\*************************************************************************/

/**
* Plugin OpenID Connect client pour SPIP 
* Auteur : B.Degoy DnC
* Copyright (c) 2018 B.Degoy    
* d'après plugin OpenID pour SPIP
* Licence GPL (c) 2007-2012 Edouard Lafargue, Mathieu Marcillaud, Cedric Morin, Fil
*/ 
 
if (!defined("_ECRIRE_INC_VERSION")) return;

include_spip('inc/meta');

/**
 * Upgrade de la base
 *
 * @param string $nom_meta_base_version
 * @param string $version_cible
 */
function oidcclient_upgrade($nom_meta_base_version,$version_cible){
	$current_version = 0.0;

	if (   (!isset($GLOBALS['meta'][$nom_meta_base_version]) )
			|| (($current_version = $GLOBALS['meta'][$nom_meta_base_version])!=$version_cible)){
		include_spip('base/oidcclient');
		if ($current_version==0.0){
			include_spip('base/create');
			maj_tables('spip_auteurs');
			ecrire_meta($nom_meta_base_version,$current_version=$version_cible,'non');
		}
		if (version_compare($current_version,"0.2","<")){
			$res = sql_select('id_auteur,oidc','spip_auteurs',"oidc<>''");
			while ($row = sql_fetch($res)){
				$oidc = rtrim($row['oidc'],'/');
				// si pas de protocole, mettre http://
				if ($oidc  AND !preg_match(';^[a-z]{3,6}://;i',$oidc ))
					$oidc = "http://".$oidc;
				if ($oidc!==$row['oidc']){
					sql_updateq('spip_auteurs',array('oidc'=>$oidc),'id_auteur='.intval($row['id_auteur']));
				}
			}
			ecrire_meta($nom_meta_base_version,$current_version="0.2",'non');
		}
		if (version_compare($current_version,"0.3","<")){
			// un index ne peut pas etre mis sur un champ de type texte (dixit mysql)
			sql_alter("TABLE spip_auteurs DROP INDEX oidc");
			ecrire_meta($nom_meta_base_version,$current_version="0.3",'non');
		}		
	}
}

/**
 * Desinstallation du plugin
 *
 * @param string $nom_meta_base_version
 */
function oidcclient_vider_tables($nom_meta_base_version) {
	sql_alter("TABLE spip_auteurs DROP oidc");
	effacer_meta($nom_meta_base_version);
}

