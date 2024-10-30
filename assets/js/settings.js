jQuery(document).ready(function() {
    var hide_tabname = function(duration) {
        if (jQuery('#certishopping_settings_form .certishopping-widget-location').val() == 'tab') {
            jQuery('#certishopping_settings_form .certishopping-widget-tab-name').show(duration);
        } else {
            jQuery('#certishopping_settings_form .certishopping-widget-tab-name').hide(duration);
        }
    };

    var hide_other_explanation = function(duration) {
        if (jQuery('#certishopping_settings_form .certishopping-widget-location').val() == 'other') {
            jQuery('#certishopping_settings_form .certishopping-widget-location-other-explain').show(duration);
        } else {
            jQuery('#certishopping_settings_form .certishopping-widget-location-other-explain').hide(duration);
        }
    };
    var hide_shortcode_explanation = function(duration) {
        if (jQuery('#certishopping_settings_form .certishopping-star-rating-location').val() == 'shortcode') {
            jQuery('#certishopping_settings_form .certishopping-widget-location-shortcode').show(duration);
        } else {
            jQuery('#certishopping_settings_form .certishopping-widget-location-shortcode').hide(duration);
        }
    };
    var hide_shortcode_loop_explanation = function(duration) {
        if (jQuery('#certishopping_settings_form .certishopping-rating-loop-location').val() == 'shortcode') {
            jQuery('#certishopping_settings_form .certishopping-rating-loop-location-shortcode').show(duration);
        } else {
            jQuery('#certishopping_settings_form .certishopping-rating-loop-location-shortcode').hide(duration);
        }
    };
    hide_tabname(0);
    hide_other_explanation(0);
    hide_shortcode_explanation(0);
    hide_shortcode_loop_explanation(0);
    jQuery('#certishopping_settings_form .certishopping-widget-location').change(function() {
        hide_tabname(1000);
        hide_other_explanation(1000);
    });
    jQuery('#certishopping_settings_form .certishopping-star-rating-location').change(function() {
        hide_shortcode_explanation(1000);
    });
    jQuery('#certishopping_settings_form .certishopping-rating-loop-location').change(function() {
        hide_shortcode_loop_explanation(1000);
    });

    jQuery('#certishopping-export-reviews').click(function() {
        document.getElementById('export_reviews_submit').click();
    });
});