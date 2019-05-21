<?php
/** Test élémentaire du serveur OIDC
* Ce test effectue toutes les étapes de l'authentification et retourne les 
* données Userinfo ou une erreur.
* 
* Usage : https://.../oidc/tests/OP-scope-All.php 
* Adresse de retour : https://.../oidc/tests/essai1.php
* 
*/

$time_start = microtime(true);

$client_id = 'essai1';
$client_secret = 'qsDr43!Ml@';

$server = 'oa.dnc.global';
$authorization_endpoint = 'https://' . $server . '/authorize';
$token_endpoint = 'https://' . $server . '/token';
$introspection_endpoint = 'https://' . $server . '/introspect'; 
$userinfo_endpoint = 'https://' . $server . '/userinfo';

define('PRIVATE', true);
require_once __DIR__.'/../../commons/configure_oidc.php';      
require_once __DIR__.'/../../oidc/includes/utils.php';

//*** End of configuration ***

ini_set('display_errors', 1);

// Set session
$state = new_session_start('essai1', SLI_SESSION_DIR);

$_SESSION['time_start'] = $time_start;


if ( !isset($_GET['error']) ) {

    if ( ! isset($_GET['code']) ) {
        // Step 1. Authorization Code request

        $_SESSION['state'] = encrypt($state); //[dnc21] 

        $data = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'scope' => 'openid profile email address phone',        // Comme OP-scope-All
            'state' => $state,
            //'prompt' => 'login', 
        );

        $trace = '';
        $trace .= '----- Step 1 : Authorization Code request -----' . "<br />";
        $trace .= 'Begin : ' . date("Y-m-d h:i:sa",time()) . "<br />";
        $trace .= 'session_name : ' . session_name() . "<br />";
        $trace .= 'state : ' . $state . "<br />";
        $trace .= 'data : ' . print_r($data,true) . "<br /><br />";
        $_SESSION['trace'] = $trace;    

        $authorization_endpoint .= '?' . http_build_query($data);
        header('Location: ' . $authorization_endpoint);
        exit();

    } else {

        // Return from Authorization Code request

        $state = $_GET['state'];

        if ( isset($state) ) {

            // Check state
            if ( $state == decrypt(@$_SESSION['state']) ) {  //[dnc21] 

                // Step 2. Token request

                $code = $_GET['code'];

                $data = array(
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                );

                $trace = @$_SESSION['trace'];
                $trace .= '----- Step 1b : Authorize result -----' . "<br />";
                $trace .= urldecode(@$_GET['trace']) . "<br />";
                $trace .= '----- Step 2 : Token request -----' . "<br />";
                $trace .= 'state : ' . $state . "<br />";
                $trace .= 'code : ' . $code . "<br />";
                $trace .= 'data : ' . print_r($data,true) . "<br /><br />";
                $_SESSION['trace'] = $trace;    

                $h = curl_init($token_endpoint);
                curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($h, CURLOPT_TIMEOUT, 10);
                curl_setopt($h, CURLOPT_USERPWD, "{$client_id}:{$client_secret}");
                curl_setopt($h, CURLOPT_POST, true);
                curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
                curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data));

                $res = curl_exec($h);

                if ( is_array(json_decode($res, true) ) ) {

                    curl_close($h);
                    $res = json_decode($res, true);

                    $access_token = $res['access_token'];

                    if  ( empty($res['error'] ) ) {

                        // Step 3 - Validate signed JWT token using introspection
                        // Post Methode  
                        $data1 = array(
                            'token' => $res['id_token'],
                            'state' => $state,
                        );

                        $trace = $_SESSION['trace'];
                        $trace .= '----- Step 3 : JWT Introspection -----' . "<br />";
                        $trace .= 'access token : ' . $access_token . "<br />";
                        $trace .= 'data : ' . print_r($data1,true) . "<br /><br />";
                        $_SESSION['trace'] = $trace;    

                        $h = curl_init($introspection_endpoint);
                        curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($h, CURLOPT_TIMEOUT, 10);
                        curl_setopt($h, CURLOPT_POST, true);
                        curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
                        curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));   
                        curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data1));

                        $res = curl_exec($h);

                        if ( is_array(json_decode($res, true) ) ) {

                            curl_close($h);
                            $jwt = json_decode($res, true);

                            $trace = $_SESSION['trace'];
                            $trace .= '----- Step 3b : JWT decoded -----' . "<br />";
                            $trace .= 'data : ' . print_r($jwt,true) . "<br /><br />";
                            $_SESSION['trace'] = $trace;   

                            if  ( empty($jwt['error'] ) ) {

                                if ( $jwt['active'] == 'true' ) {

                                    // If Token Response is valid goto step 3
                                    // Step 4. Get UserInfo

                                    /* Auth Header Methode
                                    $headr = array();
                                    $headr[] = 'Authorization: Bearer ' . $access_token;
                                    $h = curl_init();
                                    curl_setopt($h, CURLOPT_URL, $userinfo_endpoint); 
                                    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($h, CURLOPT_TIMEOUT, 10);
                                    curl_setopt($h, CURLOPT_HTTPHEADER, $headr);
                                    //curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
                                    //*/

                                    //* Post Methode  
                                    $data2 = array(
                                        'access_token' => $access_token,
                                    );

                                    $trace = $_SESSION['trace'];
                                    $trace .= '----- Step 4 : Userinfo request -----' . "<br />";
                                    $trace .= 'data : ' . print_r($data1,true) . "<br /><br />";
                                    $_SESSION['trace'] = $trace;   

                                    $h = curl_init($userinfo_endpoint);
                                    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($h, CURLOPT_TIMEOUT, 10);
                                    curl_setopt($h, CURLOPT_POST, true);
                                    curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
                                    curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type:
                                    application/x-www-form-urlencoded'));    
                                    curl_setopt($h, CURLOPT_POSTFIELDS, 
                                        http_build_query($data2));
                                    //*/

                                    $res = curl_exec($h);

                                    if ( is_array(json_decode($res, true) ) ) {

                                        curl_close($h);
                                        $res = json_decode($res, true);

                                        if  ( empty($res['error'] ) ) {

                                            // Check User ID
                                            if ( $jwt['sub'] == $res['sub'] ) {

                                                // Everithing Ok !
                                                $time_end = microtime(true);
                                                $duration = $time_end - $_SESSION['time_start'];
                                                $trace = $_SESSION['trace'];
                                                $trace .= '----- Userinfo response -----' . "<br />";
                                                $trace .= 'data : ' . print_r($res,true) . "<br /><br />";
                                                $trace .= 'duration : ' . intval($duration * 1000) . " ms<br />";
                                                $_SESSION['trace'] = $trace;  
                                                
                                                echo $trace;
                                                echo '<h2>Ok</h2>';

                                            } else  
                                                // User of ID Token doesn't match UserInfo's one
                                                exit('User mismatch, got : ' . $res['sub']);

                                        } else
                                            // Token request error
                                            exit ('UserInfo Request error : ' . $res['error'] . ' : '
                                                . $res['error_description']);

                                    } else { 
                                        if ( !empty($res) ) {
                                            // script error ?
                                            exit ('UserInfo script error : ' . $res); 
                                        } else {
                                            // Curl error during UserInfo request
                                            $error = curl_error($h);
                                            curl_close($h);
                                            exit ('UserInfo request Curl error : ' . $error );
                                        }
                                    } 

                                } else
                                    // JWT is inactive
                                    exit('Error : Invactive ID Token'); 

                            } else
                                // Invalid id_token 
                                exit('Error : Invalid ID Token');

                        } else { 
                            if ( !empty($res) ) {
                                // script error ?
                                exit ('Introspection script error : ' . $res); 
                            } else {
                                // Curl error during Introspection request
                                $error = curl_error($h);
                                curl_close($h);
                                exit ('Introspection request Curl error : ' . $error );
                            }
                        }

                    } else {
                        // Token request error
                        exit ('Token request error : ' . $res['error'] . ' : ' 
                            . $res['error_description']);
                    }

                } else { 
                    if ( !empty($res) ) {
                        // script error ?
                        exit ('Token script error : ' . $res); 
                    } else {
                        // Curl error during Token request
                        $error = curl_error($h);
                        curl_close($h);
                        exit ('Token request Curl error : ' . $error );
                    }
                }

            } else 
                // Wrong State
                exit("Authorization error : incoherent State");

        } else 
            // Missing State
            exit("Authorization error : missing State");

    } 

} else {
    // Authorization error
    $trace = $_SESSION['trace'];
    echo $trace . "<br /><br />";
     
    exit("Authorization error : {$_GET['error']} : {$_GET['error_description']}");
}
