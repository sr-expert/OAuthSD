<?php
/* create_image.php
Partie de dgy_captcha.php
Création d'une image brouillée.
Le fond de l'image est donné par le fichier bruit.png qui doit se trouver
dans le même répertoire.
Appel : create_image.php?captcha=xxxxx

Licence GNU GPL
copyright (c) 2009-2013 B.Degoy
*/

date_default_timezone_set('Europe/Paris') ; //[dnc1] Pour PHP 5.3

$curdir = getcwd();
chdir($_SERVER['DOCUMENT_ROOT']);
include 'ecrire/inc_version.php';


/*
Pour créer une image, on envoit un en-tête avec la fonction header()
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

// Afin de personnaliser chacun de nos caractères, on les stocke un par un dans des variables
$char1=substr($rand_str,0,1);
$char2=substr($rand_str,1,1);
$char3=substr($rand_str,2,1);
$char4=substr($rand_str,3,1);
$char5=substr($rand_str,4,1);

$image=imagecreatefrompng("bruit.png");
/*
putenv() fixe la valeur de la variable d'environnement pour GD. Cette valeur
n'existera que durant la vie du script courant, et l'environnement initial sera
restauré lorsque le script sera terminé
Cette ligne est utile si vous avez des problèmes lorsque la police de
caractère réside dans le même dossier que le script qui l'utilise
Remarquez que lorsqu'on utilisera les polices, il faudra enlever l'extension
.tff
*/
putenv('GDFONTPATH=' . realpath('.'));
/*
glob() retourne un tableau contenant les fichiers trouvés dans le dossier
avec l'extension .ttf. Vous pouvez donc ajouter autant de police TTF que vous voulez
*/
$files = glob("*.ttf");
foreach ($files as $filename) {
    $filename = substr($filename,0,-4); // retire l'extension .ttf
    $fonts[] = $filename; // ajoute les noms des polices sans leur extension dans un tableau
}
/*
imagecolorallocate() retourne un identifiant de couleur
On définit les couleurs RVB qu'on va utiliser pour nos polices et on
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
On crée la fonction aleatoire() qui va retourner une valeur prise au hasard dans un tableau
Elle sera utilisée pour piocher une couleur et une police au hasard pour chaque caractère
*/
function aleatoire($tab){
    $max = count($tab)-1;
    $hasard = mt_rand(0,$max);
    return ($tab[$hasard]);
}
/*
On met en forme nos caractères un par un pour les disposer sur notre
image d'origine bruit.png
imagettftext(image, taille_de_la_police, angle, coordonnée_X_à_partir_du_bord,
coordonnée_Y_à_partir_du_bord, couleur_RVB, police_de_caractères,
texte) dessine un texte avec une police TrueType
*/
imagettftext($image, 25, mt_rand(-30,30), 10, 35, aleatoire($colors), aleatoire($fonts), $char1);
imagettftext($image, 25, mt_rand(-30,30), 40, 35, aleatoire($colors), aleatoire($fonts), $char2);
imagettftext($image, 25, mt_rand(-30,30), 60, 35, aleatoire($colors), aleatoire($fonts), $char3);
imagettftext($image, 25, mt_rand(-30,30), 100, 35, aleatoire($colors), aleatoire($fonts), $char4);
imagettftext($image, 25, mt_rand(-30,30), 120, 35, aleatoire($colors), aleatoire($fonts), $char5);

// imagepng() crée une image PNG en utilisant l'image $image
imagepng($image);
// imagedestroy() libère toute la mémoire associée à l'image $image
imagedestroy($image);

?>