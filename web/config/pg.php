<?php
/* Connexion à la base de données PgSQL
OauthSD project

This code is not an open source!
You can not access, dispose, modify, transmit etc. this code without the written permission of DnC.
You can only use one coded copy provided you have a particular license from DnC.
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
All rights reserved
*/
if (!defined("_ECRIRE_INC_VERSION")) return;
spip_connect_db('localhost','5432','dnc_user','oidcinteX9BVqA8','dnc_oauth2','pg', 'oauth','','utf8');
 
