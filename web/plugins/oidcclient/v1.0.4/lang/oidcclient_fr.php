<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

// C
    'cfg_titre_parametrages' => 'Configurer le client OpenID Connect pour SPIP',
    'configuration_client' => 'Configuration de l\'application cliente',
    'configuration_serveur' => 'Configuration du serveur OIDC',
    'cfg_options' => 'Options',
    'cfg_lbl_oidcseul' => 'Seulement OIDC',
    'cfg_inf_oidcseul' => 'Imposer OIDC : l\'identification classique par login/mot de passe de SPIP n\'est plus proposée.',
    'cfg_lbl_oidcclient_client_id' => 'ID de l\'application cliente',
    'cfg_inf_oidcclient_client_id' => 'Un acronyme compact tel que "mon_application_2021"',
    'cfg_lbl_oidcclient_client_secret' => 'Secret de l\'application cliente',
    'cfg_inf_oidcclient_client_secret' => 'Une chaine similaire à un mot de passe fort, à garder secrète. Exemple :"azCxW5!pQ23=K?j3',
    'cfg_lbl_oidcclient_server_url' => 'URL du serveur OIDC',
    'cfg_inf_oidcclient_server_url' => 'URL complète du serveur OIDC, sans slash final. Exemple : "https://oa.dnc.global"',
    'cfg_lbl_oidcclient_scopes' => 'Scopes',
    'cfg_inf_oidcclient_scopes' => 'Scopes de l\'application cliente, séparés par des espaces. Exemple : "openid sli profile"',
    'cfg_lbl_oidcclient_pollingperiod' => 'Intervalle du monitoring',
    'cfg_inf_oidcclient_pollingperiod' => 'Intervalle du monitoring exprimé en ms. Exemple : 60000 pour une minute.',
    'cfg_lbl_oidcclient_appendto' => 'Bloc div',
    'cfg_inf_oidcclient_appendto' => 'Bloc div auquel ajouter l\'étiquette. Ex.: #nav (défaut)',
    'cfg_lbl_oidcclient_top' => 'Top position',
    'cfg_inf_oidcclient_top' => 'CSS top de l\'étiquette. Ex.: 12px (défaut)',
    'cfg_lbl_oidcclient_left' => 'Left position',
    'cfg_inf_oidcclient_left' => 'CSS left de l\'étiquette. Ex.: 3px (défaut)',
    
    // E
    'erreur_oidc' => 'Cet identifiant OpenID Connect ne semble pas valide',
    'erreur_oidcclient_info_manquantes' => 'OpenID Connect user ID manquant',
    
    // F
	'form_login_oidcclient' => 'Vous pouvez aussi utiliser OpenID Connect (<a href="https://oa.dnc.global/60" target="_blank">aide</a>)',
	'form_login_oidcclient_inconnu' => 'Ce login est inconnu.',
	'form_login_oidcclient_ok' => 'Ce login est un login OpenID Connect lié à l\'auteur : ',
	'form_login_oidcclient_pass' => 'Ne pas utiliser OpenID Connect, utiliser un mot de passe',
    
    // I
    'information_oidcc' => '<a href="https://oa.dnc.global/60" target="_blank">Information sur OpenID Connect</a>',
    
    // L
    'login_oidc' => 'Login OpenID Connect',
    'login_pas_lie' => 'Le login OIDC %s n\'est pas lié à un compte de cette application.',
	'login_pas_lie_msg' => 'Vous devez :<br />- soit lier ce login à un compte,<br />- soit créer un nouveau compte avec ce login.',           

    // O
	'oidcclient' => 'OpenID Connect',
    
    // R
    'retour_login' => 'Retourner au login',
    
    // S
    'session_connected_oui' => 'La session OpenID Connect est ouverte.<br/>Voulez-vous effectuer une déconnexion globale de toutes vos applications ',
    'session_connected_non' => 'Se connecter globalement par OpenID Connect',
    'session_connected_erreur' => 'La liaison avec le serveur OpenID Connect est en erreur',
    'session_expire' => 'Votre session globale OpenID Connect va bientôt expirer.<br/>Voulez-vous la prolonger ',
    'session_extend' => 'Prolonger la session ?',
    'session_open' => 'Ouvrir une session OpenID Connect',
    'session_close' => 'Clore la session OpenID Connect',

	// U
	'utilisateur_inconnu' => 'Utilisateur inconnu sur ce site',

	// V
	'verif_refusee' => 'Vérification refusée',
);
