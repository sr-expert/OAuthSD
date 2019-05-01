<?php
/* create_image.php
Partie de dgy_captcha.php
Cr�ation d'une image brouill�e.
Le fond de l'image est donn� par le fichier bruit.png qui doit se trouver
dans le m�me r�pertoire.
Appel : create_image.php?captcha=xxxxx

Licence GNU GPL
copyright (c) 2009-2013 B.Degoy
*/

date_default_timezone_set('Europe/Paris') ; //[dnc1] Pour PHP 5.3

$curdir = getcwd();
chdir($_SERVER['DOCUMENT_ROOT']);
include 'ecrire/inc_version.php';


/*
Pour cr�er une image, on envoit un en-t�te avec la fonction header()
pour dire au navigateur qu'on envoie une image
Lorsqu'on va appeler l'image avec <img src="..." /> on utlisera
bien image.php et non bruit.png
*/

header("Content-Type: image/png");

/*
$md5_hash = md5(rand(0,999));
$rand_str = substr($md5_hash, 15, 5);
*/
$md5_hash = md5(mt_rand(0,9999));
$rand_str = substr($md5_hash, strftime("%w")+3, 5);    //[dgy7] C'est mieux, non?

/*[dgy9]include_spip('session');
session_set('captcha2', $rand_str);
terminer_actualiser_sessions(); //[dgy9]
*/
$session_name = session_name();  //[dgy9]
if ( empty($session_name) ) {
    session_name('PHPSESSID');
}
session_start();
$_SESSION['captcha2'] = $rand_str;     //[dgy9]

chdir($curdir);

// Afin de personnaliser chacun de nos caract�res, on les stocke un par un dans des variables
$char1=substr($rand_str,0,1);
$char2=substr($rand_str,1,1);
$char3=substr($rand_str,2,1);
$char4=substr($rand_str,3,1);
$char5=substr($rand_str,4,1);

$image=imagecreatefrompng("bruit.png");
/*
putenv() fixe la valeur de la variable d'environnement pour GD. Cette valeur
n'existera que durant la vie du script courant, et l'environnement initial sera
restaur� lorsque le script sera termin�
Cette ligne est utile si vous avez des probl�mes lorsque la police de
caract�re r�side dans le m�me dossier que le script qui l'utilise
Remarquez que lorsqu'on utilisera les polices, il faudra enlever l'extension
.tff
*/
putenv('GDFONTPATH=' . realpath('.'));
/*
glob() retourne un tableau contenant les fichiers trouv�s dans le dossier
avec l'extension .ttf. Vous pouvez donc ajouter autant de police TTF que vous voulez
*/
$files = glob("*.ttf");
foreach ($files as $filename) {
    $filename = substr($filename,0,-4); // retire l'extension .ttf
    $fonts[] = $filename; // ajoute les noms des polices sans leur extension dans un tableau
}
/*
imagecolorallocate() retourne un identifiant de couleur
On d�finit les couleurs RVB qu'on va utiliser pour nos polices et on
les stocke dans le tableau $colors[]
Vous pouvez ajouter autant de couleurs que vous voulez
*/
$colors = array(imagecolorallocate($image, 255,0,0), // rouge cru
    imagecolorallocate($image, 109,30,100), // violet
    imagecolorallocate($image, 30,80,180), // bleu
    imagecolorallocate($image, 40,100,20), // vert
    imagecolorallocate($image, 255,90,0), // orange
    imagecolorallocate($image, 130,130,130)); // gris

/*
On cr�e la fonction aleatoire() qui va retourner une valeur prise au hasard dans un tableau
Elle sera utilis�e pour piocher une couleur et une police au hasard pour chaque caract�re
*/
function aleatoire($tab){
    $max = count($tab)-1;
    $hasard = mt_rand(0,$max);
    return ($tab[$hasard]);
}
/*
On met en forme nos caract�res un par un pour les disposer sur notre
image d'origine bruit.png
imagettftext(image, taille_de_la_police, angle, coordonn�e_X_�_partir_du_bord,
coordonn�e_Y_�_partir_du_bord, couleur_RVB, police_de_caract�res,
texte) dessine un texte avec une police TrueType
*/
imagettftext($image, 25, mt_rand(-30,30), 10, 35, aleatoire($colors), aleatoire($fonts), $char1);
imagettftext($image, 25, mt_rand(-30,30), 40, 35, aleatoire($colors), aleatoire($fonts), $char2);
imagettftext($image, 25, mt_rand(-30,30), 60, 35, aleatoire($colors), aleatoire($fonts), $char3);
imagettftext($image, 25, mt_rand(-30,30), 100, 35, aleatoire($colors), aleatoire($fonts), $char4);
imagettftext($image, 25, mt_rand(-30,30), 120, 35, aleatoire($colors), aleatoire($fonts), $char5);

// imagepng() cr�e une image PNG en utilisant l'image $image
imagepng($image);
// imagedestroy() lib�re toute la m�moire associ�e � l'image $image
imagedestroy($image);

?>