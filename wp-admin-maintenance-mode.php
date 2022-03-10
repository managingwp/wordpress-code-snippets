<?php

# Originally from https://wordpress.stackexchange.com/questions/398037/maintenance-mode-excluding-site-administrators

function wp_maintenance_mode() {
if (!current_user_can('administrator')) {
   $url = "your maintenance file url you want to display while is on maintenance";
   wp_redirect( $url );
   exit;
}
add_action('get_header', 'wp_maintenance_mode');
