<?php
/*
Disables the admin notice Litespeeds LSCache generates about conflicting plugins installed

This is a WIP and not functioning yet.

* Need to figure out how to remove the admin notice once it's been created.
* Once the below functions are ran, then create an admin notification.
* The admin notification is persistent based on the wp_options record litespeed.admin_display.thirdparty_litespeed_check
* Once this record is delete, the admin_notice will continue to show.

Original code that didn't work.

add_action('admin_init', function () {
    remove_filter('activated_plugin', LiteSpeed\Thirdparty\LiteSpeed_Check::class.'::activated_plugin',10);
    remove_filter('deactivated_plugin', LiteSpeed\Thirdparty\LiteSpeed_Check::class.'::deactivated_plugin',10);
    if ( \LiteSpeed\Admin_Display::get_option( thirdparty_litespeed_check ) ) {
        \LiteSpeed\Admin_Display::delete_option( thirdparty_litespeed_check );
    }
});

*/

add_action('wp_loaded', function () {
    remove_filter('activated_plugin', LiteSpeed\Thirdparty\LiteSpeed_Check::class.'::activated_plugin',10);
});
