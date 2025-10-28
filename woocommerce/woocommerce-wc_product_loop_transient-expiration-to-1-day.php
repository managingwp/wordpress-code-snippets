<?php
/**
 * Plugin Name: woocommerce-wc_product_loop_transient-expiration-to-1-day.php
 * Description: Change wc_product_loop transient expiration to 1 day
 * Version: 1.0.0
 * Type: snippet
 * Status: Complete
 *
 * Credit: https://github.com/mainwp/Code-Snippets-Functions/blob/master/Execute%20a%20function%20on%20a%20child%20site/WooCommerce/change-wc_product_loop_transient-expiration-to-1-day.txt
 */

add_action( 'setted_transient', 'mmx_wc_product_loop_transient', 50, 3 );
function mmx_wc_product_loop_transient( $transient, $value, $expiration ){
	$pos = strpos( $transient, 'wc_product_loop_' );
	if ( $pos !== false && $expiration == 2592000 ) {
		set_transient( $transient, $value, DAY_IN_SECONDS );
	}
}