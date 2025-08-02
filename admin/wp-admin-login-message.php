<?php
/**
 * Plugin Name: WP Admin Login Message
 * Description: Places a message on the WordPress Admin login page.
 * Version: 1.0.0
 * Type: mu-plugin
 * Status: Complete
*/

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) or die();

/**
 * Add migration message above the login form
 */
add_action( 'login_message', function( $message ) {
    $migration_message = '<div id="login_error" style="background-color: #ffb900; border-left: 4px solid #ffb900; padding: 12px; margin: 5px 0 20px; color: #23282d;">
        <strong>Notice:</strong> Your site has been migrated, if you\'re seeing this message then you\'re accessing the old server.
    </div>';
    
    return $migration_message . $message;
});

/**
 * Add custom CSS to style the message
 */
add_action( 'login_enqueue_scripts', function() {
    echo '<style type="text/css">
        .migration-notice {
            background-color: #ffb900;
            border-left: 4px solid #ff8f00;
            padding: 12px;
            margin: 5px 0 20px;
            color: #23282d;
            font-weight: 500;
        }
    </style>';
});