<?php
/**
 * Plugin Name: SAL.js Scroll Animations
 * Description: Lightweight scroll animation library (SAL.js) integrated as a mu-plugin. Adds data-sal attributes support for on-scroll animations using Intersection Observer.
 * Version: 1.0.0
 * Author: ManagingWP.io
 * Type: Snippet
 * Status: Complete
 *
 * Based on https://www.gmitropapas.com/generatepress-tips-and-tricks/generateblocks/scrolling-animation-effects-in-generateblocks-pro/
 * Installation:
 * ─────────────
 * Drop this single file into wp-content/mu-plugins/ — it activates automatically.
 * No child theme or additional files required.
 *
 *
 * Adding Animations in GenerateBlocks Pro:
 * ────────────────────────────────────────
 * 1. Open the WordPress editor and select (or create) a GenerateBlocks
 *    Container, Grid, or Headline block.
 *
 * 2. In the right-hand block settings panel, scroll down and expand the
 *    "Advanced" section.
 *
 * 3. Look for the "HTML Attributes" area. You will see two input fields
 *    side by side:
 *
 *        ┌──────────────────┐  ┌──────────────────┐
 *        │  Attribute        │  │  Value            │
 *        └──────────────────┘  └──────────────────┘
 *
 *    Click "+ Add Attribute" to create a new row, then type the attribute
 *    name on the left and its value on the right. Add one row per attribute.
 *
 * 4. Example — slide a container in from the right:
 *
 *        Attribute              Value
 *        ─────────────────────  ──────────────
 *        data-sal               slide-right
 *        data-sal-easing        easeInOutSine
 *        data-sal-duration      600
 *
 *    This produces the following HTML on the front end:
 *
 *        <div class="gb-container"
 *             data-sal="slide-right"
 *             data-sal-easing="easeInOutSine"
 *             data-sal-duration="600">
 *            ...
 *        </div>
 *
 * 5. Example — stagger three icon-box containers so they slide up
 *    one after another:
 *
 *        First Container:
 *            data-sal            slide-up
 *            data-sal-easing     easeInOutSine
 *            data-sal-duration   600
 *
 *        Second Container:
 *            data-sal            slide-up
 *            data-sal-easing     easeInOutSine
 *            data-sal-duration   600
 *            data-sal-delay      100
 *
 *        Third Container:
 *            data-sal            slide-up
 *            data-sal-easing     easeInOutSine
 *            data-sal-duration   600
 *            data-sal-delay      200
 *
 *    The increasing data-sal-delay values (0 → 100 → 200 ms) create a
 *    staggered cascade effect as the user scrolls down.
 *
 * 6. Example — a subtle fade-in for a Call to Action section:
 *
 *        data-sal            fade
 *        data-sal-easing     easeInOutSine
 *        data-sal-duration   1000
 *
 *
 * Available data-sal Animation Values:
 * ─────────────────────────────────────
 *   fade
 *   slide-up      slide-down      slide-left      slide-right
 *   zoom-in       zoom-out
 *   flip-up       flip-down       flip-left       flip-right
 *
 *
 * Optional Attribute Reference:
 * ─────────────────────────────
 *   data-sal-duration   – animation duration in ms (default: 200)
 *   data-sal-delay      – delay before animation starts in ms (default: 0)
 *   data-sal-easing     – any CSS easing value (default: ease)
 *                         Common choices: ease, easeInOutSine,
 *                         easeInOutCubic, linear
 *
 *
 * Configuration (edit the constants below):
 * ─────────────────────────────────────────
 *   SAL_MOBILE_BREAKPOINT  – Disable animations below this width in px.
 *                            Set to 0 to animate on all screen sizes.
 *   SAL_FRONT_PAGE_ONLY    – true = load only on the homepage.
 *                            false = load site-wide (default).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ┌─────────────────────────────────────────────┐
 * │  CONFIGURATION                              │
 * └─────────────────────────────────────────────┘
 */

// Set to 0 to keep animations on all screen sizes.
define( 'SAL_MOBILE_BREAKPOINT', 768 );

// Restrict to specific pages. Set to false to load site-wide.
// Examples: is_front_page(), is_page('about'), is_singular('post')
define( 'SAL_FRONT_PAGE_ONLY', false );


/**
 * Enqueue SAL.js assets.
 */
function sal_mu_enqueue_assets() {

    // Optional: limit to certain pages.
    if ( SAL_FRONT_PAGE_ONLY && ! is_front_page() ) {
        return;
    }

    // Register and enqueue the SAL stylesheet from CDN.
    wp_enqueue_style(
        'sal-css',
        'https://cdn.jsdelivr.net/npm/sal.js@0.8.5/dist/sal.css',
        array(),
        '0.8.5'
    );

    // Register and enqueue the SAL script from CDN.
    wp_enqueue_script(
        'sal-js',
        'https://cdn.jsdelivr.net/npm/sal.js@0.8.5/dist/sal.js',
        array(),
        '0.8.5',
        true
    );

    // Add the initialization script inline (no extra file needed).
    wp_add_inline_script( 'sal-js', sal_mu_get_init_script() );
}
add_action( 'wp_enqueue_scripts', 'sal_mu_enqueue_assets' );


/**
 * Return the SAL initialization JavaScript.
 *
 * @return string
 */
function sal_mu_get_init_script() {

    $breakpoint = (int) SAL_MOBILE_BREAKPOINT;

    // If no mobile breakpoint is set, return a simple init.
    if ( $breakpoint <= 0 ) {
        return 'sal();';
    }

    return <<<JS
(function() {
    var instance = sal();

    function switchAnimations() {
        if (window.innerWidth < {$breakpoint}) {
            instance.reset();
            instance.disable();
        } else {
            instance.reset();
            instance.enable();
        }
    }

    switchAnimations();
    window.addEventListener('resize', switchAnimations);
})();
JS;
}