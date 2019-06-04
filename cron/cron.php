<?php
/* cron.php
La tâche cron.php est programmée avec cPanel pour une exécution toutes les minutes :
wget -O /dev/null https://.../cron/cron.php >/dev/null 2>&1

Auteur : Bertrand Degoy 
Copyright (c) 2019 DnC  
Licence GPL3
*/  

define("CRON",true);

//* N'accepter que les requêtes locales (dont la tâche cron)
$ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
if ( $ip != $_SERVER['SERVER_ADDR'] ) exit(); 
//*/

// Autoloading by Composer
require_once __DIR__ . '/../vendor/autoload.php';
OAuth2\Autoloader::register(); 

// Server configuration (OIDC)
require_once __DIR__ . '/../oidc/includes/configure.php';


// Synchroniser les données avec Supervision
//include("cron/sync_my_pg.php");      // La liaison avec Supervision est désormais assurée par le service HTTP REST.

//[dnc27] Populer la table oidc_stats à partir des événements de la minute passée,
include_once("./oidc_stats.php");
// et générer des mails d'alerte.
exec("wget -O /dev/null " . OIDC_SERVER_URL . "/web/?page=envoyer_mail >/dev/null 2>&1");  // Nécessite Spip avec le plugin oauth
