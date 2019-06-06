<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

// C
    'cfg_titre_parametrages' => 'Konfigurieren Sie den OpenID Connect-Client für SPIP',
    'configuration_client' => 'Client-Anwendung konfigurieren',
    'configuration_serveur' => 'Konfigurieren Sie den OIDC-Server',
    'cfg_options' => 'Optionen',
    'cfg_lbl_oidcseul' => 'Nur OIDC',
    'cfg_inf_oidcseul' => 'Erzwingen Sie OIDC: die SPIP-Legacy Login/Passwort wird nicht mehr vorgeschlagen.',
    'cfg_lbl_oidcclient_client_id' => 'Client-Anwendung ID',
    'cfg_inf_oidcclient_client_id' => 'Ein kompaktes Akronym wie "mein_application_2021"',
    'cfg_lbl_oidcclient_client_secret' => 'Client-Anwendung Geheimnis',
    'cfg_inf_oidcclient_client_secret' => 'Eine Zeichenfolge, die einem sicheren Kennwort ähnelt, um sie geheim zu halten. Beispiel :"azCxW5!pQ23=K?j3',
    'cfg_lbl_oidcclient_server_url' => 'OIDC Server URL',
    'cfg_inf_oidcclient_server_url' => 'OIDC Server vollständige URL, ohne Endstrich. Beispiel : "https://oa.dnc.global"',
    'cfg_lbl_oidcclient_scopes' => 'Scopes',
    'cfg_inf_oidcclient_scopes' => 'Client-Anwendung Scopes, durch Leerzeichen getrennt. Beispiel : "openid sli profile"',
    'cfg_lbl_oidcclient_pollingperiod' => 'Überwachungsintervall',
    'cfg_inf_oidcclient_pollingperiod' => 'Überwachungsintervall in ms. Beispiel : 60000 für eine Minute.',
    'cfg_lbl_oidcclient_appendto' => 'div block',                   //TODO : traduire
    'cfg_inf_oidcclient_appendto' => 'div block to which append the tag. Ex.: #nav (default)',
    'cfg_lbl_oidcclient_top' => 'Top position',
    'cfg_inf_oidcclient_top' => 'CSS top position of the tag. Ex.: 12px (default)',
    'cfg_lbl_oidcclient_left' => 'Left position',
    'cfg_inf_oidcclient_left' => 'CSS left position of the tag. Ex.: 3px (default)',
    'client_id' => 'Client ID',
    
    // D
    'delai_reponse' => 'Reaktionszeit', 
    
    // E
    'erreur_oidc' => 'Diese OpenID Connect ID scheint ungültig zu sein',
    'erreur_oidcclient_info_manquantes' => 'Fehlende OpenID Connect ID',
    
    // F
	'form_login_oidcclient' => 'Sie können auch OpenID Connect verwenden(<a href="https://oa.dnc.global/60" target="_blank">Hilfe</a>)',
	'form_login_oidcclient_inconnu' => 'Unbekanntes Login.',
	'form_login_oidcclient_ok' => 'Dieses Login ist eine mit dem Autor verknüpfte OpenID Connect ID : ',
	'form_login_oidcclient_pass' => 'Verwenden Sie nicht OpenID Connect, verwenden Sie stattdessen ein Login/Passwort',
    
    // I
    'information_oidcc' => '<a href="https://oa.dnc.global/60" target="_blank">Informationen zu OpenID Connect</a>',
    'infos_titre' => 'Informationen zu OIDC',
    
    // L
    'login_oidc' => 'OpenID Connect Login',
    'login_pas_lie' => 'Die OIDC-Anmeldung % s ist nicht mit einem Konto dieser Anwendung verknüpft.',
	'login_pas_lie_msg' => 'Du musst :<br />- entweder mit einem Anwendungskonto verknüpfen, <br /> - oder ein neues Konto mit demselben Login erstellen.',           
    'login_spip' => 'SPIP Login ',
    'login_oidc_court' => 'OIDC Login ',
    
    // N
    'nom_auteur' => 'SPIP Autor',
    
    // O
	'oidcclient' => 'OpenID Connect',
    
    // R
    'retour_login' => 'Zurück zur Anmeldung',
    
    // S
    'serveur_url' => 'Server',
    'session_connected_oui' => 'Die OpenID Connect-Sitzung wird geöffnet.<br/>Möchten Sie alle Ihre Anwendungen trennen',
    'session_connected_non' => 'Verbinden Sie mich global mit OpenID Connect',
    'session_connected_erreur' => 'Die Verbindung zum OpenID Connect-Server ist fehlerhaft',
    'session_expire' => 'Ihre globale OpenID Connect-Sitzung läuft demnächst ab. <br/> Möchten Sie es verlängern?',
    'session_extend' => 'Verlängern Sie die Sitzung ?',
    'session_open' => 'Starten Sie eine OpenID Connect-Sitzung',
    'session_close' => 'Schließen Sie die OpenID Connect-Sitzung',
    
    // T
    't_session_restant' => 'Verbleibende Sitzung',
    'tooltip_oidctag' => 'Klicken Sie hier, um auf eine OIDC-Sitzung zu agieren',
    'tooltip_oidcinfo' => 'Klicken Sie hier, um Informationen zur OIDC-Sitzung zu erhalten',

	// U
    'utilisateur' => 'Benutzer',
	'utilisateur_inconnu' => 'Unbekannter Benutzer auf dieser Site',

	// V
	'verif_refusee' => 'Überprüfung abgelehnt',
);
