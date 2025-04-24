<?php
/**
 * Plugin Name: WP Ultimo API Fix
 * Description: Fixes the WP Ultimo API calls that fail and timeout after 10 seconds.
 * Version: 0.1.0
 * Author: Jordan
 * Author URI: https://managingwp.io/live-blog/wp-ultimo-version-1-increase-page-loads-and-curl-timeouts-for-versions-nextpress-co/
* Type: snippet
 * Status: Complete
 **/
add_filter('pre_http_request', function($pre, $r, $url) {
    if (strpos($url, 'https://versions.nextpress.co/updates/') !== 0) {
        //error_log("Good API: $url");
        return $pre;
    } else {
        // Target only WP Ultimo update/license server calls
        error_log("Bad API: $url");
        return [
            'headers' => [],
            'body'    => '',
            'response' => ['code' => 201, 'message' => 'OK'],
            'cookies'  => [],
            'filename' => null,
        ];
    }
}, 10, 3);