<?php

$client_id = 'essailogout';
$client_secret = 'qsDr43!Ml@';

$server = 'oa.dnc.global';
$authorization_endpoint = 'https://' . $server . '/authorize';
$token_endpoint = 'https://' . $server . '/token';
$introspection_endpoint = 'https://' . $server . '/introspect'; 
$userinfo_endpoint = 'https://' . $server . '/userinfo';
$logout_endpoint = 'https://' . $server . '/logout';

define('PRIVATE', true);
require_once __DIR__.'/../../oidc/includes/configure.php';      
require_once __DIR__.'/../../oidc/includes/utils.php';


?>  
<script>
  
  $(document).ready(function() {


            setInterval(function() {

                var result = "";
                $.ajax({
                    type : "get",
                    url : "<?php 
                        echo $authorization_endpoint; ?>",
                    data : { 'response_type' : 'code',
                        'client_id' : "essailogout",
                        'user_id' : spdegoy,
                        'state' :  "<?php echo str_replace(array('+', '/', "\r", "\n", '='), array('-', '_'), base64_encode(md5(rand()))); ?>",
                        'scope' : 'openid',
                        'prompt' : 'none',
                    },
                    statusCode : {
                        403 : function(data){
                            
                        }
                    },
                    error : function(obj,msg,objEvent){
                        result = false;
                    }
                });

                }, 100);

        }


    });

</script>

