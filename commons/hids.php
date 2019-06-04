<?php
/*
hids.php
[dnc40]

OauthSD project

Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/
    
if ( !defined('PRIVATE') ) die;

/**
* Un premier HIDS, statique, avec les essentiels ...
* 
* @param mixed $title
* @param mixed $origin
* @param mixed $str
* @param mixed $client_id
* @param mixed $user_id
* @param mixed $level
* @param mixed $errnum
* @param mixed $weight
* @param mixed $cnx
* @return arglist :
*   errnum : les codes générés par hids sont supérieurs à 20000 
*/
function hids($title, $origin, $str, $client_id, $user_id, $level, $errnum, $weight, $cnx) {

    global $storage_config;

    $arglist = func_get_args();

    // OIDC Authorize fait un usage limité des requêtes cross-origin.
    if ( $errnum >= 10000 AND !($errnum=='10001' OR $errnum == '10110'  OR $errnum == '10120') ) {
        $arglist[5] = 3;
        $arglist[6] = 20000 + $errnum;  // errnum
        $arglist[7] = 1000;
    }

    // Activité d'un couple client-user
    $stmt = $cnx->prepare(sprintf('SELECT * FROM %s WHERE user_id=:user_id AND client_id=:client_id ORDER BY datetime DESC LIMIT 10', $storage_config['oidc_log_table']));
    $stmt->execute(compact('user_id','client_id'));
    $coupledata = $stmt->fetchAll(\PDO::FETCH_ASSOC);   // 10 derniers événements
    // Un couple ne devrait pas bombarder.
    $datetime0 = strtotime(@$coupledata[0]['datetime']);
    $datetime9 = strtotime(@$coupledata[9]['datetime']);
    if ( $datetime9 ) { 
        $secs = $datetime0 - $datetime9;
        if ( $secs < 2 ) {
            // 10 événements en 2s, c'est trop
            $arglist[5] = 3;        // level
            if ( $errnum=='10001' OR $errnum == '10110' OR $errnum == '10120') {
                // polling trop rapide
                $arglist[2] = 'Polling much too fast ! (' . $str . ')'; // msg
                $arglist[6] = 20000;    // errnum
                $arglist[7] = 1000;     // weight
            }
            else if ( $errnum > 10000 ) {
                // autre cross-origin, 
                $arglist[2] = 'Too many CSRF ! (' . $str . ')'; // msg
                $arglist[6] = 20001;    // errnum
                $arglist[7] = 2000;     // weight
            } else {
                $arglist[2] = 'Much too fast ! (' . $str . ')'; // msg
                $arglist[6] = 20002;    // errnum
                $arglist[7] = 1000;     // weight
            }
        }
        else if ( $secs < 10 ) {
            // 10 événements en 10s, c'est beaucoup
            $arglist[5] = 2;        // level
            if ( $errnum=='10001' OR $errnum == '10110' OR $errnum == '10120') {
                // polling trop rapide
                $arglist[2] = 'Polling too fast ! (' . $str . ')'; // msg
                $arglist[6] = 20003;    // errnum
                $arglist[7] = 500;      // weight
            }
            else if ( $errnum > 10000 ) {
                // autre cross-origin, 
                $arglist[2] = 'Many CSRF ! (' . $str . ')'; // msg
                $arglist[6] = 20004;    // errnum
                $arglist[7] = 2000;      // weight
            } else {
                // autres événements rapides
                $arglist[2] = 'Too fast ! (' . $str . ')'; // msg
                $arglist[6] = 20005;    // errnum
                $arglist[7] = 100;      // weight
            }
        }
    } // sinon on n'a pas encore 10 événements

    // Un couple ne devrait pas faire trop d'erreurs.
    $weight = 0;
    foreach ( $coupledata as $void => $data ) {
        if ( $errnum < 10000 ) {  // éviter de se mordre la queue
            $weight += $data['weight'];
        }            
    }
    if ( $arglist[5] < 3 ) {   // sauter si on a déjà une condition d'erreur.
        if ( $weight > 10000 ) {
            // cela mérite une alerte
            $arglist[2] = 'Too many errors ! (' . $str . ')'; // msg
            $arglist[5] = 3;        // level
            $arglist[6] = 20006;    // errnum
            $arglist[7] = 2000;     // weight
        }
    } else if ( $arglist[5] < 2 ) {   // sauter si on a déjà une condition d'erreur.
        if ( $weight > 1000 ) {
            // cela mérite d'être vu
            $arglist[2] = 'Many errors ! (' . $str . ')'; // msg
            $arglist[5] = 2;        // level
            $arglist[6] = 20007;    // errnum
            $arglist[7] = 1000;     // weight
        }
    }

    // Un même user_id ne devrait pas bombarder.
    // Session longue ???
    // Travail nocturne ???

    return $arglist;

}

