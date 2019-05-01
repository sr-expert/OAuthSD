<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

// C
    'cfg_titre_parametrages' => 'Configure OpenID Connect client for SPIP',
    'configuration_client' => 'Configure client application',
    'configuration_serveur' => 'Configure OIDC Server',
    'cfg_options' => 'Options',
    'cfg_lbl_oidcseul' => 'OIDC only',
    'cfg_inf_oidcseul' => 'Enforce OIDC : the SPIP legacy login/password identification is no longer proposed.',
    'cfg_lbl_oidcclient_client_id' => 'Client application ID',
    'cfg_inf_oidcclient_client_id' => 'A compact acronym like "my_application_2021"',
    'cfg_lbl_oidcclient_client_secret' => 'Client application secret',
    'cfg_inf_oidcclient_client_secret' => 'A string similar to a strong password, to keep secret. Example :"azCxW5!pQ23=K?j3',
    'cfg_lbl_oidcclient_server_url' => 'OIDC Server URL',
    'cfg_inf_oidcclient_server_url' => 'OIDC Server full URL, without ending slash. Example : "https://oa.dnc.global"',
    'cfg_lbl_oidcclient_scopes' => 'Scopes',
    'cfg_inf_oidcclient_scopes' => 'Client application Scopes, space separated. Example : "openid sli profile"',
    'cfg_lbl_oidcclient_pollingperiod' => 'Monitoring interval',
    'cfg_inf_oidcclient_pollingperiod' => 'Monitoring interval in ms. Example : 60000 for one minute.',
    'cfg_lbl_oidcclient_appendto' => 'div block',
    'cfg_inf_oidcclient_appendto' => 'div block to which append the tag. Ex.: #nav (default)',
    'cfg_lbl_oidcclient_top' => 'Top position',
    'cfg_inf_oidcclient_top' => 'CSS top position of the tag. Ex.: 12px (default)',
    'cfg_lbl_oidcclient_left' => 'Left position',
    'cfg_inf_oidcclient_left' => 'CSS left position of the tag. Ex.: 3px (default)',
    'client_id' => 'Client ID',
    
    // D
    'delai_reponse' => 'Response delay', 
    
    // E
    'erreur_oidc' => 'This OpenID Connect ID seems invalid',
    'erreur_oidcclient_info_manquantes' => 'Missing OpenID Connect ID',
    
    // F
	'form_login_oidcclient' => 'You may also use OpenID Connect (<a href="https://oa.dnc.global/60" target="_blank">help</a>)',
	'form_login_oidcclient_inconnu' => 'Unknown login.',
	'form_login_oidcclient_ok' => 'This login is an OpenID Connect ID linked to author : ',
	'form_login_oidcclient_pass' => 'Do not use OpenID Connect, use a login/password instead',
    
    // I
    'information_oidc' => '<a href="https://oa.dnc.global/60" target="_blank">Information about OpenID Connect</a>',
    'infos_titre' => 'Informations about OIDC',
    
    // L
    'login_oidcc' => 'OpenID Connect login',
    'login_pas_lie' => 'The OIDC login %s is not linked to an acount of this application.',
	'login_pas_lie_msg' => 'You must :<br />- either link to an application account,<br />- or create a new account with this same login.',           
    'login_spip' => 'SPIP login',
    'login_oidc_court' => 'OIDC login',
    
    // N
    'nom_auteur' => 'SPIP author',
    
    // O
	'oidcclient' => 'OpenID Connect',
    
    // R
    'retour_login' => 'Back to login',
    
    // S
    'session_connected_oui' => 'The OpenID Connect session is opened.<br/>Do you want to disconnect all your applications ',
    'session_connected_non' => 'Connect me globally with OpenID Connect',
    'session_connected_erreur' => 'The connection to the OpenID Connect server is in error',
    'session_expire' => 'Your OpenID Connect global session will soon expire. <br/> Do you want to extend it',
    'session_extend' => 'Extend the session ?',
    'session_open' => 'Start an OpenID Connect session',
    'session_close' => 'Close the OpenID Connect session',
    
     // T
    't_session_restant' => 'Remaining session time',

	// U
    'utilisateur' => 'User',
	'utilisateur_inconnu' => 'Unknown user on this site',

	// V
	'verif_refusee' => 'Verification refused',
);
