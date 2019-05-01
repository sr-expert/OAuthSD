<?php
	# appel SPIP
	//include('spip.php');
	//echo exec('whoami'); exit();   // Pour savoir sous quel user tourne Apache/PHP
    header("Status: 301 Moved Permanently", false, 301);
    header('Location: ./web/spip.php');
    exit();      
