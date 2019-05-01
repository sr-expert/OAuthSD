<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/config');

function formulaires_configurer_sarkaspip_apparence_charger_dist(){

	clear_path_cache();
	spip_clearstatcache();

	$variables = sarkaspip_apparence_defaut_variables();
	// d'abord on lit le less
	$valeurs = sarkaspip_lire_less_from_variables($variables,"sarkaspip/apparence/");

	// puis les valeurs de la config qui surchargent eventuellement
	$valeurs = sarkaspip_lire_configs_from_variables($valeurs,"sarkaspip/apparence/");

	$valeurs['_fichier_actif'] = find_in_path("css/variables.less");
	return $valeurs;
}


function formulaires_configurer_sarkaspip_apparence_traiter_dist(){
	$res = array();
	$variables = sarkaspip_apparence_defaut_variables();
	if (_request('reset')){
		effacer_config("sarkaspip/apparence");
		$res['message_ok'] = "La configuration a ete reinitialisee";
		foreach($variables as $k=>$v){
			set_request($k);
		}
		if (file_exists($f=_DIR_RACINE."squelettes/css/variables.less")){
			@rename($f,"$f.sav");
			spip_unlink($f);
			// invalider le cache less
			include_spip('inc/invalideur');
			purger_repertoire(_DIR_VAR."cache-less");
		}
	}
	else {
		// ecrire toutes les variables dans la config
		// ainsi on peut supprimer un fichier .less foireux et retrouver la config en venant sur le formulaire
		// il suffit de faire enregistrer pour regenerer le fichier variables.less
		sarkaspip_ecrire_configs_from_variables($variables,$_POST,"sarkaspip/apparence/");
		// puis generer le fichier variables.less dans squelettes/css/
		$notfound = array();
		$erreur = sarkaspip_ecrire_less_from_variables($variables,$_POST);
		if ($erreur){
			$res['message_erreur'] = $erreur;
		}
		else {
			$res['message_ok'] = _T('config_info_enregistree')
				. "<br /><a href=\"".parametre_url($GLOBALS['meta']['adresse_site'],'var_mode','calcul')
				."\" target=\"_blank\">"._T('icone_voir_en_ligne')."</a>";
			if (autoriser('webmestre')){
				$res['message_ok'] .= " | <a href=\"". parametre_url(generer_url_public('demo/bootstrap','',false,false),'var_mode','calcul')
				."\" target=\"_blank\">"._L('Page de démonstration de la charte de style')."</a>";
			}
		}
	}
	return $res;
}


function sarkaspip_apparence_defaut_variables(){

	$defaut = array(
		'header_min_height' => '',
		'variables' => array(
			'links'=> array(
				'linkColor' => '',
				'linkColorHover' => '',
			),
			'grays'=> array(
				'black' => '',
				'grayDark' => '',
				'grayDarker' => '',
				'gray' => '',
				'grayLight' => '',
				'grayLighter' => '',
				'white' => '',
			),
			'colors' => array(
				'blue' => '',
				'blueDark' => '',
				'green' => '',
				'red' => '',
				'yellow' => '',
				'orange' => '',
				'pink' => '',
				'purple' => '',
			),
			'scaffolding' => array(
				'bodyBackground' => '',
				'textColor' => '',
			),
			'typography' => array(
				'sansFontFamily' => '',
				'serifFontFamily' => '',
				'monoFontFamily' => '',
				'baseFontFamily' => '',
				'altFontFamily' => '',
				'headingsFontFamily' => '',
				'baseFontSize' => '',
				'baseLineHeight' => '',
				'headingsFontWeight' => '',
				'headingsColor' => '',
			),
			'radius' => array(
				'baseBorderRadius' => '',
				'borderRadiusLarge' => '',
				'borderRadiusSmall' => '',
			),
			'tables' => array(
				'tableBackground' => '', // overall background-color
				'tableBackgroundAccent' => '', // for striping
				'tableBackgroundHover' => '', // for hover
				'tableBorder' => '', // table and cell border
			),
			'buttons' => array(
				'btnBackground' => '',
				'btnBackgroundHighlight' => '',
				'btnBorder' => '',

				'btnPrimaryBackground' => '',
				'btnPrimaryBackgroundHighlight' => '',

				'btnInfoBackground' => '',
				'btnInfoBackgroundHighlight' => '',

				'btnSuccessBackground' => '',
				'btnSuccessBackgroundHighlight' => '',

				'btnWarningBackground' => '',
				'btnWarningBackgroundHighlight' => '',

				'btnDangerBackground' => '',
				'btnDangerBackgroundHighlight' => '',

				'btnInverseBackground' => '',
				'btnInverseBackgroundHighlight' => '',
			),
			'forms' => array(
				'inputBackground' => '',
				'inputBorder' => '',
				'inputBorderRadius' => '',
				'inputDisabledBackground' => '',
				'formActionsBackground' => '',
				'inputHeight' => '', // base line-height + 8px vertical padding + 2px top/bottom border
			),
			'dropdowns' => array(
				'dropdownBackground' => '',
				'dropdownBorder' => '',
				'dropdownDividerTop' => '',
				'dropdownDividerBottom' => '',
				'dropdownLinkColor' => '',
				'dropdownLinkColorHover' => '',
				'dropdownLinkBackgroundHover' => '',
				'dropdownLinkColorActive' => '',
				'dropdownLinkBackgroundActive' => '',
			),
			'wells' => array(
				'wellBackground' => '',
			),
			'navbar' => array(
				'navbarCollapseWidth' => '',
				'navbarCollapseDesktopWidth' => '',

				'navbarHeight' => '',
				'navbarBackground' => '',
				'navbarBackgroundHighlight' => '',
				'navbarBorder' => '',

				'navbarText' => '',
				'navbarLinkColor' => '',
				'navbarLinkColorHover' => '',
				'navbarLinkColorActive' => '',
				'navbarLinkBackgroundHover' => '',
				'navbarLinkBackgroundActive' => '',

				'navbarBrandColor' => '',
			),
			'inverted_navbar' => array(
				// Inverted navbar
				'navbarInverseBackground' => '',
				'navbarInverseBackgroundHighlight' => '',
				'navbarInverseBorder' => '',

				'navbarInverseText' => '',
				'navbarInverseLinkColor' => '',
				'navbarInverseLinkColorHover' => '',
				'navbarInverseLinkColorActive' => '',
				'navbarInverseLinkBackgroundHover' => '',
				'navbarInverseLinkBackgroundActive' => '',

				'navbarInverseSearchBackground' => '',
				'navbarInverseSearchBackgroundFocus' => '',
				'navbarInverseSearchBorder' => '',
				'navbarInverseSearchPlaceholderColor' => '',

				'navbarInverseBrandColor' => '',
			),
			'pagination' => array(
				'paginationBackground' => '',
				'paginationBorder' => '',
				'paginationActiveBackground' => '',
			),
			'hero_unit' => array(
				'heroUnitBackground' => '',
				'heroUnitHeadingColor' => '',
				'heroUnitLeadColor' => '',
			),
		)
	);


	return $defaut;

}

function sarkaspip_lire_less_from_variables($valeurs){

	// recuperer les valeurs du theme
	$css = "@import \"css/variables.less\";\n\n";
	foreach($valeurs['variables'] as $section=>$vars){
		foreach($vars as $k=>$v){
			$css .= ".$k{color:@$k}\n";
		}
	}
	$css .= "\n";

	if (!function_exists('lesscss_compile')){
		include_once _DIR_PLUGIN_LESSCSS . "lesscss_fonctions.php";
	}
	$out = lesscss_compile($css);
	foreach($valeurs['variables'] as $section=>$vars){
		foreach($vars as $k=>$v){
			if (preg_match(",\.$k\s*\{\s*color:\s*([^};]*);?\s*\},Uims",$out,$m)){
				$valeurs['variables'][$section][$k] = trim($m[1]);
			}
		}
	}

	return $valeurs;
}

function sarkaspip_ecrire_less_from_variables($valeurs,$c){

	// lire le fichier actif
	$fichier = find_in_path("css/variables.less");
	lire_fichier($fichier,$css);

	$notfound = array();
	// le mettre a jour avec la config
	foreach($valeurs['variables'] as $section=>$vars){
		foreach($vars as $k=>$v){
			if (isset($c['variables'][$section][$k])){
				if (preg_match(",(@$k\s*:\s*?)([^;]*);,Uims",$css,$match)){
					$css = str_replace($match[0],$match[1].$c['variables'][$section][$k].";",$css);
					#var_dump($match);
				}
				else {
					$notfound[] = $k;
					//spip_log("Variable $k non trouvee dans $fichier","sarkaspip");
				}
			}
		}
	}
	if (count($notfound)){
		$erreur = "Variables non trouvées dans fichier <tt>$fichier</tt>: <tt>".implode("</tt>, <tt>",$notfound)."</tt>.";
		return $erreur;
	}

	// ecrire dans squelettes/css/variables.less
	// en sauvegardant le precedent si besoin
	$dir = sous_repertoire(_DIR_RACINE,"squelettes");
	$dir = sous_repertoire($dir,"css");

	if (file_exists($f=$dir."variables.less")){
		@rename($f,"$f.sav");
	}
	ecrire_fichier($f,$css);

	// invalider le cache
	include_spip('inc/invalideur');
	purger_repertoire(_DIR_VAR."cache-less");

	return '';
}



function sarkaspip_lire_configs_from_variables($valeurs,$prefixe=""){
	foreach($valeurs as $k=>$v){
		if (is_array($v)){
			$valeurs[$k] = sarkaspip_lire_configs_from_variables($v,"$prefixe$k/");
		}
		else {
			if ($v = lire_config("$prefixe$k")){
				$valeurs[$k] = $v;
			}
		}
	}
	return $valeurs;
}

function sarkaspip_ecrire_configs_from_variables($valeurs,$c,$prefixe=""){
	foreach($valeurs as $k=>$v){
		if (is_array($v)){
			$valeurs[$k] = sarkaspip_ecrire_configs_from_variables($v,$c[$k],"$prefixe$k/");
		}
		else {
			if (isset($c[$k])){
				ecrire_config("$prefixe$k",$c[$k]);
				$valeurs[$k] = $c[$k];
			}
			else {
				effacer_config("$prefixe$k");
				unset($valeurs[$k]);
			}
		}
	}
	return $valeurs;
}