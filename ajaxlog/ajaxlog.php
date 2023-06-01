<?php
/**
 * Plugin Name:       Ajax Logger
 * Plugin URI:        https://managingwp.io
 * Description:       Record all admin_init requests to troubleshoot high admin-ajax.php requests.
 *
**/

/**
 * Original code from - https://stackoverflow.com/questions/69234458/how-to-log-queries-that-go-to-wp-admin-admin-ajax-php
 * Debug, define('AJAXDEBUG','true'); in wp-config.php or user-config.php
 *
**/

add_action( 'admin_init', 'ajax_logger', 10, 2);

function ajax_logger() {
    // Variables
    $current_date=date('m/d/Y h:i:s a', time());

    // Settings, should all be defines in wp-config.php eventually
    // Match URI - a filter to match specific URI's not used yet.
    $match_uri="";

    // Exclude any text
    $exclude="";

    // Enable and Disable http headers and post data
    $enable_http_headers="0";
    $enable_http_post="1";
    $enable_csv_output="1";

    // Log to file
    // $file = dirname(__FILE__) . '/ajaxlog.log'; // Old method placed into wp-content/mu-plugins
    $log_file = ABSPATH . '../ajaxlog.log';
    $csv_file = ABSPATH . '../ajaxlog.csv';

    // Request Link
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

    // Grab HTTP headers.
    $http_headers = "";
    if ( $enable_http_headers == "1" ) {
        foreach (getallheaders() as $name => $value) {
            $http_headers .= "$name: $value;\n";
        }
    }

    // Grab HTTP post data.
    if ( $enable_http_post == "1" ) {
        $http_post = trim(preg_replace('/\s\s+/', ' ', print_r($_POST,true)));
    }
    
    // Logged in user?
    if ( is_user_logged_in() ) {
        if ( is_admin() ) {
            $logged_in="Logged In Admin";
        } else {
            $logged_in="Logged In";
        }
    } else {
        $logged_in="Visitor";
    }
    
    // Create message
    $log_message = "[".$current_date."] - ";
    $log_message .= $logged_in." - ";
    $log_message .= $_SERVER['REMOTE_ADDR']." - ";
    $log_message .= $actual_link." - ";
    $log_message .= "RURI:".$_SERVER['REQUEST_URI']." - ";
    $log_message .= "HH:".$http_headers." - ";
    $log_message .= "HPD:".$http_post." - ";
    $log_message .= "\n";

    // Check for excluded content
    if ($exclude !== "") {
        ajax_logger_debug($log_file, "exclude is specified - $exclude");
        if ( strpos($log_message, $exclude) !== false ) {
            ajax_logger_debug($log_file,"excluded text detected, not logging");
        } else {
            file_put_contents($log_file, $log_message, FILE_APPEND);
        }
    } else {
        file_put_contents($log_file, $log_message, FILE_APPEND);
    }
}

function ajax_logger_debug ($log_file, $log_message) {
    if (defined('AJAXDEBUG')) {
        file_put_contents($log_file, "DEBUG:".$log_message."\n", FILE_APPEND);
    }
}

?>