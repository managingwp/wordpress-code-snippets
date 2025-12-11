<?php
/**
 * Plugin Name: Failover Status Monitor
 * Description: Monitors failover status and provides notifications.
 * Version: 1.0.1
 * Status: Complete
 * Type: mu-plugin
*/

// Prevent direct access
defined('ABSPATH') or die('No script kiddies please!');


/**
 * 
 * Get Nginx Status
 * arg $url - URL to Nginx status page
 * 
 * return void
 * 
 */
function wpfailover_nginx_status($url) {
    // Curl stub at https://localhost/nginx_status check if returns 200    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_errno($ch);
    curl_close($ch);

    // Check if URL is returning 200
    if ($http_code === 200) {
        echo $response;
    } else {
        if ($curl_error === CURLE_RECV_ERROR) {
            echo 'Connection reset by peer. URL may be incorrect or NGINX status is not enabled.';
        } else {
            echo 'NGINX status is not available.';
        }
    }
}

/**
 * 
 * Sanitize and validate filters
 * arg $filters - Filters to sanitize and validate
 * 
 * return array
 */

// Sanitize and validate filters
function wpfailover_sanitize_and_validate_filters($filters) {
    // Sanitize the input
    $filters = filter_var($filters, FILTER_SANITIZE_STRING);

    // Split the input into an array
    $filters_array = explode(',', $filters);

    // Validate each item in the array
    $valid_filters = array();
    foreach ($filters_array as $filter_item) {
        $filter_item = trim($filter_item); // Remove any extra whitespace
        if (!empty($filter_item) && preg_match('/^[a-zA-Z0-9_\-]+$/', $filter_item)) {
            $valid_filters[] = $filter_item;
        }
    }
    return $valid_filters;
}

/** 
 * 
 * Get Nginx Access Logs
 * arg $log - Path to Nginx access log
 * arg $filters - Filters to apply
 * 
 * return void
 */
function wpfailover_nginx_accesslogs ($log, $filters) {
    $timeWindows = [5, 15, 30]; // Time windows in seconds

    if (!file_exists($log)) {
        echo "Log file not found.";
        return;
    } elseif (!is_readable($log)) {
        if (!is_readable($log)) {
            echo "Log file is not readable.";
            return;
        }     
    }

    if ($filters == null) {
        $filters = [];
    }

    // Ensure $filters is an array
    if (is_string($filters)) {
        $filters = wpfailover_sanitize_and_validate_filters($filters);
    } elseif ($filters == null) {
        $filters = [];
    }

    // Add current ip to filters
    if (isset($_SERVER['SERVER_ADDR'])) {
        $current_ip = $_SERVER['SERVER_ADDR'];    
        $filters[] = $current_ip;
    }

    // Add client IP to filters
    if (isset($_SERVER['REMOTE_ADDR'])) {
        $client_ip = $_SERVER['REMOTE_ADDR'];    
        $filters[] = $client_ip;
    }

    // Add Cloudflare filters
    if (get_option('wpfailover_filters_add_cloudflare')) {
        $filters[] = 'Cloudflare-Healthchecks';
        $filters[] = 'Cloudflare-Traffic-Manager';
    }

    $output = 'Applied filters: ' . implode(', ', $filters) . "\n\n";
    foreach ($timeWindows as $timeWindow) {
        $currentTime = time();
        $startTime = $currentTime - $timeWindow;
        $requestCount = 0;

        $logFile = fopen($log, 'r');
        if ($logFile) {
            while (($line = fgets($logFile)) !== false) {
                // Assuming a standard Nginx log format, extract the timestamp
                // You might need to adjust this based on your actual log format
                if (preg_match('/^\[(.*?)\]/', $line, $matches)) {
                    $logTimestamp = strtotime($matches[1]);

                    // Apply filter BEFORE counting the request
                    $is_filtered = false;
                    foreach ($filters as $filter_item) {
                        if (strpos($line, $filter_item) !== false) {
                            $is_filtered = true;
                            break; // No need to check further if already filtered
                        }
                    }

                    if (!$is_filtered && $logTimestamp >= $startTime) {
                        $requestCount++;
                    }
                }
            }
            fclose($logFile);
        }

        $output .= "Requests in the last $timeWindow seconds: $requestCount\n";
    }    
    echo $output;
}

/**
 * 
 * Display NGINX status
 * arg $url - URL to Nginx status page
 * 
 * return string
 */

function wpfailover_display_nginx_status($url) {
    ob_start();
    wpfailover_nginx_status($url);
    return ob_get_clean();
}

/**
 * 
 * Generate Status menus
 * args none
 * 
 * return void
 */
function wpfailover_status_menu() {
    add_menu_page(
        'Failover Status',
        'Failover Status',
        'manage_options',
        'wpfailover-status',
        'wpfailover_status_page',
        'dashicons-networking', 
        3 
    );
}
add_action('admin_menu', 'wpfailover_status_menu');

/**
 * 
 * Enqueue wp-color-picker
 * args none
 * 
 * return void
 * 
 */
function wpfailover_enqueue_scripts() {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
}
add_action('admin_enqueue_scripts', 'wpfailover_enqueue_scripts');

/**
 * 
 * Migrate settings
 * args none
 * 
 * return void
 */

 function wpfailover_migrate_settings (){
    $old_settings = [
        'primary_failover_ip',
        'secondary_failover_ip',
        'enable_admin_bar',
        'enable_banner',
        'admin_bar_color',
        'primary_server_name',
        'secondary_server_name',
        'nginx_status_url',
        'nginx_access_log'
    ];

    //Cycle through and update
    foreach ($old_settings as $setting) {
        if (get_option($setting)) {
            update_option('wpfailover_' . $setting, get_option($setting));
            delete_option($setting);
        }
    }
}
add_action('admin_init', 'wpfailover_migrate_settings');

/**
 * 
 * Register settings
 * args none
 * 
 * return void
 */
function wpfailover_register_settings() {
    // Rename settings to have wpf_

    register_setting('wpfailover_failover_settings', 'wpfailover_primary_failover_ip');
    register_setting('wpfailover_failover_settings', 'wpfailover_secondary_failover_ip');
    register_setting('wpfailover_failover_settings', 'wpfailover_enable_admin_bar');
    register_setting('wpfailover_failover_settings', 'wpfailover_enable_banner');
    register_setting('wpfailover_failover_settings', 'wpfailover_admin_bar_color');
    register_setting('wpfailover_failover_settings', 'wpfailover_primary_server_name');
    register_setting('wpfailover_failover_settings', 'wpfailover_secondary_server_name');
    register_setting('wpfailover_failover_settings', 'wpfailover_nginx_status_url');
    register_setting('wpfailover_failover_settings', 'wpfailover_nginx_access_log');
    register_setting('wpfailover_failover_settings', 'wpfailover_nginx_access_log_filters');
    register_setting('wpfailover_failover_settings', 'wpfailover_filters_add_cloudflare');
    register_setting('wpfailover_failover_settings', 'wpfailover_cf_loadbalancing_logs_url');
}
add_action('admin_init', 'wpfailover_register_settings');


/**
 * 
 * Add settings sections
 * args none
 * 
 * return void
 */
function wpfailover_add_settings_sections() {
    add_settings_section(
        'wpfailover_settings_section',
        'WP Failover Settings',
        'wpfailover_settings_section_callback',
        'wpfailover-status'
    );
}
add_action('admin_init', 'wpfailover_add_settings_sections');

function wpfailover_settings_section_callback() {
    echo '<p>Configure failover settings here.</p>';
}

/**
 * 
 * Failover notification logic
 * args none
 * 
 * return void
 */
function wpfailover_notification() {
    $current_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
    $primary_failover_ip = get_option('wpfailover_primary_failover_ip');
    $admin_bar_color = get_option('admin_bar_color', '#9a6400'); // Default to #9a6400 if not set

    if ($current_ip !== $primary_failover_ip) {
        if (get_option('wpfailover_enable_admin_bar')) {
            // Admin bar color.
            function wpfailover_admin_bar_color() {
                global $admin_bar_color;
                echo '<style type="text/css">
                #wpadminbar { background-color: ' . $admin_bar_color . ' !important; }
                </style>';
            }
            add_action('admin_head', 'wpfailover_admin_bar_color');

            // Add "Failover ACTIVE" to admin bar
            function wpfailover_admin_bar_node() {
                global $wp_admin_bar;
            
                // Get the URL of the failover status page
                $failover_page_url = admin_url('admin.php?page=wpfailover-status');
            
                $wp_admin_bar->add_node(array(
                    'id'    => 'failover-active',
                    'title' => 'Failover ACTIVE',
                    'href'  => $failover_page_url, 
                    'meta'  => array(
                        'class' => 'wpfailover-active-node', 
                    ),
                ));
            }
            add_action('admin_bar_menu', 'wpfailover_admin_bar_node', 999);

            // Style the "Failover ACTIVE" node
            function wpfailover_style_admin_bar_node() {
                echo '<style type="text/css">
                #wpadminbar #wp-admin-bar-failover-active > .ab-item {
                    color: white !important; 
                    background-color: red; 
                    font-weight: bold;
                }
                </style>';
            }
            add_action('admin_head', 'wpfailover_style_admin_bar_node');
        }

        if (get_option('wpfailover_enable_banner')) {
            // Display "Failover ACTIVE" as an admin notice
            function wpfailover_display_admin_notice() {
                $current_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';
                // Get the URL of the failover status page
                $failover_page_url = admin_url('admin.php?page=wpfailover-status');
                $failover_server_name = ($current_ip === get_option('secondary_failover_ip')) 
                ? get_option('wpfailover_secondary_server_name', 'Secondary')  // Use saved name or default
                : get_option('wpfailover_primary_server_name', 'Primary'); // Use saved name or default
                
                echo '<div class="notice notice-error is-dismissible">
                    <p><strong>Failover ACTIVE on ' . $current_ip . '/' . $failover_server_name . ' - Don\'t make changes as they will be lost</strong>  - <a href="' . $failover_page_url . '">View Failover Status</a></p>
                </div>';
            }
            add_action('admin_notices', 'wpfailover_display_admin_notice');
        }
    }
}
add_action('init', 'wpfailover_notification');


/** 
 * Failover Status page
 * args none
 * 
 * return void
 */
function wpfailover_status_page() {
    // Reset opcache.
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    $current_ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '';    
    $wpfailover_primary_failover_ip = get_option('wpfailover_primary_failover_ip');
    $is_failover_active = ($current_ip !== $wpfailover_primary_failover_ip);
    ?>
    <div class="wrap">
        <h1>Failover Status</h1>
        <hr>
        <h2>Introduction</h2>
        <p>This is a plugin for monitoring failovers.</p>

        <h2>Current Server</h2>
        <p>Hostname/IP: <?php echo gethostname() . '/' . $current_ip; ?></p>

        <hr>
        <h2>Status</h2>
        <p>
            <?php 
            if ($is_failover_active) {
                echo '<span style="color: red; font-weight: bold;">Failover ACTIVE </span> <span class="dashicons dashicons-no-alt"></span>';
            } else {
                echo '<span style="color: green; font-weight: bold;">Normal Operation </span> <span class="dashicons dashicons-yes"></span>';
            }
            ?>
        </p>

        <hr>
        <h2>Settings</h2>
        <details>
        <summary><b>Configure failover settings here.</b></summary>
        
        <form method="post" action="options.php">
            <?php 
                settings_fields('wpfailover_failover_settings');
                do_settings_sections('wpfailover-status'); 
            ?>
            <table class="form-table">
            <tr valign="top">
                <th scope="row">Primary Failover IP</th>
                <td>
                    <input type="text" name="wpfailover_primary_failover_ip" value="<?php echo esc_attr(get_option('wpfailover_primary_failover_ip')); ?>" />
                    <input type="text" name="wpfailover_primary_server_name" placeholder="Primary Server Name" value="<?php echo esc_attr(get_option('wpfailover_primary_server_name')); ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Secondary Failover IP</th>
                <td>
                    <input type="text" name="wpfailover_secondary_failover_ip" value="<?php echo esc_attr(get_option('wpfailover_secondary_failover_ip')); ?>" />
                    <input type="text" name="wpfailover_secondary_server_name" placeholder="Secondary Server Name" value="<?php echo esc_attr(get_option('wpfailover_secondary_server_name')); ?>" />
                </td>
            </tr>
                <tr valign="top">
                    <th scope="row">Failover Notification - Admin Bar</th>
                    <td><input type="checkbox" name="wpfailover_enable_admin_bar" value="1" <?php checked(get_option('wpfailover_enable_admin_bar'), 1); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Failover Notification - Banner</th>
                    <td><input type="checkbox" name="wpfailover_enable_banner" value="1" <?php checked(get_option('wpfailover_enable_banner'), 1); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Admin Bar Color (Failover)</th>
                    <td><input id="color-picker" type="text" name="wpfailover_admin_bar_color" class="color-picker" value="<?php echo esc_attr(get_option('wpfailover_admin_bar_color', '#9a6400')); ?>" /></td> 
                </tr>
                <tr valign="top">
                    <th scope="row">Coudflare Load Balancer Logs URL</th>
                    <td><input type="text" name="wpfailover_cf_loadbalancing_logs_url" size="60" value="<?php echo esc_attr(get_option('wpfailover_cf_loadbalancing_logs_url', '')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">NGINX Status URL</th>
                    <td><input type="text" name="wpfailover_nginx_status_url" size="60" value="<?php echo esc_attr(get_option('wpfailover_nginx_status_url', 'http://localhost/stub_status')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nginx Access Log Path</th>
                    <td><input type="text" name="wpfailover_nginx_access_log" size="60" value="<?php echo esc_attr(get_option('wpfailover_nginx_access_log', '/var/log/nginx/access.log')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nginx Access Log Filters</th>
                    <td><input type="text" name="wpfailover_nginx_access_log_filters" size="60" value="<?php echo esc_attr(get_option('wpfailover_nginx_access_log_filters', 'wp-login.php,xmlrpc.php,BetterUptime,Cloudflare-Healthchecks,Cloudflare-Traffic-Manager')); ?>" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Add Cloudflare-Traffic-Manager and Cloudflare-Healthchecks to Nginx Access Log Filters</th>
                    <td><input type="checkbox" name="wpfailover_filters_add_cloudflare" value="1" <?php checked(get_option('wpfailover_filters_add_cloudflare'), 1); ?> /></td>
            </table>
            <?php submit_button(); ?>
            <button type="button" onclick="location.reload();">Refresh</button>
        </form>
        </details>
        <hr>
        <h2>Status</h2>
        <h3>Cloudflare Failover</h3>
        <div>
            <a href="<?php echo get_option('wpfailover_cf_loadbalancing_logs_url'); ?>" target="_blank">Cloudflare Load Balancer Logs</a>
        </div>
        <div class="failover-wrapper" style="
                border: 1px solid black;
                border-radius: 15px;
                padding: 10px;
                margin-top:20px;
                background-color: lightgrey;
                ">        
            <h3>NGINX Status</h3>
            <div id="wpfailover-nginx-status">    
                <pre><?php wpfailover_nginx_status(get_option('wpfailover_nginx_status_url')); ?></pre>
            </div>
            <h3>Nginx Access Logs 5/15/30</h3>
            <div id="wpfailover-nginx-log">
                
                <p>Filters: <?php echo get_option('wpfailover_nginx_access_log_filters', 'wp-login.php,xmlrpc.php,BetterUptime'); ?> and by default current IP and client IP</p>
                <pre><?php wpfailover_nginx_accesslogs(get_option('wpfailover_nginx_access_log'),get_option('wpfailover_nginx_access_log_filters', 'wp-login.php,xmlrpc.php,BetterUptime,Cloudflare-Healthchecks,Cloudflare-Traffic-Manager')); ?></pre>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('.color-picker').wpColorPicker();
    });
    </script>

    <?php
}

