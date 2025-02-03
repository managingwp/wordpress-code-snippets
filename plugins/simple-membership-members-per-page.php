<?php
/*
Plugin Name: Simple Membership Members Per Page
Description: Change the number of members displayed per page in the Simple Membership plugin.
Version: 1.0
Author: Managing WP
Author URI: https://managingwp.io
 * Description: This script has two objectives, replace redirection_items and update
 * Type: script
 * Status: Complete
*/

function custom_swpm_members_items_per_page( $perpage ) {
    return 200; // Change 200 to whatever number you prefer.
}
add_filter( 'swpm_members_menu_items_per_page', 'custom_swpm_members_items_per_page' );
