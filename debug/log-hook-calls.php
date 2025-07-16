<?php
/**
 * log-hook-calls.php
 * Description: Log all hook calls to a file
 * Version: 1.0.0
 * Status: Complete
 *
 * Place into wp-content/mu-plugins directory and log all hook calls
 */
add_action( 'all', 'log_hook_calls' );
function log_hook_calls() {
    // Restrict logging to requests from your IP - your IP address goes here
    $client_ip = "192.168.0.1";
    // The full path to your log file:
    $log_file = ABSPATH . "../logs/log-hook-calls.log";
    if ($_SERVER['REMOTE_ADDR'] == $client_ip) {
        file_put_contents($log_file, "DEBUG: ".date("d-m-Y, H:i:s")." - ".current_filter()."\n", FILE_APPEND);
    }
}

?>