<?php
/** Projet OAuthSD
* See : https://www.toptal.com/php/build-multilingual-app-with-gettext
* [dnc35]
*/

/**
* Verifies if the given $locale is supported in the project
* @param string $locale
* @return mixed bigramme_bigramme or null
*/
function valid($locale) {
    
    // valid locales
    global $locales;
    if ( is_null($locales) ) {
        $locales = array(    //CONFIG
            'fr' => 'fr_FR',
            'en' => 'en_US',
            'en' => 'en_GB', //[dnc35c]
            'de' => 'de_DE'
        );
    } 

    // Returns bigramme_bigramme ou false
    if ( strpos($locale,'_') === false ) {
        // bigramme, convert to bigramme_bigramme 
        return ($locales[$locale]);
    } else if ( array_search($locale, $locales) !== false ) {
        // bigramme_bigramme
        return $locale;
    }

}

// Execution starts here >>>>>

if ( isset($_GET['lang']) ) {
    // the locale can be changed through the query-string
    $lang = valid( $_GET['lang'] );
    setcookie('lang', $lang); //it's stored in a cookie so it can be reused
} elseif ( isset($_COOKIE['spip_lang']) ) {  //[dnc35b]
    // if the SPIP cookie is present instead, let's give it priority
    $lang = valid( $_COOKIE['spip_lang'] );
    if ( $lang == 'en_EN' ) $lang = 'en_GB'; //[dnc35c] 
} elseif ( isset($_COOKIE['lang']) ) {
    // if the cookie is present instead, let's just keep it
    $lang = valid( $_COOKIE['lang'] ); 
} elseif ( isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) {
    // default: look for the languages the browser says the user accepts
    $langs = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    array_walk($langs, function (&$lang) { $lang = strtr(strtok($lang, ';'), ['-' => '_']); });
    foreach ($langs as $browser_lang) {
        if ( $lang = valid($browser_lang)) {
            break;
        }
    }
}

if ( is_null($lang) ) $lang = 'fr_FR';

// define the global system locale given the found language
putenv("LANG=$lang");

// useful for date functions (LC_TIME) or money formatting (LC_MONETARY) ...
setlocale(LC_ALL, $lang);

// Gettext look for ../locales/<lang>/LC_MESSAGES/main.mo
bindtextdomain('main', '../locale');
bind_textdomain_codeset('main', 'UTF-8');

// default domain the gettext() calls will respond to
textdomain('main');

