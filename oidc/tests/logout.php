<?php
/** Test élémentaire du serveur OIDC
* Ce test effectue toutes les étapes de l'authentification et termine par un logout
* 
* Usage : https://.../oidc/tests/logout.php 
* Adresse de retour : https://.../oidc/tests/logout.php
* 
*/

$time_start = microtime(true);

$client_id = 'essailogout';
$client_secret = 'qsDr43!Ml@';

$server = 'oa.dnc.global';
$authorization_endpoint = 'https://' . $server . '/authorize';
$token_endpoint = 'https://' . $server . '/token';
$introspection_endpoint = 'https://' . $server . '/introspect'; 
$userinfo_endpoint = 'https://' . $server . '/userinfo';
$logout_endpoint = 'https://' . $server . '/logout';

define('PRIVATE', true);
require_once __DIR__.'/../../oidc/../../commons/configure_oidc.php';      
require_once __DIR__.'/../../oidc/includes/utils.php';

//*** End of configuration ***

ini_set('display_errors', 1);

// Set session
$state = new_session_start('logout', SLI_SESSION_DIR);

$_SESSION['time_start'] = $time_start;

if ( !isset($_GET['error']) ) {

    if ( ! isset($_GET['code']) ) {
        // Step 1. Authorization Code request

        $_SESSION['state'] = encrypt($state); //[dnc21]

        $data = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'scope' => 'openid sli',        // granted scopes, must be in available scopes
            'state' => $state, 
        );

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

                            if  ( empty($jwt['error'] ) ) {

                                if ( $jwt['active'] == 'true' ) {

                                    // Effectuer un logout
                                    $h = curl_init($logout_endpoint);
                                    curl_setopt($h, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($h, CURLOPT_TIMEOUT, 10);
                                    curl_setopt($h, CURLOPT_POST, true);
                                    curl_setopt($h, CURLOPT_SSL_VERIFYPEER, false);
                                    curl_setopt($h, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));   
                                    curl_setopt($h, CURLOPT_POSTFIELDS, http_build_query($data1));

                                    $res = curl_exec($h);
                                    curl_close($h);
                                    
                                    if  ( empty($res['error'] ) ) {
                                        echo "That's all folks !";
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

    exit("Authorization error : {$_GET['error']} : {$_GET['error_description']}");
}
