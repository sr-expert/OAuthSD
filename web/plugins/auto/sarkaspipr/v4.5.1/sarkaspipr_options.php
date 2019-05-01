<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

if (!isset($GLOBALS['z_blocs']))
	$GLOBALS['z_blocs'] = array('content','aside','extra','head','head_js','header','footer','breadcrumb');

define('_ALBUMS_INSERT_HEAD_CSS',false);
define('_ZENGARDEN_FILTRE_THEMES','spipr');

// Liste des rubriques specialisees standard du squelette
// Pour ajouter des rubriques perso, definir de la meme facon les constantes _PERSO_XXX
// dans le fichier mes_options.php
if (!defined('_SARKASPIP_MOT_SECTEURS_SPECIALISES')) define('_SARKASPIP_MOT_SECTEURS_SPECIALISES', 'agenda:galerie:squelette:forum');
if (!defined('_SARKASPIP_TYPE_SECTEURS_SPECIALISES')) define('_SARKASPIP_TYPE_SECTEURS_SPECIALISES', 'config:config:config:config');
if (!defined('_SARKASPIP_FOND_SECTEURS_SPECIALISES')) define('_SARKASPIP_FOND_SECTEURS_SPECIALISES', 'sarkaspip/agenda:sarkaspip/galerie:sarkaspip/accueil:sarkaspip/forum');

// Modes de debug du squelette
if (!defined('_SARKASPIP_DEBUG_CSS')) define('_SARKASPIP_DEBUG_CSS', 'non');
if (!defined('_SARKASPIP_DEBUG_CFG_ARBO')) define('_SARKASPIP_DEBUG_CFG_ARBO', 'non');
if (!defined('_SARKASPIP_DEBUG_CFG_BOUTON')) define('_SARKASPIP_DEBUG_CFG_BOUTON', 'non');
if (!defined('_SARKASPIP_DEBUG_CFG_FONDS')) define('_SARKASPIP_DEBUG_CFG_FONDS', 'non');

// Liste des pages de configuration dans l'ordre de presentation
if (!defined('_SARKASPIP_PAGES_CONFIG')) define('_SARKASPIP_PAGES_CONFIG',
'accueil
|pages!sommaire:rubrique:article:auteur:breve:site:herbier:forum:agenda:galerie:album:plan:recherche
|elements_transverses!bandeau:menus:comments:formulaires:modeles:noisettes:pied
|referencement!header:backend
|outils!apparence:plugins:maintenance');

// Liste des donnees de configuration du squelette non CFG
// -- Pour les meta
if (!defined('_SARKASPIP_CONFIG_INTRO_META')) define('_SARKASPIP_CONFIG_INTRO_META', 150);
// -- Pour les documents joints et portfolio d'images
if (!defined('_SARKASPIP_CONFIG_LARGEUR_DOCUMENT')) define('_SARKASPIP_CONFIG_LARGEUR_DOCUMENT', 115);
if (!defined('_SARKASPIP_CONFIG_LARGEUR_IMAGE')) define('_SARKASPIP_CONFIG_LARGEUR_IMAGE', 115);
if (!defined('_SARKASPIP_CONFIG_TAILLE_TITRE_DOCUMENT')) define('_SARKASPIP_CONFIG_TAILLE_TITRE_DOCUMENT', 50);
if (!defined('_SARKASPIP_CONFIG_TAILLE_TITRE_IMAGE')) define('_SARKASPIP_CONFIG_TAILLE_TITRE_IMAGE', 50);
if (!defined('_SARKASPIP_CONFIG_TAILLE_DESCR_DOCUMENT')) define('_SARKASPIP_CONFIG_TAILLE_DESCR_DOCUMENT', 100);
if (!defined('_SARKASPIP_CONFIG_TAILLE_DESCR_IMAGE')) define('_SARKASPIP_CONFIG_TAILLE_DESCR_IMAGE', 100);
// -- Pour les modeles d'inscrustation de documents et d'images
if (!defined('_SARKASPIP_CONFIG_IMG_TAILLE_MAX_EMBED')) define('_SARKASPIP_CONFIG_IMG_TAILLE_MAX_EMBED', 500);
if (!defined('_SARKASPIP_CONFIG_DOC_LARGEUR_MIN')) define('_SARKASPIP_CONFIG_DOC_LARGEUR_MIN', 120);
if (!defined('_SARKASPIP_CONFIG_DOC_LARGEUR_MAX')) define('_SARKASPIP_CONFIG_DOC_LARGEUR_MAX', 350);
// -- Pour les vignettes de la galerie et des albums
if (!defined('_SARKASPIP_CONFIG_LARGEUR_GALERIE')) define('_SARKASPIP_CONFIG_LARGEUR_GALERIE', 80);
if (!defined('_SARKASPIP_CONFIG_TAILLE_MAX_VIGNETTE_1')) define('_SARKASPIP_CONFIG_TAILLE_MAX_VIGNETTE_1', 80);
if (!defined('_SARKASPIP_CONFIG_TAILLE_MAX_VIGNETTE_2')) define('_SARKASPIP_CONFIG_TAILLE_MAX_VIGNETTE_2', 40);
if (!defined('_SARKASPIP_CONFIG_TAILLE_MAX_VIGNETTE_3')) define('_SARKASPIP_CONFIG_TAILLE_MAX_VIGNETTE_3', 100);
if (!defined('_SARKASPIP_CONFIG_TAILLE_MAX_VIGNETTE_4')) define('_SARKASPIP_CONFIG_TAILLE_MAX_VIGNETTE_4', 120);
if (!defined('_SARKASPIP_CONFIG_TAILLE_MAX_PHOTO')) define('_SARKASPIP_CONFIG_TAILLE_MAX_PHOTO', 400);
// -- Pour les icones de rainette
if (!defined('_SARKASPIP_CONFIG_LARGEUR_ICONE_METEO')) define('_SARKASPIP_CONFIG_LARGEUR_ICONE_METEO', 80);
// -- Pour le contact du site
if (!defined('_SARKASPIP_CONFIG_AUTORISATION_CONTACT')) define('_SARKASPIP_CONFIG_AUTORISATION_CONTACT', '0minirezo');
// -- Pour la fenetre de dialgue utilisant le plugin SHOUTBOX
if (!defined('_SARKASPIP_CONFIG_SHOUTBOX_TAILLE')) define('_SARKASPIP_CONFIG_SHOUTBOX_TAILLE', 50);
// -- Pour le chemin du bandeau
if (!defined('_SARKASPIP_CONFIG_SYMBOLE_CHEMIN')) define('_SARKASPIP_CONFIG_SYMBOLE_CHEMIN', '&gt;');
// -- Pour la pagination
if (!defined('_SARKASPIP_CONFIG_SYMBOLE_PAGINATION')) define('_SARKASPIP_CONFIG_SYMBOLE_PAGINATION', '&nbsp;|&nbsp;');
// -- Pour l'agenda
if (!defined('_SARKASPIP_CONFIG_AGENDA_ANCRE_PAGINATION')) define('_SARKASPIP_CONFIG_AGENDA_ANCRE_PAGINATION', 'evenement');
if (!defined('_SARKASPIP_CONFIG_AGENDA_SYMBOLE_SUIVANT')) define('_SARKASPIP_CONFIG_AGENDA_SYMBOLE_SUIVANT', '&gt;&gt;');
if (!defined('_SARKASPIP_CONFIG_AGENDA_SYMBOLE_PRECEDENT')) define('_SARKASPIP_CONFIG_AGENDA_SYMBOLE_PRECEDENT', '&lt;&lt;');
// -- Pour la page afaire (plugin Tickets)
if (!defined('_SARKASPIP_AFAIRE_JALONS_AFFICHES')) define('_SARKASPIP_AFAIRE_JALONS_AFFICHES', '');
if (!defined('_SARKASPIP_AFAIRE_ID_ARTICLE')) define('_SARKASPIP_AFAIRE_ID_ARTICLE', '0');
if (!defined('_SARKASPIP_AFAIRE_TAILLE_LOGO')) define('_SARKASPIP_AFAIRE_TAILLE_LOGO', '150');
// Configuration minimale des plugins utilises par le squelette
// -- Plugin BOUTONS TEXTE
if (!defined('_SARKASPIP_CONFIG_BOUTONSTEXTE_SELECTOR')) define('_SARKASPIP_CONFIG_BOUTONSTEXTE_SELECTOR', '#wrapper');
if (!defined('_SARKASPIP_CONFIG_BOUTONSTEXTE_TXTONLY')) define('_SARKASPIP_CONFIG_BOUTONSTEXTE_TXTONLY', '_');
// -- Plugin MEDIABOX
if (!defined('_SARKASPIP_CONFIG_MEDIABOX_ACTIF')) define('_SARKASPIP_CONFIG_MEDIABOX_ACTIF', 'oui');
if (!defined('_SARKASPIP_CONFIG_MEDIABOX_TOUT')) define('_SARKASPIP_CONFIG_MEDIABOX_TOUT', 'non');
if (!defined('_SARKASPIP_CONFIG_MEDIABOX_IMAGE')) define('_SARKASPIP_CONFIG_MEDIABOX_IMAGE', '.mediabox');
if (!defined('_SARKASPIP_CONFIG_MEDIABOX_GALERIE')) define('_SARKASPIP_CONFIG_MEDIABOX_GALERIE', '.galerie .mediabox');
if (!defined('_SARKASPIP_CONFIG_MEDIABOX_SKIN')) define('_SARKASPIP_CONFIG_MEDIABOX_SKIN', 'white-shadow');
// -- Plugin SOCIALTAGS
if (!defined('_SARKASPIP_CONFIG_SOCIALTAGS_SELECTOR')) define('_SARKASPIP_CONFIG_SOCIALTAGS_SELECTOR', '#socialtags');
if (!defined('_SARKASPIP_CONFIG_SOCIALTAGS_TAGS')) define('_SARKASPIP_CONFIG_SOCIALTAGS_TAGS', 'delicious:facebook:google:netvibes');



?>
