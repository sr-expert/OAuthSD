<?php

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajouter un auteur : suivant le cas, inscription ou ajout par un admin
 *
 * @param Request $request
 * @param Response $response
 * @return void
 */
function http_collectionjson_auteurs_post_collection_dist($request, $response){
	include_spip('inc/session');
	include_spip('inc/autoriser');
	
	// Si c'est pour un visiteur anonyme, on essaye de s'inscrire
	if (
		(!$id_auteur = session_get('id_auteur') or $id_auteur <= 0)
		and $contenu = $request->getContent()
		and $json = json_decode($contenu, true)
		and is_array($json)
		and isset($json['collection']['items'][0]['data'])
		and $data = $json['collection']['items'][0]['data']
		and is_array($data)
	) {
		// Pour chaque champ envoyé, on va faire un set_request() de SPIP
		foreach ($data as $champ) {
			// Seulement pour les 3 champs acceptés pour l'inscription  (les 4 ???)
			if (
				isset($champ['name'])
				and isset($champ['value'])
				and in_array($champ['name'], array('nom_inscription', 'mail_inscription', 'password', 'password_confirmation'))
			) {
				set_request($champ['name'], $champ['value']);
			}
		}
		
		// Inscription en visiteur public par défaut
		// (TODO : pourquoi ? je ne sais pas, je ne sais plus comment on sait le type d'inscription configuré)
		$statut = '6forum';
		
		// On vérifie les erreurs
		$inscription_verifier = charger_fonction('verifier', 'formulaires/inscription');
		$erreurs = $inscription_verifier($statut);
		
		// On passe les erreurs dans le pipeline "verifier" (par exemple pour Saisies)
		$erreurs = pipeline('formulaire_verifier', array(
			'args' => array(
				'form' => 'inscription',
				'args' => array($statut),
			),
			'data' => $erreurs,
		));
		
		// S'il y a des erreurs, on va générer un JSON les listant
		if ($erreurs) {
			$response->setStatusCode(400);
			$response->headers->set('Content-Type', 'application/json');
			$response->setCharset('utf-8');
			
			$json_reponse = array(
				'collection' => array(
					'version' => '1.0',
					'href' => url_absolue(self('&')),
					'error' => array(
						'title' => _T('erreur'),
						'code' => 400,
					),
					'errors' => array(),
				),
			);
			
			foreach ($erreurs as $nom => $erreur) {
				$json_reponse['collection']['errors'][$nom] = array(
					'title' => $erreur,
					'code' => 400,
				);
			}
			$response->setContent(json_encode($json_reponse));
		}
		// Sinon on continue le traitement
		else {
			// On lance l'inscription
			$inscription_traiter = charger_fonction('traiter', 'formulaires/inscription', true);
			$retours_inscription = $inscription_traiter($statut);
			
			// Si on a bien ajouté un nouvel auteur et qu'on le récupère
			if (
				$auteur = sql_fetsel('*', 'spip_auteurs', 'email = '.sql_quote(_request('mail_inscription')))
				and $id_auteur = intval($auteur['id_auteur'])
			) {
				// On connecte le nouvel utilisateur directement !
				include_spip('inc/auth');
				auth_loger($auteur);
				
				// On va cherche la fonction qui génère la vue d'une ressource
				if ($fonction_ressource = charger_fonction('get_ressource', 'http/collectionjson/', true)) {
					// On ajoute à la requête, l'identifiant de la nouvelle ressource
					$request->attributes->set('ressource', $id_auteur);
					$response = $fonction_ressource($request, $response);
					
					// C'est une création, on renvoie 201
					$response->setStatusCode(201);
				}
			}
		}
	}
	// Sinon il faut pouvoir créer un auteur, et on utilise l'édition classique
	elseif (autoriser('creer', 'auteur')) {
		$response = collectionjson_editer_objet('auteur', 'new', $request->getContent(), $request, $response);
	}
	// Sinon on comprend pas ce qui se passe
	else {
		// On utilise la fonction d'erreur générique pour renvoyer dans le bon format
		$fonction_erreur = charger_fonction('erreur', "http/collectionjson/");
		$response = $fonction_erreur(415, $request, $response);
	}
	
	return $response;
}
