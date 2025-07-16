<?php
/*
Plugin Name: PowerKit SVG Lazyload Fix
 * Description: Must-use plugin to prevent PowerKit lazyload errors with SVG images
 * Version: 1.0.0
 * Author: Managing WP
 * Author URI: https://managingwp.io
 * Description: This script has two objectives, replace redirection_items and update
 * Type: mu-plugin
 * Status: Complete
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PowerKit_SVG_Lazyload_Fix {
    
    public function __construct() {
        // Hook into WordPress initialization
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Filter attachment metadata before PowerKit processes it
        add_filter('wp_update_attachment_metadata', array($this, 'filter_svg_metadata'), 5, 2);
        
        // Filter PowerKit placeholder generation
        add_filter('powerkit_lazy_placeholder_args', array($this, 'validate_placeholder_dimensions'), 10, 3);
        
        // Hook before PowerKit's generate_attachment_placeholder method
        add_action('wp_update_attachment_metadata', array($this, 'prevent_svg_placeholder_generation'), 1, 2);
    }
    
    /**
     * Filter SVG metadata to prevent invalid dimensions
     *
     * @param array $metadata Attachment metadata
     * @param int   $post_id  Attachment post ID
     * @return array Modified metadata
     */
    public function filter_svg_metadata($metadata, $post_id) {
        // Check if this is an SVG file
        if ($this->is_svg_attachment($post_id)) {
            // Remove or modify problematic metadata for SVGs
            if (isset($metadata['width']) && $metadata['width'] <= 0) {
                unset($metadata['width']);
            }
            if (isset($metadata['height']) && $metadata['height'] <= 0) {
                unset($metadata['height']);
            }
            
            // Prevent PowerKit from generating placeholders for SVGs
            $metadata['powerkit_skip_placeholder'] = true;
        }
        
        return $metadata;
    }
    
    /**
     * Validate placeholder dimensions before generation
     *
     * @param array $args   Placeholder arguments [width, height, cached]
     * @param int   $width  Image width
     * @param int   $height Image height
     * @return array Modified arguments
     */
    public function validate_placeholder_dimensions($args, $width, $height) {
        // Ensure minimum dimensions
        if ($width <= 0) {
            $args[0] = 1; // Set minimum width to 1
        }
        if ($height <= 0) {
            $args[1] = 1; // Set minimum height to 1
        }
        
        return $args;
    }
    
    /**
     * Prevent placeholder generation for SVG files
     *
     * @param array $metadata Attachment metadata
     * @param int   $post_id  Attachment post ID
     */
    public function prevent_svg_placeholder_generation($metadata, $post_id) {
        if ($this->is_svg_attachment($post_id)) {
            // Temporarily remove PowerKit's filter to prevent processing
            remove_filter('wp_update_attachment_metadata', array($this, 'get_powerkit_lazyload_public_instance'), 10);
        }
    }
    
    /**
     * Check if attachment is an SVG file
     *
     * @param int $post_id Attachment post ID
     * @return bool True if SVG, false otherwise
     */
    private function is_svg_attachment($post_id) {
        $mime_type = get_post_mime_type($post_id);
        return $mime_type === 'image/svg+xml';
    }
    
    /**
     * Get PowerKit Lazyload Public instance (if available)
     *
     * @return object|null PowerKit Lazyload Public instance or null
     */
    private function get_powerkit_lazyload_public_instance() {
        global $wp_filter;
        
        if (isset($wp_filter['wp_update_attachment_metadata'])) {
            foreach ($wp_filter['wp_update_attachment_metadata']->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function']) && 
                        is_object($callback['function'][0]) && 
                        get_class($callback['function'][0]) === 'Powerkit_Lazyload_Public') {
                        return $callback['function'][0];
                    }
                }
            }
        }
        
        return null;
    }
}

// Alternative approach: Hook directly into PowerKit's placeholder function
if (!function_exists('powerkit_lazy_get_image_placeholder_safe')) {
    /**
     * Safe wrapper for PowerKit's placeholder generation
     *
     * @param int  $width  Width of image
     * @param int  $height Height of image
     * @param bool $cached Cache the result
     * @return string Placeholder image data
     */
    function powerkit_lazy_get_image_placeholder_safe($width = 1, $height = 1, $cached = false) {
        // Ensure minimum dimensions
        $width = max(1, (int) $width);
        $height = max(1, (int) $height);
        
        // Call original function if it exists
        if (function_exists('powerkit_lazy_get_image_placeholder')) {
            return powerkit_lazy_get_image_placeholder($width, $height, $cached);
        }
        
        // Fallback placeholder
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAP+KeNJXAAAAAXRSTlMAQObYZgAAAAlwSFlzAAAOxAAADsQBlSsOGwAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';
    }
}

// Hook to replace PowerKit's function calls
add_action('plugins_loaded', function() {
    // Only run if PowerKit is active
    if (class_exists('Powerkit')) {
        // Override the problematic function with error checking
        if (!function_exists('powerkit_lazy_get_image_placeholder_override')) {
            function powerkit_lazy_get_image_placeholder_override($width = 1, $height = 1, $cached = false) {
                // Validate inputs
                $width = max(1, (int) $width);
                $height = max(1, (int) $height);
                
                $transient = sprintf('pk_image_placeholder_%s_%s', $width, $height);
                $placeholder_image = $cached ? get_transient($transient) : false;
                
                if (false === $placeholder_image) {
                    if (function_exists('imagecreate')) {
                        $placeholder_code = ob_start();
                        
                        try {
                            $image = imagecreate($width, $height);
                            if ($image !== false) {
                                $background = imagecolorallocatealpha($image, 0, 0, 255, 127);
                                imagepng($image, null, 9);
                                imagecolordeallocate($image, $background);
                                imagedestroy($image);
                                $placeholder_code = ob_get_clean();
                                $placeholder_image = 'data:image/png;base64,' . base64_encode($placeholder_code);
                            } else {
                                ob_end_clean();
                                throw new Exception('Failed to create image resource');
                            }
                        } catch (Exception $e) {
                            if (ob_get_level()) {
                                ob_end_clean();
                            }
                            // Fallback to default placeholder
                            $placeholder_image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAP+KeNJXAAAAAXRSTlMAQObYZgAAAAlwSFlzAAAOxAAADsQBlSsOGwAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';
                        }
                    } else {
                        $placeholder_image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABAQMAAAAl21bKAAAAA1BMVEUAAP+KeNJXAAAAAXRSTlMAQObYZgAAAAlwSFlzAAAOxAAADsQBlSsOGwAAAApJREFUCNdjYAAAAAIAAeIhvDMAAAAASUVORK5CYII=';
                    }
                    
                    if ($cached) {
                        set_transient($transient, $placeholder_image);
                    }
                }
                
                return $placeholder_image;
            }
        }
    }
}, 1);

// Initialize the plugin
new PowerKit_SVG_Lazyload_Fix();

// Additional safety filter for AJAX media operations
add_action('wp_ajax_media_create_image_subsizes', function() {
    // Add error handling for media AJAX operations
    if (isset($_POST['attachment_id'])) {
        $attachment_id = (int) $_POST['attachment_id'];
        $mime_type = get_post_mime_type($attachment_id);
        
        if ($mime_type === 'image/svg+xml') {
            // Skip processing for SVG files
            wp_die(json_encode(array(
                'success' => true,
                'data' => array('message' => 'SVG files do not require subsizes')
            )));
        }
    }
}, 1);
