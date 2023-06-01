<?php

add_action( 'all', 'log_hook_calls' );
function log_hook_calls() {
    // Restrict logging to requests from your IP - your IP address goes here
    $client_ip = "192.168.0.1";
    // The full path to your log file:
    $log_file_path = ABSPATH . "../log-hook-calls.log";
    if ($_SERVER['REMOTE_ADDR'] == $client_ip) {
#        if ( WP_DEBUG_LOG ) {
#            error_log(date("d-m-Y, H:i:s") . ": " . current_filter() . "\n", 3, $log_file_path);
#        }
    }
}

?>