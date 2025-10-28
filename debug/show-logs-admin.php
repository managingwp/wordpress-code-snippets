<?php
/**
 * Plugin Name: Application Logs
 * Description: Displays top 10 lines from a log file in the WordPress admin section.
 * Version: 1.0
 * Author: Your Name
 * Status: Complete
 * Type: snippet
*/

// Add a menu item under Tools
function application_logs_menu() {
    add_submenu_page(
        'tools.php', // Parent menu slug
        'Application Logs', // Page title
        'Application Logs', // Menu title
        'manage_options', // Capability required
        'application-logs', // Menu slug
        'application_logs_page' // Callback function
    );
}
add_action( 'admin_menu', 'application_logs_menu' );

// Callback function to display the log file content
function application_logs_page() {
    // Check if the user has the capability to access the logs page
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Access denied' );
    }

    // Output the log lines
    echo '<div class="wrap">';
    echo '<h1>Application Logs</h1>';

    application_get_log('api', 'api_err_log.txt');
    application_get_log('curl', 'curl_log.txt');

    echo '</div>';
}

function application_get_log ($log_name, $log_file) {
    // Path to the log file on the server
    $log_file_path = ABSPATH.'application/api/'.$log_file;
    echo "<h2>Log $log_file_path</h2>";
    echo "<p>Here are the last 100 lines</p>";

    // Check if the log file exists
    if ( ! file_exists( $log_file_path ) ) {
        echo '<p>The log file '.$log_file_path.' does not exist.</p>';
        return;
    }

    // Read the log file and get the top 10 lines
    $log_content = file( $log_file_path );

    // Check if the log file is empty
    if ( empty( $log_content ) ) {
        echo '<p>The log file '.$log_file_path.' is empty.</p>';
        return;
    }

    // Get the top 10 lines
    $top_10_lines = array_slice( $log_content,-100 );

    // Output the log lines
    echo '<textarea rows="10" cols="100" style="height: 300px; resize: vertical;">';
    foreach ( $top_10_lines as $line ) {
        echo esc_html( $line );
    }
    echo '</textarea>';
}