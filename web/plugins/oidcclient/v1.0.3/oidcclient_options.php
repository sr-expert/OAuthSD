<?php
/*************************************************************************\
*  SPIP, Systeme de publication pour l'internet                           *
*                                                                         *
*  Copyright (c) 2001-2016                                                *
*  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
*                                                                         *
*  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
*  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\*************************************************************************/

/**
* Plugin OpenID Connect client pour SPIP
* Auteur : B. Degoy DnC     
* d'après plugin OpenID pour SPIP
* Licence GPL (c) 2007-2012 Edouard Lafargue, Mathieu Marcillaud, Cedric Morin, Fil
* 
*/

if (!defined("_ECRIRE_INC_VERSION")) return;

// Ajouter openid à la liste des méthodes disponibles.
$GLOBALS['liste_des_authentifications']['oidcclient'] = 'oidcclient';

/**
* Pipeline permettant de modifier le tableau des informations passee a l'action
* finale d'authentification apres recuperation des informations du provider
*
* cf : inc/oidcclient.php
*/
$GLOBALS['spip_pipeline']['oidcclient_recuperer_identite'] = '';

/**
* Pipeline permettant de modifier l'url de redirection de l'action
* finale d'identification pour y ajouter en parametre les champs demandes
*
* cf : action/inscrire_oidcclient.php
*/
$GLOBALS['spip_pipeline']['oidcclient_inscrire_redirect'] = '';


/**
* Afficher l'oidc sur le formulaire de login
*	->Utilise uniquement pour spip 2.0.x
* et sur le form inscription
*
* @param <type> $flux
* @return <type>
*/
function oidcclient_recuperer_fond($flux) {
    if ($flux['args']['fond']=='formulaires/login') {
        include_spip('inc/oidcclient');
        $flux['data']['texte'] = oidcclient_login_form($flux['data']['texte'], $flux['data']['contexte']);
    }
    return $flux;
}

/**
*  Surcharge la fonction action_logout_dist afin de générer la demande de déconnexion vers OIDC.
*/
function action_logout() {
    
    include_spip('action/logout');
    
    $logout = _request('logout');
    $url = securiser_redirect_action(_request('url'));
    
    // cas particulier, logout dans l'espace public
    if ( ( $logout == 'public' OR $logout == 'local' ) and !$url) {  //[dnc28b]
        $url = url_de_base();
    }
    
    
    if ( 'local' !== $logout ) { //[dnc28b] Si on passe le paramètre logout='local', on n'opère pas de déconnexion SLO
        $void = oidclogout();  //[dnc28] OIDC Single Logout (SLO) 
    }

    // seul le loge peut se deloger (mais id_auteur peut valoir 0 apres une restauration avortee)
    if (isset($GLOBALS['visiteur_session']['id_auteur'])
    and is_numeric($GLOBALS['visiteur_session']['id_auteur'])
    // des sessions anonymes avec id_auteur=0 existent, mais elle n'ont pas de statut : double check
    and isset($GLOBALS['visiteur_session']['statut'])
    ) {

        // il faut un jeton pour fermer la session (eviter les CSRF)
        if (!$jeton = _request('jeton')
        or !verifier_jeton_logout($jeton, $GLOBALS['visiteur_session'])
        ) {
            $jeton = generer_jeton_logout($GLOBALS['visiteur_session']);
            $action = generer_url_action("logout", "jeton=$jeton");
            $action = parametre_url($action, 'logout', _request('logout'));
            $action = parametre_url($action, 'url', _request('url'));
            include_spip("inc/minipres");
            include_spip("inc/filtres");
            $texte = bouton_action(_T('spip:icone_deconnecter'), $action);
            $texte = "<div class='boutons'>$texte</div>";
            $texte .= '<script type="text/javascript">document.write("<style>body{visibility:hidden;}</style>");window.document.forms[0].submit();</script>';
            $res = minipres(_T('spip:icone_deconnecter'), $texte, '', true);
            echo $res;

            return;
        }

        include_spip('inc/auth');
        auth_trace($GLOBALS['visiteur_session'], '0000-00-00 00:00:00');
        // le logout explicite vaut destruction de toutes les sessions
        if (isset($_COOKIE['spip_session'])) {
            $session = charger_fonction('session', 'inc');
            $session($GLOBALS['visiteur_session']['id_auteur']);
            spip_setcookie('spip_session', $_COOKIE['spip_session'], time() - 3600);
        }
        // si authentification http, et que la personne est loge,
        // pour se deconnecter, il faut proposer un nouveau formulaire de connexion http
        if (isset($_SERVER['PHP_AUTH_USER'])
        and !$GLOBALS['ignore_auth_http']
        and $GLOBALS['auth_can_disconnect']
        ) {
            ask_php_auth(_T('login_deconnexion_ok'),
                _T('login_verifiez_navigateur'),
                _T('login_retour_public'),
                "redirect=" . _DIR_RESTREINT_ABS,
                _T('login_test_navigateur'),
                true);

        }
    }

    // Rediriger en contrant le cache navigateur (Safari3)
    include_spip('inc/headers');
    redirige_par_entete($url
        ? parametre_url($url, 'var_hasard', uniqid(rand()), '&')
        : generer_url_public('login'));
}

//[dnc28] OIDC Logout
function oidclogout() {
    include_spip('inc/session');
    $res = "error";
    
    $id_token = session_get('id_token');
    // Post Methode
    if ( $id_token ) {   
        // Effectuer le logout OIDC
        include_spip('inc/oidcclient_configuration');
        $data1 = array(
            'token' => $id_token,
        );
        $h = curl_init(OIDC_LOGOUT_ENDPOINT);
        curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($h, CURLOPT_TIMEOUT, 10);
        curl_setopt($h, CURLOPT_POST, true);
        curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));   
        curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data1));
        $res = curl_exec($h);
        curl_close($h);
    }
    
    return $res == "";
}