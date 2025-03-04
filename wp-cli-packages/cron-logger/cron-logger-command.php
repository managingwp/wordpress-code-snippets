<?php

if ( ! class_exists( 'WP_CLI' ) ) {
    return;
}

/**
 * Logs cron events before they run via WP-CLI.
 */
class Cron_Logger_Command {

    /**
     * Manually list upcoming cron jobs.
     *
     * ## EXAMPLES
     *
     *     wp cron-logger run
     *
     * @when after_wp_load
     */
    public function print( $args, $assoc_args ) {
        $cron_jobs = _get_cron_array();
        if ( empty( $cron_jobs ) ) {
            WP_CLI::log( "No cron jobs scheduled." );
            return;
        }

        $current_time = time();
        $due_events = [];

        foreach ( $cron_jobs as $timestamp => $events ) {
            if ( $timestamp <= $current_time ) {
                foreach ( $events as $hook => $details ) {
                    foreach ( $details as $event ) {
                        $due_events[] = sprintf(
                            'Scheduled event: "%s" at %s with args: %s',
                            $hook,
                            date( 'Y-m-d H:i:s', $timestamp ),
                            json_encode( $event['args'] )
                        );
                    }
                }
            }
        }

        if ( ! empty( $due_events ) ) {
            WP_CLI::log( "Upcoming cron events:" );
            foreach ( $due_events as $event_log ) {
                WP_CLI::log( $event_log );
            }
        } else {
            WP_CLI::log( "No due cron events found." );
        }
    }

    /**
     * Run all events due now.
     * 
     * ## EXAMPLES
     * 
     *    wp cron-logger run
     * 
     * @when after_wp_load
     */

    public function run( $args, $assoc_args ) {
        $cron_jobs = _get_cron_array();
        if ( empty( $cron_jobs ) ) {
            WP_CLI::log( "No cron jobs scheduled." );
            return;
        }

        $current_time = time();
        $due_events = [];

        foreach ( $cron_jobs as $timestamp => $events ) {
            if ( $timestamp <= $current_time ) {
                foreach ( $events as $hook => $details ) {
                    foreach ( $details as $event ) {
                        $due_events[] = $hook;
                    }
                }
            }
        }

        if ( ! empty( $due_events ) ) {
            foreach ( $due_events as $event_name ) {
                WP_CLI::log( sprintf( '** Running cron event: "%s"', $event_name ) );
                WP_CLI::runcommand( "cron event run $event_name" );
            }
        } else {
            WP_CLI::log( "No due cron events found." );        
        }
    }
}

/**
 * Hook into WP-CLI cron event execution to log each event before it runs.
 */
WP_CLI::add_hook( 'before_invoke:cron_event_run', function ( $args ) {
    if ( !empty( $args ) && is_array( $args ) ) {
        $event_name = trim( $args[0] );
        $start_time = microtime(true);
        // Store start time in a global variable for later use
        $GLOBALS['cron_event_start_time'] = $start_time;
        WP_CLI::log( sprintf( '=== [%s] Starting cron event: "%s" ===', 
            date('Y-m-d H:i:s'),
            $event_name 
        ));
    } else {
        WP_CLI::log( sprintf( '=== [%s] Running all due cron events... ===', 
            date('Y-m-d H:i:s')
        ));
    }
});

/**
 * Hook to log when a cron event finishes running
 */
WP_CLI::add_hook( 'after_invoke:cron_event_run', function ( $args ) {
    $end_time = microtime(true);
    $start_time = isset($GLOBALS['cron_event_start_time']) ? $GLOBALS['cron_event_start_time'] : $end_time;
    $duration = round($end_time - $start_time, 2);
    
    if ( !empty( $args ) && is_array( $args ) ) {
        $event_name = trim( $args[0] );
        WP_CLI::log( sprintf( '=== [%s] Completed cron event: "%s" (took %s seconds) ===', 
            date('Y-m-d H:i:s'),
            $event_name,
            $duration
        ));
    } else {
        WP_CLI::log( sprintf( '=== [%s] Completed all due cron events (took %s seconds) ===', 
            date('Y-m-d H:i:s'),
            $duration
        ));
    }
});

WP_CLI::add_command( 'cron-logger', 'Cron_Logger_Command' );