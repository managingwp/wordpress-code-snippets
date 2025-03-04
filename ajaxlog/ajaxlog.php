<?php

/**
 * ajaxlog.php
 * Description: Record all admin_init requests to troubleshoot high admin-ajax.php requests.
 * Plugin Name: Ajax Logger
 * Plugin URI: https://managingwp.io
 * Status: Complete
 **/

/**
 * Original code from - https://stackoverflow.com/questions/69234458/how-to-log-queries-that-go-to-wp-admin-admin-ajax-php
 * Debug, define('AJAXDEBUG','true'); in wp-config.php or user-config.php
 *
 **/

add_action('admin_init', 'ajax_logger', 10, 2);
function ajax_logger() {

    // Only proceed if AJAXDEBUG is enabled in wp-config.php

    if (!defined('AJAX_DEBUG_ENABLED') || AJAX_DEBUG_ENABLED !== true) {
        return;
    }

    global $log_file, $start_time, $log_message, $log_dir, $csv_file;

    //Log total request time
    $start_time = microtime(true);

    // Register shutdown hook
    add_action('shutdown', 'log_execution_time');


    // Log to file
    // $file = dirname(__FILE__) . '/ajaxlog.log'; // Old method placed into wp-content/mu-plugins
    $log_file = ABSPATH . '/logs/ajaxlog.log';
    $csv_file = ABSPATH . '/logs/ajaxlog.csv';

    //rotate log file if larger than 10M
    if (file_exists($log_file) && filesize($log_file) > 4 * 1024 * 1024) {
        rename($log_file, $log_file . '.' . date('Y-m-d_H-i-s'));
    }

    // Variables
    $current_date = date('m/d/Y H:i:s', time());

    // Match URI - a filter to match specific URI's not used yet.
    $match_uri = defined('AJAX_DEBUG_MATCH_URIS') ? AJAX_DEBUG_MATCH_URIS : "";
    //convert to array if not already, so that can iterate it later
    if (!is_array($match_uri)) {
        $match_uri = array($match_uri);
    }

    // Exclude any text
    if (defined('AJAX_DEBUG_EXCLUDES')) {
        $excludes = AJAX_DEBUG_EXCLUDES;

        if (!is_array($excludes)) {
            $excludes = array($excludes);
        }
    }

    // Enable and Disable http headers and post data
    $enable_http_headers = "0";
    $enable_http_post = "1";
    $enable_csv_output = "1";

    // Request Link
    $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

    if (str_contains($_SERVER['REQUEST_URI'], "as_async_request_queue_runner")) {
        error_log("[$current_date] " . $_SERVER['REQUEST_URI']);
    }
    // Grab HTTP headers.
    $http_headers = "";
    if ($enable_http_headers == "1") {
        foreach (getallheaders() as $name => $value) {
            $http_headers .= "$name: $value;\n";
        }
    }

    // Grab HTTP post data.
    if ($enable_http_post == "1") {
        //sort $_POST and move action to the front, for readability
        ksort($_POST);
        if (isset($_POST['action'])) {
            $_POST = array('action' => $_POST['action']) + $_POST;
        }

        $http_post = trim(preg_replace('/\s\s+/', ' ', print_r($_POST, true)));
        $http_post = str_replace(array("\r", "\n"), '', $http_post);
        $http_post = str_replace(array("Array(", "Array ("), '', $http_post);
        $http_post = trim(substr($http_post, 0, -1));
    }
    $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "";
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
    $req_method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : "";

    // Logged in user?
    if (is_user_logged_in()) {
        $logged_in = "Logged In";
        $logged_in .= current_user_can('manage_options') ? " Admin" : " User";
        $logged_in .= " - ID: " . wp_get_current_user()->ID;
    } else {
        $logged_in = "Visitor";
    }
    $is_wpcli = defined('WP_CLI') ? WP_CLI : "";
    // Create message
    $log_message[] = "[$current_date]";
    $log_message[] = "IP Address: " . $ip_address;
    $log_message[] = "Referer Page: " . $referer;
    $log_message[] = "Req URI: " . $actual_link;
    $log_message[] = "Headers: " . $http_headers;
    $log_message[] = "Req Method: " . $req_method;
    $log_message[] = "WP-CLI: " . $is_wpcli;
    $log_message[] = "Post Data: " . $http_post;
    $log_message[] = "Server PID: " . getmypid();
    global $wpdb;
    $log_message[] = "mysql PID: " . $wpdb->dbh->thread_id;
    $log_message[] = $logged_in;

    $log_message = implode(" | ", $log_message);
    //$log_message = implode("\n", $log_message);
    // $log_message .= "\n";

    // Check for excluded content
    if (isset($excludes)) {
        foreach ($excludes as $exclude) {
            if (strpos($log_message, $exclude) !== false) {
                //ajax_logger_debug($log_file,"[$current_date] excluded text detected ($exclude), not logging");
                return;
            }
        }
    }
    // ajax_logger_debug($log_file, $log_message);
}

function log_execution_time() {
    global $start_time, $log_file, $log_message;
    $end_time = microtime(true);
    $duration = $end_time - $start_time;
    $log_message .= " | Duration: " . number_format($duration, 4) . " seconds\n";
    ajax_logger_debug($log_file, $log_message);
}

function ajax_logger_debug($log_file, $log_message) {
    // Ensure the directory exists
    $log_dir = dirname($log_file);
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    file_put_contents($log_file, "DEBUG: $log_message \n", FILE_APPEND);
}
