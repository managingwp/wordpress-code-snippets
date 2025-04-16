<?php
/** 
 * Plugin Name: Disable WP Rocket Cache when Litespeed Cache is enabled. 
 * Description: This plugin disables WP Rocket cache when Litespeed Cache is enabled. It checks if Litespeed Cache is active and if so, it disables WP Rocket cache.
 * Version: 0.1.0
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
    // Cache WP Rocket status for potential first-run cleaning
    static $wp_rocket_was_active = null;
    
    if ( $wp_rocket_was_active === null && function_exists( 'rocket_clean_domain' ) ) {
        $wp_rocket_was_active = true;
    }
    
    // Only disable WP Rocket cache if LiteSpeed Cache is active
    if ( class_exists( 'LiteSpeed_Cache' ) ) {
        // Disable page caching in WP Rocket
        add_filter( 'do_rocket_generate_caching_files', '__return_false' );
        
        // Uncomment these if you want to disable other caching features
        //add_filter( 'rocket_cache_enabled', '__return_false' );
        //add_filter( 'rocket_cache_mobile', '__return_false' );
        //add_filter( 'rocket_cache_ssl', '__return_false' );
        //add_filter( 'rocket_cache_reject_uri', '__return_empty_array' );
        
        // Clean WP Rocket cache once if it was active
        if ( $wp_rocket_was_active && function_exists( 'rocket_clean_domain' ) ) {
            add_action( 'shutdown', function() {
                rocket_clean_domain();
                // Use transient to prevent repeated cleaning
                set_transient( 'wp_rocket_litespeed_cache_cleaned', true, WEEK_IN_SECONDS );
            }, 999 );
        }
    }
}, 11 ); // Higher priority to make sure both plugins are loaded