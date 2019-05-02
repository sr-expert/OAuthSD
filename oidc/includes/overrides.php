<?php
/**
* 
*/

//[dnc6] Redefine  JWT::generateJwtHeader

// Create the closure
$generateJwtHeader = function ($payload, $algorithm) {   
    //[dnc6] jwk or jku claims in header
    if ( JWK_IN_JWT_HEADER ) {
        $jh = array(
            'typ' => 'JWT',
            'alg' => $algorithm,
            'kid' => $payload['kid'],
            'jwk' => OIDC_SERVER_URL . '/oidc/jwks/' . $payload['kid'] . '.json',
        ); 
    } else if ( JKU_IN_JWT_HEADER ) {  
        $jh = array(
            'typ' => 'JWT',
            'alg' => $algorithm,
            'kid' => $payload['kid'],
            'jku' => OIDC_SERVER_URL . '/oidc/jwks.json',
        );
    } else {
        $jh = array(
            'typ' => 'JWT',
            'alg' => $algorithm,
        );

    }

    return $jh;
};

// redefine
runkit_method_redefine(
    'JWT',
    'generateJwtHeader',
    $generateJwtHeader,
    RUNKIT_ACC_PUBLIC
);