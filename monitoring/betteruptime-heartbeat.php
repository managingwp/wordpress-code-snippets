<?
/**
 * Plugin Name: betteruptime-heartbeat.php
 * Description: Monitor WordPress cron via Better Uptime heartbeat checks. Originally from https://www.sprucely.net/knowledge-base/monitoring-wordpress-cron-via-heartbeat-checks/
 * Version: 1.0.0
 * Status: Complete
 * Type: mu-plugin
 * 
 * Add define( 'BETTERUPTIME_CRON_MONITOR_KEY', 'SXuNXxHWREsstjnXrBMhNx3B' ); to wp-config.php
 * 
 *
*/

add_action( 'betteruptime_heartbeat_monitor', 'betteruptime_remote_heartbeat_ping' );

/**
 * Ping remote Better Uptime heartbeat URL using key stored in wp-config.php constant.
 *
 * @return void
 */
function betteruptime_remote_heartbeat_ping() {
        if ( defined( 'BETTERUPTIME_CRON_MONITOR_KEY' ) ) {
                wp_safe_remote_get( 'https://betteruptime.com/api/v1/heartbeat/' . BETTERUPTIME_CRON_MONITOR_KEY );
        }
}

/**
 * Add cron interval.
 */
add_filter( 'cron_schedules', 'betteruptime_cron_interval' );

/**
 * Create cron interval for every 5 minutes.
 *
 * @param array $schedules Array of defined cron intervals.
 * @return $schedules
 */
function betteruptime_cron_interval( $schedules ) {
        $schedules['five_minutes'] = array(
                'interval' => 300, // Time interval in seconds.
                'display'  => esc_html__( 'Every 5 Minutes' ),
        );
        return $schedules;
}
// Schedule event if not already scheduled.
if ( ! wp_next_scheduled( 'betteruptime_heartbeat_monitor' ) ) {
        wp_schedule_event( time(), 'five_minutes', 'betteruptime_heartbeat_monitor' );
}
