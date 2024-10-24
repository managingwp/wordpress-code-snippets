<?php 
/**
 * woo-commerce-hide-costofgoodssold-metadata.php
 * Description: Hides item metadata for the WooCommerce Costs of Goods Sold on specific Booster for WooCommerce plugin shortcodes
 * Type: snippet
 * Status: Complete
 */

add_filter( 'woocommerce_hidden_order_itemmeta', function ( $hidden_meta ) {

  // Let's hide the meta data for WooCommerce Cost og
  $hidden_meta = array(
      '_reduced_stock',
      '_wc_cog_item_cost',
      '_wc_cog_item_total_cost'
      );

  return $hidden_meta;
});