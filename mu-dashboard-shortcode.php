<?php

add_filter( 'wp_nav_menu', 'do_shortcode' );

add_shortcode( 'MENU_SHORTCODE', 'dcwd_menu_shortcode_shortcode' );
function dcwd_menu_shortcode_shortcode( $atts, $content = "" ) {
    $dashboard_link = str_replace("https://", "", get_dashboard_url(get_current_user_id()));
    return $dashboard_link;
}
