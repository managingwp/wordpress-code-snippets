<?php
/**
 * Plugin Name: WP Redirect Log
 * Description: Logs all wp_redirect and wp_safe_redirect calls to a log file in the uploads directory.
 * Version: 1.0.1
 * Author: ManagingWP
 * Author URI: https://github.com/managingwp/wordpress-code-snippets
 * Type: mu-plugin
 * Status: Complete
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hook into WP redirects
add_filter( 'wp_redirect',      'wprl_log_redirect', 10, 2 );
add_filter( 'wp_safe_redirect', 'wprl_log_redirect', 10, 2 );

// Also catch raw Location headers
add_filter( 'wp_headers', 'wprl_log_raw_header' );

/**
 * Get full path to our log file in uploads/
 *
 * @return string
 */
function wprl_get_log_file() {
    $uploads = wp_upload_dir();
    $basedir = trailingslashit( $uploads['basedir'] );
    $file    = $basedir . 'wp-redirects.log';

    if ( ! file_exists( $file ) ) {
        @touch( $file );
        @chmod( $file, 0664 );
    }

    return $file;
}

/**
 * Append a line to our log file
 *
 * @param string $line
 * @return void
 */
function wprl_write_log( $line ) {
    $file = wprl_get_log_file();
    file_put_contents( $file, $line . PHP_EOL, FILE_APPEND | LOCK_EX );
}

/**
 * Build readable backtrace frames for logging.
 *
 * @param int $limit
 * @return array
 */
function wprl_get_backtrace_frames( $limit = 20 ) {
    $frames  = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, $limit + 8 );
    $summary = array();

    foreach ( $frames as $frame ) {
        $function = $frame['function'] ?? '';

        // Skip this plugin's trace helper/log wrappers to surface the real redirect path.
        if ( strpos( $function, 'wprl_' ) === 0 ) {
            continue;
        }

        $file = isset( $frame['file'] ) ? wp_basename( $frame['file'] ) : '[internal]';
        $line = isset( $frame['line'] ) ? (int) $frame['line'] : 0;

        if ( ! empty( $frame['class'] ) ) {
            $function = $frame['class'] . ( $frame['type'] ?? '::' ) . $function;
        }

        if ( $function === '' ) {
            $function = '[main]';
        }

        $summary[] = sprintf( '%s:%d %s()', $file, $line, $function );

        if ( count( $summary ) >= $limit ) {
            break;
        }
    }

    return $summary;
}

/**
 * Log wp_redirect / wp_safe_redirect usage
 *
 * @param string $location
 * @param int    $status
 * @return string
 */
function wprl_log_redirect( $location, $status ) {
    // Keep one caller for quick scanning and add full stack for deep debugging.
    $trace      = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
    $caller     = isset( $trace[2] ) ? $trace[2] : ( $trace[1] ?? array() );
    $caller_info = '';
    if ( ! empty( $caller['file'] ) ) {
        $caller_info = wp_basename( $caller['file'] ) . ':' . ( $caller['line'] ?? 0 );
    }
    $backtrace = wprl_get_backtrace_frames( 20 );

    // Grab siteurl and home from options
    $siteurl = get_option( 'siteurl' );
    $home    = get_option( 'home' );

    $msg_lines = array(
        '[WP-REDIRECT]',
        'request: ' . ( $_SERVER['REQUEST_URI'] ?? 'unknown' ),
        'location: ' . $location,
        'status: ' . (int) $status,
        'caller: ' . ( $caller_info ?: 'unknown' ),
        'siteurl: ' . $siteurl,
        'home: ' . $home,
        'trace:',
    );

    if ( empty( $backtrace ) ) {
        $msg_lines[] = '  01. n/a';
    } else {
        foreach ( $backtrace as $index => $frame ) {
            $msg_lines[] = sprintf( '  %02d. %s', $index + 1, $frame );
        }
    }

    $msg_lines[] = str_repeat( '-', 80 );
    $msg = implode( PHP_EOL, $msg_lines );
    wprl_write_log( $msg );

    return $location;
}

/**
 * Log any raw Location headers before send
 *
 * @param array $headers
 * @return array
 */
function wprl_log_raw_header( $headers ) {
    if ( ! empty( $headers['Location'] ) ) {
        $backtrace = wprl_get_backtrace_frames( 20 );

        // Grab siteurl and home from options
        $siteurl = get_option( 'siteurl' );
        $home    = get_option( 'home' );

        $msg_lines = array(
            '[WP-HEADER]',
            'request: ' . ( $_SERVER['REQUEST_URI'] ?? 'unknown' ),
            'location: ' . $headers['Location'],
            'siteurl: ' . $siteurl,
            'home: ' . $home,
            'trace:',
        );

        if ( empty( $backtrace ) ) {
            $msg_lines[] = '  01. n/a';
        } else {
            foreach ( $backtrace as $index => $frame ) {
                $msg_lines[] = sprintf( '  %02d. %s', $index + 1, $frame );
            }
        }

        $msg_lines[] = str_repeat( '-', 80 );
        $msg = implode( PHP_EOL, $msg_lines );
        wprl_write_log( $msg );
    }
    return $headers;
}
