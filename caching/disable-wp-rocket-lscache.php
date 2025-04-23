<?php
/**
 * Plugin Name: Disable WP Rocket Cache when Litespeed Cache is enabled.
 * Description: This plugin disables WP Rocket cache when Litespeed Cache is enabled. It checks if Litespeed Cache is active and if so, it disables WP Rocket cache.
 * Version: 0.2.0
 * Author: Jordan
 * Author URI: https://github.com/managingwp/wordpress-code-snippets
 * Type: mu-plugin
 * Status: Complete
 *
 *
 *
 */
namespace WP_Rocket\Helpers\Disable_With_LiteSpeed;

// Standard plugin security, keep this line in place.
defined( 'ABSPATH' ) or die();

/**
 * Check if LiteSpeed Cache is active and if so, disable WP Rocket caching
 */
add_action( 'plugins_loaded', function() {
    // Only proceed if LiteSpeed Cache is active
    if ( defined('LSCWP_V') ) {
        // Disable page caching in WP Rocket
        add_filter( 'do_rocket_generate_caching_files', '__return_false' );
        // Disable preload in WP Rocket
        add_filter( 'rocket_preload_cache', '__return_false' );

        // Uncomment these if you want to disable other caching features
        //add_filter( 'rocket_cache_enabled', '__return_false' );
        //add_filter( 'rocket_cache_mobile', '__return_false' );
        //add_filter( 'rocket_cache_ssl', '__return_false' );
        //add_filter( 'rocket_cache_reject_uri', '__return_empty_array' );

        // Clean WP Rocket cache once if it's active and not already cleaned
        if ( function_exists( 'rocket_clean_domain' ) && !get_transient( 'wp_rocket_litespeed_cache_cleaned' ) ) {
            add_action( 'shutdown', function() {
                rocket_clean_domain();
                // Use transient to prevent repeated cleaning
                set_transient( 'wp_rocket_litespeed_cache_cleaned', true, WEEK_IN_SECONDS );
            }, 999 );
        }
    }
}, 11 ); // Higher priority to make sure both plugins are loaded

/**
 * Admin Menu "WP Rocket Cache Disable" under Tools
 * This will show a message if WP Rocket cache is disabled and preload is disabled
 */

add_action( 'admin_menu', function() {
    add_management_page(
        'WP Rocket / LiteSpeed Cache',
        'WP Rocket / LiteSpeed Cache',
        'manage_options',
        'wp-rocket-litespeed-cache',
        function() {
            echo '<div class="wrap">';
            echo '<h1>WP Rocket / LiteSpeed Cache</h1>';
            // Check if LiteSpeed Cache is active
            echo '<h2>LiteSpeed Cache</h2>';
            if ( defined('LSCWP_V') ) {
                echo 'LiteSpeed Cache is active, version: ' . LSCWP_V;
                echo "defind('LSCWP_V') = true";
            } else {
                echo '<p>LiteSpeed Cache is not active.</p>';
                echo "defind('LSCWP_V') = false";
            }
            // Check if WP Rocket cache is disabled
            echo '<h1>WP Rocket</h1>';
            if ( ! apply_filters( 'do_rocket_generate_caching_files', true ) ) {
                echo '<p>WP Rocket cache is disabled.</p>';
                echo "apply_filters( 'do_rocket_generate_caching_files') = false";
            } else {
                echo '<p>WP Rocket cache is enabled.</p>';
                echo "apply_filters( 'do_rocket_generate_caching_files') = true";
            }
            // Check if WP Rocket cache is cleaned
            echo '<h1>WP Rocket Cache Cleaned</h1>';
            if ( get_transient( 'wp_rocket_litespeed_cache_cleaned' ) ) {
                echo '<p>WP Rocket cache has been cleaned.</p>';
                echo "get_transient( 'wp_rocket_litespeed_cache_cleaned') = true";
            } else {
                echo '<p>WP Rocket Cache Not Cleaned</p>';
                echo "get_transient( 'wp_rocket_litespeed_cache_cleaned') = false";
            }
            // Check if WP Rocket cache is preloaded
            echo '<h1>WP Rocket Cache Preload</h1>';
            if ( ! apply_filters( 'rocket_preload_cache', true ) ) {
                echo '<p>WP Rocket cache is not preloaded.</p>';
                echo "function_exists( 'rocket_preload_cache' ) = false";
            } else {
                echo '<p>WP Rocket cache is preloaded.</p>';
                echo "function_exists( 'rocket_preload_cache' ) = true";
            }
            echo '</div>';
        }
   );
}, 10 );
/**
 * Add a notice to the admin dashboard if WP Rocket cache is disabled
 */
add_action( 'admin_notices', function() {
    if ( defined('LSCWP_V') && ! apply_filters( 'do_rocket_generate_caching_files', true ) ) {
        echo '<div class="notice notice-warning is-dismissible">';
        echo '<p>WP Rocket cache is disabled because LiteSpeed Cache is active. <a href="' . esc_url( admin_url( 'tools.php?page=wp-rocket-litespeed-cache' ) ) . '">Click here to view the status.</a></p>';
        echo '</div>';
    }
}, 10 );
