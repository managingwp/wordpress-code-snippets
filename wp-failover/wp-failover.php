<?php
/*
Plugin Name: Failover Status Monitor
Description: Monitors failover status and provides notifications.
*/

// Prevent direct access
defined('ABSPATH') or die('No script kiddies please!');

// Add menu item under Dashboard
function add_failover_status_menu() {
    add_menu_page(
        'Failover Status',
        'Failover Status',
        'manage_options',
        'failover-status',
        'failover_status_page',
        'dashicons-networking', 
        3 
    );
}
add_action('admin_menu', 'add_failover_status_menu');

// Enqueue the necessary scripts and styles for the color picker
function enqueue_color_picker_scripts() {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'enqueue_color_picker_scripts');

// Failover Status page content
function failover_status_page() {
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    $current_ip = $_SERVER['SERVER_ADDR'];    
    $primary_failover_ip = get_option('primary_failover_ip');
    $is_failover_active = ($current_ip !== $primary_failover_ip);
    ?>
    <div class="wrap">
        <h2>Failover Status</h2>

        <h3>Introduction</h3>
        <p>This is a plugin for monitoring failovers.</p>

        <h3>Current Server</h3>
        <p>Hostname/IP: <?php echo gethostname() . '/' . $current_ip; ?></p>

        <h3>Status</h3>
        <p>
            <?php 
            if ($is_failover_active) {
                echo '<span style="color: red; font-weight: bold;">Failover ACTIVE </span> <span class="dashicons dashicons-no-alt"></span>';
            } else {
                echo '<span style="color: green; font-weight: bold;">Normal Operation </span> <span class="dashicons dashicons-yes"></span>';
            }
            ?>
        </p>

        <h3>Settings</h3>
        <form method="post" action="options.php">
            <?php 
                settings_fields('failover_settings');
                do_settings_sections('failover-status'); 
            ?>
            <table class="form-table">
            <tr valign="top">
                <th scope="row">Primary Failover IP</th>
                <td>
                    <input type="text" name="primary_failover_ip" value="<?php echo esc_attr(get_option('primary_failover_ip')); ?>" />
                    <input type="text" name="primary_server_name" placeholder="Primary Server Name" value="<?php echo esc_attr(get_option('primary_server_name')); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Secondary Failover IP</th>
                <td>
                    <input type="text" name="secondary_failover_ip" value="<?php echo esc_attr(get_option('secondary_failover_ip')); ?>" />
                    <input type="text" name="secondary_server_name" placeholder="Secondary Server Name" value="<?php echo esc_attr(get_option('secondary_server_name')); ?>" />
                </td>
            </tr>
                <tr valign="top">
                    <th scope="row">Failover Notification - Admin Bar</th>
                    <td><input type="checkbox" name="enable_failover_admin_bar" value="1" <?php checked(get_option('enable_failover_admin_bar'), 1); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Failover Notification - Banner</th>
                    <td><input type="checkbox" name="enable_failover_banner" value="1" <?php checked(get_option('enable_failover_banner'), 1); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Admin Bar Color (Failover)</th>
                    <td><input id="color-picker" type="text" name="admin_bar_color" class="color-picker" value="<?php echo esc_attr(get_option('admin_bar_color', '#9a6400')); ?>" /></td> 
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.color-picker').wpColorPicker();
    });
    </script>

    <?php
}

// Register settings
function register_failover_settings() {
    register_setting('failover_settings', 'primary_failover_ip');
    register_setting('failover_settings', 'secondary_failover_ip');
    register_setting('failover_settings', 'enable_failover_admin_bar');
    register_setting('failover_settings', 'enable_failover_banner');
    register_setting('failover_settings', 'admin_bar_color');
    register_setting('failover_settings', 'primary_server_name');
    register_setting('failover_settings', 'secondary_server_name');
}
add_action('admin_init', 'register_failover_settings');

// Add settings sections
function add_failover_settings_sections() {
    add_settings_section(
        'failover_settings_section',
        'Failover Settings',
        'failover_settings_section_callback',
        'failover-status'
    );
}
add_action('admin_init', 'add_failover_settings_sections');

function failover_settings_section_callback() {
    echo '<p>Configure failover settings here.</p>';
}

// Failover notification logic
function failover_notification() {
    $current_ip = $_SERVER['SERVER_ADDR'];
    $primary_failover_ip = get_option('primary_failover_ip');
    $admin_bar_color = get_option('admin_bar_color', '#9a6400'); // Default to #9a6400 if not set

    if ($current_ip !== $primary_failover_ip) {
        if (get_option('enable_failover_admin_bar')) {
            // Admin bar color.
            function admin_bar_color() {
                global $admin_bar_color;
                echo '<style type="text/css">
                #wpadminbar { background-color: ' . $admin_bar_color . ' !important; }
                </style>';
            }
            add_action('admin_head', 'admin_bar_color');

            // Add "Failover ACTIVE" to admin bar
            function add_failover_admin_bar_node() {
                global $wp_admin_bar;
            
                // Get the URL of the failover status page
                $failover_page_url = admin_url('admin.php?page=failover-status');
            
                $wp_admin_bar->add_node(array(
                    'id'    => 'failover-active',
                    'title' => 'Failover ACTIVE',
                    'href'  => $failover_page_url, 
                    'meta'  => array(
                        'class' => 'failover-active-node', 
                    ),
                ));
            }
            add_action('admin_bar_menu', 'add_failover_admin_bar_node', 999);

            // Style the "Failover ACTIVE" node
            function style_failover_admin_bar_node() {
                echo '<style type="text/css">
                #wpadminbar #wp-admin-bar-failover-active > .ab-item {
                    color: white !important; 
                    background-color: red; 
                    font-weight: bold;
                }
                </style>';
            }
            add_action('admin_head', 'style_failover_admin_bar_node');
        }

        if (get_option('enable_failover_banner')) {
            // Display "Failover ACTIVE" as an admin notice
            function display_failover_admin_notice() {
                $current_ip = $_SERVER['SERVER_ADDR'];
                // Get the URL of the failover status page
                $failover_page_url = admin_url('admin.php?page=failover-status');
                $failover_server_name = ($current_ip === get_option('secondary_failover_ip')) 
                ? get_option('secondary_server_name', 'Secondary')  // Use saved name or default
                : get_option('primary_server_name', 'Primary'); // Use saved name or default
                
                echo '<div class="notice notice-error is-dismissible">
                    <p><strong>Failover ACTIVE on ' . $current_ip . '/' . $failover_server_name . '</strong> - <a href="' . $failover_page_url . '">View Failover Status</a></p>
                </div>';
            }
            add_action('admin_notices', 'display_failover_admin_notice');
        }
    }
}
add_action('init', 'failover_notification');