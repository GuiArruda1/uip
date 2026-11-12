/**
 * UIP Real Estate — Tracking & DataLayer Bridge
 * Captures Elementor Pro forms, brochure downloads, direct contacts, and user interactions
 * Ensures 100% compatibility with Google Ads, GA4, and Meta Pixel.
 */
(function($) {
    'use strict';

    window.dataLayer = window.dataLayer || [];

    var config = window.uipTrackingConfig || {
        development: '',
        language: 'pt',
        isSingle: false,
        postType: ''
    };

    /**
     * Helper to log in console when debug mode is enabled
     */
    function logEvent(name, payload) {
        if (window.uipTrackingDebug || window.location.search.indexOf('gtm_debug') !== -1) {
            console.log('[UIP Tracking] ' + name, payload);
        }
    }

    /**
     * Safe dataLayer push helper
     */
    function pushDataLayer(data) {
        window.dataLayer.push(data);
        logEvent(data.event || 'push', data);
    }

    $(document).ready(function() {

        /**
         * 1. Elementor Pro Form Submission Listener
         */
        $(document).on('submit_success', function(event, response) {
            var $form = $(event.target);
            var formName = $form.attr('name') || $form.attr('aria-label') || 'Elementor Form';
            var formId = $form.find('input[name="form_id"]').val() || $form.attr('id') || 'unknown';
            
            // Check development name from field or page config
            var postTitle = $form.find('input[name="form_fields[post_title]"]').val();
            var development = postTitle || config.development || 'UIP Real Estate';

            var isBrochure = false;
            var lowerName = formName.toLowerCase();
            var submitText = $form.find('button[type="submit"]').text().toLowerCase();

            if (lowerName.indexOf('brochur') !== -1 || lowerName.indexOf('empreendimento') !== -1 || submitText.indexOf('brochur') !== -1) {
                isBrochure = true;
            }

            // A. Base Lead Conversion Event (Google Ads Conversion 1: SclxCNyh_pgcEMCX6IhA)
            pushDataLayer({
                event: 'form_submit',
                form_id: formId,
                form_name: formName,
                development: development,
                language: config.language
            });

            // Also push gads_form_lead alias for 100% GTM trigger coverage
            pushDataLayer({
                event: 'gads_form_lead',
                form_id: formId,
                form_name: formName,
                development: development
            });

            // B. If Brochure Request: Trigger Brochure Conversions (Google Ads 2: msUPCPjh_pgcEMCX6IhA & Meta Pixel Lead)
            if (isBrochure) {
                pushDataLayer({
                    event: 'brochura_geral',
                    form_id: formId,
                    form_name: formName,
                    development: development,
                    form_type: 'brochura',
                    language: config.language
                });

                // GA4 Event for Brochure
                pushDataLayer({
                    event: 'download_brochura_geral',
                    development: development
                });
            }

            // C. Legacy GA4 Contact Form Compatibility (GTM-TWKSH78)
            pushDataLayer({
                event: 'form_contacto',
                form_id: formId,
                development: development
            });

            // D. Standard Recommended GA4 Lead Event
            pushDataLayer({
                event: 'generate_lead',
                form_id: formId,
                form_name: formName,
                development: development,
                value: isBrochure ? 1 : 0.5,
                currency: 'EUR'
            });
        });

        /**
         * 2. Brochure Download Button Click Listener
         */
        $(document).on('click', 'button, a, .elementor-button', function(e) {
            var $btn = $(this);
            var text = ($btn.text() || '').trim();
            var lower = text.toLowerCase();

            if (lower.indexOf('brochura') !== -1 || lower.indexOf('brochure') !== -1) {
                pushDataLayer({
                    event: 'download_brochura_geral_click',
                    button_text: text,
                    development: config.development || 'UIP Real Estate'
                });
            } else if (lower.indexOf('visita virtual') !== -1 || lower.indexOf('virtual tour') !== -1) {
                var eventName = 'visita_virtual_click';
                if (window.location.pathname.indexOf('/villas') !== -1) {
                    eventName = 'visita_virtual_garden_villas';
                } else if (window.location.pathname.indexOf('/apartamentos') !== -1) {
                    eventName = 'visita_virtual_ap_residences';
                }
                pushDataLayer({
                    event: eventName,
                    button_text: text,
                    development: config.development || 'UIP Real Estate'
                });
            }
        });

        /**
         * 3. Direct Contact Click Handlers (Phone, Email, WhatsApp, Maps)
         */
        $(document).on('click', 'a[href^="tel:"], a[href^="mailto:"], a[href*="whatsapp.com"], a[href*="wa.me"], a[href*="maps.google"], a[href*="goo.gl/maps"]', function(e) {
            var href = $(this).attr('href') || '';
            var text = ($(this).text() || '').trim();

            if (href.indexOf('tel:') === 0) {
                pushDataLayer({
                    event: 'click_to_call',
                    event_category: 'contact',
                    link_url: href,
                    link_text: text
                });
                pushDataLayer({
                    event: 'contacto',
                    contact_method: 'phone'
                });
            } else if (href.indexOf('mailto:') === 0) {
                pushDataLayer({
                    event: 'click_to_email',
                    event_category: 'contact',
                    link_url: href,
                    link_text: text
                });
                pushDataLayer({
                    event: 'contacto',
                    contact_method: 'email'
                });
            } else if (href.indexOf('whatsapp') !== -1 || href.indexOf('wa.me') !== -1) {
                pushDataLayer({
                    event: 'click_to_whatsapp',
                    event_category: 'contact',
                    link_url: href
                });
                pushDataLayer({
                    event: 'contacto',
                    contact_method: 'whatsapp'
                });
            } else if (href.indexOf('maps') !== -1) {
                pushDataLayer({
                    event: 'google_maps',
                    link_url: href
                });
            }
        });

        /**
         * 4. Social Media Outbound Click Listener
         */
        $(document).on('click', 'a[href*="facebook.com"], a[href*="instagram.com"], a[href*="linkedin.com"], a[href*="youtube.com"]', function() {
            var href = $(this).attr('href');
            pushDataLayer({
                event: 'social_media_click',
                destination_url: href
            });
        });

        /**
         * 5. Polylang Language Switcher Listener
         */
        $(document).on('click', '.pll-switcher-item a, .lang-item a', function() {
            var targetLang = $(this).attr('lang') || $(this).text().trim().toLowerCase();
            pushDataLayer({
                event: 'change_language',
                to_language: targetLang,
                from_language: config.language
            });
        });

    });

})(jQuery);

