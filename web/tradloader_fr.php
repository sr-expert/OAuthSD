<?php
// This is a SPIP language file  --  Ceci est un fichier langue de SPIP
// Fichier source, a modifier dans svn://zone.spip.org/spip-zone/_outils_/spip_loader/trunk/
$GLOBALS[$GLOBALS['idx_lang']] = array(

	// B
	'bouton_suivant' => 'Commencer l’installation >>',
	'bouton_suivant_maj' => 'Lancer la mise à jour >>',

	// C
	'ce_repertoire' => 'de ce répertoire',

	// D
	'donnees_incorrectes' => '<h4>Données incorrectes. Veuillez réessayer, ou utiliser l’installation manuelle.</h4>
		<p>Erreur produite : @erreur@</p>',
	'du_repertoire' => 'du répertoire',

	// E
	'echec_chargement' => '<h4>Le chargement a échoué. Veuillez réessayer, ou utiliser l’installation manuelle.</h4>',
	'echec_php' => 'Votre version de PHP @php1@ n’est pas compatible avec cette version de SPIP qui nécessite au moins PHP @php2@.',

	// S
	'spip_loader_maj' => 'La version @version@ de spip_loader.php est disponible.',

	// T
	'texte_intro' => '<p>Le programme va télécharger les fichiers de @paquet@ à l’intérieur @dest@.</p>',
	'texte_preliminaire' => '<br /><h2>Préliminaire : <b>Régler les droits d’accès</b></h2>
<p><b>Le répertoire courant n’est pas accessible en écriture.</b></p>
<p>Pour y remédier, utilisez votre client FTP afin de régler les droits d’accès à ce répertoire (répertoire d’installation de @paquet@).<br />
La procédure est expliquée en détail dans le guide d’installation. Au choix :</p>
<ul>
<li><b>Si vous avez un client FTP graphique</b>, réglez les propriétés du répertoire courant afin qu’il soit accessible en écriture pour tous.</li>
<li><b>Si votre client FTP est en mode texte</b>, changez le mode du répertoire à la valeur @chmod@.</li>
<li><b>Si vous avez un accès Telnet</b>, faites un <i>chmod @chmod@ repertoire_courant</i>.</li>
</ul>
<p>Une fois cette manipulation effectuée, vous pourrez <b><a href=\'@href@\'>recharger cette page</a></b> afin de commencer le téléchargement puis l’installation.</p>
<p>Si l’erreur persiste, vous devrez passer par la procédure d’installation classique (téléchargement de tous les fichiers par FTP).</p>',
	'titre' => 'Téléchargement de @paquet@',
	'titre_maj' => 'Mise à jour de @paquet@',
	'titre_version_courante' => 'Version actuellement installée : ',
	'titre_version_future' => 'Installation de la version : '
);
