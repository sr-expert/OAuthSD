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

@define('_OIDCCLIENT_LOG', true);

/**
* Ajout au formulaire de login
*
* @param string $texte
* @param array $contexte
* @return string
*/
function oidcclient_login_form($texte,$contexte){
    $scriptoidc = "";
    if ($login = $contexte['var_login']) {

        $r = sql_fetsel('login,oidc','spip_auteurs','oidc='.sql_quote($login));   // Le login indiqué est il OIDC ?

        if ( $r['oidc'] ) {
            // Le login indiqué est un login OIDC
            $message = _T('oidcclient:form_login_oidcclient_ok') . $r['login'] 
            . "<br />[<a href=\"#\" onclick=\"jQuery('.editer_session .explication').hide();toggle_password(true);return false;\">"._T('oidcclient:form_login_oidcclient_pass')."</a>]";
            $scriptoidc = "jQuery('#var_login').keyup(function(){
            if (jQuery(this).val()!='".addslashes($login)."') {
            jQuery('.editer_session .explication').hide();
            toggle_password(true);
            } else {
            jQuery('.editer_session .explication').show();
            }
            });";
        } else
            $message = _T('oidcclient:form_login_oidcclient');
    } else
        $message = _T('oidcclient:form_login_oidcclient');

    // pas de required sur password
    $texte = preg_replace(",(<input[^>]*id='password'[^>]*)required='required'([^>]*/>),Uims","$1$2",$texte);

    $texte .= "<style type='text/css'><!--"
    ."input#var_login {width:10em;background-image : url(".find_in_path('images/oidc-16.png').");background-repeat:no-repeat;background-position:center left;padding-left:18px !important;}\n"
    ."input#password {width:10em;padding-right:18px;}\n"
    .".editer_session .explication {margin:-5px 0 10px;font-style:italic;}"
    ."//--></style>"
    ."<script type='text/javascript'>"
    ."/*<![CDATA[*/
    jQuery('#var_login').parents('form').addClass('oidcclient');"
    ."var memopass='';"
    ."function toggle_password(show){
    if (show) {
    if (memopass)
    jQuery('#password_holder').before(memopass);
    jQuery('#password_holder').remove();
    memopass = '';
    jQuery('.editer_password').show();
    }
    else {
    jQuery('#password').after('<span id=\"password_holder\"></span>');
    memopass = jQuery('#password').detach();
    jQuery('.editer_password').hide();
    }
    };"
    ."jQuery(function(){jQuery('.editer_session').prepend('<div class=\'explication\'>".addslashes($message)."</div>');"
    .($scriptoidc?"if (!jQuery('.editer_password').is('.erreur')) toggle_password(false);":"")
    ."$scriptoidc});"
    ."/*]]>*/"
    ."</script>";
    return $texte;
}



/**
* determine si un login est de type oidc
* @param <type> $login
* @return <type>
*/
function is_oidc($login){ 
    return true;
}

/**
* Nettoyer et mettre en forme une url OpenID
*
* @param string $url_oidc
* @return string
*/
function nettoyer_oidc($oidc){
    return trim($oidc);
}

/**
* TODO: Verifier que le login OIDC est valide en demandant à l'utilisateur de s'authentifier !
*
* @param string $login_oidc
*/
function verifier_oidc($login_oidc){
    return true;
}


/**
* Logs pour openID, avec plusieurs niveaux pour le debug (1 a 3)
*
* @param mixed $data : contenu du log
* @param int(1) $niveau : niveau de complexite du log
* @return null
**/
function oidcclient_log($data, $niveau=1){
    if (!defined('_OIDCCLIENT_LOG') OR _OIDCCLIENT_LOG < $niveau) return;
    spip_log('OpenID client : '.$data, 'oidcclient');
}
