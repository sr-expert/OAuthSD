<?php
/**
* Appliquer les règles métiers de l'organisation pour attribuer des privilèges à l'utilisateur final.
* Construit les valeurs des claims profil et scope à introduire dans le jeton d'identité.
* @param string $user_id : utilisateur final identifié par le contrôleur Authorize,
* @param string $client_id : application cliente passée aux contrôleurs Authorize et Token,
* @param string $scope : les scopes passés au contrôleur Authorize, 
* @param mixed $cnx : objet PDO connecteur à la base de données.
* 
* OauthSD project
* Auteur : Bertrand Degoy 
* Copyright (c) 2016-2018 DnC  
* Licence GPLv3
*/
function interscope( $user_id, $client_id, $scope, $cnx ) {
 
    // appliquer ici les règles métier de l'organisation 
    // pour déterminer les privilèges.

    return array( 
        'profil' => $profil,  
        'scope'  => $scope  
    );

}
