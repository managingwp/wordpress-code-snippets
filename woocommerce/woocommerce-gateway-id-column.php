<?php
/**
 * Plugin Name: WooCommerce Gateway ID Column
 * Description: Adds a Gateway ID column to WooCommerce Orders and Subscriptions admin lists showing the payment gateway
 * Version: 2.1.0
 * Author: Jordan
 * Author URI: https://managingwp.io/
 * Type: mu-plugin
 * Status: Complete
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// WOOCOMMERCE SUBSCRIPTIONS FUNCTIONALITY
// =============================================================================

/**
 * Check if WooCommerce Subscriptions is active and initialize subscription features
 */
function wcg_init_subscriptions() {
    if (!function_exists('wcs_get_subscription')) {
        return;
    }
    
    add_filter('manage_edit-shop_subscription_columns', 'wcs_add_gateway_id_column', 20);
    add_action('manage_shop_subscription_posts_custom_column', 'wcs_populate_gateway_id_column', 10, 2);
    add_action('manage_shop_subscription_posts_custom_column', 'wcs_populate_parent_order_column', 10, 2);
    add_filter('manage_edit-shop_subscription_sortable_columns', 'wcs_make_gateway_id_sortable');
    add_action('pre_get_posts', 'wcs_gateway_id_orderby');
    add_action('show_user_profile', 'wcg_show_user_saved_payment_methods', 5);
    add_action('edit_user_profile', 'wcg_show_user_saved_payment_methods', 5);
    add_action('show_user_profile', 'wcs_show_user_payment_methods');
    add_action('edit_user_profile', 'wcs_show_user_payment_methods');
}
add_action('admin_init', 'wcg_init_subscriptions');

/**
 * Add Gateway ID column to subscriptions admin table
 *
 * @param array $columns Existing columns
 * @return array Modified columns
 */
function wcs_add_gateway_id_column($columns) {
    // Insert Gateway ID and Parent Order columns after the Status column
    $new_columns = array();
    
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        
        // Add columns after status
        if ($key === 'status') {
            $new_columns['parent_order'] = __('Parent Order', 'woocommerce-subscriptions');
            $new_columns['gateway_id'] = __('Gateway ID', 'woocommerce-subscriptions');
        }
    }
    
    return $new_columns;
}

/**
 * Populate Gateway ID column with subscription payment method
 *
 * @param string $column Column name
 * @param int $post_id Subscription post ID
 */
function wcs_populate_gateway_id_column($column, $post_id) {
    if ($column === 'gateway_id') {
        global $wpdb;
        
        // Query the database directly to get the raw _payment_method value
        $payment_method = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_payment_method' LIMIT 1",
            $post_id
        ));
        
        if (!empty($payment_method)) {
            $payment_method_title = '';
            
            // Try to get the gateway title if available
            if (function_exists('wcs_get_subscription')) {
                $subscription = wcs_get_subscription($post_id);
                if ($subscription) {
                    $payment_method_title = $subscription->get_payment_method_title();
                }
            }
            
            // Display the title first (if available), then the raw gateway ID in brackets
            if (!empty($payment_method_title)) {
                echo '<strong>' . esc_html($payment_method_title) . '</strong>';
            }
            echo '<br><span style="color: #666; font-size: 0.85em;">[' . esc_html($payment_method) . ']</span>';
        } else {
            echo '<span style="color: #999;">—</span>';
        }
    }
}
add_action('manage_shop_subscription_posts_custom_column', 'wcs_populate_gateway_id_column', 10, 2);

/**
 * Populate Parent Order column with order status and link
 *
 * @param string $column Column name
 * @param int $post_id Subscription post ID
 */
function wcs_populate_parent_order_column($column, $post_id) {
    if ($column === 'parent_order') {
        if (!function_exists('wcs_get_subscription')) {
            echo '<span style="color: #999;">—</span>';
            return;
        }
        
        $subscription = wcs_get_subscription($post_id);
        if (!$subscription) {
            echo '<span style="color: #999;">—</span>';
            return;
        }
        
        $parent_order_id = $subscription->get_parent_id();
        
        if (!$parent_order_id) {
            echo '<span style="color: #999;">—</span>';
            return;
        }
        
        $parent_order = wc_get_order($parent_order_id);
        if (!$parent_order) {
            echo '<span style="color: #999;">—</span>';
            return;
        }
        
        $status = $parent_order->get_status();
        $status_name = wc_get_order_status_name($status);
        
        // Get status color based on WooCommerce status colors
        $status_colors = array(
            'pending' => '#e5e5e5',
            'processing' => '#c6e1c6',
            'on-hold' => '#f8dda7',
            'completed' => '#c8d7e1',
            'cancelled' => '#e5e5e5',
            'refunded' => '#e5e5e5',
            'failed' => '#eba3a3',
        );
        
        $color = isset($status_colors[$status]) ? $status_colors[$status] : '#e5e5e5';
        $text_color = in_array($status, array('failed', 'processing')) ? '#333' : '#777';
        
        $order_url = admin_url('post.php?post=' . $parent_order_id . '&action=edit');
        
        echo '<a href="' . esc_url($order_url) . '" style="text-decoration: none;">';
        echo '<span style="background: ' . esc_attr($color) . '; color: ' . esc_attr($text_color) . '; padding: 4px 8px; border-radius: 3px; display: inline-block; font-size: 0.9em; white-space: nowrap;">';
        echo esc_html($status_name);
        echo '</span>';
        echo '<br>';
        echo '<span style="color: #2271b1; font-weight: 500;">#' . esc_html($parent_order_id) . '</span>';
        echo '</a>';
    }
}

/**
 * Make Gateway ID column sortable (optional enhancement)
 *
 * @param array $columns Sortable columns
 * @return array Modified sortable columns
 */
function wcs_make_gateway_id_sortable($columns) {
    $columns['gateway_id'] = 'payment_method';
    $columns['parent_order'] = 'parent_order';
    return $columns;
}

/**
 * Handle sorting by gateway ID
 *
 * @param WP_Query $query The query object
 */
function wcs_gateway_id_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ('payment_method' === $query->get('orderby')) {
        $query->set('meta_key', '_payment_method');
        $query->set('orderby', 'meta_value');
    }
    
    if ('parent_order' === $query->get('orderby')) {
        $query->set('meta_key', '_order_parent');
        $query->set('orderby', 'meta_value_num');
    }
}

/**
 * Add payment methods info box to user profile
 *
 * @param WP_User $user User object
 */
function wcs_show_user_payment_methods($user) {
    // Only show for users who can manage WooCommerce or admins viewing profiles
    if (!current_user_can('manage_woocommerce') && get_current_user_id() !== $user->ID) {
        return;
    }
    
    // Check if WooCommerce Subscriptions is active
    if (!function_exists('wcs_get_users_subscriptions')) {
        return;
    }
    
    global $wpdb;
    
    // Get all subscriptions for this user
    $subscriptions = wcs_get_users_subscriptions($user->ID);
    
    if (empty($subscriptions)) {
        return;
    }
    
    // Collect unique payment methods
    $payment_methods = array();
    
    foreach ($subscriptions as $subscription) {
        $subscription_id = $subscription->get_id();
        
        // Query database directly for raw payment method
        $payment_method = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_payment_method' LIMIT 1",
            $subscription_id
        ));
        
        if (!empty($payment_method)) {
            $payment_method_title = $subscription->get_payment_method_title();
            
            if (!isset($payment_methods[$payment_method])) {
                $payment_methods[$payment_method] = array(
                    'gateway_id' => $payment_method,
                    'title' => $payment_method_title,
                    'count' => 0,
                    'subscription_ids' => array()
                );
            }
            
            $payment_methods[$payment_method]['count']++;
            $payment_methods[$payment_method]['subscription_ids'][] = $subscription_id;
        }
    }
    
    if (empty($payment_methods)) {
        return;
    }
    
    ?>
    <h2><?php _e('Subscription Payment Methods', 'woocommerce-subscriptions'); ?></h2>
    <table class="form-table">
        <tr>
            <th><?php _e('Registered Payment Gateways', 'woocommerce-subscriptions'); ?></th>
            <td>
                <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">
                    <?php foreach ($payment_methods as $method_data): ?>
                        <div style="margin-bottom: 12px; padding: 10px; background: #fff; border-left: 4px solid #2271b1;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <?php if (!empty($method_data['title'])): ?>
                                        <strong style="font-size: 14px;"><?php echo esc_html($method_data['title']); ?></strong>
                                        <br>
                                    <?php endif; ?>
                                    <span style="color: #666; font-size: 13px;">
                                        Gateway ID: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($method_data['gateway_id']); ?></code>
                                    </span>
                                </div>
                                <div style="text-align: right;">
                                    <span style="background: #2271b1; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px;">
                                        <?php echo esc_html($method_data['count']); ?> <?php echo _n('subscription', 'subscriptions', $method_data['count'], 'woocommerce-subscriptions'); ?>
                                    </span>
                                </div>
                            </div>
                            <div style="margin-top: 8px; font-size: 12px; color: #666;">
                                <?php 
                                $subscription_links = array();
                                foreach ($method_data['subscription_ids'] as $sub_id) {
                                    $subscription_links[] = '<a href="' . esc_url(admin_url('post.php?post=' . $sub_id . '&action=edit')) . '">#' . $sub_id . '</a>';
                                }
                                echo implode(', ', $subscription_links);
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="description">
                    <?php _e('This shows all payment gateways currently registered for this user\'s subscriptions.', 'woocommerce-subscriptions'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

// =============================================================================
// SAVED PAYMENT METHODS FUNCTIONALITY
// =============================================================================

/**
 * Render payment tokens display
 * Reusable function to display payment tokens consistently
 *
 * @param array $tokens Array of WC_Payment_Token objects
 * @param string $context Context: 'profile' (default) or 'order' for different styling
 */
function wcg_render_payment_tokens($tokens, $context = 'profile') {
    if (empty($tokens)) {
        return;
    }
    
    foreach ($tokens as $token) {
        $token_id = $token->get_id();
        $gateway_id = $token->get_gateway_id();
        $token_value = $token->get_token();
        $token_type = $token->get_type();
        $is_default = $token->is_default();
        $display_name = $token->get_display_name();
        
        // Get gateway title
        $gateway_title = '';
        $gateways = WC()->payment_gateways->payment_gateways();
        if (isset($gateways[$gateway_id])) {
            $gateway_title = $gateways[$gateway_id]->get_title();
        }
        
        // Card-specific details
        $card_details = '';
        if ($token_type === 'CC' && method_exists($token, 'get_last4')) {
            $card_type = $token->get_card_type();
            $last4 = $token->get_last4();
            $expiry_month = $token->get_expiry_month();
            $expiry_year = $token->get_expiry_year();
            
            if ($card_type && $last4) {
                $card_details = sprintf('%s •••• %s', esc_html($card_type), esc_html($last4));
            }
            
            if ($expiry_month && $expiry_year) {
                $card_details .= sprintf(' (Exp: %s/%s)', esc_html($expiry_month), esc_html(substr($expiry_year, -2)));
            }
        }
        
        // Different styling based on context
        if ($context === 'order') {
            // Compact order detail style
            ?>
            <div style="margin-bottom: 8px; padding: 8px; background: #f9f9f9; border-radius: 3px; font-size: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <?php if (!empty($card_details)): ?>
                            <strong><?php echo $card_details; ?></strong>
                        <?php elseif (!empty($display_name)): ?>
                            <strong><?php echo esc_html($display_name); ?></strong>
                        <?php else: ?>
                            <strong><?php _e('Saved token', 'woocommerce'); ?></strong>
                        <?php endif; ?>
                        <br>
                        <span style="color: #666;">
                            Token ID: <?php echo esc_html($token_id); ?>
                        </span>
                    </div>
                    <div style="margin-left: 12px;">
                        <?php if ($is_default): ?>
                            <span style="background: #46b450; color: #fff; padding: 2px 6px; border-radius: 10px; font-size: 10px;">
                                <?php _e('Default', 'woocommerce'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
        } else {
            // Full profile style
            ?>
            <div style="margin-bottom: 12px; padding: 10px; background: #fff; border-left: 4px solid #2271b1;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1;">
                        <?php if (!empty($gateway_title)): ?>
                            <strong style="font-size: 14px;"><?php echo esc_html($gateway_title); ?></strong>
                            <br>
                        <?php endif; ?>
                        
                        <?php if (!empty($card_details)): ?>
                            <span style="color: #333; font-size: 13px; display: block; margin: 4px 0;">
                                <?php echo $card_details; ?>
                            </span>
                        <?php elseif (!empty($display_name)): ?>
                            <span style="color: #333; font-size: 13px; display: block; margin: 4px 0;">
                                <?php echo esc_html($display_name); ?>
                            </span>
                        <?php endif; ?>
                        
                        <span style="color: #666; font-size: 13px; display: block; margin-top: 4px;">
                            Gateway ID: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($gateway_id); ?></code>
                        </span>
                        
                        <span style="color: #999; font-size: 12px; display: block; margin-top: 4px;">
                            Token ID: <?php echo esc_html($token_id); ?>
                            <?php if (!empty($token_value)): ?>
                                | Token: <code style="background: #f9f9f9; padding: 1px 4px; border-radius: 2px; font-size: 11px;"><?php echo esc_html(substr($token_value, 0, 20)) . (strlen($token_value) > 20 ? '...' : ''); ?></code>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div style="text-align: right; margin-left: 12px;">
                        <?php if ($is_default): ?>
                            <span style="background: #46b450; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px; display: inline-block; margin-bottom: 4px;">
                                <?php _e('Default', 'woocommerce'); ?>
                            </span>
                            <br>
                        <?php endif; ?>
                        <span style="background: #f0f0f0; color: #666; padding: 4px 8px; border-radius: 12px; font-size: 11px; display: inline-block;">
                            <?php echo esc_html(ucfirst($token_type)); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php
        }
    }
}

/**
 * Display saved payment methods (tokens) in user profile
 * Shows WooCommerce payment tokens with gateway, token ID, and card details
 *
 * @param WP_User $user User object
 */
function wcg_show_user_saved_payment_methods($user) {
    // Only show for users who can manage WooCommerce or admins viewing profiles
    if (!current_user_can('manage_woocommerce') && get_current_user_id() !== $user->ID) {
        return;
    }
    
    // Check if WooCommerce payment tokens functionality is available
    if (!class_exists('WC_Payment_Tokens')) {
        return;
    }
    
    // Get all saved payment tokens for this user
    $tokens = WC_Payment_Tokens::get_customer_tokens($user->ID);
    
    if (empty($tokens)) {
        return;
    }
    
    ?>
    <h2><?php _e('Saved Payment Methods', 'woocommerce'); ?></h2>
    <table class="form-table">
        <tr>
            <th><?php _e('Payment Tokens', 'woocommerce'); ?></th>
            <td>
                <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">
                    <?php wcg_render_payment_tokens($tokens); ?>
                </div>
                <p class="description">
                    <?php _e('This shows all saved payment methods (tokens) stored for future transactions. These are different from payment gateways used in past orders.', 'woocommerce'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

// =============================================================================
// WOOCOMMERCE ORDERS FUNCTIONALITY
// =============================================================================

/**
 * Check if WooCommerce is active and initialize order features
 */
function wcg_init_orders() {
    if (!function_exists('wc_get_order')) {
        return;
    }
    
    add_filter('manage_edit-shop_order_columns', 'wco_add_gateway_id_column', 20);
    add_action('manage_shop_order_posts_custom_column', 'wco_populate_gateway_id_column', 10, 2);
    add_filter('manage_edit-shop_order_sortable_columns', 'wco_make_gateway_id_sortable');
    add_action('pre_get_posts', 'wco_gateway_id_orderby');
    add_action('show_user_profile', 'wcg_show_user_saved_payment_methods', 5);
    add_action('edit_user_profile', 'wcg_show_user_saved_payment_methods', 5);
    add_action('show_user_profile', 'wco_show_user_payment_methods');
    add_action('edit_user_profile', 'wco_show_user_payment_methods');
    add_action('woocommerce_admin_order_data_after_billing_address', 'wco_display_payment_method_details', 10, 1);
    add_action('woocommerce_process_shop_order_meta', 'wco_save_payment_gateway_edit', 60, 2);
    add_action('admin_notices', 'wco_display_gateway_change_notice');
    add_action('restrict_manage_posts', 'wco_add_gateway_filter_dropdown');
    add_filter('parse_query', 'wco_filter_orders_by_gateway');
}
add_action('admin_init', 'wcg_init_orders');

/**
 * Add Gateway ID column to orders admin table
 *
 * @param array $columns Existing columns
 * @return array Modified columns
 */
function wco_add_gateway_id_column($columns) {
    // Insert Gateway ID column after the Status column
    $new_columns = array();
    
    foreach ($columns as $key => $column) {
        $new_columns[$key] = $column;
        
        // Add Gateway ID column after order_status
        if ($key === 'order_status') {
            $new_columns['gateway_id'] = __('Gateway ID', 'woocommerce');
        }
    }
    
    return $new_columns;
}

/**
 * Populate Gateway ID column with order payment method
 *
 * @param string $column Column name
 * @param int $post_id Order post ID
 */
function wco_populate_gateway_id_column($column, $post_id) {
    if ($column === 'gateway_id') {
        global $wpdb;
        
        // Query the database directly to get the raw _payment_method value
        $payment_method = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_payment_method' LIMIT 1",
            $post_id
        ));
        
        if (!empty($payment_method)) {
            $payment_method_title = '';
            
            // Try to get the gateway title if available
            if (function_exists('wc_get_order')) {
                $order = wc_get_order($post_id);
                if ($order) {
                    $payment_method_title = $order->get_payment_method_title();
                }
            }
            
            // Display the title first (if available), then the raw gateway ID in brackets
            if (!empty($payment_method_title)) {
                echo '<strong>' . esc_html($payment_method_title) . '</strong>';
            }
            echo '<br><span style="color: #666; font-size: 0.85em;">[' . esc_html($payment_method) . ']</span>';
        } else {
            echo '<span style="color: #999;">—</span>';
        }
    }
}

/**
 * Make Gateway ID column sortable
 *
 * @param array $columns Sortable columns
 * @return array Modified sortable columns
 */
function wco_make_gateway_id_sortable($columns) {
    $columns['gateway_id'] = 'payment_method';
    return $columns;
}

/**
 * Handle sorting by gateway ID
 *
 * @param WP_Query $query The query object
 */
function wco_gateway_id_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ('payment_method' === $query->get('orderby')) {
        $query->set('meta_key', '_payment_method');
        $query->set('orderby', 'meta_value');
    }
}

/**
 * Add payment methods info box to user profile
 *
 * @param WP_User $user User object
 */
function wco_show_user_payment_methods($user) {
    // Only show for users who can manage WooCommerce or admins viewing profiles
    if (!current_user_can('manage_woocommerce') && get_current_user_id() !== $user->ID) {
        return;
    }
    
    // Check if WooCommerce is active
    if (!function_exists('wc_get_orders')) {
        return;
    }
    
    global $wpdb;
    
    // Get all orders for this user
    $orders = wc_get_orders(array(
        'customer_id' => $user->ID,
        'limit' => -1,
    ));
    
    if (empty($orders)) {
        return;
    }
    
    // Collect unique payment methods
    $payment_methods = array();
    
    foreach ($orders as $order) {
        $order_id = $order->get_id();
        
        // Query database directly for raw payment method
        $payment_method = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_payment_method' LIMIT 1",
            $order_id
        ));
        
        if (!empty($payment_method)) {
            $payment_method_title = $order->get_payment_method_title();
            
            if (!isset($payment_methods[$payment_method])) {
                $payment_methods[$payment_method] = array(
                    'gateway_id' => $payment_method,
                    'title' => $payment_method_title,
                    'count' => 0,
                    'order_ids' => array()
                );
            }
            
            $payment_methods[$payment_method]['count']++;
            $payment_methods[$payment_method]['order_ids'][] = $order_id;
        }
    }
    
    if (empty($payment_methods)) {
        return;
    }
    
    ?>
    <h2><?php _e('Order Payment Methods', 'woocommerce'); ?></h2>
    <table class="form-table">
        <tr>
            <th><?php _e('Registered Payment Gateways', 'woocommerce'); ?></th>
            <td>
                <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">
                    <?php foreach ($payment_methods as $method_data): ?>
                        <div style="margin-bottom: 12px; padding: 10px; background: #fff; border-left: 4px solid #2271b1;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <?php if (!empty($method_data['title'])): ?>
                                        <strong style="font-size: 14px;"><?php echo esc_html($method_data['title']); ?></strong>
                                        <br>
                                    <?php endif; ?>
                                    <span style="color: #666; font-size: 13px;">
                                        Gateway ID: <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($method_data['gateway_id']); ?></code>
                                    </span>
                                </div>
                                <div style="text-align: right;">
                                    <span style="background: #2271b1; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 12px;">
                                        <?php echo esc_html($method_data['count']); ?> <?php echo _n('order', 'orders', $method_data['count'], 'woocommerce'); ?>
                                    </span>
                                </div>
                            </div>
                            <div style="margin-top: 8px; font-size: 12px; color: #666;">
                                <?php 
                                // Show first 5 order links
                                $order_links = array();
                                $display_orders = array_slice($method_data['order_ids'], 0, 5);
                                foreach ($display_orders as $order_id) {
                                    $order_links[] = '<a href="' . esc_url(admin_url('post.php?post=' . $order_id . '&action=edit')) . '">#' . $order_id . '</a>';
                                }
                                echo implode(', ', $order_links);
                                
                                // Show count if there are more orders
                                if (count($method_data['order_ids']) > 5) {
                                    echo ' <span style="color: #999;">+' . (count($method_data['order_ids']) - 5) . ' more</span>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p class="description">
                    <?php _e('This shows all payment gateways currently registered for this user\'s orders.', 'woocommerce'); ?>
                </p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Display payment method details in Order Details section
 * Appears right after the billing address section
 * Includes inline gateway editor for failed/pending orders without transaction ID
 *
 * @param WC_Order $order Order object
 */
function wco_display_payment_method_details($order) {
    if (!$order) {
        return;
    }
    
    global $wpdb;
    
    // Get raw payment method from database
    $payment_method = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_payment_method' LIMIT 1",
        $order->get_id()
    ));
    
    $payment_method_title = $order->get_payment_method_title();
    $transaction_id = $order->get_transaction_id();
    $order_status = $order->get_status();
    
    // Determine if gateway can be edited
    $allowed_statuses = array('failed', 'pending');
    $can_edit_gateway = in_array($order_status, $allowed_statuses) && empty($transaction_id);
    
    // Get all available payment gateways
    $available_gateways = array();
    if ($can_edit_gateway) {
        $all_gateways = WC()->payment_gateways->payment_gateways();
        foreach ($all_gateways as $gateway) {
            // Only include enabled gateways
            if ($gateway->enabled === 'yes') {
                $available_gateways[$gateway->id] = $gateway->get_title();
            }
        }
    }
    
    // Get customer saved payment methods for this gateway
    $customer_id = $order->get_customer_id();
    $saved_tokens = array();
    if ($customer_id && !empty($payment_method) && class_exists('WC_Payment_Tokens')) {
        $saved_tokens = WC_Payment_Tokens::get_customer_tokens($customer_id, $payment_method);
    }
    
    ?>
    <div class="wco-payment-method-details" style="padding: 12px 12px 0;">
        <h3 style="margin: 0 0 12px 0; font-size: 14px;"><?php _e('Order Payment Details', 'woocommerce'); ?></h3>
        
        <div style="margin-bottom: 12px;">
            <p style="margin: 0 0 8px 0;">
                <strong><?php _e('Payment method:', 'woocommerce'); ?></strong>
            </p>
            
            <?php if ($can_edit_gateway && !empty($available_gateways)): ?>
                <!-- Editable gateway dropdown -->
                <div style="padding-left: 12px; margin-bottom: 8px;">
                    <select name="wco_payment_gateway" id="wco_payment_gateway" style="width: 100%; max-width: 400px;">
                        <option value="">— <?php _e('No Change', 'woocommerce'); ?> —</option>
                        <?php foreach ($available_gateways as $gateway_id => $gateway_title): ?>
                            <option value="<?php echo esc_attr($gateway_id); ?>" <?php selected($payment_method, $gateway_id); ?>>
                                <?php echo esc_html($gateway_title); ?> [<?php echo esc_html($gateway_id); ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php wp_nonce_field('wco_update_gateway_' . $order->get_id(), 'wco_gateway_nonce'); ?>
                    <p class="description" style="margin-top: 4px;">
                        <?php _e('Current gateway:', 'woocommerce'); ?> <strong><?php echo esc_html($payment_method_title); ?></strong> [<code><?php echo esc_html($payment_method); ?></code>]
                    </p>
                </div>
            <?php else: ?>
                <!-- Read-only display -->
                <?php if (!empty($payment_method_title)): ?>
                    <p style="margin: 0 0 4px 0; padding-left: 12px;">
                        <?php echo esc_html($payment_method_title); ?>
                    </p>
                <?php endif; ?>
                
                <!-- Show restriction reason -->
                <?php if (!in_array($order_status, $allowed_statuses) || !empty($transaction_id)): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-left: 4px solid #ff9800; padding: 8px 12px; margin: 8px 0 8px 12px; border-radius: 3px;">
                        <p style="margin: 0; color: #856404; font-size: 12px;">
                            <strong>⚠️ <?php _e('Gateway cannot be changed:', 'woocommerce'); ?></strong>
                            <?php if (!empty($transaction_id)): ?>
                                <?php _e('Order has a transaction ID (payment processed).', 'woocommerce'); ?>
                            <?php elseif (!in_array($order_status, $allowed_statuses)): ?>
                                <?php printf(__('Order status is "%s" (only failed/pending orders can be edited).', 'woocommerce'), wc_get_order_status_name($order_status)); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (!empty($payment_method)): ?>
                <p style="margin: 0 0 8px 0; padding-left: 12px; color: #666; font-size: 12px;">
                    <strong><?php _e('Gateway ID:', 'woocommerce'); ?></strong>
                    <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">
                        <?php echo esc_html($payment_method); ?>
                    </code>
                </p>
            <?php else: ?>
                <p style="margin: 0 0 8px 0; padding-left: 12px; color: #999;">
                    <?php _e('No payment method set', 'woocommerce'); ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($transaction_id)): ?>
                <p style="margin: 0 0 4px 0;">
                    <strong><?php _e('Transaction ID:', 'woocommerce'); ?></strong>
                </p>
                <p style="margin: 0 0 8px 0; padding-left: 12px; color: #666; font-size: 12px; word-break: break-all;">
                    <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">
                        <?php echo esc_html($transaction_id); ?>
                    </code>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($saved_tokens)): ?>
                <p style="margin: 12px 0 8px 0; border-top: 1px solid #ddd; padding-top: 12px;">
                    <strong><?php _e('Customer Saved Payment Method:', 'woocommerce'); ?></strong>
                </p>
                <div style="padding-left: 12px;">
                    <?php wcg_render_payment_tokens($saved_tokens, 'order'); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Save payment gateway changes when order is updated
 * Only allows changes for failed/pending orders without transaction ID
 *
 * @param int $order_id Order ID being saved
 * @param WP_Post $post Post object
 */
function wco_save_payment_gateway_edit($order_id, $post) {
    // Check if our field is set
    if (!isset($_POST['wco_payment_gateway']) || !isset($_POST['wco_gateway_nonce'])) {
        return;
    }
    
    // Sanitize new gateway ID
    $new_gateway_id = sanitize_text_field($_POST['wco_payment_gateway']);
    
    // If empty ("No Change" selected), do nothing
    if (empty($new_gateway_id)) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['wco_gateway_nonce'], 'wco_update_gateway_' . $order_id)) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_shop_orders')) {
        return;
    }
    
    // Get the order
    $order = wc_get_order($order_id);
    if (!$order) {
        return;
    }
    
    // Verify order status and transaction ID restrictions
    $allowed_statuses = array('failed', 'pending');
    $order_status = $order->get_status();
    $transaction_id = $order->get_transaction_id();
    
    if (!in_array($order_status, $allowed_statuses) || !empty($transaction_id)) {
        return;
    }
    
    // Get old gateway for comparison (from database directly)
    global $wpdb;
    $old_gateway_id = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_payment_method' LIMIT 1",
        $order_id
    ));
    $old_gateway_title = $wpdb->get_var($wpdb->prepare(
        "SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_payment_method_title' LIMIT 1",
        $order_id
    ));
    
    // Only proceed if gateway actually changed
    if ($new_gateway_id === $old_gateway_id) {
        return;
    }
    
    // Get new gateway title
    $new_gateway_title = '';
    $gateways = WC()->payment_gateways->payment_gateways();
    if (isset($gateways[$new_gateway_id])) {
        $gateway = $gateways[$new_gateway_id];
        
        // Verify gateway is enabled
        if ($gateway->enabled !== 'yes') {
            return;
        }
        
        $new_gateway_title = $gateway->get_title();
    } else {
        return; // Gateway doesn't exist
    }
    
    // Update directly in database (runs AFTER WooCommerce's save at priority 50)
    update_post_meta($order_id, '_payment_method', $new_gateway_id);
    update_post_meta($order_id, '_payment_method_title', $new_gateway_title);
    
    // Clear all caches
    wp_cache_delete($order_id, 'orders');
    wp_cache_delete('order-' . $order_id, 'orders');
    wp_cache_delete($order_id, 'post_meta');
    clean_post_cache($order_id);
    
    // Get current user info for audit trail
    $current_user = wp_get_current_user();
    $user_display = $current_user->display_name . ' (' . $current_user->user_login . ')';
    
    // Add order note for audit trail
    $note = sprintf(
        __('Payment gateway changed from "%s" [%s] to "%s" [%s] by %s', 'woocommerce'),
        $old_gateway_title ? $old_gateway_title : $old_gateway_id,
        $old_gateway_id,
        $new_gateway_title,
        $new_gateway_id,
        $user_display
    );
    $order->add_order_note($note);
    
    // Set transient for admin notice
    set_transient(
        'wco_gateway_changed_' . get_current_user_id(),
        array(
            'order_id' => $order_id,
            'old_gateway' => $old_gateway_title ? $old_gateway_title : $old_gateway_id,
            'new_gateway' => $new_gateway_title
        ),
        30
    );
}

/**
 * Display admin notice after successful gateway change
 */
function wco_display_gateway_change_notice() {
    // Check for transient
    $notice_data = get_transient('wco_gateway_changed_' . get_current_user_id());
    
    if (!$notice_data) {
        return;
    }
    
    // Delete transient
    delete_transient('wco_gateway_changed_' . get_current_user_id());
    
    // Display success notice
    ?>
    <div class="notice notice-success is-dismissible">
        <p>
            <strong><?php _e('Payment Gateway Updated:', 'woocommerce'); ?></strong>
            <?php printf(
                __('Changed from "%s" to "%s" for Order #%d', 'woocommerce'),
                esc_html($notice_data['old_gateway']),
                esc_html($notice_data['new_gateway']),
                esc_html($notice_data['order_id'])
            ); ?>
        </p>
    </div>
    <?php
}

/**
 * Add Gateway ID filter dropdown to orders list
 */
function wco_add_gateway_filter_dropdown() {
    global $typenow;
    
    // Only add on shop_order post type
    if ($typenow !== 'shop_order') {
        return;
    }
    
    global $wpdb;
    
    // Get all unique payment methods from orders
    $payment_methods = $wpdb->get_results(
        "SELECT DISTINCT meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_payment_method' 
        AND meta_value != '' 
        ORDER BY meta_value ASC"
    );
    
    if (empty($payment_methods)) {
        return;
    }
    
    // Get currently selected filter
    $selected = isset($_GET['gateway_filter']) ? sanitize_text_field($_GET['gateway_filter']) : '';
    
    // Get gateway titles for display
    $gateways = WC()->payment_gateways->payment_gateways();
    
    ?>
    <select name="gateway_filter" id="gateway_filter">
        <option value=""><?php _e('All Gateways', 'woocommerce'); ?></option>
        <?php foreach ($payment_methods as $method): ?>
            <?php 
            $gateway_id = $method->meta_value;
            $gateway_title = $gateway_id;
            
            // Try to get friendly title
            if (isset($gateways[$gateway_id])) {
                $gateway_title = $gateways[$gateway_id]->get_title();
            }
            ?>
            <option value="<?php echo esc_attr($gateway_id); ?>" <?php selected($selected, $gateway_id); ?>>
                <?php echo esc_html($gateway_title); ?> [<?php echo esc_html($gateway_id); ?>]
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Filter orders by selected gateway
 *
 * @param WP_Query $query The query object
 */
function wco_filter_orders_by_gateway($query) {
    global $pagenow, $typenow;
    
    // Only filter on shop_order edit.php page in admin
    if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'shop_order') {
        return;
    }
    
    // Check if gateway filter is set
    if (!isset($_GET['gateway_filter']) || empty($_GET['gateway_filter'])) {
        return;
    }
    
    $gateway_id = sanitize_text_field($_GET['gateway_filter']);
    
    // Add meta query to filter by payment method
    $meta_query = array(
        array(
            'key' => '_payment_method',
            'value' => $gateway_id,
            'compare' => '='
        )
    );
    
    $query->set('meta_query', $meta_query);
}

