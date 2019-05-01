<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Tester si on arrive à se connecter ou pas, renvoie soit 401 soit les infos de l'utilisatrice connectée
 *
 * @param Request $request
 * @param Response $response
 * @return void
 */
function http_collectionjson_credentials_get_collection_dist($request, $response){
	include_spip('inc/session');
	include_spip('inc/autoriser');
	
	$auth = charger_fonction('auth', 'inc/');
	$retour = $auth();
	
	// Les deux cas où c'est bien connecté : chaîne vide ou tableau d'infos
	if (
		($retour === '' or is_array($retour))
		and $id_auteur = session_get('id_auteur')
		and $id_auteur > 0
	) {
		// On va cherche la fonction qui génère la vue d'une ressource
		if ($fonction_ressource = charger_fonction('get_ressource', 'http/collectionjson/', true)) {
			// On ajoute à la requête, l'identifiant de la nouvelle ressource et on change la collection pour "auteurs"
			$request->attributes->set('collection', 'auteurs');
			$request->attributes->set('ressource', $id_auteur);
			$response = $fonction_ressource($request, $response);
		}
	}
	// Sinon on comprend pas ce qui se passe
	else {
		// On utilise la fonction d'erreur générique pour renvoyer dans le bon format
		$fonction_erreur = charger_fonction('erreur', "http/collectionjson/");
		$response = $fonction_erreur(401, $request, $response);
	}
	
	return $response;
}
