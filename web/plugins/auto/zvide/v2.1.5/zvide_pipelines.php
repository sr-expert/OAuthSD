<?php

// Sécurité
if (!defined("_ECRIRE_INC_VERSION")) return;

/**
 * Pipeline recuperer_fond pour ajouter les blocs de la page par défaut
 *
 * @param array $flux
 * @return array
 */
function zvide_recuperer_fond($flux){
	// Le pipeline n'est utilisé que si le noiZetier est actif, ZPIP-vide pouvant être utilisé seulement pour un reset.
	if (defined('_DIR_PLUGIN_NOIZETIER')) {
		include_spip('inc/noizetier');
		$fond = $flux['args']['fond'];
		if(!is_array($fond))
			$bloc = substr($fond,0,strpos($fond,'/'));
		else
			$bloc = '';
		// Si on est sur un bloc contenu, navigation ou extra, on ajoute les noisettes de la page par defaut
		// On ajoute également une ancre correspondant au nom du bloc
		if (in_array($bloc,array('contenu','navigation','extra'))) {
			$contexte = $flux['data']['contexte'];
			$contexte['bloc'] = 'pre_'.$bloc;
			$contexte['type'] = 'defaut';
			$contexte['composition'] = '';
			if ($flux['args']['contexte']['voir']=='noisettes' && autoriser('configurer','noizetier'))
					$complements_pre = recuperer_fond('noizetier-generer-bloc-voir-noisettes',$contexte,array('raw'=>true));
				else
					$complements_pre = recuperer_fond('noizetier-generer-bloc',$contexte,array('raw'=>true));
			$contexte['bloc'] = 'post_'.$bloc;
			if ($flux['args']['contexte']['voir']=='noisettes' && autoriser('configurer','noizetier'))
					$complements_post = recuperer_fond('noizetier-generer-bloc-voir-noisettes',$contexte,array('raw'=>true));
				else
					$complements_post = recuperer_fond('noizetier-generer-bloc',$contexte,array('raw'=>true));
			$ancre = "<a name=\"$bloc\"></a>\n";
			$flux['data']['texte'] = $ancre.$complements_pre['texte'].$flux['data']['texte'].$complements_post['texte'];
		}
	}
	return $flux;
}

?>
