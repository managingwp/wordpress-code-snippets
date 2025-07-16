<?php
/**
 * Plugin Name: Disable WooCommerce Checkout & Restrict Logins to Admins
 * Description: Disables WooCommerce checkout and blocks login for all users except administrators.
 * Version: 1.0.0
 * Type: mu-plugin
 * Status: Complete
*/

// Only activate if maintenance mode is enabled in wp-config.php
// Add this line to wp-config.php to enable: define('WP_MAINTENANCE_MODE', true);
if (!defined('WP_MAINTENANCE_MODE') || !WP_MAINTENANCE_MODE) {
    return;
}

// Array of user IDs that can bypass all restrictions
$bypass_user_ids = [
    698,    // User 1
    4829,   // User 2
    // Add more user IDs as needed
];

// Helper function to check if current user can bypass restrictions
function can_bypass_restrictions($bypass_user_ids = []) {
    // Allow administrators
    if (current_user_can('manage_options')) {
        return true;
    }
    
    // Allow specific user IDs
    $current_user_id = get_current_user_id();
    if (in_array($current_user_id, $bypass_user_ids)) {
        return true;
    }
    
    return false;
}

// Block checkout by redirecting to cart with disabled message
add_action('template_redirect', function () use ($bypass_user_ids) {
    if (function_exists('is_checkout') && is_checkout() && !can_bypass_restrictions($bypass_user_ids)) {
        // Add notice about disabled checkout
        wc_add_notice(__('Checkout is currently disabled due to site maintenance, please try again later.'), 'error');
        
        // Redirect to cart page
        wp_redirect(wc_get_cart_url());
        exit;
    }
});

// Disable cart and checkout endpoints for non-admins
add_filter('woocommerce_cart_redirect_after_error', function ($url) use ($bypass_user_ids) {
    if (!can_bypass_restrictions($bypass_user_ids)) {
        return wc_get_cart_url();
    }
    return $url;
});

// Prevent non-admins from logging in
add_action('wp_authenticate_user', function ($user) use ($bypass_user_ids) {
    // Allow administrators
    if (user_can($user, 'administrator')) {
        return $user;
    }
    
    // Allow specific user IDs
    if (in_array($user->ID, $bypass_user_ids)) {
        return $user;
    }
    
    return new WP_Error('access_denied', __('Only administrators can log in at this time due to site maintenance.'));
}, 10, 1);

// Optional: Show message on login page
add_action('login_message', function () {
    echo '<p style="color: red;">Logins are currently restricted to administrators due to site maintenance.</p>';
});

// Add warning to custom login pages (works with custom login forms)
add_action('wp_head', function () {
    // Check if we're on a login page (including custom login pages)
    if (is_page('login') || strpos($_SERVER['REQUEST_URI'], '/login') !== false || is_page('log-in') || is_page('signin') || is_page('sign-in')) {
        echo '<style>
            .login-maintenance-warning {
                background-color: #ffebee;
                border: 1px solid #f44336;
                color: #d32f2f;
                padding: 15px;
                margin: 20px 0;
                border-radius: 4px;
                text-align: center;
                font-weight: bold;
            }
        </style>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                var loginForm = document.querySelector("form[action*=\"login\"], .login-form, #loginform, .wp-login-form, form[name=\"loginform\"]");
                if (loginForm) {
                    var warningDiv = document.createElement("div");
                    warningDiv.className = "login-maintenance-warning";
                    warningDiv.innerHTML = "⚠️ Logins are currently restricted to administrators due to site maintenance.";
                    loginForm.parentNode.insertBefore(warningDiv, loginForm);
                }
            });
        </script>';
    }
});

// Handle AJAX login requests (if using AJAX login)
add_action('wp_ajax_nopriv_custom_login', function () {
    wp_send_json_error([
        'message' => 'Logins are currently restricted to administrators due to site maintenance.'
    ]);
});

// Handle REST API login attempts
add_action('rest_api_init', function () use ($bypass_user_ids) {
    add_filter('rest_authentication_errors', function ($result) use ($bypass_user_ids) {
        if (!is_wp_error($result) && !can_bypass_restrictions($bypass_user_ids)) {
            return new WP_Error('maintenance_mode', 'Logins are currently restricted to administrators due to site maintenance.', ['status' => 503]);
        }
        return $result;
    });
});