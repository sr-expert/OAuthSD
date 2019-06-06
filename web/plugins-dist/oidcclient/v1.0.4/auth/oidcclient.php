<?php
/***************************************************************************\
*  SPIP, Systeme de publication pour l'internet                           *
*                                                                         *
*  Copyright (c) 2001-2016                                                *
*  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
*                                                                         *
*  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
*  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

/* auth/oidcclient.php
* Plugin OpenID Connect client pour SPIP
* Auteur : B.Degoy DnC
* Copyright (c) 2018 B.Degoy
*/

if (!defined('_ECRIRE_INC_VERSION')) {
    return;
}

include_spip('inc/oidcclient_configuration');


/**
* Demande d'authentification vers OIDC 
* @param string $login
* @param string $pass
* @param string $serveur
* @param bool $phpauth
* @return array|bool
*/
function auth_oidcclient_dist($login, $pass, $serveur = '', $phpauth = false) {
    // OIDC Step 1
    include_spip('inc/oidc_steps');     
    $void = oidc_step_1( $login );
}

/**
* Cette fonction est appellée par l'action auth au retour du serveur d'authentification.
* 
*/
function auth_oidcclient_terminer_identifier_login($args) {

    $res = sql_fetsel("*", "spip_auteurs", "login=" . sql_quote($args, '', 'text'));

    // Ne pas revenir vers action_auth_dist() qui ne semble pas assurer la redirection, mais poursuivre l'action ici

    if ( empty($res) ) { // Probablement échec de la validation du token, ne pas en dire plus à l'end-user
        include_spip('inc/minipres');
        echo minipres($error, 
            '<center>Erreur<br/><a href="/web/spip.php?page=login">Back to login</a></center>'
        );  //[dnc17]
        //TODO: vérifier que l'HIDS est prévenu
        session_set('loggedby');  //[dnc54a]
    }   

    if ( is_string($res) ) { // Erreur
        $redirect = _request('redirect');
        $redirect = parametre_url($redirect, 'var_erreur', $res, '&');
        include_spip('inc/headers');
        redirige_par_entete($redirect);
    }

    // sinon on loge l'auteur identifie, et on finit (redirection automatique) 
    auth_loger($res);
    session_set('loggedby', 'oidc');  //[dnc54a]

    // continuer vers caller (ou redirect ???)
    include_spip('inc/session');
    $caller = session_get('caller');
    redirige_par_entete($caller);          
}


/**
* Completer le formulaire de login avec le js ou les saisie specifiques a ce mode d'auth
*
* @param array $flux
* @return array
*/
function auth_oidcclient_formulaire_login($flux) {
    return $flux;
}


/**
* Informer du droit de modifier ou non son login
*
* @param string $serveur
* @return bool
*   toujours true pour un auteur cree dans SPIP
*/
function auth_oidcclient_autoriser_modifier_login($serveur = '') {
    if (strlen($serveur)) {
        return false;
    } // les fonctions d'ecriture sur base distante sont encore incompletes
    return true;
}

/**
* Verification de la validite d'un login pour le mode d'auth concerne
*
* @param string $new_login
* @param int $id_auteur
*  si auteur existant deja
* @param string $serveur
* @return string
*  message d'erreur si login non valide, chaine vide sinon
*/
function auth_oidcclient_verifier_login($new_login, $id_auteur = 0, $serveur = '') {
    // login et mot de passe
    if (strlen($new_login)) {
        if (strlen($new_login) < _LOGIN_TROP_COURT) {
            return _T('info_login_trop_court_car_pluriel', array('nb' => _LOGIN_TROP_COURT));
        } else {
            $n = sql_countsel('spip_auteurs',
                "login=" . sql_quote($new_login) . " AND id_auteur!=" . intval($id_auteur) . " AND statut!='5poubelle'", '', '',
                $serveur);
            if ($n) {
                return _T('info_login_existant');
            }
        }
    }

    return '';
}


/**
* Retrouver le login de quelqu'un qui cherche a se loger
* Reconnaitre aussi ceux qui donnent leur email au lieu du login
*
* @param string $login
* @param string $serveur
* @return string
*/
function auth_oidcclient_retrouver_login($login, $serveur = '') {

    if (!strlen($login)) {   
        // pas la peine de requeter
        return null;
    }

    $l = sql_quote($login, $serveur, 'text');

    if ($r = sql_getfetsel('login', 'spip_auteurs',
    "statut<>'5poubelle'" .
    " AND (login=$l)", '', '', '', '', $serveur)) {
        return $r;

    } else {            
        // Si pas d'auteur avec ce login regarder s'il a saisi son mail.
        if ($r = sql_getfetsel('login', 'spip_auteurs',
        "statut<>'5poubelle'" .
        " AND login<>'' AND email=$l", '', '', '', '', $serveur)) {
            return $r;

        } else {      
            // Voir si un compte est lié à ce login oidc 
            return sql_getfetsel('login', 'spip_auteurs',
                "statut<>'5poubelle'" .
                " AND login<>'' AND oidc=$l", '', '', '', '', $serveur);
        }
    }
}



function auth_oidcclient_autoriser_modifier_pass($serveur = '') {
    return false;
}


/**
* Verification de la validite d'un mot de passe pour le mode d'auth concerne
* c'est ici que se font eventuellement les verifications de longueur mini/maxi
* ou de force
*
* @param string $login
*  Le login de l'auteur : permet de verifier que pass et login sont differents
*  meme a la creation lorsque l'auteur n'existe pas encore
* @param string $new_pass
*  Nouveau mot de passe
* @param int $id_auteur
*  si auteur existant deja
* @param string $serveur
* @return string
*  message d'erreur si login non valide, chaine vide sinon
*/
function auth_oidcclient_verifier_pass($login, $new_pass, $id_auteur = 0, $serveur = '') {
    // Pas de mot de passe avec OIDC
    return '';
} 

