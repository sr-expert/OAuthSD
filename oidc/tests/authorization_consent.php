<?php
/** authorization_consent.php
* 
* Test du serveur OIDC
* Ce test effectue une demande d'authentification (prompt=login) puis, en cas de succès, 
* une demande de consentement.
* Puis enchaîne toutes les étapes de l'authentification et retourne les 
* données Userinfo ou une erreur.
* 
* Usage : https://oidc.intergros.dom/oidc/tests/essai1.php 
* Adresse de retour : https://oidc.intergros.dom/oidc/tests/essai1.php
* 
*/

//[dnc24]

$time_start = microtime(true);

$client_id = 'authorization_consent';
$client_secret = 'qsDr43!Ml@';

$oidc_server = 'oa.dnc.global';
$authorization_endpoint = 'https://' . $oidc_server . '/authorize';
$token_endpoint = 'https://' . $oidc_server . '/token';
$introspection_endpoint = 'https://' . $oidc_server . '/introspect'; 
$userinfo_endpoint = 'https://' . $oidc_server . '/userinfo';

define('PRIVATE', true);
require_once __DIR__.'/../../commons/configure_oidc.php'; 
require_once OIDCSRV_ROOT_PATH . 'includes/server.php';   
require_once OIDCSRV_ROOT_PATH . 'includes/utils.php'; 

//*** End of configuration ***

ini_set('display_errors', 1);

// Set session
new_session_start('oauthsd', SLI_SESSION_DIR);

$SESSION['time_start'] = $time_start;

////////////// Login //////////////////

if ( !isset($_GET['error']) ) {

    if ( ! isset($_GET['code']) ) {
        // Step 1. Authorization Code request

        @session_regenerate_id();
        $state = session_id();
        $_SESSION['state'] = encrypt($state); //[dnc21] [dnc33]

        $data = array(
            'response_type' => 'code',
            'client_id' => $client_id,
            'scope' => 'openid profile sli privileges',        // granted scopes, must be in available scopes
            'state' => $state,
            'prompt' => 'consent',    
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
            if ( $state == decrypt(@$_SESSION['state']) ) {  //[dnc21] [dnc33]

                // Step 2. Token request

                $code = $_GET['code'];

                $data = array(
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                );

                $trace = @$_SESSION['trace'];
                $trace .= '----- Step 1c : Authorize result -----' . "<br />";
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
                    $res1 = json_decode($res, true); 

                    if  ( empty($res1['error'] ) ) {

                        $access_token = @$res1['access_token'];

                        // Step 3 - Validate signed JWT token using introspection
                        // Post Methode  
                        $data1 = array(
                            'token' => $res1['id_token'],
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

                                if ( @$jwt['at_hash'] ) {
                                    // Verify access token
                                    $hash = hash('sha256', $access_token);
                                    $ath = substr($hash, 0, strlen($hash) / 2);
                                    $encryptionUtil = new \OAuth2\Encryption\Jwt();
                                    $ath = $encryptionUtil->urlSafeB64Encode($ath);
                                    if ( $ath !== $jwt['at_hash'] ) {
                                        // Token request error
                                        exit ('Access Token not valid');
                                    }       
                                }

                                if ( $jwt['active'] == 'true' ) {

                                    // Everithing Ok !
                                    $time_end = microtime(true);
                                    $duration = $time_end - $SESSION['time_start'];
                                    
                                    echo $trace;
                                    echo '<h2>Ok</h2>';


                                } else
                                    // JWT is inactive
                                    exit('Error : Invactive ID Token'); 

                            } else
                                // Invalid id_token 
                                exit('Error : Invalid ID Token : ' . $jwt['error'] . '<br /><br />Trace :<br />' . $_SESSION['trace']);

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
                        exit ('Token request error : ' . $res1['error'] . ' : ' 
                            . $res1['error_description']);
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
    $trace = @$_SESSION['trace'];
    echo $trace . "<br /><br />";

    exit("Authorization error : {$_GET['error']} : {$_GET['error_description']}");
}









