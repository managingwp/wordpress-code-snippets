<?php
/**
 * nginx-gravity-forms-stripe-cache-exclude.php
 * Description: Exclude Gravity Forms or Stripe from caching
 * Version: 1.0.0
 * Type: snippet
 * Status: Complete
 * 
 * Credit: Aon @ GridPane Community forum https://community.gridpane.com/t/gravity-forms-stripe-exclude-a-page-from-nginx-server-caching/3621/2
 * 
 * -----------------------------------------------------------------------------
 * Alternative solutions - Load the form via AJAX bypassing the WordPress cache.
 * https://gravitywiz.com/cache-busting-with-gravity-forms/
 * 
 * - Add the following to gravity forms shotcodes - [gravityforms id="123" cachebuster="1"]
 * - Add to functions.php add_filter( 'gfcb_enable_cache_buster', '__return_true' );
 * 
 * Known Limitations
 * - Gravity Forms Cache Buster does not work with single File Upload fields due to browser security. It does work with Multi-file Upload fields since they upload via AJAX.
 */

function exclude_gravityforms_or_stripe_from_cache() {
    global $post;

    // Check if the post is available
    if (is_a($post, 'WP_Post')) {
        $has_gravity_form = has_shortcode($post->post_content, 'gravityform');
        $has_stripe = has_shortcode($post->post_content, 'stripe'); // Assuming 'stripe' is the name of the Stripe shortcode.

        // Check if the content contains either a Gravity Form or a Stripe
        if ($has_gravity_form || $has_stripe) {
            header('do-not-cache: true');
        }
    }
}
add_action('send_headers', 'exclude_gravityforms_or_stripe_from_cache');

?>