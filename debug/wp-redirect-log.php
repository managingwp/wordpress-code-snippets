<?php
/**
 * Plugin Name: WP Redirect Log
 * Description: Logs all wp_redirect and wp_safe_redirect calls to a log file in the uploads directory.
 * Version: 1.0
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
 * Log wp_redirect / wp_safe_redirect usage
 *
 * @param string $location
 * @param int    $status
 * @return string
 */
function wprl_log_redirect( $location, $status ) {
    // short backtrace to find caller
    $trace      = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 5 );
    $caller     = isset( $trace[2] ) ? $trace[2] : $trace[1];
    $caller_info = '';
    if ( ! empty( $caller['file'] ) ) {
        $caller_info = wp_basename( $caller['file'] ) . ':' . ( $caller['line'] ?? 0 );
    }

    // Grab siteurl and home from options
    $siteurl = get_option( 'siteurl' );
    $home    = get_option( 'home' );

    $msg = sprintf(
        "[WP-REDIRECT] %s → %s (status %d) called by %s | siteurl=%s | home=%s",
        $_SERVER['REQUEST_URI'] ?? 'unknown',
        $location,
        $status,
        $caller_info,
        $siteurl,
        $home
    );
    wprl_write_log( $msg );

    // Also emit a short backtrace to the PHP error log for deeper debugging
    error_log( '[WP-REDIRECT TRACE] ' . ( $_SERVER['REQUEST_URI'] ?? 'unknown' ) . ' → ' . $location );
    error_log( print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 ), true ) );

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
        // Grab siteurl and home from options
        $siteurl = get_option( 'siteurl' );
        $home    = get_option( 'home' );

        $msg = sprintf(
            "[WP-HEADER] sending Location: %s (on %s) | siteurl=%s | home=%s",
            $headers['Location'],
            $_SERVER['REQUEST_URI'] ?? 'unknown',
            $siteurl,
            $home
        );
        wprl_write_log( $msg );

        // Also emit a short backtrace to the PHP error log for deeper debugging
        error_log( '[WP-HEADER TRACE] ' . ( $_SERVER['REQUEST_URI'] ?? 'unknown' ) . ' → ' . $headers['Location'] );
        error_log( print_r( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 ), true ) );
    }
    return $headers;
}
