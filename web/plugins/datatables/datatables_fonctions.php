<?php
/*
Plugin DataTables   
//o

Copyright(c) 2017 DnC
Auteurs : Bertrand Degoy
Licence : GPL 3
*/

/**
* Retourne un tableau contenant les noms de champ de la table indiquée et leur type.
* Exemple : 
* 
* @param mixed $table
*/
function filtre_table_champs($table) {
    
    include_spip('base/trouver_table');
    
    $trouver_table = charger_fonction('trouver_table', 'base');
    $desc = $trouver_table($table);
    //echo '<pre>'; print_r($desc['field']); echo '</pre>';
    
    $fields = array();
    foreach ( $desc['field'] as $name => $value ) {
        $type = explode(' ', trim($value))[0];
        $fields[$name] = $type;    
    }
    
    return $fields;
}

/**
* Retourne le nom du champ id de la table, qui doit être le premier
* 
* @param mixed $table
*/
function filtre_table_champ_id($table) {
     return 'id_' . substr($table, 0, -1);    
}



function filtre_implode($array) {
    return @implode(',', $array);
}
?>
