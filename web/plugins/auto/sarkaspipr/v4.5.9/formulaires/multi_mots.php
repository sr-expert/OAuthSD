<?php
function formulaires_multi_mots_charger_dist($mot){
	// $mot vaut l'id du mot selectionne lorsque l'appel s'est fait a partir des noisettes des mots cles de la colonne extra
	// $mot est nul lorsque l'appel se fait directement par le bouton mot-cle du menu inc_nav_raccourci, 

	$valeurs = array();
	$valeurs['mot_1'] = $mot;
	$valeurs['mot_2'] = '';
	$valeurs['mot_3'] = '';
	// Si au chargement $mot existe c'est qu'on vient d'un mot-cle direct
	if ($mot) 
		$valeurs['message_ok'] = 'oui';

	return $valeurs;
}

function formulaires_multi_mots_verifier_dist($mot){
   
	$valeurs = array();
	$valeurs['mot_1'] = intval(_request('mot_1'));
	$valeurs['mot_2'] = intval(_request('mot_2'));
	$valeurs['mot_3'] = intval(_request('mot_3'));
	if (array_sum($valeurs) == 0) {
		return array('message_erreur' =>_T('sarkaspip:choisir_un_critere'));
	}
}
function formulaires_multi_mots_traiter_dist($mot){
	
	return array('message_ok' => 'oui');
}
?>