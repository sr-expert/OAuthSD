<?php
/*
Projet OAuthSD
API HTTP REST
Adaptation du plugin collectionjson à OAuthSD.

Cette adaptation poursuit trois objectifs :
- définir les types autorisés,
- interdire de lister certains champs sensibles,
- affiner les autorisations et authentifier avec OAuthSD,
- servir OAuthSD : statistiques.

copyright(c) 2019 Bertrand Degoy DnC
*/

//CONFIG

/* Cette API permet l'accès à la plupart des données manipulées par SPIP, les 
types correspondant aux tables éponymes de la base de données. Cependant, autant 
pour des raisons pratiques que de sécurité, les types servis sont limités à ceux 
qui seront définis ici.
Si la chaine est '', tous les types seront autorisés.
Attention : le type est au singulier !*/ 
define('API_HTTP_TYPES_AUTORISES','oidclog,user,client,auteur,credential');  

/* S'agissant d'un serveur d'authentification, certaines données sont particulièrement 
sensibles et doivent rester sanctuarisées.*/ 
define('API_HTTP_CHAMPS_SENSIBLES','client_secret,client_ip,password,pgp'); 

/* Si l'API peut être accessible autrement que par un client de confiance par 
un canal sûr, il faut authentifier l'origine de la requête. 
Cette constante peut aussi être fixée à false pendant le développement.
Il faudra aussi définir l'URL de base du serveur d'authentification.*/
define('API_HTTP_AUTHENTICATE', true);
define('AUTHENTICATION_SERVER_URL', 'https://oa.dnc.global/');      // Ne peut-on pas trouver cela ailleurs?

/* Si API_HTTP_AUTHENTICATE est fixé à true, l'authentification du client requiert 
un jeton d'identité valide. Celui-ci contient la déclaration sub égale à l'id du 
client (client_id). Si la constante suivante est non nulle, sub devra être égal à  
l'une des valeurs indiquées. */
define('API_HTTP_CLIENT_ID', '');   

/* Si API_HTTP_AUTHENTICATE a été fixé à false, il convient d'appliquer tout ou 
partie des vérifications suivantes : */
define('API_HTTP_CLIENT_IP', ''); // Vérifier que la requête vient de l'IP indiquée si non nulle
define('API_HTTP_CLIENT_HOST', ''); // Vérifier que la requête vient du host indiqué si non nul

/* Si la requête ne définit pas le paramètre limit, les requêtes acceptant le 
paramètre limit utiliseront la valeur suivante par défaut : */
define ('API_DEFAULT_LENGTH', 100);

/* Nombre maximum de lignes que peut retourner une requête (dans le cas où le paramètre 
limit n'est pas requis, comme quand la requête n'accepte que les paramètres tmin et/ou tmax) :*/
define ('API_MAX_ITEMS_RETURNED', 1000);