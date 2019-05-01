<?php
/*
Projet OAuthSD
API HTTP REST pour DataTables

copyright(c) 2019 Bertrand Degoy DnC
*/

/**
* Produit le contenu du JSON d'une collection par un échafaudage générique
* 
* @param string $collection Nom de la collection à générer
* @param array $contexte Tableau associatif de l'environnement (à priori venant du GET)
* @return array Retourne un tableau associatif représentant la collection suivant la grammaire JSON ou un tableau vide si erreur (générera une 404)
**/

function datatables_get_collection($collection, $contexte) {

    // On s'appuie sur l'API objet pour générer un JSON
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

    // On ne génère pas la pagination, mais on en tient compte pour la requête
    $pagination = isset($contexte['count']) ? $contexte['count'] : 20;
    $offset = isset($contexte['offset']) ? $contexte['offset'] : 0;

    // On requête
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
        $items[] = datatables_get_objet(objet_type($table_collection), $champs[$cle_objet], $champs);
    }

    $json = $items;

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



