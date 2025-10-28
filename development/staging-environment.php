<?php
/**
 * Plugin Name: staging-environment.php
 * Description: This snippet will enable and disable plugins based on the WP_ENVIRONMENT_TYPE 
 * Version: 1.0.0
 * Type: Snippet
 * Status: Complete
 * 
 * Originally from https://pantheon.io/docs/environment-specific-config
 * Also look at https://kamilgrzegorczyk.com/2018/05/02/how-to-disable-plugins-on-certain-environment/
 */

# List Development Plugins
$plugins_development = array(
    'debug-bar/debug-bar.php',
    'developer/developer.php',
    'wp-reroute-email/wp-reroute-email.php'
);

# List Production Plugins
$plugins_production = array(
    'woocommerce-zapier/woocommerce-zapier.php'
);

# For sites that are live
if ( WP_ENVIRONMENT_TYPE == 'production' ) {
    //error_log("Running in production");
    
    # Disable Development Plugins
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    foreach ($plugins_development as $plugin_dev) {
        if(is_plugin_active($plugin_dev)) {
            deactivate_plugins($plugin_dev);
        }
    }
    
    # Disable jetpack_development_mode
    add_filter( 'jetpack_development_mode', '__return_false' );

    # Enable Production plugins
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    foreach ($plugins_production as $plugin_prod) {
        if(is_plugin_inactive($plugin_prod)) {
            activate_plugin($plugin_prod);
        }
    }

# For all other instances
} else {
    //error_log("Not running in production.");
    
    # Activate Development Plugins
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    foreach ($plugins_development as $plugin_dev) {
        if(is_plugin_inactive($plugin_dev)) {
            activate_plugin($plugin_dev);
        }
    }
    
    # De-activate production plugins that trigger automated calls
    require_once(ABSPATH . 'wp-admin/includes/plugin.php');
    foreach ($plugins_production as $plugin_prod) {
        if(is_plugin_active($plugin_prod)) {
            deactivate_plugins($plugin_prod);
        }
    }
    
    # Enable development mode for jetpack
    add_filter( 'jetpack_development_mode', '__return_true' );
}


# Change admin bar color based on enviroment
if ( WP_ENVIRONMENT_TYPE == 'staging' ) {
    add_action('admin_notices', function () {
        global $pagenow;
        echo '<div class="notice notice-error" style="background:yellow;color:black;">
        <p><b>You\'re on the staging site!</b></p>
        </div>';
    });
}


//error_log(WP_ENVIRONMENT_TYPE);
