<?php
/**
 * Plugin Name: Show Server IP in Admin
 * Description: Shows the server IP address as a notice in the WordPress admin.
 * Version: 1.0.0
 * Status: Complete
 * Type: snippet
 *
 * Place into a theme functions.php, as an mu-plugin, or as a snippet in WP Codebox.
 * To only show on the dashboard, add: if ( 'index.php' !== $GLOBALS['pagenow'] ) { return; }
 */
add_action( 'admin_notices', function () {
    $server_ip = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( $_SERVER['SERVER_ADDR'] ) : 'unknown';
    echo '<div class="notice notice-info"><p>Server IP: <strong>' . esc_html( $server_ip ) . '</strong></p></div>';
} );
