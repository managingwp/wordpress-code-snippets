<?php
/**
 * Plugin Name: PHP Memory Info
 * Description: Adds a Tools -> PHP Memory page to display PHP ini and WordPress memory settings and current usage.
 * Version: 1.0
 * Author: ManagingWP
 * Author URI: https://github.com/managingwp/wordpress-code-snippets
 * Type: mu-plugin
 * Status: Complete
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook into admin menu to add the Tools > PHP Memory page
add_action( 'admin_menu', 'pm_add_php_memory_page' );

/**
 * Register the PHP Memory page under Tools
 */
function pm_add_php_memory_page() {
    add_management_page(
        'PHP Memory',            // Page title
        'PHP Memory',            // Menu title
        'manage_options',        // Capability
        'php-memory-info',       // Menu slug
        'pm_render_php_memory_page' // Callback to render the page
    );
}

/**
 * Render the PHP Memory Info page
 */
function pm_render_php_memory_page() {
    // Check permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // PHP ini settings
    $ini_settings = array(
        'file_uploads'        => ini_get( 'file_uploads' ),
        'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
        'post_max_size'       => ini_get( 'post_max_size' ),
        'memory_limit'        => ini_get( 'memory_limit' ),
        'max_execution_time'  => ini_get( 'max_execution_time' ),
        'max_input_time'      => ini_get( 'max_input_time' ),
    );

    // WordPress memory settings
    $wp_memory_limit     = defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'Not defined';
    $wp_max_memory_limit = defined( 'WP_MAX_MEMORY_LIMIT' ) ? WP_MAX_MEMORY_LIMIT : 'Not defined';

    // Current memory usage in MB
    $current_memory_usage = round( memory_get_usage() / 1024 / 1024, 2 );

    // Output
    echo '<div class="wrap">';
    echo '<h1>PHP Memory Info</h1>';

    echo '<h2>PHP ini settings for file uploads</h2>';
    echo '<table class="widefat fixed"><thead><tr><th>Setting</th><th>Value</th></tr></thead><tbody>';
    foreach ( $ini_settings as $key => $value ) {
        echo '<tr><td>' . esc_html( $key ) . '</td><td>' . esc_html( $value ) . '</td></tr>';
    }
    echo '</tbody></table>';

    echo '<h2>WordPress Memory Settings</h2>';
    echo '<table class="widefat fixed"><thead><tr><th>Setting</th><th>Value</th></tr></thead><tbody>';
    echo '<tr><td>WP_MEMORY_LIMIT</td><td>' . esc_html( $wp_memory_limit ) . '</td></tr>';
    echo '<tr><td>WP_MAX_MEMORY_LIMIT</td><td>' . esc_html( $wp_max_memory_limit ) . '</td></tr>';
    echo '</tbody></table>';

    echo '<h2>What these WordPress settings do</h2>';
    echo "<p><strong>WP_MEMORY_LIMIT:</strong> The max amount of PHP memory WordPress will try to use for front-end operations (themes, plugins, media processing). It's a soft target—it cannot exceed PHP's own memory_limit in php.ini.</p>";
    echo "<p><strong>WP_MAX_MEMORY_LIMIT:</strong> A higher memory ceiling for admin tasks: dashboard, cron jobs, updates, and other background processes. Again, bounded by PHP's own memory_limit in php.ini.</p>";

    echo '<h2>Current memory usage</h2>';
    echo '<p>' . esc_html( $current_memory_usage ) . ' MB</p>';

    echo '</div>';
}
