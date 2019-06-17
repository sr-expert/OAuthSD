<?php

/**
*  Créer une paire de clés publique/privée pour un client et l'enregistrer si 
* elle n'existe pas déjà.          
* 
* * @param mixed $id_client       //*****
*/
function create_and_save_pkeys( $id_client, $client_id ) {
  
    // Les clés existent ?
    $key_row = sql_fetsel('*', 'spip_public_keys', "client_id='$client_id'"); 
    
    if ( empty($key_row) ) {      
        
        // Créer une paire de clé publique/privée avec RS256 

        $config = array(
            "digest_alg" => "sha256",            // Vu de JWT, correspond au défaut : RS256
            //[dnc58] "private_key_bits" => 4096,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        // Create the private and public key
        $res = openssl_pkey_new($config);
        
        if ( !is_null($res)) {
            // Extract the private key from $res to $privKey
            openssl_pkey_export($res, $privKey);
            $key_row['private_key'] = $privKey;
            // Extract the public key from $res to $pubKey
            $pubKey = openssl_pkey_get_details($res);
            $key_row['public_key'] = $pubKey["key"];
            // Save in table
            $key_row['client_id'] = $client_id;
            $key_row['id_client'] = $id_client;
            $key_row['encryption_algorithm'] = 'RS256';
            sql_insertq('spip_public_keys', $key_row);
            
            return true;
            
        } else {
            // erreur lors de la génération de la paire de clés
            return false;
        }
    }

}