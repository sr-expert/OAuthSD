<?php
/*
Projet OAuthSD
API HTTP REST pour DataTables
copyright(c) 2019 Bertrand Degoy DnC
*/

// Le format datatables ne gère pas les méthodes POST, PUT et DELETE.

// Sécurité
if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

include_spip('http4datatables_fonctions');

/**
* Contenu json d'une erreur
*
* @param int $code Le code HTTP de l'erreur à générer
* @return string Retourne le contenu de l'erreur à renvoyer dans la réponse
*/
function http_datatables_erreur_dist($code, $request, $response) {
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
        //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG
        include_spip('inc/filtres');

        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode(array(
            'error' => $erreur,
        )));
    }
    else {
        $response->setContent('');
    }

    return $response;
}

/**
* Index général de l'API
* http://site/http.api/datatables/
*
* @param Request $request    : L'objet Request contenant la requête HTTP
* @param Response $response : L'objet Response qui contiendra la
*                              réponse envoyée à l'utilisateur
*
* @return Response Retourne un objet Response modifié suivant ce
*                    qu'on a trouvé
*/
function http_datatables_get_index_dist($request, $response) {
    include_spip('base/objets');
    include_spip('inc/autoriser');

    $links = array();
    foreach (lister_tables_objets_sql() as $table => $desc) {

        if ( API_HTTP_TYPES_AUTORISES !== '' ) {          //*****
            // Vérifier que le type est autorisé
            if ( strpos( API_HTTP_TYPES_AUTORISES, rtrim(table_objet($table),'s') ) === false ) 
                continue;   
        }

        if (autoriser('get_collection', table_objet($table))) {
            $links[] = array(
                'rel' => 'collection',
                'name' => table_objet($table),
                'prompt' => _T($desc['texte_objets']),
                'href' => rtrim(url_absolue(self('&')), '/') . '/' . table_objet($table) . '/',
            );
        } 
    }

    $response->setStatusCode(200);
    $response->setCharset('utf-8');
    $response->headers->set('Content-Type', 'application/json');
    $response->setContent(json_encode($links));

    return $response;
}

/**
* GET sur une collection
* http://site/http.api/json/patates
* 
* @param Request $request L'objet Request contenant la requête HTTP
* @param Response $response L'objet Response qui contiendra la réponse envoyée à l'utilisateur
* @return Response Retourne un objet Response modifié suivant ce qu'on a trouvé
*/
function http_datatables_get_collection_dist($request, $response) {
    
    $format = $request->attributes->get('format');
    $collection = $request->attributes->get('collection');
    $contexte = $request->query->all();
    
    //DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

    // S'il existe une fonction globale, dédiée à ce type de ressource, qui gère TOUTE la requête, on n'utilise QUE ça
    // Cette fonction doit donc évidemment renvoyer un objet Response valide
    if ($fonction_collection = charger_fonction('get_collection', "http/$format/$collection/", true)) {
        $response = $fonction_collection($request, $response);
    }
    // Sinon on essaye de trouver différentes méthodes pour produire le JSON
    else {
        $json = datatables_get_collection($collection, $contexte);

        // Si on finit avec un truc vide, on génère une 404
        if (empty($json)) {
            $fonction_erreur = charger_fonction('erreur', 'http/datatables/');
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
* http://site/http.api/json/patates/1234
* 
* @param Request $request L'objet Request contenant la requête HTTP
* @param Response $response L'objet Response qui contiendra la réponse envoyée à l'utilisateur
* @return Response Retourne un objet Response modifié suivant ce qu'on a trouvé
*/
function http_datatables_get_ressource_dist($request, $response){
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

            $item = datatables_get_objet($objet, $id_objet);
        }

        // Si pas vide
        if (!empty($item)) {
            // On le réencode en vrai JSON
            $json = json_encode($item);

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
* Vue générique d'un objet en JSON
* 
* Cette fonction sert à mutualiser le code d'échafaudage pour générer le GET d'un objet.
* 
* @param string $objet Type de l’objet dont on veut générer la vue
* @param int $id_objet Identifiant de l’objet dont on veut générer la vue
* @param string $contenu Optionnellement, les champs SQL déjà récupérés de l’objet, pour éviter de faire une requête
*/
function datatables_get_objet($objet, $id_objet, $champs=array()) {
    include_spip('inc/filtres');

    $collection = table_objet($objet); // l'objet au pluriel
    $table_sql = table_objet_sql($objet);
    $cle = id_table_objet($objet);
    $description = lister_tables_objets_sql($table_sql);

    // S'il n'y a pas de champs, on fait une requête, par défaut on récupère uniquement les champs éditables
    if (empty($champs)) {
        //$select = isset($description['champs_editables']) ? $description['champs_editables'] : '*';
        $select = get_public_fields( $objet );      //*****
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

    return $data;
}

