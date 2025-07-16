<?php
/**
 * Plugin Name: Menu Shortcode
 * Description: Adds a shortcode to output the dashboard link.
 * Version: 1.0.0
 * Type: snippet
 * Status: WIP
 */

add_filter( 'wp_nav_menu', 'do_shortcode' );

add_shortcode( 'menu_shortcode', 'dcwd_menu_shortcode_shortcode' );
function dcwd_menu_shortcode_shortcode( $atts, $content = "" ) {
    $dashboard_link = str_replace("https://", "", get_dashboard_url(get_current_user_id()));
    return $dashboard_link;
}
