<?php

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Inclusions nécessaires au bon fonctionnement de formulaires/inscription3_cextras.html
 *
 * Notamment pour la fonction champs_extras_objet
 *
 */
if (defined('_DIR_PLUGIN_CEXTRAS')) {
	include_spip('cextras_pipelines');
	include_spip('inc/cextras');
}
