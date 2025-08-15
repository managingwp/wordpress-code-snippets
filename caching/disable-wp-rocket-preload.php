<?php
/**
 * Plugin Name: Disable WP Rocket Cache Preload
 * Description: This plugin disables WP Rocket cache preload functionality on a WordPress site.
 * Version: 0.1.0
 * Author: Jordan
 * Author URI: https://github.com/managingwp/wordpress-code-snippets
 * Type: mu-plugin
 * Status: Complete
 *
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * 1) Turn off the “Preload cache” option (sitemap-based preloading).
 *    This overrides whatever the UI setting says on each site.
 */
add_filter( 'pre_get_rocket_option_manual_preload', '__return_zero', PHP_INT_MAX );

/**
 * 2) (Defensive) Make the sitemap list empty so the runner has nothing to do.
 *    Not strictly required when #1 is in place, but harmless and belt-and-suspenders.
 */
add_filter( 'pre_get_rocket_option_sitemaps', function () { return array(); }, PHP_INT_MAX );

/**
 * 3) Keep the legacy bot off as well (older WP Rocket versions).
 */
add_filter( 'do_run_rocket_bot', '__return_false', PHP_INT_MAX );

/**
 * 4) (Unrelated to cache preloading, but commonly confused)
 *    Disable link-preloading hints so no one mistakes it for cache preloading.
 */
add_filter( 'pre_get_rocket_option_preload_links', '__return_zero', PHP_INT_MAX );
