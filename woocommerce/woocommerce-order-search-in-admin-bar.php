<?php 
/**
 * Plugin Name: woocommerce-order-search-in-admin-bar.php
 * Description: Add shop order search in the admin bar
 * Version: 1.0.0
 * Type: Snippet
 * Status: Complete
 *
 * Credit - https://stackoverflow.com/questions/48629362/wordpress-woocommerce-shop-order-search-in-admin-bar
 * Fixes by Ovidiu Maghetiu from https://wpcodebox.com/
 * Copied from https://gist.github.com/ovidiumght/9a47917169b003dba50114c6b6fd30ed?fbclid=IwAR0iw_fbJAvBeKSxPKlAMUnm8M1N7cZzOTMSKj0LCElnnyFFk5-XVfzIscs
 */

add_action('wp_before_admin_bar_render', function() {
    // Get current screen
    $screen = get_current_screen();

    // Check if current user is an administrator
    if( !current_user_can('administrator') ) {
        return;
    }
    

    
    global $wp_admin_bar;

    $search_query = '';
    $search_query = !empty($_POST['order_search']) ? $_POST['order_search'] : '';
    $search_query = intval($search_query);
    
        if($search_query) {
            wp_redirect('edit.php?post_type=shop_order&s=' . $search_query);
        }



    $wp_admin_bar->add_menu(array(
    'id' => 'admin_bar_shop_order_form',
    'title' => '<form method="post" action="">
    <input name="order_search" type="text" placeholder="Order or Sub ID" style="width:100px;height: 10px;padding-left: 5px;">
    <button class="button button-primary" style="padding: 0px 10px 0px 10px!important; height: 5px!important;">Check</button>
    <input name="post_type" value="shop_order" type="hidden">
    </form>'
    ));
    
},100
);
