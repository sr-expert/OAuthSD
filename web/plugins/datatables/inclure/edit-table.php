<?php
/* //o1
Plugin DataTables
Fonction de callback pour l'édition des champs
Auteur B.Degoy DnC 2017
licence GPL3
*/

include_spip('base/abstract_sql');
   
$table = sql_quote($_POST['table']);
$objet = substr($table, 0, -1);
$table = 'spip_' . $table;

$val=sql_quote($_POST['value']);
$value_post = (isset($val) ? $val : '');
$row_id_post = (isset($_POST['row_id']) ? (int)$_POST['row_id'] : '');          // un id d'objet
$column_post = (isset($_POST['column']) ? sql_quote($_POST['column']) : '');    // un nom de champ

if ( !is_null($column_post) AND !is_null($row_id_post) AND !is_null($value_post) ) {
    sql_updateq($table, array($column_post => $value_post), 'id_' . $objet . "='" . $row_id_post . "'"); 
}

?>