<?php
/**
* Gestion du formulaire de d'édition de user, version étendue.
* 
* Le paramètre id_client définit l'application. 
* S'il n'est pas nul, on lit les scopes de l'application pour en déduire les 
* champs de profil à afficher.
* S'il est nul, on affiche un formulaire minimal.
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
* @param int|string $id_user
*     Identifiant du user. 'new' pour un nouveau user.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un user source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du user, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return string
*     Hash du formulaire
*/
function formulaires_editer_user_ext_identifier_dist($id_user='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){

    return serialize(array(intval($id_user)));

}

/**
* Chargement du formulaire d'édition de user
*
* Déclarer les champs postés et y intégrer les valeurs par défaut
*
* @uses formulaires_editer_objet_charger()
*
* @param int|string $id_user
*     Identifiant du user. 'new' pour un nouveau user.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un user source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du user, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Environnement du formulaire
*/
function formulaires_editer_user_ext_charger_dist($id_user='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){

    $valeurs = formulaires_editer_objet_charger('user', $id_user, '', $lier_trad, $retour, $config_fonc, $row, $hidden);

    $id_client = _request('id_client');

    if ( !is_null($id_client) ) {
        $valeurs['id_client'] = $id_client;
        // Déterminer les scopes disponibles pour le client
        $res = sql_fetsel('scope', 'spip_clients', "id_client='" . $id_client . "'");
        $scopes = $res['scope'];  // liste de scopes séparés par un espace
        if ( !is_null($scopes) ) {
            $valeurs['scopes'] = $scopes;
        }
    }

    return $valeurs;
}

/**
* Vérifications du formulaire d'édition de user
*
* Vérifier les champs postés et signaler d'éventuelles erreurs
*
* @uses formulaires_editer_objet_verifier()
*
* @param int|string $id_user
*     Identifiant du user. 'new' pour un nouveau user.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un user source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du user, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Tableau des erreurs
*/
function formulaires_editer_user_ext_verifier_dist($id_user='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){
    
    // Déterminer les champs obligatoires selon les scopes
    $scopes = _request('scopes');
    $oblis = array('email', 'password');
    if ( strpos( $scopes, 'profile') !== false ) {
        $oblis = array_merge($oblis, array('gender', 'family_name', 'given_name'));
    }
    if ( strpos( $scopes, 'address') !== false ) {
        $oblis = array_merge($oblis, array('street_address','locality','postal_code', 'country'));
    }
    if ( strpos( $scopes, 'phone') !== false ) {
        $oblis = array_merge($oblis, array('phone_number'));
    }


    $erreurs = formulaires_editer_objet_verifier('user', $id_user, $oblis);
    
    //[dnc47] Vérifier que l'username est un nom court sans espaces ni caractères spéciaux
    $username = _request('username'); 
    if(preg_match("#[^A-Za-z0-9_\.]#", $username)) {
        // Erreur
        $erreurs['username'] = _T('user:username_mauvais');     
    } else {
        // Vérifier l'unicité de l'username
        $res = sql_fetsel('id_user, username', 'spip_users', "username='" . $username . "'");
        if ( !empty($res['username']) AND $res['id_user'] !== $id_user ) {
            $erreurs['username'] = _T('user:username_exists');
        }  
    } 

    // Vérifier l'unicité de l'E-mail
    $email = _request('email');
    $res = sql_fetsel('email', 'spip_users', "email='" . $email . "'");
    if ( !empty($res['email']) ) {
        $erreurs['email'] = _T('user:email_exists');
    }

    // Vérifier les deux entrées de mot de passe
    $pswd1 = _request('password');
    if ( (int)($pswd1) == 0  OR strlen($pswd1) !== 9 ) $erreurs['password2'] = _T('user:pswd1_non_conforme');    
    $pswd2 = _request('password2');
    if ( $pswd2 !== $pswd1 ) $erreurs['password2'] = _T('user:pswd2_non_identique');    

    return $erreurs;
}

/**
* Traitement du formulaire d'édition de user
*
* Traiter les champs postés
*
* @uses formulaires_editer_objet_traiter()
*
* @param int|string $id_user
*     Identifiant du user. 'new' pour un nouveau user.
* @param string $retour
*     URL de redirection après le traitement
* @param int $lier_trad
*     Identifiant éventuel d'un user source d'une traduction
* @param string $config_fonc
*     Nom de la fonction ajoutant des configurations particulières au formulaire
* @param array $row
*     Valeurs de la ligne SQL du user, si connu
* @param string $hidden
*     Contenu HTML ajouté en même temps que les champs cachés du formulaire.
* @return array
*     Retours des traitements
*/
function formulaires_editer_user_ext_traiter_dist($id_user='new', $retour='', $lier_trad=0, $config_fonc='', $row=array(), $hidden=''){

    // Le password qui est enregistré crypté
    $raw_password = _request('password');
    if ( !is_null($raw_password) ) {
        $_POST['password'] = sha1($raw_password); // voir Pdo.php::checkPassword
    }

    /* Calculer username à partir de l'e-mail
    $email = _request('email');
    $_POST['username'] = hash("sha256", $email . $raw_password);
    //*/
    
    $retours = formulaires_editer_objet_traiter('user', $id_user, '', $lier_trad, $retour, $config_fonc, $row, $hidden);

    // Rétablir la version non cryptée du password
    $_POST['password'] = $raw_password;

    return $retours;
}