<?php
/**
 * UIP Theme Functions & Definitions
 * Child theme of Hello Elementor
 *
 * Theme Name: UIP
 * Author: Sanzza
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'UIP_THEME_VERSION', '1.0.0' );
define( 'UIP_THEME_DIR', get_stylesheet_directory() );
define( 'UIP_THEME_URI', get_stylesheet_directory_uri() );

/**
 * Enqueue child theme scripts and styles
 */
function uip_enqueue_scripts() {
    // Parent theme style
    wp_enqueue_style( 'hello-elementor', get_template_directory_uri() . '/style.css' );

    // Child theme stylesheet
    wp_enqueue_style( 'uip-style', get_stylesheet_uri(), array( 'hello-elementor' ), UIP_THEME_VERSION );

    // Modular custom CSS if present
    if ( file_exists( UIP_THEME_DIR . '/css/custom.css' ) ) {
        wp_enqueue_style( 'uip-custom', UIP_THEME_URI . '/css/custom.css', array( 'uip-style' ), UIP_THEME_VERSION );
    }

    // Modular custom Vanilla JS if present
    if ( file_exists( UIP_THEME_DIR . '/js/custom.js' ) ) {
        wp_enqueue_script( 'uip-custom', UIP_THEME_URI . '/js/custom.js', array(), UIP_THEME_VERSION, true );
    }
}
add_action( 'wp_enqueue_scripts', 'uip_enqueue_scripts', 20 );

/**
 * Include modular helpers and custom logic from /inc/
 */
$uip_inc_files = glob( UIP_THEME_DIR . '/inc/*.php' );
if ( ! empty( $uip_inc_files ) ) {
    foreach ( $uip_inc_files as $file ) {
        if ( basename( $file ) !== 'index.php' ) {
            require_once $file;
        }
    }
}
