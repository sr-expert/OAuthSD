<?php


// Autoloading by Composer
require_once '../../vendor/autoload.php';
// https://github.com/ovh/php-ovh
use \Ovh\Api;
// https://github.com/ovh/php-ovh-sms
use \Ovh\Sms\SmsApi;

$time_start = microtime(true);

define('PRIVATE', true);
require_once __DIR__.'/../../commons/configure_oidc.php'; 
require_once OIDCSRV_ROOT_PATH . 'includes/server.php';    

//*** End of configuration ***

sendsms('+33786822490','coucou');

/**
* Send a sms with OVH API
*  https://api.ovh.com/console/#/sms/{serviceName}/jobs#POST
* 
* usage : sendsms('+33786822490','coucou')
* 
* @param mixed $receivers   Ex: "+3360000000" Receiver parameter must be valid international phone numbers.
* @param mixed $message
*/
function sendsms($receiver, $message) {   

    //$message = substr($message, 0, 160);  // 160 chars max


    // Init SmsApi object
    $Sms = new SmsApi( OVHSMSAPI_APPLICATIONKEY, OVHSMSAPI_APPLICATIONSECRET, OVHSMSAPI_ENDPOINT, OVHSMSAPI_CONSUMER_KEY );

    // Get available SMS accounts
    $accounts = $Sms->getAccounts();

    if ( $accounts[0] ) {

        // Set the account 
        $Sms->setAccount($accounts[0]);
        $senders = $Sms->getSenders();
        
        $details = $Sms->getUserDetails( OVHSMSAPIUSER );
        if ( 
        $details['quotaInformations']['quotaStatus'] === 'inactive' 
        OR ($details['quotaInformations']['quotaStatus'] === 'active' AND $details['quotaInformations']['quotaLeft'] > 0 ) 
        ) { 
            
            // Set user
            $void = $Sms->setUser( OVHSMSAPIUSER );
            // Create a new message that will allow the recipient to answer (to FR receipients only)
            $Message = $Sms->createMessage();
            $Message->setSender($senders[0]);
            $Message->addReceiver($receiver);
            $Message->setIsMarketing(false);

            // Send it
            return $Message->send($message);

        } else
            return false;

    } else 
        return false;

}
