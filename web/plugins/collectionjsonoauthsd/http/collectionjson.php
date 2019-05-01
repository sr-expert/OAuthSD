<?php
/*
Projet OAuthSD
API HTTP REST
Adaptation du plugin collectionjson à OAuthSD.   

Format json

copyright(c) 2019 Bertrand Degoy DnC
*/

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

//include_spip('collectionjson_fonctions');

/**
 * Contenu collection+json d'une erreur
 *
 * @param int $code Le code HTTP de l'erreur à générer
 * @return string Retourne le contenu de l'erreur à renvoyer dans la réponse
 */
function http_collectionjson_erreur_dist($code, $request, $response) {
	$response->setStatusCode($code);
	$erreur = array('code' => "$code");
	
	switch ($code) {
		case '401':
			$erreur['title'] = _T('http:erreur_401_titre');
			$erreur['message'] = _T('http:erreur_401_message');
			break;
		case '404':
			$erreur['title'] = _T('http:erreur_404_titre');
			$erreur['message'] = _T('http:erreur_404_message');
			break;
		case '415':
			$erreur['title'] = _T('http:erreur_415_titre');
			$erreur['message'] = _T('http:erreur_415_message');
			break;
         case '500':
            $erreur['title'] = 'Server error';
            break;
		default:
			$erreur = false;
	}
	
	// Si on reconnait une erreur on l'encapsule dans une collection avec erreur
	if ($erreur) {
		include_spip('inc/filtres');
		
		$response->headers->set('Content-Type', 'application/json');
		$response->setContent(json_encode(array(
			'collection' => array(
				'version' => '1.0',
				'href' => url_absolue(self('&')),
				'error' => $erreur,
			),
		)));
	}
	else {
		$response->setContent('');
	}
	
	return $response;
}

/**
 * Index général de l'API
 * http://site/http.api/collectionjson/
 *
 * @param Request $request	: L'objet Request contenant la requête HTTP
 * @param Response $response : L'objet Response qui contiendra la
 *							  réponse envoyée à l'utilisateur
 *
 * @return Response Retourne un objet Response modifié suivant ce
 *					qu'on a trouvé
 */
function http_collectionjson_get_index_dist($request, $response) {
	include_spip('base/objets');
	include_spip('inc/autoriser');
	
	$links = array();
	foreach (lister_tables_objets_sql() as $table => $desc) {
		if (autoriser('get_collection', table_objet($table))) {
			$links[] = array(
				'rel' => 'collection',
				'name' => table_objet($table),
				'prompt' => _T($desc['texte_objets']),
				'href' => rtrim(url_absolue(self('&')), '/') . '/' . table_objet($table) . '/',
			);
		}
	}
	
	$json = array(
		'collection' => array(
			'version' => '1.0',
			'href' => url_absolue(self('&')),
			'links' => $links,
		),
	);
	
	// On le passe tout ça dans un pipeline avant de retourner la réponse
	$json = pipeline(
		'http_collectionjson_get_index_contenu',
		array(
			'args' => array(
				'requete' => $request,
				'reponse' => $response,
			),
			'data' => $json,
		)
	);
	
	$response->setStatusCode(200);
	$response->setCharset('utf-8');
	$response->headers->set('Content-Type', 'application/json');
	$response->setContent(json_encode($json));

	return $response;
}

/**
 * GET sur une collection
 * http://site/http.api/collectionjson/patates
 * 
 * @param Request $request L'objet Request contenant la requête HTTP
 * @param Response $response L'objet Response qui contiendra la réponse envoyée à l'utilisateur
 * @return Response Retourne un objet Response modifié suivant ce qu'on a trouvé
 */
function http_collectionjson_get_collection_dist($request, $response) {
	$format = $request->attributes->get('format');
	$collection = $request->attributes->get('collection');
	$contexte = $request->query->all();
	
	// S'il existe une fonction globale, dédiée à ce type de ressource, qui gère TOUTE la requête, on n'utilise QUE ça
	// Cette fonction doit donc évidemment renvoyer un objet Response valide
	if ($fonction_collection = charger_fonction('get_collection', "http/$format/$collection/", true)) {
		$response = $fonction_collection($request, $response);
	}
	// Sinon on essaye de trouver différentes méthodes pour produire le JSON
	else {
		$json = collectionjson_get_collection($collection, $contexte);

		// COMPAT : à remplacer par le nouveau pipeline "collectionjson_get_collection"
		// peut-être à supprimer directement si personne ne l'a jamais utilisé…
		$json = pipeline(
			'http_collectionjson_get_collection_contenu',
			array(
				'args' => array(
					'requete' => $request,
					'reponse' => $response,
				),
				'data' => $json,
			)
		);
		
		// Si on finit avec un truc vide, on génère une 404
		if (empty($json)) {
			$fonction_erreur = charger_fonction('erreur', 'http/collectionjson/');
			$response = $fonction_erreur(404, $request, $response);
		}
		// Sinon on encode et on renvoie correctement la réponse
		else {
			$json = json_encode($json);
			
			$response->setStatusCode(200);
			$response->setCharset('utf-8');
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent($json);
		}
	}
	
	return $response;
}

/*
 * GET sur une ressource
 * http://site/http.api/collectionjson/patates/1234
 * 
 * @param Request $request L'objet Request contenant la requête HTTP
 * @param Response $response L'objet Response qui contiendra la réponse envoyée à l'utilisateur
 * @return Response Retourne un objet Response modifié suivant ce qu'on a trouvé
 */
function http_collectionjson_get_ressource_dist($request, $response){
	$format = $request->attributes->get('format');
	$collection = $request->attributes->get('collection');
	
	// S'il existe une fonction globale, dédiée à ce type de ressource, qui gère TOUTE la requête, on n'utilise QUE ça
	// Cette fonction doit donc évidemment renvoyer un objet Response valide
	if ($fonction_ressource = charger_fonction('get_ressource', "http/$format/$collection/", true)) {
		$response = $fonction_ressource($request, $response);
	}
	// Sinon on essaye de trouver différentes méthodes pour produire le JSON et en déduire les headers :
	// - par une fonction dédiée au JSON
	// - par un squelette
	// - par un échafaudage générique
	else {
		$json = array();
		
		// S'il existe une fonction dédiée au contenu d'une ressource de cette collection, on l'utilise
		// Cette fonction ne doit retourner QUE le contenu JSON
		if ($fonction_ressource_contenu = charger_fonction('get_ressource_contenu', "http/$format/$collection/", true)) {
			$json = $fonction_ressource_contenu($request, $response);
		}
		// Sinon on essaye de le remplir avec un squelette
		else {
			// Pour l'instant on va simplement chercher un squelette du type de la ressource
			// Le squelette prend en contexte les paramètres du GET + l'identifiant de la ressource en essayant de faire au mieux
			include_spip('base/objets');
			$ressource = $request->attributes->get('ressource');
			$cle = id_table_objet($collection);
			$contexte = array(
				$cle => $ressource,
				'ressource' => $ressource,
			);
			$contexte = array_merge($request->query->all(), $request->attributes->all(), $contexte);
			
			if ($skel = trim(recuperer_fond("http/$format/$collection-ressource", $contexte))) {
				// On décode ce qu'on a trouvé pour avoir un tableau PHP
				$json = json_decode($skel, true);
			}
		}
		
		// Si on n'a toujours aucun contenu json, et que la collection est bien un objet SPIP,
		// on en échafaude un avec les API d'objets
		if (
			empty($json)
			and $table_collection = table_objet_sql($collection)
			and $objets = lister_tables_objets_sql()
			and isset($objets[$table_collection])
		) {
			$objet = objet_type($collection);
			$id_objet = intval($ressource);
			
			$item = collectionjson_get_objet($objet, $id_objet);
			
			$json = array(
				'collection' => array(
					'version' => '1.0',
					'href' => url_absolue(self('&')),
					'items' => array(
						$item,
					)
				),
			);
		}
		
		// On passe le json dans un pipeline
		$json = pipeline(
			'http_collectionjson_get_ressource_contenu',
			array(
				'args' => array(
					'requete' => $request,
					'reponse' => $response,
				),
				'data' => $json,
			)
		);
		
		// Si le json n'est pas vide
		if (!empty($json)) {
			// On le réencode en vrai JSON
			$json = json_encode($json);
			
			// Et la réponse est ok
			$response->setStatusCode(200);
			$response->setCharset('utf-8');
			$response->headers->set('Content-Type', 'application/json');
			$response->setContent($json);
		}
		// Si on ne trouve rien c'est que ça n'existe pas
		else {
			// On utilise la fonction d'erreur générique pour renvoyer dans le bon format
			$fonction_erreur = charger_fonction('erreur', "http/$format/");
			$response = $fonction_erreur(404, $request, $response);
		}
	}
	
	return $response;
}

/**
 * POST sur une collection : création d'une nouvelle ressource
 * http://site/http.api/collectionjson/patates
 *
 * @param Request $request
 * @param Response $response
 * @return Response
 */
function http_collectionjson_post_collection_dist($request, $response) {
	$format = $request->attributes->get('format');
	$collection = $request->attributes->get('collection');
	
	// S'il existe une fonction globale, dédiée à ce type de ressource, qui gère TOUTE la requête, on n'utilise QUE ça
	// Cette fonction doit donc évidemment renvoyer un objet Response valide
	if ($fonction_ressource = charger_fonction('post_collection', "http/$format/$collection/", true)) {
		$response = $fonction_ressource($request, $response);
	}
	// Sinon on échafaude en utilisant l'API des objets
	else {
		include_spip('base/objets');
		$objet= objet_type($collection);
		$response = collectionjson_editer_objet($objet, 'new', $request->getContent(), $request, $response);
	}
	
	return $response;
}

/**
 * PUT sur une ressource : modification d'une ressource existante
 * http://site/http.api/collectionjson/patates/1234
 *
 * @param Request $request
 * @param Response $response
 * @return Response
 */
function http_collectionjson_put_ressource_dist($request, $response) {
	$format = $request->attributes->get('format');
	$collection = $request->attributes->get('collection');
	
	// S'il existe une fonction globale, dédiée à ce type de ressource, qui gère TOUTE la requête, on n'utilise QUE ça
	// Cette fonction doit donc évidemment renvoyer un objet Response valide
	if ($fonction_ressource = charger_fonction('put_ressource', "http/$format/$collection/", true)) {
		$response = $fonction_ressource($request, $response);
	}
	// Sinon on échafaude en utilisant l'API des objets
	else {
		include_spip('base/objets');
		$id_objet = intval($request->attributes->get('ressource'));
		$objet= objet_type($collection);
		$response = collectionjson_editer_objet($objet, $id_objet, $request->getContent(), $request, $response);
	}
	
	return $response;
}

/**
 * Vue générique d'un objet en JSON
 * 
 * Cette fonction sert à mutualiser le code d'échafaudage pour générer le GET d'un objet.
 * 
 * @param string $objet Type de l’objet dont on veut générer la vue
 * @param int $id_objet Identifiant de l’objet dont on veut générer la vue
 * @param string $contenu Optionnellement, les champs SQL déjà récupérés de l’objet, pour éviter de faire une requête
 */
function collectionjson_get_objet($objet, $id_objet, $champs=array()) {
	include_spip('inc/filtres');
	
	$collection = table_objet($objet); // l'objet au pluriel
	$table_sql = table_objet_sql($objet);
	$cle = id_table_objet($objet);
	$description = lister_tables_objets_sql($table_sql);
	
	// S'il n'y a pas de champs, on fait une requête, par défaut on récupère uniquement les champs éditables
	if (empty($champs)) {
		//$select = isset($description['champs_editables']) ? $description['champs_editables'] : '*';
        $select = get_public_fields( $objet );      
		$champs = sql_fetsel($select, $table_sql, $cle . ' = ' . intval($id_objet));
	}
    
    if (empty($champs)) {
        // erreur : retourner
        return array();     //???
    }
	
	$data = array();
	foreach ($champs as $champ=>$valeur) {
		$data[] = array('name' => $champ, 'value' => $valeur);
	}
	
	$item = array(
		'href' => url_absolue("http.api/collectionjson/$collection/$id_objet"),
		'links' => array(
			array('rel' => 'edit', 'href' => url_absolue("http.api/collectionjson/$collection/$id_objet")),
			array('rel' => 'alternate', 'type' => 'text/html', 'href' => url_absolue(generer_url_entite($id_objet, $objet))),
		),
		'data' => $data,
	);
	
	return $item;
}

/**
 * Édition générique d'un objet en JSON
 * 
 * Cette fonction sert à mutualiser le code d'échafaudage entre le POST et le PUT pour créer ou modifier un objet.
 * 
 * @param string $objet Type de l’objet à éditer
 * @param int $id_objet Identifiant de l’objet à éditer
 * @param string $contenu Contenu JSON de l’objet à éditer
 * @param Request $request
 * @param Response $response
 * @return Response
 */
function collectionjson_editer_objet($objet, $id_objet, $contenu, $request, $response) {
	// Si la requête a bien un contenu et qu'on a bien un tableau PHP et qu'on a au moins le bon tableau "data"
	if (
		$contenu
		and $json = json_decode($contenu, true)
		and is_array($json)
		and isset($json['collection']['items'][0]['data'])
		and $data = $json['collection']['items'][0]['data']
		and is_array($data)
	) {
		include_spip('inc/filtres');
		include_spip('base/objets');
		$cle_objet = id_table_objet($objet);
		$new = !intval($id_objet);
		
		// Pour chaque champ envoyé, on va faire un set_request() de SPIP
		foreach ($data as $champ) {
			if (isset($champ['name']) and isset($champ['value'])) {
				set_request($champ['name'], $champ['value']);
			}
		}
		
		// On va chercher la fonction de vérification de cet objet
		$erreurs = array();
		if ($fonction_verifier = charger_fonction('verifier', "formulaires/editer_$objet/", true)) {
			$erreurs = $fonction_verifier($id_objet);
		}
		
		// On passe les erreurs dans le pipeline "verifier" (par exemple pour Saisies)
		$erreurs = pipeline('formulaire_verifier', array(
			'args' => array(
				'form' => "editer_$objet",
				'args' => array($id_objet),
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
			$retours = array();
			if ($fonction_traiter = charger_fonction('traiter', "formulaires/editer_$objet/", true)) {
				$retours = $fonction_traiter($id_objet);
			}
			
			// On passe dans le pipeline "traiter"
			$retours = pipeline('formulaire_traiter', array(
				'args' => array(
					'form' => "editer_$objet",
					'args' => array($id_objet),
				),
				'data' => $retours,
			));
			
			// Si on a bien modifié l'objet sans erreur
			if (!$retours['message_erreur'] and $id_objet = $retours[$cle_objet]) {
				// On va cherche la fonction qui génère la vue d'une ressource
				if ($fonction_ressource = charger_fonction('get_ressource', 'http/collectionjson/', true)) {
					// On ajoute à la requête, l'identifiant de la nouvelle ressource
					$request->attributes->set('ressource', $id_objet);
					$response = $fonction_ressource($request, $response);
				}
				// Si c'était une création, on renvoie 201
				if ($new) {
					$response->setStatusCode(201);
				}
			}
		}
	}
	else {
		// On utilise la fonction d'erreur générique pour renvoyer dans le bon format
		$fonction_erreur = charger_fonction('erreur', "http/collectionjson/");
		$response = $fonction_erreur(415, $request, $response);
	}
	
	return $response;
}

// Ce qui suit devrait être dans le fichier collectionjsonoauthsd_fonctions.php, mais SPIP ne le trouve pas ???

/**
 * Produit le contenu du JSON d'une collection
 * - par un squelette
 * - par un échafaudage générique
 * 
 * @param string $collection Nom de la collection à générer
 * @param array $contexte Tableau associatif de l'environnement (à priori venant du GET)
 * @return array Retourne un tableau associatif représentant la collection suivant la grammaire Collection+JSON ou un tableau vide si erreur (générera une 404)
 **/
function collectionjson_get_collection($collection, $contexte) {
    // Allons chercher un squelette de base qui génère le JSON de la collection demandée
    // Le squelette prend en contexte les paramètres du GET uniquement
    if ($json = recuperer_fond("http/collectionjson/$collection", $contexte)) {
        // On décode ce qu'on a trouvé
        $json = json_decode($json, true);
    }
    // Si on ne trouve rien on essaie de s'appuyer sur l'API objet pour générer un JSON
    else  {
        include_spip('base/abstract_sql');
        include_spip('base/objets');
        
        // Si la collection demandée ne correspond pas à une table
        // d'objet on arrête tout
        if (!in_array(
            table_objet_sql($collection),
            array_keys(lister_tables_objets_sql())
        )) {
            // On ne renvoit rien, et ça devrait générer une erreur
            return array();
        }
        
        // On génère la pagination si besoin
        $links = array();
        //$pagination = 10;
        // Générer la pagination en fonction du paramètre count, 20 par défaut
        $pagination = isset($contexte['count']) ? $contexte['count'] : 20;
        $offset = isset($contexte['offset']) ? $contexte['offset'] : 0;
        $nb_objets = sql_countsel(table_objet_sql($collection));
        
        // On ajoute des liens de pagination
        if ($offset > 0) {
            $offset_precedant = max(0, $offset-$pagination);
            $links[] = array(
                'rel' => 'prev',
                'prompt' => _T('public:page_precedente'),
                'href' => url_absolue(
                    parametre_url(self('&'), 'offset', $offset_precedant)),
            );
        }
        if (($offset + $pagination) < $nb_objets) {
            $offset_suivant = $offset + $pagination;
            $links[] = array(
                'rel' => 'prev',
                'prompt' => _T('public:page_suivante'),
                'href' => url_absolue(
                    parametre_url(self('&'), 'offset', $offset_suivant)),
            );
        }
        
        // On requête l'ensemble de cette page d'un coup
        $table_collection = table_objet_sql($collection);
        $cle_objet = id_table_objet($table_collection);
        $description = lister_tables_objets_sql($table_collection);
        $select = isset($description['champs_editables']) ? array_merge($description['champs_editables'], array($cle_objet)) : '*';
        // Ne pas lister les champs sensibles !
        foreach( $select as $index => $value ) {
            if ( strpos(API_HTTP_CHAMPS_SENSIBLES, $value) !== False ) unset($select[$index]);
        }
        
        $lignes = sql_allfetsel($select, $table_collection,'','','',"$offset,$pagination");
        
        $items = array();
        foreach ($lignes as $champs) {
            $items[] = collectionjson_get_objet(objet_type($table_collection), $champs[$cle_objet], $champs);
        }
        
        $json = array(
            'collection' => array(
                'version' => '1.0',
                'href' => url_absolue(parse_url(self('&'), PHP_URL_PATH)),
                'links' => $links,
                'items' => $items,
            ),
        );
    }
    
    // Et on le passe dans un pipeline
    $json = pipeline(
        'collectionjson_get_collection',
        array(
            'args' => array(
                'collection' => $collection,
                'contexte' => $contexte,
            ),
            'data' => $json,
        )
    );
    
    return $json;
}

if (!function_exists('get_public_fields') ) {
    function get_public_fields( $collection ) {
        include_spip('base/objets');

        $table_collection = table_objet_sql($collection);
        $cle_objet = id_table_objet($table_collection);
        $description = lister_tables_objets_sql($table_collection);
        $select = isset($description['champs_editables']) ? array_merge($description['champs_editables'], array($cle_objet)) : '*';

        // Ne pas lister les champs sensibles !
        foreach( $select as $index => $value ) {
            if ( strpos(API_HTTP_CHAMPS_SENSIBLES, $value) !== False ) unset($select[$index]);
        }

        return $select;
    }
}
