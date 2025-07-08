<?php
/**
 * Plugin Name: Disable WooCommerce Checkout & Restrict Logins to Admins
 * Description: Disables WooCommerce checkout and blocks login for all users except administrators.
 * Type: mu-plugin
 * Status: Complete
*/

// Block checkout by redirecting to cart with disabled message
add_action('template_redirect', function () {
    if (function_exists('is_checkout') && is_checkout() && !current_user_can('manage_options')) {
        // Add notice about disabled checkout
        wc_add_notice(__('Checkout is currently disabled due to site maintenance, please try again later.'), 'error');
        
        // Redirect to cart page
        wp_redirect(wc_get_cart_url());
        exit;
    }
});

// Disable cart and checkout endpoints for non-admins
add_filter('woocommerce_cart_redirect_after_error', function ($url) {
    if (!current_user_can('manage_options')) {
        return wc_get_cart_url();
    }
    return $url;
});

// Prevent non-admins from logging in
add_action('wp_authenticate_user', function ($user) {
    if (!user_can($user, 'administrator')) {
        return new WP_Error('access_denied', __('Only administrators can log in at this time due to site maintenance.'));
    }
    return $user;
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
add_action('rest_api_init', function () {
    add_filter('rest_authentication_errors', function ($result) {
        if (!is_wp_error($result) && !current_user_can('manage_options')) {
            return new WP_Error('maintenance_mode', 'Logins are currently restricted to administrators due to site maintenance.', ['status' => 503]);
        }
        return $result;
    });
});