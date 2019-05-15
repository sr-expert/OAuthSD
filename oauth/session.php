<?php
/*
session.php

CloudSession Controler

Gestion de variables de session inter applications web.

OauthSD project
This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/

// include our OAuth2 Server object
define('PRIVATE', true);
require_once __DIR__.'/includes/server.php';        

$request = OAuth2\Request::createFromGlobals();
$response = new OAuth2\Response();

$ResourceController = $server->getResourceController();

// Verify request
if (!$ResourceController->verifyResourceRequest($request, $response) ) {
    $response->send();
    die;
}
// Client is authorized
$token = $ResourceController->getToken();

// Verify format of session_id
$session_id = $request->query['session_id'];
$sanitized_session_id = preg_replace('/[^A-Za-z0-9"\']/', '', $session_id);
if ( $sanitized_sesion_id !== $session_id ) {
    $response->setError(403,"Forbidden","Bad session id");
    $response->send();
    die;     
}

// Verify Scope
if ( strpos($token['scope'], 'session' ) !== false ) {
    // L'application qui accède à la session doit avoir le scope 'session' 
    $response->setError(403,"Forbidden","Missing \"session\" scope for this client");
    $response->send();
    die;   
} 

// Etablir une connexion aux données
$cnx = new \PDO($connection['dsn'], $connection['username'], $connection['password']);

// L'utilisateur final est-il connecté ?
$uid = $token['user_id'];
// See if user is connected
$isconnected = false;
if ( ! empty($uid) ) {
    // verify wheter user is (was) already connected or not
    $stmt = $cnx->prepare($sql = "SELECT * FROM spip_access_tokens WHERE user_id=:uid AND client_id=:client_id ORDER BY expires DESC");
    $stmt->execute(compact('client_id', 'uid'));
    $authdata = $stmt->fetch(\PDO::FETCH_ASSOC);
    //verify access token not expired for more than 1h
    $isconnected = (bool)( strtotime($authdata['expires']) + 3600 > time() );   // le fuseau horaire du serveur doit être Z (UTC)!
}
if ( ! isconnected ) {
    // L'utilisateur final n'est pas connecté
    $response->setError(403,"Forbidden", "User not connected");
    $response->send();
    die;   
} 

$mode = $request->query['mode'];

switch ( $mode ) {

    case 'read' :

        // CloudSession info
        $sessioninfo = array();
        $stmt = $cnx->prepare($sql = "SELECT * FROM spip_cloudsession WHERE session_id=:session_id");
        $stmt->execute(compact('session_id'));
        $sessioninfo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ( $stmt->errorCode() ) {
            $response->setError(500, "session_error","Error while reading session data");
            $response->send();
            die;

        } else { 

            // Verify consistency of user_id between token and sessioninfo
            if ( $uid !== $sessioninfo['user_id'] ) {
                // L'utilisateur final n'est pas le bon
                $response->setError(403, "Forbidden", "Wrong user");
                $response->send();
                die;
            }   

            // unusefull data
            unset($sessioninfo['cloudsession_id']); // inutile ?
            unset($sessioninfo['session_id']);  // inutile de répéter

            // Retourner les données au format JSON dans le corps de la réponse    
            echo json_encode(array_merge( 
                array(
                    'success' => true, 
                    'client_id' => $token['client_id'],
                    'user_id' => $token['user_id'],
                    'expires' => $token["expires"],
                ), 
                $sessioninfo
                )
            );

        }

        break;

    case 'create' :
        // Créer une CloudSession
        // Vérifier que la requête provient bien d'une application cliente



        // Vérifier l'unicité de l'identificateur de session
        $stmt = $cnx->prepare($sql = "SELECT * FROM spip_cloudsession WHERE session_id=:session_id");
        $stmt->execute(compact('session_id'));
        $sessioninfo = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ( !empty($sessioninfo) ) {
            // Si collision, échec
            $response->setError(409, "session_error","Session ID collision");
            $response->send();
            die;
        }
        // Créer la session
        $stmt = $cnx->prepare($sql = "INSERT INTO spip_cloudsession SET session_id=:session_id, user_id=:user_id, writing=0");
        $stmt->execute(compact('session_id', 'uid'));
        if ( $stmt->errorCode() ) {
            $response->setError(500, "session_error","Error while creating session");
            $response->send();
            die;
        } 
        // Succès de la création
        // Retourner CloudSession info
        $sessioninfo = array();
        $stmt = $cnx->prepare($sql = "SELECT * FROM spip_cloudsession WHERE session_id=:session_id");
        $stmt->execute(compact('session_id'));
        $sessioninfo = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ( $stmt->errorCode() ) {
            $response->setError(500, "data_error","Error while reading session data");
            $response->send();
            die;

        } else {   
            unset($userinfo['session_id']);  // inutile de répéter
            unset($userinfo['data']);  // c'est vide

            // Retourner les données au format JSON dans le corps de la réponse    
            echo json_encode(array_merge( 
                array(
                    'success' => true, 
                    'client_id' => $token['client_id'],
                    'user_id' => $token['user_id'],
                    'expires' => $token["expires"],
                ), 
                $sessioninfo
                )
            );
        }

        break;

    case 'write':

        // Nouvelles données
        $vardata = array();
        $vardata = json_decode($request->query['data'], true);

        if ( count($vardata) ) {

            // Relire les données CloudSession info
            $sessioninfo = array();
            $data= array();
            $stmt = $cnx->prepare($sql = "SELECT * FROM spip_cloudsession WHERE session_id=:session_id");
            $stmt->execute(compact('session_id'));

            if ( $stmt->errorCode() ) {
                $response->setError(500, "session_error","Error while writing session data");
                $response->send();
                die; 
            } else {     
                $sessioninfo = $stmt->fetch(\PDO::FETCH_ASSOC);
                $data = json_decode($sessioninfo['data'], true);
                // Fusionner en écrasant les anciennes valeurs par les nouvelles
                $newdata = json_encode(array_merge( $data, $vardata ));
                // Enregistrer
                $stmt = $cnx->prepare($sql = "UPDATE spip_cloudsession SET data=:newdata WHERE session_id=:session_id");
                $stmt->execute(compact('newdata','session_id'));

                // Retourner la réponse
                if ( (bool)$stmt->errorCode() ) {
                    $response->setError(500, "session_error","Error while writing session data");
                    $response->send();
                    die; 
                } 
                // Succès de l'écriture
                // Retourner les données au format JSON dans le corps de la réponse    
                echo json_encode(
                    array(
                        'success' => true, 
                    )
                );

            }

        } else {
            // Pas de données
            $response->setError(500, "session_error","No data, nothing done");
            $response->send();
            die;       

        }
        break;



    default :

        // Mode inconnu
        $response->setError(400, "session_error","Unknown session mode in request");
        $response->send();
        die;   
} 
