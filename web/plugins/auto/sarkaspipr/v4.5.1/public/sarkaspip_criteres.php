<?php
/**
 * Squelette SarkaSPIP v4
 * (c) 2005-2012 Licence GPL 3
 */

if (!defined("_ECRIRE_INC_VERSION")) return;

// =======================================================================================================================================
// Critere : tout_voir
// =======================================================================================================================================
// Auteur: SarkASmeL
// Fonction : permet d'Žviter une erreur quand le plugin ACCES RESTREINT n'est pas actif. Le critre ne fait rien
// =======================================================================================================================================
//
if (!defined('_DIR_PLUGIN_ACCESRESTREINT')) {
	function critere_tout_voir($idb, &$boucles, $crit) {
		return NULL;
	}
}
?>