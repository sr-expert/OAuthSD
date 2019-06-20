<?php
/* oidc_stats.php
Appelé toutes les minutes par cron.php.
Calcule à partir de oidc_logs les comptes d'événements chaque minute.

Une fois encore : il faut des temps UTC !

Auteur : Bertrand Degoy 
Copyright (c) 2019 DnC  
Licence GPL3
*/     

//* N'accepter que l'appel par la tâche cron
if ( ! defined('CRON') ) exit(); 
//*/

// Autoloading by Composer
require_once __DIR__ . '/../vendor/autoload.php';
OAuth2\Autoloader::register(); 
// Server configuration (OIDC)
include_once __DIR__. '/../commons/configure_oidc.php';

//DebugBreak("435347910947900005@127.0.0.1;d=1");  //DEBUG

// connection locale (définie par configure.php)
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);   // MySQL

// Déterminer le datetime de la dernière entrée dans la table
$stmt = $cnx->prepare(sprintf("SELECT * FROM %s ORDER BY datetime DESC LIMIT 1", $storage_config['oidc_stat_table']));
$stmt->execute();
$result = $stmt->fetch(\PDO::FETCH_ASSOC);
$last = strtotime(@$result['datetime']);  // timestamp
if ( is_null($last)) {
    // La table est vide, démarrer ce jour à 0h
    $last = strtotime(date('Y-m-d'));  // timestamp
}
if ( time() - $last > 86400 * 2 ) {
    // en cas de gros retard ne pas remonter plus de 2 jours
    $last = time() - 86400 * 2;  // timestamp
}
$time = $last + 60;   // timestamp
while ( !is_null($time) AND $time < time() ) {        // on rattrappe le temps présent 
    usleep(100000); // attendre 100ms pour éviter la surcharge en cas de gros retard     
    $datetime = date('Y-m-d H:i', $time); // datetime minute ronde 
    insert_one_minute( $datetime );
    $time = $time + 60;  // minute par minute
} 

function insert_one_minute( $datetime ) {
    global $cnx, $storage_config;

    // Sélectionner les entrées de log de la minute passée  
    if ( is_null($datetime) ) $datetime = date("Y-m-d H:i");  // datetime minute ronde
    $precedente =  date("Y-m-d H:i", strtotime($datetime ) - 60);   // datetime minute ronde
    $stmt = $cnx->prepare(sprintf("SELECT * FROM %s WHERE datetime >:precedente AND datetime <=:datetime ORDER BY datetime DESC", $storage_config['oidc_log_table']));
    $stmt->execute(compact('precedente','datetime'));
    $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    //[dnc27e] Déterminer le premier événement de la minute écoulée (0 si pas d'événements au cours de cette minute)
    if ( !empty($results) ) {

        $id_oidc_log = ( ! is_null($results[0]['id_oidc_log']) ? $results[0]['id_oidc_log'] : 0 ); 

        if ( $id_oidc_log ) {
            // Calculer les comptes
            $authorize_count = 0 ;
            $introspect_count = 0 ;
            $token_count = 0 ;
            $authorize_ok_count = 0;
            $userinfoext_count = 0 ;
            $errors_count = 0 ;     
            foreach ($results as $result ) {
                $authorize_count += ((@$result['errnum'] == '1')? 1:0);
                $introspect_count += ((@$result['errnum'] == '2')? 1:0);
                $token_count += ((@$result['errnum'] == '3')? 1:0);
                $authorize_ok_count +=((@$result['errnum'] == '159' )? 1:0);
                $userinfoext_count += ((@$result['errnum'] == '5')? 1:0);
                $errors_count +=((@$result['level'] == '3' )? 1:0);            
            }
            //$notnull = (bool)($authorize_count+$introspect_count+$token_count+$authorize_ok_count+$userinfoext_count+$errors_count > 0);
            $notnull = true;
            if ( $notnull) {
                // Créer un nouvel enregistrement
                $stmt = $cnx->prepare(sprintf("INSERT INTO %s (datetime, id_oidc_log, authorize_count, introspect_count, token_count, authorize_ok_count, userinfoext_count, errors_count) VALUES(:datetime, :id_oidc_log, :authorize_count, :introspect_count, :token_count, :authorize_ok_count, :userinfoext_count, :errors_count)", $storage_config['oidc_stat_table']));
                $stmt->execute(compact('datetime', 'id_oidc_log', 'authorize_count', 'introspect_count', 'token_count', 'authorize_ok_count', 'userinfoext_count', 'errors_count'));
            }
        }
    }

}
