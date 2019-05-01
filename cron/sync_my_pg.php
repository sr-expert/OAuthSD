<?php
/* sync_mypg.php

*** ABANDONNÉ ***

NOTA : Pour Intergros, la synchronisation entre tables mySQL et PgSQL a été abandonnée le 7 mars 2019 au profit d'une liaison par HTTP REST. 

Cette version d’OAuthSD présente la particularité de ne pas gérer les utilisateurs finaux. Au lieu de cela, une application distincte "Supervision" permet aux administrateurs des applications clientes, déléguant l’authentification à ce serveur, de contrôler l’enregistrement des utilisateurs finaux et de leur accorder des permissions sur les applications.

C’est cette dernière fonctionnalité qui a justifié la configuration particulière.

OAuthSD, comme tout serveur OpenID Connect (serveur OIDC), ne gère pas les permissions des utilisateurs finaux sur les ressources protégées. Il ignore quel utilisateur final a le droit d’utiliser telle ou telle application, et quels seront ses droits à l’intérieur de cette application.

En revanche, le jeton JWT créé par un serveur OIDC peut transmettre des scopes particuliers qui seront interprétés par les applications pour accorder des droits à l’utilisateur final authentifié par le jeton. Parce que la spécification d’OpenID Connect ne normalise pas l’emploi des scopes pour cet usage, un serveur OIDC se doit d’être transparent aux scopes en question. Le rôle du serveur OIDC est d’authentifier l’utilisateur final, d’identifier de façon certaine l’application cliente par laquelle cet utilisateur accède et de transmettre des scopes, le tout étant sécurisé par la signature cryptographique du jeton JWT.

L’application Supervision définit les scopes particuliers en fonction des applications clientes et des utilisateurs.

Dans cette configuration :
- l’application Supervision est maître des données relatives aux utilisateurs finaux. Elle leur permet de s’inscrire et de gérer leurs identifiants de connexion ainsi que leurs données personnelles. Elle offre aux superviseurs le moyen de gérer les autorisations accordées aux utilisateurs finaux selon les applications.
- le serveur OIDC est maître des données relatives aux applications et fournit les fonctionnalités permettant aux auteurs/administrateurs des applications clientes de les enregistrer sur ce serveur et de gérer leur configuration.

Liaison entre le serveur OIDC et l’application Supervision

Pour rester conforme à la spécification, le serveur OIDC ne doit pas être modifié pour cette application particulière. De plus, il convient de protéger les données sensibles du serveur OIDC (mot de passe des utilisateurs finaux, secret et clé privée des applications etc.). Nous avons opté pour une séparation des bases de données et une synchronisation limitée aux données utiles, ce qui permet d’isoler les données sensibles situées sur le serveur OIDC.

Outre cet avantage, la séparation des bases de données permet de développer et d’héberger les deux services sur des bases techniques indépendantes, la seule contrainte étant que le serveur OIDC puisse accéder à la base de données de l’application Supervision par un canal sûr.

Le serveur OIDC a la responsabilité de la synchronisation :

- dans le sens OIDC -> Supervision :
champs de la table clients (a minima) : champs client_id (clé unique), scope, domaine.

- dans le sens Supervision -> OIDC :
champs de la table users (a minima) : username (clé unique), password, scope.

Les scopes ainsi transmis par l’application Supervision, attachés à un utilisateur donné, sont introduits dans le jeton JWT par le serveur OIDC selon le mécanisme standard.
Les enregistrements sont marqués par le drapeau boolean sync = false lors de toute modification. 
Le script de synchronisation lève le drapeau à true.

Une tâche CRON est programmée avec cPanel pour une exécution toutes les minutes :
wget -O /dev/null https://oidc.dnc.global/cron/sync_my_pg.php >/dev/null 2>&1

Auteur : Bertrand Degoy 
Copyright (c) 2019 DnC  
Tous droits réservés
*/     

//[sync1]

//* N'accepter que l'appel par la tâche cron
if ( ! defined('CRON') ) exit(); 
//*/

// Autoloading by Composer
require __DIR__ . '/../vendor/autoload.php';
OAuth2\Autoloader::register(); 

// Server configuration
require_once __DIR__ . '/../oidc/includes/configure.php';

// Bases de données à synchroniser :
// connection est la base du serveur, locale (définie par configure.php)
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);   // MySQL
// connection2 est la base distante
$connexion2 = array(
    'dsn' => 'pgsql:dbname=oidcdnc_server;host=localhost;port=5432',    // PostgreSQL 
    'username' => 'oidcdnc_admin', 
    'password' => 'oidcY10CWrB9!'
);
$storage2_config = array(
    // tables 
    'client_table' => 'oauth_clients',
    'user_table' => 'oauth_users',
);
$cnx2 = new \PDO($connexion2['dsn'], $connexion2['username'], $connexion2['password']);


////////  OIDC -> Supervision //////////

/* Tous les champs de la table clients distante existent avec le même nom dans la base locale.
Autrement dit, la table utilisateurs locale comprend tous les champs de la base distante et des champs supplémentaires, 
propres à OIDC.
Noter que les champs commençant par id_ sont des clés primaires et ne seront pas recopiés.
*/

// clients locaux non synchronisés (drapeau sync 0) MySQL transforme les boolean en tinyint(1) et false/true en 0/1.
$stmt = $cnx->prepare(sprintf("SELECT * FROM %s WHERE sync = 0", $storage_config['client_table']));
$stmt->execute();
$results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
foreach ( $results as $result ) {

    $client_id = $result['client_id'];
    // le client existe-t-il dans la base distante?
    $stmt = $cnx2->prepare(sprintf('SELECT * from %s where client_id=:client_id', $storage2_config['client_table']));
    if ( $stmt->execute(compact('client_id')) === false ) {
        // erreur
        $sqlstate = $stmt3->errorCode();
        $errorinfo = $stmt3->errorInfo();
        if (DEBUG) log_error("SyncMyPg" ,"PDO error 11 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);
    }

    $client = $stmt->fetch(\PDO::FETCH_ASSOC);
    // valeurs
    $client_id = $result['client_id']; 
    $scope = $result['scope']; 
    $domain = parse_url($result['redirect_uri'])["host"]; 
    $date_publication = $result['date_publication']; 
    $statut = $result['statut'];
    $texte1 = $result['texte1'];
    $texte2 = $result['texte2'];
    $sync = true;

    $errorinfo = null;
    if ( $client ) {
        // mettre à jour un client existant
        $stmt = $cnx2->prepare(sprintf('UPDATE %s SET domain=:domain, scope=:scope, date_publication=:date_publication, statut=:statut, texte1=:texte1, texte2=:texte2, sync=:sync WHERE client_id=:client_id', $storage2_config['client_table']));
        if ( $stmt->execute(compact('domain', 'scope', 'date_publication', 'statut', 'texte1', 'texte2', 'sync', 'client_id')) === false ) {
            // erreur
            $sqlstate = $stmt->errorCode();
            $errorinfo = $stmt->errorInfo();
            if (DEBUG) log_error("SyncMyPg" ,"PDO error 12 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);
        }
    } else {
        // re-créer le client en conservant la valeur de la clé primaire
        $stmt = $cnx2->prepare(sprintf('INSERT INTO %s (client_id, domain, scope, date_publication, statut, texte1, texte2, sync) VALUES (:client_id, :domain, :scope, :date_publication, :statut, :texte1, :texte2, :sync)', $storage2_config['client_table']));
        if ( $stmt->execute(compact('domain', 'scope', 'date_publication', 'statut', 'texte1', 'texte2', 'sync', 'client_id')) === false ) {
            // erreur
            $sqlstate = $stmt->errorCode();
            $errorinfo = $stmt->errorInfo();
            if (DEBUG) log_error("SyncMyPg" ,"PDO error 13 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);
        }    
    }
    if ( ! $errorinfo ) {
        // Lever le drapeau sync
        $stmt = $cnx->prepare(sprintf("UPDATE %s SET sync=1 WHERE client_id=:client_id", $storage_config['client_table'])); 
        if ( $stmt->execute(compact('client_id')) == false ) {
            // erreur
            $sqlstate = $stmt->errorCode();
            $errorinfo = $stmt->errorInfo();
            if (DEBUG) log_error("SyncMyPg" ,"PDO error 14 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);
        }
    }
}


///////// Supervision -> OIDC ///////////

/* Tous les champs de la table utilisateurs locale existent avec le même nom dans la base distante.
Autrement dit, la table utilisateurs distante comprend tous les champs de la base locale et des champs supplémentaires.
Noter que les champs commençant par id_ sont des clés primaires et ne seront pas recopiés.
*/

// Obtenir un array des champs de la table utilisateurs locale pour faire l'intersection qui suit.
$localfields = array();
$stmt = $cnx2->prepare(sprintf('SELECT * from %s', $storage2_config['user_table']));
if ( $stmt->execute() === false ) {
    // erreur
    $sqlstate = $stmt->errorCode();
    $errorinfo = $stmt->errorInfo();
    if (DEBUG) log_error("SyncMyPg" ,"PDO error 20 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);
} else {
    $localfields = $stmt->fetch(\PDO::FETCH_ASSOC);

    // users distants non synchronisés (drapeau sync NULL ou false)
    $stmt = $cnx2->prepare(sprintf('SELECT * from %s where sync=false OR sync IS NULL', $storage2_config['user_table']));
    if ( $stmt->execute() === false ) {
        // erreur
        $sqlstate = $stmt->errorCode();
        $errorinfo = $stmt->errorInfo();
        if (DEBUG) log_error("SyncMyPg" ,"PDO error 21 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);

    } else {
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ( $results as $result ) {

            $username = $result['username'];
            // l'utilisateur existe-t-il dans la base locale?
            $stmt = $cnx->prepare(sprintf('SELECT * from %s where username=:username', $storage_config['user_table']));
            $stmt->execute(compact('username'));
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ( $user ) {
                // Le plus simple est encore de supprimmer l'enregistrement avant de le recréer.
                $stmt = $cnx->prepare(sprintf('DELETE from %s where username=:username', $storage_config['user_table']));
                if ( $stmt->execute(compact('username')) === false ) {
                    // erreur
                    $sqlstate = $stmt->errorCode();
                    $errorinfo = $stmt->errorInfo();
                    if (DEBUG) log_error("SyncMyPg" ,"PDO error 22 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);
                }
            }    

            $result_loc = array_intersect_key($result, $localfields);  // l'intersection réduit le résultat aux champs présents localement.
            // créer un nouvel utilisateur
            $prep = array();
            foreach($result_loc as $k => $v ) {
                if ( strpos($k, 'id_') === false ) {     // ne pas recopier une clé primaire
                    $prep[':'.$k] = $v;
                } else {
                    unset($result_loc[$k]);
                }
            }
            unset($result_loc['sync']); 
            $result_loc['sync'] = true;
            $stmt = $cnx->prepare(sprintf("INSERT INTO %s ( " . implode(', ',array_keys($result_loc)) . ") VALUES (" . implode(', ',array_keys($prep)) . ")", $storage_config['user_table']));
            if ( $stmt->execute($result_loc) === false ) {
                // erreur
                $sqlstate = $stmt->errorCode();
                $errorinfo = $stmt->errorInfo();
                if (DEBUG) log_error("SyncMyPg" ,"PDO error 23 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);

            } else {
                // Lever le drapeau sync
                $stmt = $cnx2->prepare(sprintf("UPDATE %s SET sync=true WHERE username=:username", $storage2_config['user_table'])); 
                if ( $stmt->execute(compact('username')) == false ) {
                    // erreur
                    $sqlstate = $stmt->errorCode();
                    $errorinfo = $stmt->errorInfo();
                    if (DEBUG) log_error("SyncMyPg" ,"PDO error 24 : " . $sqlstate . ' - ' . $errorinfo, null, null, $cnx);

                }
            }

        }

    }

}

$void = 1;    // stoooop!
