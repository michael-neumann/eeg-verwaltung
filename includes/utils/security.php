<?php
if (!defined('ABSPATH')) { exit; }
function eeg_verw_utils_security_bootstrap(){}
function eeg_verw_get_client_ip(){
    $keys = ['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED','HTTP_X_CLUSTER_CLIENT_IP','HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR'];
    foreach ($keys as $k){
        if (!empty($_SERVER[$k])){
            $ipList = explode(',', $_SERVER[$k]);
            foreach ($ipList as $ip){
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) { return $ip; }
            }
        }
    }
    return '';
}
