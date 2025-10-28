<?php
/**
 * Plugin Name: MU Shortcode: SVG QR for Current URL
 * Description: Adds [qr_svg] shortcode that renders an SVG QR code for the current page URL (or a provided URL).
 * Author: ChatGPT
 * Type: Snippet
 * Status: Complete
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) exit;

add_shortcode('qr_svg', function($atts = [], $content = null){
	$atts = shortcode_atts([
		// size in px (square), e.g. 200 => 200x200
		'size'   => '200',
		// ECC error correction level: L, M, Q, H
		'ecc'    => 'M',
		// quiet zone (margin in "modules")
		'margin' => '2',
		// optional: override URL; otherwise current URL is used
		'url'    => '',
		// optional: CSS class for the <img>
		'class'  => 'qr-svg',
		// optional: alt text
		'alt'    => 'QR code',
	], $atts, 'qr_svg');

	// Determine the target URL
	$target_url = trim($atts['url']);
	if ($target_url === '') {
		// Build the exact current URL including scheme, host, and request URI
		$scheme = (is_ssl() ? 'https://' : 'http://');
		$host   = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : parse_url(home_url(), PHP_URL_HOST);
		$uri    = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
		$target_url = $scheme . $host . $uri;
	}

	// Sanitize/normalize attributes
	$size   = max(64, (int)$atts['size']); // sensible minimum
	$ecc    = strtoupper(preg_replace('/[^LMQH]/', 'M', $atts['ecc']));
	if (!in_array($ecc, ['L','M','Q','H'], true)) $ecc = 'M';
	$margin = max(0, (int)$atts['margin']);
	$class  = sanitize_html_class($atts['class']);
	$alt    = esc_attr($atts['alt']);

	// Use goqr.me's API (api.qrserver.com) to return SVG directly
	// Docs: https://goqr.me/api/
	$src = add_query_arg([
		'format' => 'svg',
		'size'   => "{$size}x{$size}",
		'ecc'    => $ecc,
		'margin' => $margin,
		'data'   => rawurlencode($target_url),
	], 'https://api.qrserver.com/v1/create-qr-code/');

	// Output <img> pointing to the SVG (browser will render the SVG)
	$html  = '<img';
	$html .= ' src="' . esc_url($src) . '"';
	$html .= ' width="' . (int)$size . '" height="' . (int)$size . '"';
	$html .= ' loading="lazy" decoding="async"';
	if (!empty($class)) $html .= ' class="' . esc_attr($class) . '"';
	$html .= ' alt="' . $alt . '"';
	$html .= ' />';

	return $html;
});
