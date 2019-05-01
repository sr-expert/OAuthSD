<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/meta');

// Installation et mise à jour
function aveline_upgrade($nom_meta_version_base, $version_cible)
{
	$version_actuelle = '0.0';

	// Historiquement, la version 0.2.X correspondent au garde noisettes (< 1.0.0)
	if (isset($GLOBALS['meta']['gn_base_version'])) {
		$version_actuelle = $GLOBALS['meta']['gn_base_version'];
		effacer_meta('gn_base_version');
	}

	if (
		(!isset($GLOBALS['meta'][$nom_meta_version_base]))
		|| (($version_actuelle = $GLOBALS['meta'][$nom_meta_version_base]) != $version_cible)
	) {
		if (isset($GLOBALS['table_des_tables']['noisettes'])) {
			// On calcule le tableau des noisettes
			include_spip('base/abstract_sql');
			$noisettes = sql_allfetsel('*', 'spip_noisettes', '1');

			if (sizeof($noisettes) > 0) {
				// On remet au propre les parametres
				foreach ($noisettes as $cle => $noisette) {
					$noisettes[$cle]['parametres'] = unserialize($noisette['parametres']);
				}

				// On applique les mises à jour
				$noisettes = aveline_maj_noisettes($noisettes, $version_actuelle);

				// Il faut serializer les paramètres avant mise en base
				foreach ($noisettes as $cle => $noisette) {
					$noisettes[$cle]['parametres'] = serialize($noisette['parametres']);
				}

				// On update la base
				sql_replace_multi('spip_noisettes', $noisettes);
			}
		}
		ecrire_meta($nom_meta_version_base, $version_actuelle = $version_cible, 'non');
	}
}

// Désinstallation
function aveline_vider_tables($nom_meta_version_base)
{
	// On efface la version enregistrée
	effacer_meta($nom_meta_version_base);
}

// Mise à jour des noisettes

function aveline_maj_noisettes($noisettes, $current_version)
{
	if (version_compare($current_version, '0.1', '>') && version_compare($current_version, '0.3.0', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			$noisettes[$cle]['parametres'] = str_replace('gn_public:', 'avelinepublic:', $noisettes[$cle]['parametres']);
		}
	}
	if (version_compare($current_version, '0.3.2', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			if (in_array($noisette['noisette'], array(
				'auteur-articles',
				'liste_articles',
				'mot-articles',
				'page-recherche-articles',
				'liste_breves',
				'mot-breves',
				'page-recherche-breves',
				'liste_auteurs',
				'page-recherche-auteurs',
				'rubrique-sous_rubriques',
				'rubriques_secteur_langue',
				'rubriques_racine',
				'page-recherche-rubriques',
				'liste_forums',
				'mot-forums',
				'selecteur_archives',
			))) {
				foreach ($noisette['parametres'] as $param => $valeur) {
					if ($param == 'tri' and $valeur == 'nb_articles') {
						$noisettes[$cle]['parametres'][$param] = 'compteur_articles';
					}
					if ($param == 'senstri' and intval($valeur) == 0) {
						$noisettes[$cle]['parametres'][$param] = '';
					}
					if ($param == 'senstri' and intval($valeur) == 1) {
						$noisettes[$cle]['parametres'][$param] = 'inverse';
					}
					if ($param == 'tri' and $valeur == 'nbre_commentaires') {
						$noisettes[$cle]['parametres'][$param] = 'compteur_forum';
					}
					if ($param == 'tri' and $valeur == 'note') {
						$noisettes[$cle]['parametres'][$param] = 'moyenne';
					}
					if ($param == 'liste_articles') {
						$noisettes[$cle]['parametres']['branche'] = $noisettes[$cle]['parametres'][$param];
						unset($noisettes[$cle]['parametres'][$param]);
					}
					if ($param == 'exclure_article_en_cours') {
						$noisettes[$cle]['parametres']['exclure_objet_en_cours'] = $noisettes[$cle]['parametres'][$param];
						unset($noisettes[$cle]['parametres'][$param]);
					}
					if ($param == 'pas_selecteur_archives' and $valeur == 'annee_mois') {
						$noisettes[$cle]['parametres'][$param] = 'mois';
					}
					if ($param == 'liste_breves') {
						$noisettes[$cle]['parametres']['branche'] = $noisettes[$cle]['parametres'][$param];
						unset($noisettes[$cle]['parametres'][$param]);
					}
					if ($param == 'exclure_breve_en_cours') {
						$noisettes[$cle]['parametres']['exclure_objet_en_cours'] = $noisettes[$cle]['parametres'][$param];
						unset($noisettes[$cle]['parametres'][$param]);
					}
				}
			}
			if (in_array($noisette['noisette'], array('page-recherche-articles', 'page-recherche-auteurs', 'page-recherche-rubriques', 'page-recherche-breves'))) {
				$noisettes[$cle]['parametres']['tri'] = 'points';
				$noisettes[$cle]['parametres']['senstri'] = 'inverse';
			}
			if (in_array($noisette['noisette'], array(
				'liste_documents',
				'article-documents',
				'rubrique-documents',
				'page-recherche-documents',
			))) {
				foreach ($noisette['parametres'] as $param => $valeur) {
					if ($param == 'senstri' and intval($valeur) == 0) {
						$noisettes[$cle]['parametres'][$param] = '';
					}
					if ($param == 'senstri' and intval($valeur) == 1) {
						$noisettes[$cle]['parametres'][$param] = 'inverse';
					}
				}
			}
		}
	}
	if (version_compare($current_version, '0.3.3', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			if (in_array($noisette['noisette'], array(
				'liste_documents',
				'article-documents',
				'rubrique-documents',
				'page-recherche-documents',
			))) {
				foreach ($noisette['parametres'] as $param => $valeur) {
					if ($param == 'tri' and $valeur == 'multi titre') {
						$noisettes[$cle]['parametres'][$param] = 'titre';
					}
				}
			}
		}
	}
	if (version_compare($current_version, '0.3.4', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			if (in_array($noisette['noisette'], array(
				'auteur-articles',
				'liste_articles',
				'mot-articles',
				'page-recherche-articles',
				'liste_breves',
				'mot-breves',
				'page-recherche-breves',
				'liste_auteurs',
				'page-recherche-auteurs',
				'rubrique-sous_rubriques',
				'rubriques_secteur_langue',
				'rubriques_racine',
				'page-recherche-rubriques',
				'liste_forums',
				'mot-forums',
				'selecteur_archives',
			))) {
				foreach ($noisette['parametres'] as $param => $valeur) {
					if ($param == 'tri' and $valeur == 'num titre') {
						$noisettes[$cle]['parametres'][$param] = 'titre';
					}
				}
			}
		}
	}
	if (version_compare($current_version, '0.3.5', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			if (in_array($noisette['noisette'], array(
				'liste_sites',
				'mot-sites',
				'page-recherche-sites',
				'liste_syndic_articles',
				'site-syndic_articles',
				'page-recherche-syndic_articles',
			))) {
				foreach ($noisette['parametres'] as $param => $valeur) {
					if ($param == 'liste_sites') {
						$noisettes[$cle]['parametres']['branche'] = $noisettes[$cle]['parametres'][$param];
						unset($noisettes[$cle]['parametres'][$param]);
					}
					if ($param == 'exclure_site_en_cours') {
						$noisettes[$cle]['parametres']['exclure_objet_en_cours'] = $noisettes[$cle]['parametres'][$param];
						unset($noisettes[$cle]['parametres'][$param]);
					}
					if ($param == 'liste_syndic_articles') {
						$noisettes[$cle]['parametres']['branche'] = $noisettes[$cle]['parametres'][$param];
						unset($noisettes[$cle]['parametres'][$param]);
					}
				}
			}
		}
	}
	if (version_compare($current_version, '0.3.6', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			$noisettes[$cle]['parametres'] = str_replace('aveline_public:', 'avelinepublic:', $noisettes[$cle]['parametres']);
		}
	}
	if (version_compare($current_version, '0.3.7') == 0) {
		// Réparation d'une boulette qui ne concerne que la version 0.3.7
		foreach ($noisettes as $cle => $noisette) {
			$noisettes[$cle]['parametres'] = str_replace('avelinepublic:colon2', '::', $noisettes[$cle]['parametres']);
			$noisettes[$cle]['parametres'] = str_replace('avelinepublic:gt', '>', $noisettes[$cle]['parametres']);
		}
	}
	if (version_compare($current_version, '0.3.8', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			foreach ($noisette['parametres'] as $param => $valeur) {
				if (in_array($param, array(
				  'texte_devant_mots_cles',
				  'liste_texte_devant_mots_cles',
				  'texte_devant_rubrique',
				  'liste_texte_devant_rubrique',
				  'texte_devant_article',
				  'liste_texte_devant_article',
				))) {
					if ($valeur == '::') {
						$noisettes[$cle]['parametres'][$param] = 'avelinepublic:colon2';
					}
					if ($valeur == '>') {
						$noisettes[$cle]['parametres'][$param] = 'avelinepublic:gt';
					}
				}
			}
		}
	}
	if (version_compare($current_version, '0.4.0', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			$anciennes = array(
				'ecrire:titre_documents_joints',
				'spip:info_documents',
				'spip:info_portfolio',
				'public:forum',
				'spip:titre_forum',
				'public_public:articles_populaires',
				'public:breves',
			);
			$nouvelles = array(
				'medias:titre_documents_joints',
				'medias:info_documents',
				'medias:info_portfolio',
				'forum:forum',
				'forum:titre_forum',
				'avelinepublic:articles_les_plus_populaires',
				'breves:breves',
			);
			$noisettes[$cle]['parametres'] = str_replace($anciennes, $nouvelles, $noisettes[$cle]['parametres']);
		}
	}
	if (version_compare($current_version, '0.4.1', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			if (in_array($noisette['noisette'], array(
				'anythingslider_liste_articles',
				'anythingslider_liste_evenements',
				'anythingslider_liste_syndic_articles',
			))) {
				foreach ($noisette['parametres'] as $param => $valeur) {
					if ($param == 'start_stopped') {
						$noisettes[$cle]['parametres']['auto_play'] = ($valeur == '') ? 'on' : '';
					}
				}
			}
		}
	}
	if (version_compare($current_version, '0.4.2', '<')) {
		foreach ($noisettes as $cle => $noisette) {
			if (substr($noisette['noisette'], -9) == 'portfolio') {
				if (isset($noisettes[$cle]['parametres']['vignettes_carrees']) and $noisettes[$cle]['parametres']['vignettes_carrees'] != '') {
					$noisettes[$cle]['parametres']['type_vignettes'] = 'carrees_pleines';
				}
			}
		}
	}

	return $noisettes;
}
