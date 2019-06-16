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
 
/* Plugin OpenID Connect client pour SPIP - Configuration 
Auteur : B.Degoy
Copyright (c) 2018 DnC
Licence GPL v3.0
*/

include_spip('inc/config');
define('OIDC_CLIENT_ID', lire_config('oidcclient/cfg_oidcclient_client_id'));
define('OIDC_CLIENT_SECRET', lire_config('oidcclient/cfg_oidcclient_client_secret'));  // To keep secret !!!
define('OIDC_AUTHORIZATION_ENDPOINT', lire_config('oidcclient/cfg_oidcclient_server_url') . '/authorize');
define('OIDC_TOKEN_ENDPOINT', lire_config('oidcclient/cfg_oidcclient_server_url') . '/token');
define('OIDC_INTROSPECTION_ENDPOINT', lire_config('oidcclient/cfg_oidcclient_server_url') . '/introspect'); 
define('OIDC_USERINFO_ENDPOINT', lire_config('oidcclient/cfg_oidcclient_server_url') . '/userinfo');
define('OIDC_LOGOUT_ENDPOINT', lire_config('oidcclient/cfg_oidcclient_server_url') . '/logout');
$pollperiod = (lire_config('oidcclient/cfg_oidcclient_pollingperiod')? lire_config('oidcclient/cfg_oidcclient_pollingperiod') : '60000');
define('OIDC_POLLPERIOD', $pollperiod);
$tag_appendto = (lire_config('oidcclient/cfg_oidcclient_appendto')? lire_config('oidcclient/cfg_oidcclient_appendto') : '#nav');
define('OIDC_TAG_APPENDTO', $tag_appendto);
$tag_left = (lire_config('oidcclient/cfg_oidcclient_left')? lire_config('oidcclient/cfg_oidcclient_left') : '3px');
define('OIDC_TAG_LEFT', $tag_left);
$tag_top = (lire_config('oidcclient/cfg_oidcclient_top')? lire_config('oidcclient/cfg_oidcclient_top') : '12px');
define('OIDC_TAG_TOP', $tag_top);
