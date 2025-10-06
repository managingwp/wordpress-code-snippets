<?php
/**
 * Plugin Name: Podcast Download Canonical Guard
 * Description: Prevents Seriously Simple Podcasting plugin redirect loops on missing podcast download URLs by disabling canonical redirects for 404 responses on /podcast-download/ paths.
 * Version: 0.1.0
 * Author: Jordan
 * Author URI: https://managingwp.io/
 * Type: snippet
 * Status: Complete
 */

define('PODCAST_CANONICAL_GUARD_DEBUG', apply_filters('podcast_canonical_guard_debug', false));

if (!function_exists('podcast_canonical_guard_parse_route')) {
    function podcast_canonical_guard_parse_route($url_or_path)
    {
        $path = wp_parse_url($url_or_path, PHP_URL_PATH);

        if ($path === null && is_string($url_or_path) && strpos($url_or_path, '/') === 0) {
            $path = preg_replace('#\?.*$#', '', $url_or_path);
        }

        if (!$path || strpos($path, '/podcast-download/') === false) {
            return null;
        }

        $normalized_path = untrailingslashit($path);

        if (!preg_match('#^/podcast-download/(\d+)/([^/]+)\.mp3$#i', $normalized_path, $matches)) {
            return null;
        }

        return [
            'path'             => $path,
            'normalized_path'  => $normalized_path,
            'post_id'          => absint($matches[1]),
            'requested_slug'   => sanitize_title($matches[2]),
        ];
    }
}

if (!function_exists('podcast_canonical_guard_send_404')) {
    function podcast_canonical_guard_send_404($requested_path, $reason = '', $status = 404)
    {
        if (PODCAST_CANONICAL_GUARD_DEBUG) {
            error_log(sprintf('[Podcast Canonical Guard] Forced %d for %s (%s)', $status, $requested_path, $reason ?: 'unspecified'));
        }

        global $wp_query;

        if ($wp_query instanceof WP_Query) {
            $wp_query->set_404();
        }

        status_header($status);
        nocache_headers();

        if (!headers_sent()) {
            header('Content-Type: text/plain; charset=utf-8');
        }

        echo sprintf("Podcast download unavailable (%d).", $status);

        exit;
    }
}

add_filter('redirect_canonical', function ($redirect_url, $requested_url) {
    // Bail early if WordPress has not parsed the request yet.
    if (!did_action('parse_request')) {
        return $redirect_url;
    }

    $requested = podcast_canonical_guard_parse_route($requested_url);

    if (!$requested) {
        return $redirect_url;
    }

    // Allow other components to short-circuit this behaviour entirely.
    if (false === apply_filters('podcast_canonical_guard_should_intercept', true, $requested['path'], $redirect_url, $requested_url)) {
        return $redirect_url;
    }

    $redirect_host = $redirect_url ? wp_parse_url($redirect_url, PHP_URL_HOST) : null;
    $requested_host = wp_parse_url($requested_url, PHP_URL_HOST);

    $redirect = $redirect_url ? podcast_canonical_guard_parse_route($redirect_url) : null;

    $hosts_match = !$redirect_host || !$requested_host || strcasecmp($redirect_host, $requested_host) === 0;
    $paths_match = $redirect && $redirect['normalized_path'] === $requested['normalized_path'];

    $should_block = ($hosts_match && $paths_match) || is_404();

    if (!$should_block) {
        return $redirect_url;
    }

    /**
     * Fires when a canonical redirect is being suppressed for a podcast download URL.
     *
     * @param string|null $redirect_url  The URL WordPress planned to redirect to.
     * @param string      $requested_url The original requested URL.
     * @param string      $path          Parsed path portion of the requested URL.
     */
    do_action('podcast_canonical_guard_blocked_redirect', $redirect_url, $requested_url, $requested['path']);

    if (PODCAST_CANONICAL_GUARD_DEBUG) {
        error_log(sprintf('[Podcast Canonical Guard] Suppressed redirect for path: %s (was %s)', $requested_url, $redirect_url));
    }

    return false;
}, 10, 2);

add_action('template_redirect', function () {
    $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '';

    if ($request_uri === '') {
        return;
    }

    $route = podcast_canonical_guard_parse_route($request_uri);

    if (!$route) {
        return;
    }

    $post_id = $route['post_id'];
    $requested_slug = $route['requested_slug'];
    $path = $route['path'];

    if (!$post_id) {
        podcast_canonical_guard_send_404($path, 'missing-id');
    }

    $post = get_post($post_id);

    if (!$post || 'publish' !== $post->post_status) {
        podcast_canonical_guard_send_404($path, 'missing-post');
    }

    $canonical_slug = sanitize_title($post->post_name);

    if (strcasecmp($canonical_slug, $requested_slug) !== 0) {
        podcast_canonical_guard_send_404($path, 'slug-mismatch', 410);
    }
}, 0);
