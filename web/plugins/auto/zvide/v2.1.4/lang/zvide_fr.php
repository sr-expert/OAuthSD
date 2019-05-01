<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// Fichier source, a modifier dans svn://zone.spip.org/spip-zone/_squelettes_/zpip-vide/trunk/lang/
if (!defined('_ECRIRE_INC_VERSION')) return;

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// C
	'configurer_zvide' => 'En-tête et pied de page',

	// D
	'description_bloc_post_contenu' => 'Les noisettes de ce bloc seront insérées sur toutes les pages après le bloc <i>Contenu</i>.',
	'description_bloc_post_extra' => 'Les noisettes de ce bloc seront insérées sur toutes les pages après le bloc <i>Extra</i>.',
	'description_bloc_post_navigation' => 'Les noisettes de ce bloc seront insérées sur toutes les pages après le bloc <i>Navigation</i>.',
	'description_bloc_pre_contenu' => 'Les noisettes de ce bloc seront insérées sur toutes les pages avant le bloc <i>Contenu</i>.',
	'description_bloc_pre_extra' => 'Les noisettes de ce bloc seront insérées sur toutes les pages avant le bloc <i>Extra</i>.',
	'description_bloc_pre_navigation' => 'Les noisettes de ce bloc seront insérées sur toutes les pages avant le bloc <i>Navigation</i>.',
	'description_page-401' => 'Cette page est affichée lorsqu’un visiteur demande à voir une page pour laquelle il n’est pas autorisé.',
	'description_page-404' => 'Cette page est affichée lorsqu’un visiteur demande à voir une page qui n’existe pas ou plus.',
	'description_page-agenda' => 'Page destinée à présenter les évènements / l’agenda de votre site.',
	'description_page-auteurs' => 'Page optionnelle permettant de lister tous les auteurs du site.',
	'description_page-forum' => 'Cette page est appelée lorsqu’un visiteur souhaiter poster un message dans un forum.',
	'description_page-jour' => 'Page à utiliser en conjonction avec un mini-calendrier. Y lister les objets sur lesquels porte le mini-calendrier.',
	'description_page-login' => 'Cette page est nécessaire pour se connecter à l’espace privé. Par sécurité, si la noisette <i>Formulaire d’identification</i> spécifique à cette page n’est pas insérée dans le bloc <i>Contenu</i>, elle y sera insérée d’office.',
	'description_page-mots' => 'Page optionnelle permettant de lister tous les mots-clés du site.',
	'description_page-plan' => 'Cette page est appelée pour afficher le plan du site.',
	'description_page-recherche' => 'Cette page est affichée lorsqu’une recherche est effectuée sur le site.',
	'description_page-spip_pass' => 'Cette page est affichée lorsqu’un visiteur a oublié son mot de passe et souhaite en changer.',
	'description_page_article' => 'Page par défaut pour les articles.',
	'description_page_auteur' => 'Page par défaut pour les auteurs.',
	'description_page_breve' => 'Page par défaut pour les brèves.',
	'description_page_evenement' => 'Page par défaut pour les évènements.',
	'description_page_groupe_mots' => 'Page facultative pour les groupes de mots-clés.',
	'description_page_mot' => 'Page par défaut pour les mot-clés.',
	'description_page_rubrique' => 'Page par défaut pour les rubriques.',
	'description_page_site' => 'Page par défaut pour les sites web référencés.',
	'description_pagedefaut' => 'Les blocs de cette page seront ajoutés sur toutes les pages du site.',

	// E
	'explication_liens_add' => 'Vous pouvez saisir ici un ou plusieurs liens additionnels à mettre dans le pieds de page. Si vous ajoutez plusieurs liens, pensez à les séparer avec un |. Vous pouvez utiliser les raccourcis SPIP. Par exemple : <code>[Contact->12] | [Mentions légales->art13]</code>',
	'explication_masquer_connexion' => 'Masquer les liens permettant de se connecter / se déconnecter ?',
	'explication_masquer_logo' => 'Masquer le logo du site ?',
	'explication_masquer_plan' => 'Masquer le lien d’accès au plan du site ?',
	'explication_masquer_rss' => 'Masquer le lien pointant sur le flux RSS du site ?',
	'explication_masquer_slogan' => 'Masquer le slogan du site ?',
	'explication_menu_lang' => 'Cette option n’affecte que les sites multilingues.<br />L’option <em>Défaut</em> reproduit le fonction de Zpip-dist : un formulaire de choix de langue est affiché sur toutes les pages. Lorsqu’une langue est sélectionnée par l’utilisateur, la page est rechargée en lui passant un paramètre <code>lang</code>. Ce fonctionnement est adapté aux sites utilisant les blocs multilingues (<code><multi></code>) dans les objets éditoriaux et ayant définit la variable globale <code>forcer_lang</code> à <code>true</code>.<br />L’option <em>Page d’accueil seulement</em> affichera le formulaire de sélection de langue uniquement sur la page d’accueil.<br />L’option <em>Retour à la page d’accueil</em> affichera le formulaire sur toutes les pages, mais le choix d’une langue entraînera le retour à la page d’accueil dans la langue choisie.<br />Enfin, l’option <em>Liens de traduction</em> correspond aux sites utilisant des liens de traduction entre articles. Le formulaire de choix de la langue ne sera affiché que sur les pages ne correspondant pas à un objet éditorial (accueil, plan du site, etc.). Sur les pages des articles, le formulaire sera affiché si des traductions sont disponibles et pointera sur ces traductions. Le fonctionnement sera équivalent sur les pages des rubriques si le plugin <em>trad_rub</em> est installé.',

	// L
	'label_choix_menu_lang_defaut' => 'Défaut',
	'label_choix_menu_lang_liens_trad' => 'Liens de traduction',
	'label_choix_menu_lang_masquer' => 'Masquer sur toutes les pages',
	'label_choix_menu_lang_retour_sommaire' => 'Retour à la page d’accueil',
	'label_choix_menu_lang_sommaire' => 'Page d’accueil seulement',
	'label_liens_add' => 'Liens additionnels',
	'label_masquer_connexion' => 'Lien de connexion',
	'label_masquer_logo' => 'Logo du site',
	'label_masquer_plan' => 'Plan du site',
	'label_masquer_rss' => 'Flux RSS',
	'label_masquer_slogan' => 'Slogan du site',
	'label_menu_lang' => 'Menu de langues',
	'label_options_en_tete' => 'Options de l’en-tête de page',
	'label_options_pied' => 'Options du pied de page',
	'label_taille_logo' => 'Taille maximum du logo en pixels',

	// N
	'nom_bloc_post_contenu' => 'Post-Contenu',
	'nom_bloc_post_extra' => 'Post-Extra',
	'nom_bloc_post_navigation' => 'Post-Navigation',
	'nom_bloc_pre_contenu' => 'Pré-Contenu',
	'nom_bloc_pre_extra' => 'Pré-Extra',
	'nom_bloc_pre_navigation' => 'Pré-Navigation',
	'nom_page-401' => 'Erreur 401',
	'nom_page-404' => 'Erreur 404',
	'nom_page-agenda' => 'Agenda',
	'nom_page-auteurs' => 'Auteurs',
	'nom_page-forum' => 'Forum',
	'nom_page-jour' => 'Jour',
	'nom_page-login' => 'Se connecter',
	'nom_page-mots' => 'Mots-Clés',
	'nom_page-plan' => 'Plan du site',
	'nom_page-recherche' => 'Recherche sur le site',
	'nom_page-sommaire' => 'Page d’accueil du site',
	'nom_page-spip_pass' => 'Mot de passe oublié',
	'nom_page_article' => 'Article',
	'nom_page_auteur' => 'Auteur',
	'nom_page_breve' => 'Brève',
	'nom_page_evenement' => 'Évènement',
	'nom_page_groupe_mots' => 'Groupe de mots-clés',
	'nom_page_mot' => 'Mot-Clé',
	'nom_page_rubrique' => 'Rubrique',
	'nom_page_site' => 'Site référencé',
	'nom_pagedefaut' => 'Page par défaut',

	// Z
	'zvide' => 'Zpip-vide'
);

?>
