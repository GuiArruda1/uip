<?php
/**
 * UIP Performance & Core Web Vitals Optimizations
 * Improves Lighthouse metrics (LCP, FCP, TBT, Best Practices) at theme level
 *
 * @package UIP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Remove WordPress Emoji bloat and unnecessary head links
 */
function uip_cleanup_wp_head() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'wp_generator' );
    remove_action( 'wp_head', 'wp_shortlink_wp_head' );
}
add_action( 'init', 'uip_cleanup_wp_head' );

/**
 * 2. Resource Hints (Preconnect) & LCP Image Preload
 */
function uip_resource_hints_and_preload() {
    echo '<link rel="preconnect" href="https://www.googletagmanager.com" crossorigin>' . "\n";
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

    // Preload Hero LCP background image on Front Page
    if ( is_front_page() ) {
        $upload_dir = wp_upload_dir();
        $base_url   = trailingslashit( $upload_dir['baseurl'] );
        $desktop_lcp = $base_url . '2025/10/banner-uip.webp';
        echo '<link rel="preload" as="image" href="' . esc_url( $desktop_lcp ) . '" fetchpriority="high" media="(min-width: 768px)">' . "\n";
    }
}
add_action( 'wp_head', 'uip_resource_hints_and_preload', 2 );

/**
 * 3. Defer theme scripts so they never block rendering or main thread
 */
function uip_defer_theme_scripts( $tag, $handle, $src ) {
    $defer_handles = array( 'uip-custom', 'uip-tracking' );

    if ( in_array( $handle, $defer_handles, true ) && false === strpos( $tag, 'defer' ) ) {
        return str_replace( ' src=', ' defer src=', $tag );
    }

    return $tag;
}
add_filter( 'script_loader_tag', 'uip_defer_theme_scripts', 10, 3 );
