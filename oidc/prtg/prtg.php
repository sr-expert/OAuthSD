<?php
/*
prtg.php
Ce script est appelé périodiquement par PRTG pour le suivi des autorisations.

OauthSD project
Auteur : Bertrand Degoy 
Copyright (c) 2016-2018 DnC  
Licence GPLv3
*/

include('./prtg_utils.php');

$ip = filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
if ( $ip != '164.132.107.166' ) exit(); //DnC PRTG uniquement //CONFIG

// Retrieve old values
$old_values = oidc_read_values(PRTG_OIDC_FILE);
$lap = time() - $old_values['time']; // temps écoulé (s) 

if ( $lap > 0 ) { 

    // Retreive current values and refresh old values
    $current_values = oidc_read_values(CURRENT_OIDC_FILE);
    $current_values['time'] = time();
    oidc_write_values(PRTG_OIDC_FILE, $current_values);

    // Compute Requests rate 
    $total_requests_rate = (int)( ( $current_values['total_requests'] -  $old_values['total_requests'] ) / $lap * 60 ) ;    // req/min
    // Compute Good requests rate 
    $good_requests_rate = (int)( ( $current_values['good_requests'] -  $old_values['good_requests'] ) / $lap * 60 ) ;    // req/min
    // Compute Authentications rate 
    $authentications_rate = (int)( ( $current_values['authentications'] -  $old_values['authentications'] ) / $lap * 60 ) ;    // req/min
    // Compute false requests rate 
    $false_requests_rate =  (int)( $total_requests_rate - $authentications_rate );
    
    //TODO : xml header
    echo'
    <?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <prtg>
            <result>
                <channel>Total requests rate</channel>
                <value>$total_requests_rate</value>
            </result>
            <result>
                <channel>Good requests rate</channel>
                <value>$good_requests_rate</value> 
            </result>
            <result>
                <channel>Authentications rate</channel>
                <value>$authentications_rate</value>
            </result>
            <result>
                <channel>False requests rate</channel>
                <value>$false_requests_rate</value>
            </result>
        </prtg>
    </xml>';

}
exit();


