<?php

function certishopping_args_shortcode_to_html($atts)
{
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    if (!isset($settings['partner_id']) OR empty($settings['partner_id'])) {
        return null;
    }
    $atts = shortcode_atts(array(
        'data-partner-id' => $settings['partner_id'],
        'data-badge-extra-class' => 'csc-reviews-modal-float-fixed-in-mobile',
        // 'data-badge-position' => 'left',
        // 'data-badge-style' => 'horizontal',
        'data-lang'=>'fr',
    ), $atts);

    $html_args = '';
    foreach ($atts as $key => $value) {
        $html_args .= ' ' . esc_html__($key) . '=' . '"' . esc_html__($value) . '"';
    }
    return $html_args;
}

function wc_certishopping_widget_slider_reviews_fnc($atts, $content = "")
{
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    if (!isset($settings['partner_id']) OR empty($settings['partner_id'])) {
        return '<div></div>';
    }
    $html_args = certishopping_args_shortcode_to_html($atts);
    return '<div certishopping-widget-reviews ' . $html_args . ' ></div>';
}

add_shortcode('certishopping_widget_slider_reviews', 'wc_certishopping_widget_slider_reviews_fnc');


function wc_certishopping_widget_modal_reviews_fnc($atts, $content = "")
{
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    if (!isset($settings['partner_id']) OR empty($settings['partner_id'])) {
        return '<div></div>';
    }
    $html_args = certishopping_args_shortcode_to_html($atts);

    return '<div certishopping-widget-modal-reviews  ' . $html_args . ' ></div>';
}

add_shortcode('certishopping_widget_modal_reviews', 'wc_certishopping_widget_modal_reviews_fnc');


function wc_certishopping_widget_reviews_modal_hook_footer()
{
    echo do_shortcode('[certishopping_widget_modal_reviews]');
   // $product_data = wc_certishopping_get_product_data($product);
    // $product = wc_get_product();
    // $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    //if (isset($settings['widget_reviews_modal']) && $settings['widget_reviews_modal']) {
        // echo do_shortcode('[certishopping_widget_modal_reviews
		// 	data-badge-position=""
        //     data-badge-style=""
        //     data-lang="' . (($settings['language']) ? $settings['language'] : 'fr') . '"
        // 	data-badge-extra-class="csc-reviews-modal-float-fixed-in-mobile" ]');
  //  }
}

add_action('wp_footer', 'wc_certishopping_widget_reviews_modal_hook_footer');