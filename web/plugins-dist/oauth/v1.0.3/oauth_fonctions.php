<?php
/**
 * Fonctions utiles au plugin OAuth 2.0
 *
 * @plugin     OAuth 2.0
 * @copyright  2016
 * @author     DnC
 * @licence    GNU/GPL
 * @package    SPIP\Oauth\Fonctions
 */

if (!defined('_ECRIRE_INC_VERSION')) return;

//* 2019/02/26
  function filtre_purify($str) {   
    $arr = explode(' - ', $str);    // On suppose que la partie utile du message se trouve après deux ' - '.
    if ( is_array($arr) ) {
        //présenter les données pour l'affichage.
        $msg = strip_tags($arr[2]); 
        return '<span style="min-width:400px">' . $msg . '</span>';     // Imposer une largeur minimale à msg pour éviter une colonne étroite 
    } else {
        // tel-quel
        return $str;
    }    
} //*/


/* Fonctions pour le projet Monitoring by DnC
Auteur : B.Degoy http://degoy.com
Tous droits réservés
Copyright (c) 2017 DnC

*/
/* //o5
  function filtre_purify($str) {   
    $arr = unserialize($str);
    if ( is_array($arr) ) {
        //présenter les données pour l'affichage
        return '<pre style="min-width:800px">' . print_r($arr, TRUE) . '</pre>';     //o5 Imposer une largeur minimale à msg pour éviter une colonne étroite 
    } else {
        // tel-quel
        return $str;
    }    
} //*/


/**  unix2dateLocale
* Retourne une chaîne de caractères représentant une date/heure locale.
* Utilise le formatage de la fonction strftime(). Voir : http://php.net/manual/fr/function.strftime.php
* "suivant la localisation locale. Les noms des mois, des jours de la semaine mais aussi d'autres chaînes 
* dépendant de la location, respecteront la localisation courante définie par la fonction setlocale()".
* Cela ne fait de différence que si lon veut afficher les jours et les mois en clair, ce qui n'est pas le
* cas du format par défaut.  
* @param mixed $timestamp Unix Time (ou Epoche). Si null, la fonction retourne l'instant courant.
* @param mixed $format       
*/
function unix2dateLocale( $timeserial=NULL, $format = "%d %m %Y %H:%M:%S" ) {
    return strftime($format, $timeserial);
}


/* ATTENTION : Noter que les fonctions ci-dessous ne traitent pas le fuseau horaire.
Il n'y a qu'une façon de procéder avec rigueur :
- on suppose que toutes les dates sont données dans le fuseau horaire UTC (Z),
- l'heure du système du serveur doit être également réglé sur UTC (Z).
*/

/** unix2date
* Retourne une chaîne de caractères représentant l'heure ou l'instant courant.
* Noter que la conversion d'un timestamp Unix Time ne tient pas compte de la zone horaire courante. 
* Utilise le formatage de la classe DateTime. Voir : http://php.net/manual/en/function.date.php
* @param mixed $timestamp Unix Time (ou Epoche). Si null, la fonction retourne l'instant courant.
* @param mixed $format         
*/
function unix2date( $timestamp, $format="d m Y H:i:s" ) {
    if ( empty($timestamp) )  $timestamp = time();
    $date = new DateTime("@$timestamp"); 
    return $date->format($format);    
}

/**
* Retourne une chaîne de caractères représentant le timestamp Unix Time (ou Epoche) correspondant à la 
* chaîne de caractères donnée.
* Pour que cela fonctionne bien, il faut donner la chaîne de caractères sous le format ISO8601 = "Y-m-d\TH:i:sO"
* Noter que la conversion en un timestamp Unix Time ne tient pas compte de la zone horaire courante.
* @param mixed $strdatetime La chaîne à convertir. Si nulle, retourne le timestamp courant.
*/
function date2unix( $strdatetime =NULL ) {
    $d = new DateTime($strdatetime);
    return sprintf('%.0f', $d->getTimestamp());    
}

/**
* Retourne une chaîne de caractères représentant le timestamp Unix Time (ou Epoche) correspondant aux 
* chaînes de caractères données.
* Défaut : date et/ou heure courante.
* @param mixed $date
* @param mixed $time
*/
function dateheure2unix ($date=NULL, $time=NULL) {
    if ( empty($date) ) $date = strftime("%d-%m-%Y");
    if ( empty($time) ) $time = strftime("%H:%M:%S");
    $datetime = $date . 'T' . $time;   // format ISO8601
    return date2unix($datetime);
}


