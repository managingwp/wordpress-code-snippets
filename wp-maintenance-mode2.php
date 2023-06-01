<?php
/**
 * Plugin Name: Maintenance Mode for Non-Admins
 * Description: This plugin displays a maintenance message for non-administrative users.
 * Author: Your Name
 * Version: 1.0
 */

function maintenance_mode_for_non_admins() {
    if (current_user_can('activate_plugins')) {
        return;
    }

    $maintenance_message = '<h1>Maintenance Mode</h1><p>Sorry, our site is currently undergoing maintenance. Please check back soon.</p>';

    wp_die($maintenance_message, 'Maintenance Mode', ['response' => 503]);
}

add_action('wp', 'maintenance_mode_for_non_admins');
