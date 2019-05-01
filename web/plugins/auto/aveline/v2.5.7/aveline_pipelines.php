<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Pipeline noizetier_config_export pour ajouter la version_base des noisettes au YAML d'export du noizetier.
 *
 * @param array $config
 *
 * @return array
 */
function aveline_noizetier_config_export($config)
{
	$config['aveline_base_version'] = $GLOBALS['meta']['aveline_base_version'];

	return $config;
}

/**
 * Pipeline noizetier_config_import pour traiter le tableau de noisettes au cas où les noisettes soient d'une ancienne version.
 *
 * @param array $config
 *
 * @return array
 */
function aveline_noizetier_config_import($config)
{
	$version_actuelle = null;
	// Aveline s'est temporairement appelée Garde-Noisettes (GN)
	if (isset($config['aveline_base_version'])) {
		$version_actuelle = $config['aveline_base_version'];
	} elseif (isset($config['gn_base_version'])) {
		$version_actuelle = $config['gn_base_version'];
	}

	if ($version_actuelle) {
		include_spip('aveline_administrations');
		$config['noisettes'] = aveline_maj_noisettes($config['noisettes'], $version_actuelle);
	}

	return $config;
}
