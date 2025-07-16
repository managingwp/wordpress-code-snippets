<?php
/**
 * disable-plugin-updates.php
 * Description: Remove plugin update notices
 * Version: 1.0.0
 * Type: snippet
 * Status: Complete
 **/
add_filter( 'site_transient_update_plugins', function ( $value ) {
   $plugins_disable=[
      "canada-post-woocommerce-shipping-premium",
      "woocommerce-cost-of-goods",
      "woocommerce-product-bundles",
   ];
   foreach ( $plugins_disable as $plugin ) {
      unset( $value->response[$plugin."/".$plugin.".php"]);
   }

   # -- Single problematic plugins that don't follow the format of plugin-name/plugin-name.php uncomment to utilize.
   #unset( $value->response["show-single-variations-premium/iconic-woo-show-single-variations.php"]);

   return $value;
});