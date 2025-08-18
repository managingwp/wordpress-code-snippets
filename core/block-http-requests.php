<?php
/**
 * Plugin Name: Block HTTP Requests to a list of URLs
 * Description: This plugin blocks HTTP requests to specific URLs, such as the WP Ultimo update server.
 * Version: 0.1.0
 * Author: Jordan
 * Author URI: https://managingwp.io/
* Type: snippet
 * Status: Complete
 **/

// Define the list of URL patterns to block
$blocked_urls = [
    'https://versions.nextpress.co/updates/',
    'https://api.wpultimo.com/',
    'https://licensing.example.com/',
    // Add more URLs to block as needed
];

add_filter('pre_http_request', function($pre, $r, $url) use ($blocked_urls) {
    $is_blocked = false;
    
    // Check if the URL matches any of the blocked patterns
    foreach ($blocked_urls as $blocked_pattern) {
        if (strpos($url, $blocked_pattern) === 0) {
            $is_blocked = true;
            break;
        }
    }
    
    if (!$is_blocked) {
        //error_log("Good API: $url");
        return $pre;
    } else {
        // Block the request
        error_log("Blocked API: $url");
        return [
            'headers' => [],
            'body'    => '',
            'response' => ['code' => 201, 'message' => 'OK'],
            'cookies'  => [],
            'filename' => null,
        ];
    }
}, 10, 3);