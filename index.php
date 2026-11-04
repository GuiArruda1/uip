<?php
/**
 * Main Template
 * Theme: UIP (Child theme of Hello Elementor)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        the_content();
    }
}

get_footer();
