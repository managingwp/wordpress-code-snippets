<?php
/**
 * nginx-helper-purge-schedule.php
 * Description: Purge NGINX cache when a scheduled post is published
 * Version: 1.0.0
 * Type: snippet
 * Status: Complete
 */
add_action('transition_post_status', 'flush_nginx_cache_on_scheduled_post', 10, 3);

function flush_nginx_cache_on_scheduled_post($new_status, $old_status, $post) {    
    if ( $new_status === 'publish' && $old_status === 'future' ) {
        if (is_plugin_active('nginx-helper/nginx-helper.php')) {
            global $nginx_purger;
            if (isset($nginx_purger)) {
                $nginx_purger->log( 'mu-plugin/nginx-helper-purge-schedule.php purge_all due to scheduled post publish' );
                $nginx_purger->purge_all();
            } else {
                error_log('nginx_purger object not available');
            }
        } else {
            error_log('nginx-helper plugin not active or not detected');
        }
    }
}