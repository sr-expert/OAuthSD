Choix (statique) du fournisseur d'identité.

L'objectif de la méthode statifi est de permettre à l'utilisateur final de se connecter à l'aide d'un compte Google, Facebook etc.
Il ne s'agit pas de déléguer l'authentification des applications à ces Identity Provider (IP), mais de déléguer l'identification de l'OpenID Connect Provider (OP) à ces IP.
Ainsi :
- il n'y a pas de possibilité de tracking : l'IP ne voit que le serveur, pas l'application.
- on peut toujours limiter l'accès aux seuls utilsateurs inscrits sur l'OP.

Voir :
https://connect.ed-diamond.com/MISC/MISC-098/OpenID-Connect-presentation-du-protocole-et-etude-de-l-attaque-Broken-End-User-Authentication
