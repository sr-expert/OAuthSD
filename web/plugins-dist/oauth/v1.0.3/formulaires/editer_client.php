<?php
/**
* Gestion du formulaire de d'édition de client
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
* @param int|string $id_client
*     Identifiant du client. 'new' pour un nouveau client.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un client source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du client, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return string
*     Hash du formulaire
*/
function formulaires_editer_client_identifier_dist($id_client='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){
    return serialize(array(intval($id_client)));
}

/**
* Chargement du formulaire d'édition de client
*
* Déclarer les champs postés et y intégrer les valeurs par défaut
*
* @uses formulaires_editer_objet_charger()
*
* @param int|string $id_client
*     Identifiant du client. 'new' pour un nouveau client.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un client source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du client, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Environnement du formulaire
*/
function formulaires_editer_client_charger_dist($id_client='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){

    if ( !is_null(_request('id_client')) ) $id_client = _request('id_client');

    $valeurs = formulaires_editer_objet_charger('client', $id_client, '', $lier_trad, $retour, $config_fonc, $row, $hidden);

    // Pour restituer la saisie multiple grant_types
    $valeurs['grant_types'] = explode(' ', $valeurs['grant_types'] );

    return $valeurs;
}

/**
* Vérifications du formulaire d'édition de client
*
* Vérifier les champs postés et signaler d'éventuelles erreurs
*
* @uses formulaires_editer_objet_verifier()
*
* @param int|string $id_client
*     Identifiant du client. 'new' pour un nouveau client.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un client source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du client, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Tableau des erreurs
*/
function formulaires_editer_client_verifier_dist($id_client='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){
    $erreurs = array();

    // Déduire client_id de id_client
    $id_client = (int)_request('id_client');
    if ( $id_client == 0 ) $id_client = 'new';
    $client_row = sql_fetsel('*', 'spip_clients', "id_client='$id_client'");
    $client_id = $client_row['client_id'];
    $_GET['client_id'] = $client_id;    
    
    // Créer si nécessaire une paire de clé publique/privée avec RS256              //*****
    
    /*
    // Les clés existent ?
    $key_row = sql_fetsel('*', 'spip_public_keys', "client_id='$client_id'"); 
    if ( empty($key_row) ) {   
        $config = array(
            "digest_alg" => "sha256",            // Vu de JWT, correspond au défaut : RS256
            "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        // Create the private and public key
        $res = openssl_pkey_new($config);
        if ( !is_null($res)) {
            // Extract the private key from $res to $privKey
            openssl_pkey_export($res, $privKey);
            $key_row['private_key'] = $privKey;
            // Extract the public key from $res to $pubKey
            $pubKey = openssl_pkey_get_details($res);
            $key_row['public_key'] = $pubKey["key"];
            // Save in table
            $key_row['client_id'] = $client_id;
            $key_row['id_client'] = $id_client;
            $key_row['encryption_algorithm'] = 'RS256';
            sql_insertq('spip_public_keys', $key_row);
        } else {
            // erreur lors de la génération de la paire de clés
        } 
        
    } //*/
    
    include_spip('inc/pkeys');
    $void = create_and_save_pkeys( $id_client, $client_id );
    //TODO : unicité de client_id : vérifier que client_id n'existe pas déjà avec un autre id_client

    $erreurs = formulaires_editer_objet_verifier('client', $id_client, array('redirect_uri', 'grant_types', 'scope', 'client_id'));

    return $erreurs;
}

/**
* Traitement du formulaire d'édition de client
*
* Traiter les champs postés
*
* @uses formulaires_editer_objet_traiter()
*
* @param int|string $id_client
*     Identifiant du client. 'new' pour un nouveau client.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un client source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du client, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Retours des traitements
*/
function formulaires_editer_client_traiter_dist($id_client='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){

    // Pour l'édition d'un client existant
    if ( !empty(_request('id_client')) ) {
        $id_client = _request('id_client');
    }

    /* Pour enregistrer une saisie multiple (serialize)
    $grant_types_data = _request('grant_types');
    if ( is_array( $grant_types_data ) ) {
    set_request('grant_types', serialize(_request('grant_types')));        
    } //*/

    //* Pour enregistrer une saisie multiple (string)
    $grant_types_data = _request('grant_types');
    if ( is_array( $grant_types_data ) ) {
        set_request('grant_types', implode(' ', $grant_types_data));        
    } //*/


    // Enregistrer 
    $retours = formulaires_editer_objet_traiter('client', $id_client, '', $lier_trad, $retour, $config_fonc, $row, $hidden);

    /* Lier le client à l'auteur courant - NON : apparament c'est automatique ??? Merci la Fabrique !
    include_spip('action/editer_liens');
    $id_auteur = ( (isset($GLOBALS['visiteur_session'])) ? $GLOBALS['visiteur_session']['id_auteur'] : null );
    if ( empty($retour) AND !is_null($id_auteur) ) {
    objet_associer(array('auteur' => $ids), array('client' => $id_client));
    }  */
    return $retours;
}