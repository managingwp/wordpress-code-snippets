<?php
/**
 * Plugin Name: Change Admin Email
 * Description: Changes the admin email address if it hasn't been changed already without triggering email validation.
 * Version: 1.0.0
 * Type: mu-plugin
 * Status: Complete
*/

// Ensure this is being run within the context of WordPress.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function change_admin_email() {
    // Define the new admin email.
    $new_admin_email = 'newadmin@example.com';

    // Get the current admin email.
    $current_admin_email = get_option( 'admin_email' );

    // Check if the current admin email is already set to the new email.
    if ( $current_admin_email !== $new_admin_email ) {
        // Update the admin email without triggering email validation.
        remove_action( 'update_option_new_admin_email', 'update_option_new_admin_email' );
        update_option( 'admin_email', $new_admin_email );
    }
}

// Hook the function to WordPress initialization action.
add_action( 'init', 'change_admin_email' );

