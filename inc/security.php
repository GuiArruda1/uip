<?php
/**
 * UIP Security Hardening
 * Implements XML-RPC disablement, REST user enumeration protection,
 * SVG upload XSS/XXE sanitization, and HTTP security headers.
 *
 * @package UIP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Disable XML-RPC and remove X-Pingback header
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

add_filter( 'wp_headers', function( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
} );

/**
 * 2. Block unauthenticated REST API user enumeration (/wp/v2/users)
 */
add_filter( 'rest_endpoints', function( $endpoints ) {
    if ( ! is_user_logged_in() ) {
        unset( $endpoints['/wp/v2/users'] );
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $endpoints;
} );

/**
 * Block ?author=N enumeration on frontend
 */
add_action( 'template_redirect', function() {
    if ( ! is_admin() && isset( $_GET['author'] ) && is_numeric( $_GET['author'] ) ) {
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
} );

/**
 * 3. Strict SVG Upload Defense & XXE Protection
 */
add_filter( 'upload_mimes', function( $mimes ) {
    unset( $mimes['svgz'] );
    return $mimes;
} );

/**
 * Sanitize and validate SVG contents on upload
 *
 * @param array $file Uploaded file data.
 * @return array
 */
function uip_sanitize_svg_upload( $file ) {
    $type = $file['type'] ?? '';
    $ext  = strtolower( pathinfo( $file['name'] ?? '', PATHINFO_EXTENSION ) );

    if ( 'svg' !== $ext && 'image/svg+xml' !== $type ) {
        return $file;
    }

    $file_path = $file['tmp_name'] ?? '';
    if ( ! $file_path || ! file_exists( $file_path ) ) {
        return $file;
    }

    $content = file_get_contents( $file_path );
    if ( false === $content ) {
        $file['error'] = __( 'Não foi possível ler o ficheiro SVG.', 'uip' );
        return $file;
    }

    // Block common SVG attack vectors (scripts, foreignObjects, javascript: URLs, inline events, XXE)
    $dangerous_patterns = array(
        '/<\s*script/i',
        '/<\s*foreignObject/i',
        '/href\s*=\s*["\']\s*javascript:/i',
        '/xlink:href\s*=\s*["\']\s*javascript:/i',
        '/\bon[a-z]+\s*=\s*["\']/i',
        '/<\s*!\s*ENTITY/i',
        '/<\s*!\s*DOCTYPE[^>]+SYSTEM/i',
        '/<\s*use[^>]+xlink:href=["\']https?:/i',
    );

    foreach ( $dangerous_patterns as $pattern ) {
        if ( preg_match( $pattern, $content ) ) {
            $file['error'] = __( 'Ficheiro SVG rejeitado: contém elementos ou scripts potencialmente inseguros.', 'uip' );
            return $file;
        }
    }

    // Validate XML structure safely without external network/entity resolution (XXE prevention)
    $prev_entities = null;
    if ( PHP_VERSION_ID < 80000 && function_exists( 'libxml_disable_entity_loader' ) ) {
        $prev_entities = libxml_disable_entity_loader( true );
    }
    $prev_errors = libxml_use_internal_errors( true );

    $dom    = new DOMDocument();
    $loaded = $dom->loadXML( $content, LIBXML_NONET | LIBXML_NOWARNING );

    libxml_clear_errors();
    libxml_use_internal_errors( $prev_errors );
    if ( null !== $prev_entities && function_exists( 'libxml_disable_entity_loader' ) ) {
        libxml_disable_entity_loader( $prev_entities );
    }

    if ( ! $loaded ) {
        $file['error'] = __( 'Ficheiro SVG inválido ou corrompido.', 'uip' );
        return $file;
    }

    return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'uip_sanitize_svg_upload' );

/**
 * 4. Send HTTP Security Headers and remove X-Powered-By
 */
add_action( 'send_headers', function() {
    if ( headers_sent() ) {
        return;
    }

    if ( function_exists( 'header_remove' ) ) {
        header_remove( 'X-Powered-By' );
    }

    header( 'X-Frame-Options: SAMEORIGIN' );
    header( 'X-Content-Type-Options: nosniff' );
    header( 'Referrer-Policy: strict-origin-when-cross-origin' );
    header( 'Permissions-Policy: geolocation=(self), microphone=(), camera=()' );
} );
