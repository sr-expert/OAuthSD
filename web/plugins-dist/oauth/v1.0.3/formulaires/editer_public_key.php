<?php
/**
* Gestion du formulaire de d'édition de public_key
*
* @plugin     OAuth 2.0
* @copyright  2016
* @author     DnC
* @licence    GNU/GPL
* @package    SPIP\Oauth\Formulaires
*/

if (!defined('_ECRIRE_INC_VERSION')) return;

include_spip('inc/actions');
include_spip('inc/editer');

/**
* Identifier le formulaire en faisant abstraction des paramètres qui ne représentent pas l'objet edité
*
* @param int|string $id_public_key
*     Identifiant du public_key. 'new' pour un nouveau public_key.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un public_key source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du public_key, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return string
*     Hash du formulaire
*/
function formulaires_editer_public_key_identifier_dist($id_public_key='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){
    return serialize(array(intval($id_public_key)));
}

/**
* Chargement du formulaire d'édition de public_key
*
* Déclarer les champs postés et y intégrer les valeurs par défaut
*
* @uses formulaires_editer_objet_charger()
*
* @param int|string $id_public_key
*     Identifiant du public_key. 'new' pour un nouveau public_key.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un public_key source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du public_key, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Environnement du formulaire
*/
function formulaires_editer_public_key_charger_dist($id_public_key='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){

    if ( !is_null(_request('id_public_key')) ) $id_public_key = _request('id_public_key');

    // Déduire client_id de id_client
    $id_client = (int)_request('id_client');
    $client_row = sql_fetsel('*', 'spip_clients', "id_client='$id_client'");
    $client_id = $client_row['client_id']; 

    $key_row = sql_fetsel('*', 'spip_public_keys', "client_id='$client_id'");
    $id_public_key = ( !empty($key_row['id_public_key'] ) ? (int)$key_row['id_public_key'] : 'new' ); 

    // Créer si nécessaire une paire de clé publique/privée  
    if ( empty(trim($key_row['public_key'])) AND !empty($client_id) ) {   

        /*
        $config = array(
        "digest_alg" => "sha256",            // correspond au défaut : RS256
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );

        // Create the private and public key
        $res = openssl_pkey_new($config);
        */
        $res = openssl_pkey_new();        // par défaut la clé générée a une longueur de 2048 bits

        // Extract the private key from $res to $privKey
        openssl_pkey_export($res, $privKey);
        $key_row['private_key'] = $privKey;

        // Extract the public key from $res to $pubKey
        $pubKey = openssl_pkey_get_details($res);
        $key_row['public_key'] = $pubKey["key"];

        $key_row['encryption_algorithm'] = 'RS256';
        $key_row['client_id'] = $client_id;
        $key_row['id_client'] = $id_client;

        // Enregistrer une nouvelle paire de clefs
        _save_keys($id_client, $client_id, $privKey, $pubKey['key'], $key_row['encryption_algorithm'], $id_public_key);     

    } 

    $valeurs = $key_row;

    return $valeurs;
}

/**
* Vérifications du formulaire d'édition de public_key
*
* Vérifier les champs postés et signaler d'éventuelles erreurs
*
* @uses formulaires_editer_objet_verifier()
*
* @param int|string $id_public_key
*     Identifiant du public_key. 'new' pour un nouveau public_key.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un public_key source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du public_key, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Tableau des erreurs
*/
function formulaires_editer_public_key_verifier_dist($id_public_key='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){
    $erreurs = array();

    //TODO : unicité de client_id : vérifier que client_id n'existe pas déjà avec un autre id_public_key

    $erreurs = formulaires_editer_objet_verifier('public_key', $id_public_key, array('public_key', 'private_key','client_id'));

    return $erreurs;
}

/**
* Traitement du formulaire d'édition de public_key
*
* Traiter les champs postés
*
* @uses formulaires_editer_objet_traiter()
*
* @param int|string $id_public_key
*     Identifiant du public_key. 'new' pour un nouveau public_key.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un public_key source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du public_key, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Retours des traitements
*/
function formulaires_editer_public_key_traiter_dist($id_public_key='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){

    // Déduire client_id de id_client
    $id_client = (int)_request('id_client');
    $client_row = sql_fetsel('*', 'spip_clients', "id_client='$id_client'");
    $client_id = $client_row['client_id'];

    $key_row = sql_fetsel('*', 'spip_public_keys', "client_id='$client_id'");
    $id_public_key = ( !empty($key_row['id_public_key'] ) ? (int)$key_row['id_public_key'] : 'new' ); 

    // Enregistrer 
    /* public_key n'est pas un objet TODO: en faire un objet
    $retours = formulaires_editer_objet_traiter('public_key', $id_public_key, '', $lier_trad, $retour, $config_fonc, $row, $hidden);
    return $retours; 
    */
    _save_keys($id_client, $client_id, $_POST['private_key'], $_POST['public_key'], $_POST['$encryption_algorithm'], $id_public_key);     
    return array(); 

    //TODO : vérifier le bon enregistrement de client_id et id_client      

}


function _get_client_id() {

    return $client_id; 
}


function _save_keys($id_client, $client_id, $private_key, $public_key, $encryption_algorithm, $id_public_key='new' ) {

    $encryption_algorithm = ( !empty($encryption_algorithm) ? $encryption_algorithm : 'RS256');
    $row = array(
        'id_client' => $id_client,
        'client_id' => $client_id,
        'private_key' => $private_key,
        'public_key' => $public_key,
        'encryption_algorithm' => $encryption_algorithm
    );
    if ( $id_public_key === 'new' ) {  
        $ret = sql_insertq('spip_public_keys', $row); 
    } else {
        $ret = sql_updateq('spip_public_keys', $row, "id_public_key='$id_public_key'");
    }

    return $ret;      
}
