<?php
/**
 * mu-dashboard-shortcode.php
 * Description: Unsure what the usage of this snippet is.
 * Type: snippet
 * Status: WIP
 */

add_filter( 'wp_nav_menu', 'do_shortcode' );

add_shortcode( 'menu_shortcode', 'dcwd_menu_shortcode_shortcode' );
function dcwd_menu_shortcode_shortcode( $atts, $content = "" ) {
    $dashboard_link = str_replace("https://", "", get_dashboard_url(get_current_user_id()));
    return $dashboard_link;
}
