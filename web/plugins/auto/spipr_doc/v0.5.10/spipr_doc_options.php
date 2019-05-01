<?php

if (!defined('_ECRIRE_INC_VERSION')) return;

if (!isset($GLOBALS['z_blocs']))
	$GLOBALS['z_blocs'] = array('content','aside','head','head_js','header','footer');

if (isset($GLOBALS['visiteur_session']['statut']) AND $GLOBALS['visiteur_session']['statut']=='0minirezo')
	$GLOBALS['marqueur'].=":minirezo";

define('_ZENGARDEN_FILTRE_THEMES','spipr');
define('_ALBUMS_INSERT_HEAD_CSS',false);

function title2anchor($titre,$id_article=""){
	include_spip("inc/charsets");
	$titre = strip_tags($titre);
	$titre = translitteration($titre);
	$titre = supprimer_numero($titre);
	$titre = preg_replace(",\W,","",$titre);
	$titre = strtolower($titre);
	if (preg_match(',^\d,',$titre))
		$titre = "a$titre";
	return $titre;
}

function urls_generer_url_article_dist($id, $args, $ancre){

	$row = sql_fetsel('id_rubrique,titre','spip_articles','id_article='.intval($id));
	// si il y a bien une rubrique > 0 (on laisse passer les pages uniques)
	if ($row['id_rubrique']>0)
		return generer_url_entite($row['id_rubrique'],'rubrique',$args,title2anchor($row['titre'],$id),true);

	return null;
}

function placeholder($texte,$p=true){
	if (!$texte
		AND !strlen($texte)
		AND (isset($GLOBALS['visiteur_session']['statut']) AND $GLOBALS['visiteur_session']['statut']=='0minirezo')){
		$texte = "<i class='mute' title='Inserer un texte'>¤</i>";
		if ($p)
			$texte = "<p class='placeholder muted'>$texte</p>";
	}
	return $texte;
}
