<?php 
// Credit - https://stackoverflow.com/questions/48629362/wordpress-woocommerce-shop-order-search-in-admin-bar
// Add shop order search in the admin bar
// Fixes by Ovidiu Maghetiu from https://wpcodebox.com/

add_action('wp_before_admin_bar_render', function() {
    // Get current screen
    $screen = get_current_screen();

    // Check if current user is an administrator
    if( !current_user_can('administrator') ) {
        return;
    }
    
    // Don't show on order page
    if ( $screen->base == "edit" ) {
        return;
    } else {
    
        global $wp_admin_bar;

        $search_query = '';
        if (!empty($_GET['post_type']) && $_GET['post_type'] == 'shop_order' ) {
            $search_query = !empty($_GET['s']) ? $_GET['s'] : '';
                if($search_query) {
                    $order = get_post(intval($search_query));
                        if($order) {
                            wp_redirect(get_edit_post_link($order->ID, ''));
                            exit;
                        }
                }
        }


        $wp_admin_bar->add_menu(array(
        'id' => 'admin_bar_shop_order_form',
        'title' => '<form method="get" action="'.get_site_url().'/wp-admin/edit.php?post_type=shop_order">
        <input name="s" type="text" placeholder="Order or Sub ID" value="' . $search_query . '" style="width:100px;height: 10px;padding-left: 5px;">
        <button class="button button-primary" style="padding: 0px 10px 0px 10px!important; height: 5px!important;">Check</button>
        <input name="post_type" value="shop_order" type="hidden">
        </form>'
        ));
    }
},100
);
