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

// Voir : https://contrib.spip.net/jQuery-UI-4180
function oidcclient_jqueryui_plugins($plugins){
    $plugins[] = "jquery.ui.dialog";
    return $plugins;
}


/**
 * ajouter un champ oidc sur le formulaire CVT editer_auteur
 *
 * @param array $flux
 * @return array
 */
function oidcclient_editer_contenu_objet($flux){
	if ($flux['args']['type']=='auteur') {
		$oidc = recuperer_fond('formulaires/inc-oidcclient', $flux['args']['contexte']);
		$flux['data'] = preg_replace('%(<(div|li) class=["\'][^"\']*editer_email(.*?)</\\2>)%is', '$1'."\n".$oidc, $flux['data']);
	}
	return $flux;
}

/**
 * Ajouter la valeur oidcclient dans la liste des champs de la fiche auteur
 *
 * @param array $flux
 */
function oidcclient_formulaire_charger($flux){
	// si le charger a renvoye false ou une chaine, ne rien faire
	if (is_array($flux['data'])){
		if ($flux['args']['form']=='editer_auteur'){
			$flux['data']['oidc'] = ''; // un champ de saisie oidc
			if ($id_auteur = intval($flux['data']['id_auteur']))
				$flux['data']['oidc'] = sql_getfetsel('oidc','spip_auteurs','id_auteur='.intval($id_auteur));
		}
		if ($flux['args']['form']=='inscription'){
			$flux['data']['_forcer_request'] = true; // forcer la prise en compte du post
			$flux['data']['oidc'] = ''; // un login oidc
			if ($erreur = _request('var_erreur'))
				$flux['data']['message_erreur'] = _request('var_erreur');
			elseif(_request('oidc') AND (!_request('nom_inscription') OR !_request('mail_inscription')))
				$flux['data']['message_erreur'] = _T('oidcclient:erreur_oidcclient_info_manquantes');
		}
	}
	return $flux;
}


/**
 * Verifier la saisie de l'url oidcclient sur la fiche auteur
 *
 * @param array $flux
 */
function oidcclient_formulaire_verifier($flux){
	if ($flux['args']['form']=='editer_auteur'){
		if ($login_oidc = _request('oidc')){
			include_spip('inc/oidcclient');
			$login_oidc = nettoyer_oidc($login_oidc);
			if (!verifier_oidc($login_oidc))
				$flux['data']['oidc']=_T('oidcclient:erreur_oidc');
		}
	}	
	return $flux;
}



/**
 * ajouter l'user_id OIDC soumis lors de la soumission du formulaire CVT editer_auteur
 * et lors de l'update d'un auteur a l'inscription en 2.1
 * 
 * @param array $flux
 * @return array
 */
function oidcclient_pre_edition($flux){
	if ($flux['args']['table']=='spip_auteurs') {
		if (!is_null($oidc = _request('oidc'))) {
			include_spip('inc/oidcclient');
			$flux['data']['oidc'] = nettoyer_oidc($oidc);
		}
	}
	return $flux;
}

/**
 * Afficher l'oidcclient sur la fiche de l'auteur
 * @param array $flux 
 */
function oidcclient_afficher_contenu_objet($flux){
	if ($flux['args']['type']=='auteur'
		AND $id_auteur = $flux['args']['id_objet']
		AND $oidc = sql_getfetsel('oidc','spip_auteurs','id_auteur='.intval($id_auteur))
	){
		$flux['data'] .= propre("<div class='champ contenu_oidc'><img src='".find_in_path('images/oidc-16.png')
			."' alt='"._T('oidcclient:oidcclient')."' width='16' height='16' />"
			. " $oidc</div>");

	}

	return $flux;
}
