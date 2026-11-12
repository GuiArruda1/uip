<?php
/**
 * UIP Tracking & Measurement Architecture
 * Integrates Google Tag Manager (GTM) and initializes the dataLayer
 *
 * @package UIP
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'UIP_GTM_ID' ) ) {
    // Default GTM Container ID for UIP Real Estate
    define( 'UIP_GTM_ID', 'GTM-TGFWF786' );
}

/**
 * Get GTM Container ID (allows filtering)
 *
 * @return string
 */
function uip_get_gtm_id() {
    return apply_filters( 'uip_gtm_id', UIP_GTM_ID );
}

/**
 * Seed dataLayer and inject GTM script into <head>
 */
function uip_inject_gtm_head() {
    $gtm_id = uip_get_gtm_id();
    if ( empty( $gtm_id ) ) {
        return;
    }

    // Determine current language
    $language = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : get_locale();

    // Determine page context
    $post_id   = get_queried_object_id();
    $post_type = get_post_type( $post_id ) ? get_post_type( $post_id ) : 'other';
    $title     = is_singular() ? get_the_title( $post_id ) : wp_get_document_title();

    $page_data = array(
        'page_language' => $language,
        'post_type'     => $post_type,
        'post_id'       => $post_id,
        'page_title'    => esc_js( $title ),
    );

    if ( 'empreendimentos' === $post_type || 'imoveis' === $post_type ) {
        $page_data['development_name'] = esc_js( get_the_title( $post_id ) );
    }
    ?>
<!-- Google Tag Manager (UIP Measurement) -->
<script>
window.dataLayer = window.dataLayer || [];
window.dataLayer.push({
    'event': 'uip_page_context',
    <?php foreach ( $page_data as $key => $val ) : ?>
    '<?php echo esc_js( $key ); ?>': '<?php echo esc_js( $val ); ?>',
    <?php endforeach; ?>
});
(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( $gtm_id ); ?>');
</script>
<!-- End Google Tag Manager -->
    <?php
}
add_action( 'wp_head', 'uip_inject_gtm_head', 1 );

/**
 * Inject GTM noscript iframe immediately after <body> opening tag
 */
function uip_inject_gtm_body() {
    $gtm_id = uip_get_gtm_id();
    if ( empty( $gtm_id ) ) {
        return;
    }
    ?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( $gtm_id ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
    <?php
}
add_action( 'wp_body_open', 'uip_inject_gtm_body', 1 );

/**
 * Enqueue tracking JS listener script
 */
function uip_enqueue_tracking_scripts() {
    $file_path = UIP_THEME_DIR . '/js/uip-tracking.js';
    if ( file_exists( $file_path ) ) {
        $version = filemtime( $file_path );
        wp_enqueue_script(
            'uip-tracking',
            UIP_THEME_URI . '/js/uip-tracking.js',
            array( 'jquery' ),
            $version,
            true
        );

        $development = is_singular( 'empreendimentos' ) ? get_the_title() : '';
        wp_localize_script(
            'uip-tracking',
            'uipTrackingConfig',
            array(
                'development' => $development,
                'language'    => function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : 'pt',
                'isSingle'    => is_singular(),
                'postType'    => get_post_type(),
            )
        );
    }
}
add_action( 'wp_enqueue_scripts', 'uip_enqueue_tracking_scripts', 100 );
