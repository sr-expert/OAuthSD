<?php
/**
* Projet OAuthSD
* cors.php
* [dnc36b]
* See:
*  https://developer.mozilla.org/en-US/docs/Web/HTTP/Server-Side_Access_Control
* 
*/ 

if ( !defined('__AUTHORIZE') ) die;  

// apache_request_headers replacement for nginx 
if (!function_exists('apache_request_headers')) { 
    function apache_request_headers() { 
        foreach($_SERVER as $key=>$value) { 
            if (substr($key,0,5)=="HTTP_") { 
                $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5))))); 
                $out[$key]=$value; 
            }else{ 
                $out[$key]=$value; 
            } 
        } 
        return $out; 
    } 
} 

/**
* Analyze request, determine if it is CORS or not. If CORS detect actual/preflight.
* Prepare response headers in accordance.
* 
* We follow this flowchart : https://www.html5rocks.com/static/images/cors_server_flowchart.png
*
* @param string $client_domain  full URL of client domain - ex : https://mon_site.com
* @param string $vrh : valid request headers - default to 'Accept, Accept-Language, Content-Language, Content-Type' 
* @param string $vrm : valid request methods - default to 'GET POST HEAD'
* @param mixed $aceh : Expose these response header to client - default to 'x-requested-with'
* @param mixed $acam : Set Access-Control-Allow-Methodsresponse header  - default to 'GET,POST,HEAD'
* @param mixed $acac : Set the Access-Control-Allow-Credentials response header - default to false
* 
* returns array 
*/
function cors_what_request( $client_domain, $vrm = null, $vrh = null, $aceh = null, $acam = null, $acac = false) {

    $request_headers = apache_request_headers();
    
    $response_headers = array();
    $what = array();

    // Is it a CORS request, Actual or Preflight ?
    if ( $request_headers) {

        if ( !empty($origin = $request_headers['origin']) OR @$_SERVER['HTTP_X_REQUESTED_WITH'] == "XMLHttpRequest" ) {   // Problem : Firefox don't always send Origin header

            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {

                // OPTIONS method
                if ( !empty( $req_acrm = $request_headers['Access-Control-Request-Method']) ) {
                    // Preflight request? Check valid method 
                    if ( is_null($vrm) ) $vrm = 'GET POST HEAD';
                    if ( strpos($vrm, $req_acrm) === False ) {
                        $what = array(
                            'type' => 'error',
                            'msg' => "invalid ACRM : $req_acrm",  
                        );  

                    } else {    
                        if ( !empty( $req_acrh = $request_headers['Access-Control-Request-Header']) ) {
                            // Preflight request? Check valid Access-Control-Request-Header
                            if ( is_null($vrh) ) $vrh = "Accept, Accept-Language, Content-Language, Content-Type"; // simple headers    
                            if ( strpos($vrh, $req_acrh) === False ) {
                                // Invalid ACRH
                                $what = array(
                                    'type' => 'error',
                                    'msg' => "invalid ACRH : $req_acrh",
                                    'origin' => $origin,
                                    'method' => $acrm,     
                                );

                            } else {
                                // Preflight request with Access-Control-Request-Header
                                $what = array(
                                    'type' => 'preflight',
                                    'origin' => $origin,
                                    'acrh' => $req_acrh,
                                    'method' => $acrm,    
                                );
                            }

                        } else { 
                            // Preflight request
                            $what = array(
                                'type' => 'preflight',
                                'origin' => $origin,
                                'method' => $acrm,    
                            );
                        }
                    }    

                } else {
                    // Actual request 
                    $what = array(
                        'type' => 'actual',
                        'origin' => $origin,    
                    );     
                }        

            } else {
                // Actual request 
                $what = array(
                    'type' => 'actual',
                    'origin' => $origin,  
                );   
            }

        } else {
            // Not a CORS request
            $what = array(
                'type' => 'notcors',
            );
        }   

    } else {
        // échec de la lecture des headers
        $what = array(
            'type' => 'error',
            'msg' => 'request_headers failed',  
        );   
    }

    // Return on error
    if ( $what['error'] ) {
        return $what;
    }


    // Prepare response headers
    
    if ( $what['type'] !== 'nocors' ) {
        // Ordinary request
        return $what;
        
    } else if ( $what['type'] === 'actual' ) {
        // CORS actual request
        if ( is_null( $aceh ) ) $aceh = 'x-requested-with';
        // Expose these response header to client
        $response_headers['Access-Control-Expose-Headers'] = $aceh;   
    
    } else if ( $what['type'] === 'preflight' ) {
        // CORS preflight request
        
        // Set Access-Control-Allow-Methods
        if ( is_null( $acam ) ) $acam = 'GET,POST,HEAD'; 
        $response_header['Access-Control-Allow-Methods'] = $acam;
        
        // Set Access-Control-Request-Header
        if ( is_null( $vrh ) ) $vrh = 'Content-Type';
        // If Access-Control-Request-Header was defined, allow the same in response.
        $acah = !is_null($what['req_acrh'])? $what['req_acrh'] : $vrh;    
        $response_header['Access-Control-Allow-Headers'] = $acah;
        
        // Set Access-Control-Max-Age to 1 minute
        $response_header['Access-Control-Max-Age'] = 60;
    } 
    
    // Set the Access-Control-Allow-Origin response header   
    $response_headers['Access-Control-Allow-Origin'] = $client_domain; 
    
    // Set the Access-Control-Allow-Credentials response header
    $response_headers['Access-Control-Allow-Origin'] = $acac? 'true' : 'false';
    
    $what['response_headers'] = $response_headers;
    
    return $what;

}