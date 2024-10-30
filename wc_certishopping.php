<?php
/**
 *
 *
 * Plugin Name: Certishopping Social Reviews for Woocommerce
 * Plugin URI: http://www.certishopping.com?utm_source=certishopping_plugin_woocommerce&utm_medium=plugin_page_link&utm_campaign=woocommerce_plugin_page_link
 * Description: Certishopping Social Reviews helps Woocommerce store owners generate a ton of reviews for their products. Certishopping is the only solution which makes it easy to share your reviews automatically to your social networks to gain a boost in traffic and an increase in sales
 * Version: 4.2.9
 * Author: Certishopping  <support@certishopping.com>
 * Author URI: https://business.certishopping.com/
 * Text Domain: Certishopping
 * Copyright: © 2021 Certishopping
 * Domain Path: /languages/
 */

register_activation_hook(__FILE__, 'wc_certishopping_activation');
register_uninstall_hook(__FILE__, 'wc_certishopping_uninstall');
register_deactivation_hook(__FILE__, 'wc_certishopping_deactivate');
add_action('plugins_loaded', 'wc_certishopping_init');
add_action('init', 'wc_certishopping_redirect');
add_action('wp_enqueue_scripts', 'wc_certishopping_add_custom_styles');

function wc_certishopping_init()
{
        $is_admin = is_admin();
        if ($is_admin)
        {
            if (isset($_GET['download_exported_reviews'])) {
                if(current_user_can('manage_options')) {
                    require('classes/class-wc-certishopping-export-reviews.php');	
                    $export = new Certishoping_Review_Export();
                    list($file, $errors) = $export->exportReviews();	
                    if(is_null($errors)) {
                        ytdbg($file,'Reviews Export Success:');
                        $export->downloadReviewToBrowser($file);	
                    } else {
                        ytdbg($errors,'Reviews Export Fail:');
                    }
                }
                exit;
            }
            include (plugin_dir_path(__FILE__) . 'templates/wc-certishopping-settings.php');
            include (plugin_dir_path(__FILE__) . 'lib/certishopping-api/Certishopping.php');
            add_action('admin_menu', 'wc_certishopping_admin_settings');
        }
        else
        {
            include (plugin_dir_path(__FILE__) . 'templates/wc-certishopping-settings.php');
            include (plugin_dir_path(__FILE__) . 'lib/certishopping-api/Certishopping.php');
        }
        $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
        if (!empty($certishopping_settings['app_key']) && wc_certishopping_compatible())
        {
            if (!$is_admin)
            {
                add_action('wp_head', 'wc_certishopping_load_js');
                add_action('template_redirect', 'wc_certishopping_front_end_init');
            }
        }
        require ('wc-certishopping-shortcodes.php');
      //  activate_order_status();
}

function wc_certishopping_redirect()
{
        if (get_option('wc_certishopping_just_installed', false))
        {
            delete_option('wc_certishopping_just_installed');
            wp_redirect(((is_ssl() || force_ssl_admin() || force_ssl_login()) ? str_replace('http:', 'https:', admin_url('admin.php?page=woocommerce-certishopping-settings-page')) : str_replace('https:', 'http:', admin_url('admin.php?page=woocommerce-certishopping-settings-page'))));
            exit;
        }
}
function wc_certishopping_admin_settings()
{
        add_action('admin_enqueue_scripts', 'wc_certishopping_admin_styles');
        $page = add_menu_page('Certishopping', 'Certishopping', 'manage_options', 'woocommerce-certishopping-settings-page', 'wc_display_certishopping_admin_page', 'none', null);
}
function wc_certishopping_front_end_init()
{
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    add_action('woocommerce_thankyou', 'wc_certishopping_conversion_track');
   
        if (is_product())
        {

            $widget_location = $settings['widget_location'];
            
            if ($settings['disable_native_review_system'])
            {
                add_filter('comments_open', 'wc_certishopping_remove_native_review_system', null, 2);
            }
            if ($widget_location == 'footer')
            {
                add_action('woocommerce_after_single_product', 'wc_certishopping_widget_hook', 10);
            }
            elseif ($widget_location == 'tab')
            {
                add_action('woocommerce_product_tabs', 'wc_certishopping_show_widget_in_tab');
            }
            elseif($widget_location == 'other'){
                add_action('','');
                add_shortcode('certishopping_show_product_widget', 'wc_certishopping_widget_shortcode');

            }
            if (array_key_exists('star_rating_location', $settings)){
                    $star_rating_location = $settings['star_rating_location'];
                    if($star_rating_location == "single_product"){
                        add_action('woocommerce_single_product_summary', 'wc_certishopping_rating_hook', 7);
                        wp_enqueue_style('certishoppingSideBootomLineStylesheet', plugins_url('assets/css/bottom-line.css', __FILE__));

                    }elseif($star_rating_location == "shortcode"){
                        add_action('','');
                        add_shortcode('certishopping_star_rating', 'wc_certishopping_rating_short_code');
                        wp_enqueue_style('certishoppingSideBootomLineStylesheet', plugins_url('assets/css/bottom-line.css', __FILE__));
                    }
            }
           
        }
            add_action('woocommerce_after_shop_loop_item', 'wc_certishopping_show_buttomline_catalog', 7);
            wp_enqueue_style('certishoppingSideBootomLineStylesheet', plugins_url('assets/css/bottom-line.css', __FILE__));
            // add_action('woocommerce_after_shop_loop_item', 'wc_certishopping_show_buttomline_catalog', 7);
         
        

}

function wc_certishopping_add_custom_styles()
{
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    if (isset($settings['custom_css']) && !empty($settings['custom_css']))
    {
        wp_enqueue_style('certishoppingSideBootomLineStylesheet', plugins_url('assets/css/bottom-line.css', __FILE__));
        wp_add_inline_style('certishoppingSideBootomLineStylesheet', $settings['custom_css']);
    }
}

function wc_certishopping_activation()
{
    if (current_user_can('activate_plugins'))
    {
        update_option('wc_certishopping_just_installed', true);
       
        if(isset($_REQUEST['plugin']) && !empty($_REQUEST['plugin'])){
         $plugin = $_REQUEST['plugin'];
        }
        check_admin_referer("activate-plugin_{$plugin}");
        $default_settings = get_option('certishopping_settings', false);
        if (!is_array($default_settings))
        {
            add_option('certishopping_settings', wc_certishopping_get_degault_settings());
        }
        update_option('native_star_ratings_enabled', get_option('woocommerce_enable_review_rating'));
        update_option('woocommerce_enable_review_rating', 'no');
    }
}

function wc_certishopping_uninstall()
{
    if (current_user_can('activate_plugins') && __FILE__ == WP_UNINSTALL_PLUGIN)
    {
        check_admin_referer('bulk-plugins');
        delete_option('certishopping_settings');
    }
}
function wc_certishopping_load_js()
{
    if( class_exists('woocommerce') ) 
    {
        $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
        if (!isset($settings['partner_id']) OR empty($settings['partner_id'])) {
            return null;
        }
        $partner_id=$settings['partner_id'];
        $url = "https://certishopping.com/api/widget/v8/javascript/widgetv8.min.js?partner_id=".$partner_id."&platform=woocommerce"
        ?>
        <script src="<?php echo $url;?>"></script>
        <?php
    }
}



function wc_certishopping_get_product_data($product)
{
    $product_data = array();
    $order = new WC_Order();
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $product_data['app_key'] = $settings['app_key'];
    $product_data['shop_domain'] = wc_certishopping_get_shop_domain();
    $product_data['url'] = get_permalink($product->get_id());
    $product_data['lang'] = $settings['language'];
    $product_data['partner_id'] = $settings['partner_id'];
    $product_data['name'] = $product->get_name();
    $certishoppingApiObject = new Certishopping( $product_data['app_key'], $settings['app_key']);
    $product_data['id'] =$product->get_id();
    $product_data['title'] = $product->get_title();
    $product_data['image-url'] = wc_certishopping_get_product_image_url($product->get_id());

        if ($product->get_sku()){
            $product_data['sku'] = $product->get_sku();
        }
        else
        {
            $product_data['sku'] = $product->get_id();
        }
        $specs_data = array();
        if ($product->get_sku())
        {
            $specs_data['external_sku'] = $product->get_sku();
        }
        else
        {
            $specs_data['external_sku'] =$product->get_id();
        }
        if ($product->get_attribute('upc'))
        {
            $specs_data['upc'] = $product->get_attribute('upc');
        }
        if ($product->get_attribute('isbn'))
        {
            $specs_data['isbn'] = $product->get_attribute('isbn');
        }
        if ($product->get_attribute('brand'))
        {
            $specs_data['brand'] = $product->get_attribute('brand');
        }
        if ($product->get_attribute('mpn'))
        {
            $specs_data['mpn'] = $product->get_attribute('mpn');
        }
        if (!empty($specs_data))
        {
            $product_data['specs'] = $product->get_sku();
        }
    $product_data['sku'] =(isset($specs_data['external_sku']) && $specs_data['external_sku']) ? $specs_data['external_sku'] :(string) $product['product_id'];
    $permalink = get_permalink( $product->get_id() );
    $product_data['app_key'] = $settings['app_key'];
    $product_data['url'] = get_permalink($product->get_id());
    $product_data['id_link']=  $permalink . '#product';
    $product_data['description'] = preg_replace('/(\[.*\])/', '', wp_strip_all_tags($product->get_description()));
    $product_data['name'] = $product->get_name();
    $certishoppingApiObject = new Certishopping( $product_data['app_key'], $settings['app_key']);
    $product_data['id'] =$product->get_id();
    $product_data['title'] = $product->get_title();
    $product_data['image-url'] = wc_certishopping_get_product_image_url($product->get_id());
    //if (isset($product_data['sku']) & !empty($product_data['sku'])){

        $reviews = $certishoppingApiObject->getReviewProduct($product_data['sku'],$product_data['id'], $product_data['partner_id']);
        $list_reviews= json_decode(json_encode( $reviews));
        $product_data['reviews'] = $list_reviews;
        //$currency = wc_certishopping_get_order_currency($order);
        $currency = get_woocommerce_currency();
        $product_data['price'] = $product->get_price();
        $product_data['devise'] = $currency;
             
   // }
    return $product_data;
}
/* show buttom line in single product*/

function wc_certishopping_show_buttomline()
{
    $product = wc_get_product();
        $product_data = wc_certishopping_get_product_data($product);
     $html= "<div class='certishopping bottomLine' 
                    certishopping-widget-product-stars
	   				data-product-id='" . esc_attr($product_data['id']) . "'
	   				data-product-sku='" . esc_attr($product_data['sku']) . "'
	   				data-partner-id='" . esc_attr($product_data['partner_id']) . "'
                    data-lang='" . esc_attr($product_data['lang']) . "'
                    data-product-name='" . esc_attr($product_data['name']) . "'
                    data-price='" . esc_attr($product_data['price']) . "'
                    data-devise='" . esc_attr($product_data['devise']) . "'
                    data-product-url='" . esc_attr($product_data['url']) . "'
                    data-img-url='" . esc_attr($product_data['image-url']) . "'
                       >
           </div>";
           return $html;
        
}
function wc_certishopping_rating_short_code(){
    $content = wc_certishopping_show_buttomline();
    return $content;
    
}
function wc_certishopping_rating_hook(){
    $content = wc_certishopping_show_buttomline();
    echo $content;
}

function wc_certishopping_show_widget(){
    $product = wc_get_product();
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $product_data = wc_certishopping_get_product_data($product);
    $html = "<div certishopping-widget-reviews-product id='certishopping-widget-reviews-product'
     data-partner-id='" . esc_attr($settings['partner_id']) . "'
     data-product-id='" . esc_attr($product_data['id']) . "'
	 data-product-sku='" . esc_attr($product_data['sku']) . "'
     data-lang='" . esc_attr($product_data['lang']) . "' 
     data-product-name='" . esc_attr($product_data['name']) . "'
     data-price='" . esc_attr($product_data['price']) . "'
     data-devise='" . esc_attr($product_data['devise']) . "'
     data-product-url='" . esc_attr($product_data['url']) . "'
     data-img-url='" . esc_attr($product_data['image-url']) . "'
     ></div>";
     return $html;
}
function wc_certishopping_tructured_data(){
    $product = wc_get_product();
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $product_data = wc_certishopping_get_product_data($product);
    $list_reviews =  $product_data["reviews"];
    if (isset($list_reviews) &!empty($list_reviews) ){
     $first_review = $list_reviews[0];
         $product_data['get_total_rating'] =$first_review->get_total_rating;
         $product_data['sku '] = $first_review->sku;
         $product_data['count_reviews_product'] = $first_review->count_reviews_product;
         $reviews_rate  = $first_review->reviews;
         if (count($reviews_rate) != 0) {
             $product_data['rate'] = $reviews_rate[0]->rate;
             $product_data['first_name'] = $reviews_rate[0]->first_name;
             $product_data['description'] =$reviews_rate[0]->description;
             $product_data['date_humanize'] =$reviews_rate[0]->date_humanize;
         }
         $htmlRSJson = "";
         if($product_data["get_total_rating"]>0 && $product_data["count_reviews_product"]>0 ){
             if(!empty( $product_data)){
                     $htmlRSJson =  '<script type="application/ld+json">';
                     $htmlRSJson .=  '{';
                         $htmlRSJson .=  '"@context": "http://schema.org/",';
                         $htmlRSJson .=  '  "@type": "Product",';
                         $htmlRSJson .=  '   "@id":"' . esc_attr($product_data['id_link']) . '",';
                         $htmlRSJson .=  '   "sku":"' . esc_attr($product_data['sku']) . '",';
                         $htmlRSJson .=  ' "description": "' .   esc_attr($product_data['description']). '",';
                         $htmlRSJson .=  ' "image":"' .esc_attr($product_data['image-url']) . '",';
                         $htmlRSJson .=  '  "name": "' . esc_attr($product_data['name']). '",';
                         $htmlRSJson .=  '  "url": "' .esc_attr($product_data['url']) . '" ,';
                 
                         $htmlRSJson .=  ' "aggregateRating":';
                         $htmlRSJson .=  '{';
                             $htmlRSJson .=     ' "@type": "AggregateRating",';
                             $htmlRSJson .=         ' "ratingValue": "' . esc_attr($product_data["get_total_rating"]) . '",';
                             $htmlRSJson .=            '"reviewCount": "' . esc_attr($product_data["count_reviews_product"]) . '"';
                             $htmlRSJson .=      '  },';
                             $htmlRSJson .= '"offers":' ;
                             
                             $htmlRSJson .=  '{';
                                 $htmlRSJson .=   '"@type": "Offer",';
                                 $htmlRSJson .=  '"availability": "https://schema.org/InStock",';
                                 $htmlRSJson .= ' "price": "' . esc_attr($product_data['price']) . '",';
                                 $htmlRSJson .=   '"priceCurrency": "' . esc_attr($product_data['devise']) . '"';
                                 $htmlRSJson .= '},';
                 
                 
                             $htmlRSJson .=      '"review":' ;
                             $htmlRSJson .=   '{';
                                 $htmlRSJson .=          '"@type": "Review",';
                                 $htmlRSJson .=           '"datePublished":"' . esc_attr($product_data["date_humanize"]) . '",';
                                 $htmlRSJson .=          ' "author":';
                                 $htmlRSJson .=    ' {';
                                     $htmlRSJson .=             ' "@type": "Thing",';
                                     $htmlRSJson .=             ' "name": "' . esc_attr($product_data["first_name"]) . '"';
                                     $htmlRSJson .=        ' },';
                                     $htmlRSJson .=       ' "reviewRating":';
                                     $htmlRSJson .=   '{';
                                         $htmlRSJson .=            '"@type": "Rating",';
                                         $htmlRSJson .=          ' "ratingValue":"' . esc_attr($product_data["rate"]) . '",';            
                                         $htmlRSJson .=         ' "description":"' . esc_attr($product_data["description"]) . '"';
                                         $htmlRSJson .=      '}';
                                         $htmlRSJson .=   '}';
                                         $htmlRSJson .= '}';
                                         $htmlRSJson .= ' </script> ' ;
                                        //  printf($htmlRSJson); 
                                   
                                     
             }
         }
         return $htmlRSJson;
     }
}
function wc_certishopping_show_widget_in_tab($tabs)
{   
    $product = wc_get_product();
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
        $tabs['certishopping_widget'] = array(
            'title' => $settings['widget_tab_name'],
            'priority' => 50,
            'callback' => 'wc_certishopping_widget_hook'
        );
    return $tabs;
}

function wc_certishopping_widget_shortcode(){
    $content = wc_certishopping_show_widget();
    $data = wc_certishopping_tructured_data();
    $html = $content.$data;
    return $html;
}

function wc_certishopping_widget_hook(){
    $content = wc_certishopping_show_widget();
    $data = wc_certishopping_tructured_data();
    echo $content;
    echo $data;
}


function wc_certishopping_get_product_data_catalog($product)
{
    $product_data = array();
    // $order = new WC_Order();
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $product_data['app_key'] = $settings['app_key'];
    $product_data['shop_domain'] = wc_certishopping_get_shop_domain();
    $product_data['lang'] = $settings['language'];
    $product_data['partner_id'] = $settings['partner_id'];
    $certishoppingApiObject = new Certishopping( $product_data['app_key'], $settings['app_key']);
    $product_data['id'] =$product->get_id();

        if ($product->get_sku()){
            $product_data['sku'] = $product->get_sku();
        }
        else
        {
            $product_data['sku'] = $product->get_id();
        }
        $specs_data = array();
        if ($product->get_sku())
        {
            $specs_data['external_sku'] = $product->get_sku();
        }
        else
        {
            $specs_data['external_sku'] =$product->get_id();
        }
        if ($product->get_attribute('upc'))
        {
            $specs_data['upc'] = $product->get_attribute('upc');
        }
        if ($product->get_attribute('isbn'))
        {
            $specs_data['isbn'] = $product->get_attribute('isbn');
        }
        if ($product->get_attribute('brand'))
        {
            $specs_data['brand'] = $product->get_attribute('brand');
        }
        if ($product->get_attribute('mpn'))
        {
            $specs_data['mpn'] = $product->get_attribute('mpn');
        }
        if (!empty($specs_data))
        {
            $product_data['specs'] = $product->get_sku();
        }
    $product_data['sku'] =(isset($specs_data['external_sku']) && $specs_data['external_sku']) ? $specs_data['external_sku'] :(string) $product['product_id'];
    $product_data['app_key'] = $settings['app_key'];
    $product_data['url'] = get_permalink($product->get_id());
    $product_data['id'] =$product->get_id();
  
    return $product_data;
}
/* show buttom line in catalog*/
function wc_certishopping_show_buttomline_catalog()
{
    // $product = wc_get_product();
    // $show_bottom_line = is_product() ? $product->get_reviews_allowed() == true : true;
	// if($show_bottom_line) {
        global $product;
        $show_bottom_line = is_product() ? $product->get_reviews_allowed() == true : true;
        if($show_bottom_line) {
        $product_data = wc_certishopping_get_product_data_catalog($product);
        echo"<div class='certishopping bottomLine' 
                        certishopping-widget-product-stars
                        data-position='catalog'
                        data-product-id='" .esc_attr($product_data['id']) . "'
                        data-product-sku='" . esc_attr($product_data['sku']) . "'
                        data-partner-id='" . esc_attr($product_data['partner_id']) . "'
                        data-url='" . esc_url($product_data['url']) . "' 
                        data-lang='" . esc_attr($product_data['lang']) . "'>
                    </div>";
 
    }
    
}

function wc_certishopping_get_shop_domain()
{
    return parse_url(get_bloginfo('url') , PHP_URL_HOST);
}
function wc_certishopping_remove_native_review_system($open, $post_id)
{
    if (get_post_type($post_id) == 'product')
    {
        return false;
    }
    return $open;
}

function wc_certishopping_get_product_image_url($product_id)
{
    $url = wp_get_attachment_url(get_post_thumbnail_id($product_id));

    return $url ? $url : null;

} 
function wc_certishopping_get_single_map_data($order_id)
{
    global $woocommerce, $post;
    $order = new WC_Order($order_id);
    $order_new = null;
    $order_new = array();
    $past_orders['order_id'] = $order_id;
    $order_number = $order->get_order_number();
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $orderStatus = $order->get_status();
    $statusChoosen = $settings['command_status'];
    $list_status= wc_certishopping_getOrderStatus();
    $past_orders['status'] = str_replace('wc-', '',$orderStatus);
    $site_id=$settings['partner_id'];
    $data['order_date'] = date('Y-m-d H:i:s', strtotime($order->get_date_created()));
    $products=[];
    foreach ($order->get_items() as $product)
    {
        $_product = wc_get_product( (string)$product['product_id']);
        $product_data = array();
        if (is_object($_product))
        {
            
            $product_data['url'] = get_permalink((int)$product['product_id']);
            $product_data['name'] = $product['name'];
            $product_data['image'] = wc_certishopping_get_product_image_url($product['product_id']);
            $product_data['price']= $_product->get_price();
            $product_data['description'] = preg_replace('/(\[.*\])/', '', wp_strip_all_tags($_product->get_description()));
          $specs_data = array();
            if ($_product->get_sku())
            {
                $specs_data['external_sku'] = $_product->get_sku();
            }
           $product_data['sku'] = $_product->get_sku();
            if ($_product->get_attribute('upc'))
            {
                $specs_data['upc'] = $_product->get_attribute('upc');
           }
            if ($_product->get_attribute('isbn'))
            {
                $specs_data['isbn'] = $_product->get_attribute('isbn');
            }
            if ($_product->get_attribute('brand'))
            {
                $specs_data['brand'] = $_product->get_attribute('brand');
            }
            if ($_product->get_attribute('mpn'))
            {
                $specs_data['mpn'] = $_product->get_attribute('mpn');
            }
            if (!empty($specs_data))
            {
                $product_data['specs'] = $specs_data;
            }
            $product_data['specs'] = $specs_data;
        }
        if(count($product_data) > 0){
            $product_new = array(
                //'sku' => $specs_data['external_sku'] ,  
               // 'sku'=> (isset($specs_data['external_sku']) && $specs_data['external_sku']) ? $specs_data['external_sku'] :(string) $product['product_id'],
               'sku'=> $specs_data['external_sku'],
               'product_id'=>$product['product_id'], 
               'url' => $product_data['url'],          
                'image' => (string)$product_data['image'],
                'name' => $product_data['name'],
                'price'=>$product_data['price'],
                'description' =>$product_data['description'],
                'currency'=>get_woocommerce_currency(),
            );
            $products[] = $product_new; 
        }
        
    }
    if ($order->get_billing_email() && !preg_match('/\d$/', $order->get_billing_email())) { $data['email'] = $order->get_billing_email(); } else { $data['email'] = null; }
    if ($order->get_billing_last_name()) { $data['last_name'] = $order->get_billing_last_name(); } else { $data['last_name'] = null; }
    if ($order->get_billing_first_name()) { $data['first_name'] = $order->get_billing_first_name(); } else {  $data['first_name'] = null; }
    if ($order->get_billing_country()) { $data['country'] = $order->get_billing_country(); } else { $data['country'] = null; }
    if ($order->get_billing_address_1()) { $data['address1'] = $order->get_billing_address_1(); } else { $data['address1'] = null; }
    if ($order->get_billing_address_2()) { $data['address2'] = $order->get_billing_address_2(); } else { $data['address2'] = null; }
    if ($order->get_billing_postcode()) { $data['postcode'] = $order->get_billing_postcode(); } else { $data['postcode'] = null; }
    if ($order->get_billing_city()) { $data['city'] = $order->get_billing_city(); } else { $data['city'] = null; }
    if ($order->get_billing_company()) { $data['company'] = $order->get_billing_company(); } else { $data['company'] = null; }

    $address =array(
      'country' =>$data['country'],
       'address1'=>$data['address1'],
       'address2'=>$data['address2'],
       'postcode'=>$data['postcode'],
       'city'=>$data['city'],
       'phone'=>$order->get_billing_phone(),
       'company'=>$data['company'],
       'date_add'=>$data['order_date'],

    );
    $list_id = array(1,2,3,4,5,6,7,8,9,10,11,12,13);
    $status_id = get_status_id($past_orders['status']);
    if($status_id == null){
        $status_id = max($list_id) + 1;
        array_push($list_id, $status_id);
    }
   
        $order_new = array(
            'status' => $status_id,
            'last_name' =>$data['last_name'],
            'first_name' => $data['first_name'],
            'reference' => (string)$order_number,
            'products' =>  $products ,
            'email' =>  $data['email'],
            'site_id'=>$site_id,
            'address'=>$address,
            'type'=>'submit',
        );

    return $order_new;
}

function wc_certishopping_get_past_orders($options = [])
{
    $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());

    $result = null;
    $args = array(
        'post_type' => 'shop_order',
        'posts_per_page' => (isset($options['posts_per_page'])) ? (int)$options['posts_per_page'] : -1
    );
    if (defined('WC_VERSION') && (version_compare(WC_VERSION, '2.2.0') >= 0))
    {
        $args['post_status'] = $certishopping_settings['command_status'];
    }
   
    else
    {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'shop_order_status',
                'field' => 'slug',
                'terms' => array(
                    'completed'
                ) ,
                'operator' => 'IN'
            )
        );
    }
    if (isset($options['order_ids']) && is_array($options['order_ids']))
    {
        $args['post__in'] = $options['order_ids'];
    }
    $query = new WP_Query($args);
    wp_reset_query();
    $orders = array();
    if ($query->have_posts())
    {
        
        while ($query->have_posts())
        {
            $query->the_post();
            $order = $query->post;
            $order_new = wc_certishopping_get_single_map_data($order->ID);
            if (!is_null($order_new))
            {
                $orders[] = $order_new;
            }            
        }
    }
    return $orders;
}

// function wc_certishopping_past_order_time_query($where = '')
// {
//     // posts in the last 30 days
//     $where .= " AND post_date > '" . date('Y-m-d', strtotime('-90 days')) . "'";
//     return $where;
// }
/* btn submit past order */ 
function wc_certishopping_send_past_orders()
{
    $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $access = $certishopping_settings['access'];
    $expired_at  = $certishopping_settings['expired_at'];
    $certishopping_api = new Certishopping($certishopping_settings['app_key'], $certishopping_settings['secret']);
    if(!$access ||  $certishopping_api->is_expired($expired_at)){
        $get_oauth_token_response = $certishopping_api->getOauthToken();
        if (!empty($get_oauth_token_response) && !empty($get_oauth_token_response['access'])){
            $certishopping_api->setToken($get_oauth_token_response['access']);
        }
    }else{
        $certishopping_api->setToken($access);
    }
    try {

        $past_orders = wc_certishopping_get_past_orders(['posts_per_page' => (int)$certishopping_settings['certishopping_order_total_to_send']]);
        if(count($past_orders ) > 0){
            $bulk_creation_purchases = $certishopping_api->Manualbulkrequest($past_orders);
            $is_success = ($bulk_creation_purchases && isset($bulk_creation_purchases->status) && ($bulk_creation_purchases->status == 'Processing all')) ? true : false;
            if ($is_success)
            {
                wc_certishopping_display_message('Past orders sent successfully', false);
            }
            else
            {
                wc_certishopping_display_message('An error occurred while sending orders. Please contact our support.', true);
            }
        }
      

    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}

function wc_certishopping_conversion_track($order_id)
{
    $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $order = new WC_Order($order_id);
    $currency = wc_certishopping_get_order_currency($order);

    $conversion_params = "app_key=" . $certishopping_settings['app_key'] . "&order_id=" . $order_id . "&order_amount=" . $order->get_total() . "&order_currency=" . $currency;
    $APP_KEY = $certishopping_settings['app_key'];
    $DATA = "certishoppingTrackConversionData = {orderId: " . $order_id . ", orderAmount: " . $order->get_total() . ", orderCurrency: '" . $currency . "'}";
    $DATA_SCRIPT = "<script>" . esc_html($DATA) . "</script>";
    $IMG = "<img 
   	src='https://api.certishopping.com/conversion_tracking.gif?$conversion_params'
	width='1'
	height='1'></img>";
    $NO_SCRIPT = "<noscript>" . esc_html($IMG) . "</noscript>";
}
function wc_certishopping_get_degault_settings()
{
    return array(
        'app_key' => '',
        'secret' => '',
        'partner_id' => '',
        'login_username' => '',
        'login_password' => '',
        'widget_location' => 'footer',
        'language' => 'fr',
        'widget_tab_name' => 'Reviews',
        'certishopping_language_as_site' => true,
        'show_submit_past_orders' => true,
        'certishopping_order_status' => array('wc-completed'),
        'command_status'=> array('wc-completed'),
        'disable_native_review_system' => true,
        'native_star_ratings_enabled' => 'no',
        'custom_css' => '',
        'star_rating_location'=> 'single_product',
        'certishopping_order_total_to_send' => 10,
        'auto_synchronize' => false ,
        'show_submit_products_list' => true,
    );
}
function wc_certishopping_admin_styles($hook)
{
    if ($hook == 'toplevel_page_woocommerce-certishopping-settings-page')
    {
        wp_enqueue_script('certishoppingSettingsJs', plugins_url('assets/js/settings.js', __FILE__) , array(
            'jquery-effects-core'
        ));
        wp_enqueue_style('certishoppingSettingsStylesheet', plugins_url('assets/css/certishopping.css', __FILE__));
    }
    wp_enqueue_style('certishoppingSideLogoStylesheet', plugins_url('assets/css/side-menu-logo.css', __FILE__));
}
function wc_certishopping_compatible()
{
    return version_compare(phpversion() , '5.2.0') >= 0 && function_exists('curl_init');
}
function wc_certishopping_deactivate()
{
    update_option('woocommerce_enable_review_rating', get_option('native_star_ratings_enabled'));
}

add_filter('woocommerce_tab_manager_integration_tab_allowed', 'wc_certishopping_disable_tab_manager_managment');

function wc_certishopping_disable_tab_manager_managment($allowed, $tab = null)
{
    if ($tab == 'certishopping_widget')
    {
        $allowed = false;
        return false;
    }
}
function wc_certishopping_get_order_currency($order)
{
    if (is_null($order) || !is_object($order))
    {
        return '';
    }
    if(method_exists($order,'get_currency')) {
		return $order->get_currency();
	}
	if(isset($order->order_custom_fields) && isset($order->order_custom_fields['_order_currency'])) {		
 		if(is_array($order->order_custom_fields['_order_currency'])) {
 			return $order->order_custom_fields['_order_currency'][0];
 		}	
	}
    return '';
}

function wc_certishopping_getOrderStatus()
{
    $resultat = array();
    if (function_exists('wc_get_order_statuses')) {
        $results = wc_get_order_statuses();
        foreach ($results as $key => $status) {
            $resultat[] = $status;
        }
        return $resultat;
    } else {
        global $wpdb;
        global $table_prefix;
        $query = "SELECT t.term_id AS orders_status_id,t.slug AS orders_status_name
            FROM " . $table_prefix . "terms AS t 
            LEFT JOIN " . $table_prefix . "term_taxonomy AS tt ON tt.term_id = t.term_id 
            WHERE tt.taxonomy IN ('shop_order_status')
            ORDER BY orders_status_id ASC";

        $myrows = $wpdb->get_results($query);
        if (!empty($myrows)) {
            foreach ($myrows as $res) {
                $resultat[$res->orders_status_id] = $res->orders_status_name;
            }
            return $resultat;
        }
    }
    return NULL;
}
function get_activated_status() {
    $output = array();
    $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $statusChoosen = $certishopping_settings['command_status'];
        if ( is_array( $statusChoosen ) && count( $statusChoosen ) > 0 ) {
            foreach ( $statusChoosen as $val ) {
                if ( strpos( $val, 'wc-' ) > -1 ) {
                    $val = substr( $val, 3 );
                }
                $output[] = $val;
            }
        }
        return $output;
}

function get_status_id($status) {
    $id = null;
    switch ($status) {
        case 'completed':
            $id = 5;
        break;
        case 'processing' :
            $id = 4;
        break;
        case 'cancelled' :
            $id = 3;
        break;
        case 'refunded' :
            $id = 2;
        break;
        case 'on-hold' :
            $id = 1;
        break;
        case 'pending' :
            $id = 6;
        break;
        case 'failed' :
            $id = 7;
        break;
        case 'lpc_delivered' :
            $id = 8;
        break;
        case 'lpc_transit' :
            $id = 9;
        break;
        case 'lpc_ready_to_ship' :
            $id = 10;
        break;
        case 'lpc_anomaly' :
            $id = 11;
        break;
        case 'lpc_partial_exp' :
            $id = 12;
        break;
        case 'checkout-draft' :
            $id = 13;
            break;
    }
    return $id;
}

// /*Change status */
// function activate_order_status() {
//     $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
//     $statusChoosen = $certishopping_settings['command_status'];
//     if(is_array($statusChoosen)){
//         foreach($statusChoosen as $status){
//             $status = str_replace('wc-', '',$status); 
//             add_action( "woocommerce_order_status_{$status}",  'wc_certishopping_woocommerce_order_status' , 10 );
//         }
//     }
  
   
// }
add_action( "woocommerce_order_status_changed",  'wc_certishopping_woocommerce_order_status' , 10 );
add_action( "woocommerce_new_order",  'wc_certishopping_woocommerce_order_status' , 10 );
function wc_certishopping_woocommerce_order_status($order_id)
{
    $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $access = $certishopping_settings['access'];
    $expired_at  = $certishopping_settings['expired_at'];
    $certishopping_api = new Certishopping($certishopping_settings['app_key'], $certishopping_settings['secret']);
    if(!$access ||  $certishopping_api->is_expired($expired_at)){
        $get_oauth_token_response = $certishopping_api->getOauthToken();
        if (!empty($get_oauth_token_response) && !empty($get_oauth_token_response['access'])){
            $certishopping_api->setToken($get_oauth_token_response['access']);
        }
    }else{
        $certishopping_api->setToken($access);
    }
    try {
        $past_order[]= wc_certishopping_get_single_map_data($order_id);
        $bulk_creation_purchases = $certishopping_api->Autobulkrequest($past_order);
        $is_success = ($bulk_creation_purchases && isset($bulk_creation_purchases->status) && ($bulk_creation_purchases->status == 'Processing all')) ? true : false;
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}

// Activer la traduction.
add_action('plugins_loaded', 'wc_certishopping_load_textdomain');
function wc_certishopping_load_textdomain()
{
    load_plugin_textdomain( 'woo-certishopping-reviews', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
function wc_certishopping_generateVariantsProduct($product){
    $weight_unit = get_option('woocommerce_weight_unit');
    if(   $product->is_type('variable')){
        $vars = $product->get_available_variations();
        $products_variant =[];
  
        foreach ( $product->get_available_variations() as $variation ) {
            $sku = $variation["sku"];
            $id =$variation["variation_id"];
            $wc_certi_variation_object = new WC_Product_Variation( $id );
            $attributes = $wc_certi_variation_object->get_attributes();
            $products_attributes = array();
            foreach ( $attributes as $attribute_taxonomy => $attribute_value ) {
                       $attribute_name = wc_attribute_label( $attribute_taxonomy );
                       $attribute=array(
                        "name"=>$attribute_name,
                        "value"=>$attribute_value,
                       );
                       $products_attributes[]=$attribute;
             }
            
            $permalink = $wc_certi_variation_object->get_permalink();
            $image_id = $wc_certi_variation_object->get_image_id();
            // Get the variation image URL
            $image_url = wp_get_attachment_image_url( $image_id, 'full' );
            // Get the variation regular price
            $get_regular_prices = $wc_certi_variation_object->get_regular_price();
            // Get the variation sale price
            $get_sale_prices = $wc_certi_variation_object->get_sale_price();
            // Get the variation price
            $get_prices = $wc_certi_variation_object->get_price();
            if ( $wc_certi_variation_object->is_on_sale() ) {
                $price=$get_regular_prices ;
                $sale_price =$get_sale_prices;
            }else{
                $price = $get_prices;
                $sale_price ='';
            }
            $description =  preg_replace('/(\[.*\])/', '', wp_strip_all_tags($variation["variation_description"]));
            $url = $variation["image"]["url"];
            //$color = $variation["attributes"]['attribute_color'];
            //$availability = wp_strip_all_tags($variation["availability_html"]);
            //$price = $variation["display_price"];
            $availability_stocks = $wc_certi_variation_object->get_stock_quantity();
            $availability ='';
            if($availability_stocks > 0){
                $availability ="in-stock";
            }elseif ($availability_stocks == 0) {
                $availability="out-of-stock";
            }
            $dimenssions = $variation["dimensions_html"];
            $weight = $variation["weight"];
            $link = get_permalink($id);
                $variants= array( 
                    'sku' => $sku,
                    'id'=> $id ,
                    'price' => $price,
                    'sale_price' => $sale_price,
                    'description' => $description,
                    'availability' => $availability,
                    'weight' =>$weight,
                    'image' => $image_url,
                    //'color' => $color,
                    //'size' => $dimenssions,
                    'weight_unit' => $weight_unit,
                    'link' => $permalink,
                    'attribute' => $products_attributes,
                );
                $products_variant[]=$variants;

        }
        return $products_variant;
    }

}

function wc_certishopping_generateProductObject($product){
    $weight_unit = get_option('woocommerce_weight_unit');
    $product_id   = $product->get_id();
    $product_name = $product->get_name();
    $description =  preg_replace('/(\[.*\])/', '', wp_strip_all_tags(($product->get_description())));
    $sku = $product->get_sku();
   // $price = $product->get_price();
    //$regular_price = $product->get_regular_price();
    $tax_status = $product->get_tax_status();
    $manage_stock = $product->get_manage_stock();
    $get_stock_quantity = $product->get_stock_quantity();
    $get_stock_status = $product->get_stock_status();
    $product_url = get_permalink($product->get_id() );;
    $weight = $product->get_weight();
    $product_image = wc_certishopping_get_product_image_url($product->get_id());
    //$sale_price = $product->get_sale_price();
    $availabilitys = $product->get_availability();
    //$availability = $availabilitys['availability'];
   // $availability = $availabilitys['message'];
    //$availability = wp_strip_all_tags($availabilitys['class']);
    $availability_status =$product->is_in_stock();
    if($availability_status =true){
        $availability = "in-stock";
    }else if($availability_status =false){
        $availability = "out-of-stock";
    }
    $size = $product->get_attribute('size');
    $color = $product ->get_attribute('color');
    $brand =  $product ->get_attribute('brand');
    $mpn = $product ->get_attribute('mpn');
    $gtin = $product ->get_attribute('gtin');
    $settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $site_id=$settings['partner_id'];
    $variants =wc_certishopping_generateVariantsProduct($product);
    $get_price = $product->get_price();
    $get_sale_price = $product->get_sale_price();
    $get_regular_price = $product->get_regular_price();
    if ( $product->is_on_sale() ) {
    $price = $get_regular_price;
    $sale_price =  $get_sale_price;
     } else{
       $price= $get_price ;
       $sale_price='';
     }

   return  array(
        "site_id" => $site_id,
        'sku' => $sku,
        'name' => $product_name,
        'price' => $price,
        'sale_price' => $sale_price,
       // 'regular_price' => $regular_price,
        'description' => $description,
        'product_id' => $product_id,
        'availability' => $availability,
        'weight' =>$weight,
        'tax_status'  => $tax_status,
        'manage_stock' => $manage_stock,
        'get_stock_quantity' =>$get_stock_quantity,
        'get_stock_status' =>$get_stock_status,
        'url' => $product_url,
        'image' => $product_image,
        'size' => $size ,
        'color' => $color,
        'brand' => $brand,
        'mpn' => $mpn,
        'gtin' => $gtin,
        'variants' =>$variants,
        'weight_unit' => $weight_unit,
    );
}

function wc_certishopping_get_productsListe_active($page_num){
    $args = array(
        'status'            => array('publish'),
        'type'              => array_merge( array_keys( wc_get_product_types() ) ),
        'limit'             => 500,
        'page'              => $page_num,
        'orderby'           => 'date',
        'order'             => 'DESC',
        'paginate'          => true,
    );
    $products = wc_get_products( $args ) ;
    $productList =[];
    if(property_exists($products, 'products')){
        $products = $products->products;
        foreach( $products as $product ) {
            $product_data  = wc_certishopping_generateProductObject($product);
            $productList[] = $product_data;
        }
    }
    
    
    return $productList;
}

function wc_certishopping_woocommerce_products_list()
{
    $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
    $access = $certishopping_settings['access'];
    $expired_at  = $certishopping_settings['expired_at'];
    $certishopping_api = new Certishopping($certishopping_settings['app_key'], $certishopping_settings['secret']);
    if(!$access ||  $certishopping_api->is_expired($expired_at)){
        $get_oauth_token_response = $certishopping_api->getOauthToken();
        if (!empty($get_oauth_token_response) && !empty($get_oauth_token_response['access'])){
            $certishopping_api->setToken($get_oauth_token_response['access']);
        }
    }else{
        $certishopping_api->setToken($access);
    }
    try{
        $page_num = 1;
        $past_products= wc_certishopping_get_productsListe_active($page_num);
        while (isset($past_products) && !empty($past_products)){
            $bulk_products_list = $certishopping_api->AutoProductList($past_products);
            $page_num = $page_num +1;
            $past_products= wc_certishopping_get_productsListe_active($page_num);
            
        }

      
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
}


add_action( 'added_post_meta', 'wc_certishopping_update_productsListe_active', 10, 4 );
add_action( 'save_post', 'wc_certishopping_update_productsListe_active', 10, 4 );
function wc_certishopping_update_productsListe_active($post_id) {
    $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
   if($certishopping_settings['auto_synchronize'] == true){
    if ( get_post_type( $post_id ) == 'product' &&  get_post_status($post_id) =='publish') { 
        $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
        $access = $certishopping_settings['access'];
        $expired_at  = $certishopping_settings['expired_at'];
        $certishopping_api = new Certishopping($certishopping_settings['app_key'], $certishopping_settings['secret']);
        if(!$access ||  $certishopping_api->is_expired($expired_at)){
            $get_oauth_token_response = $certishopping_api->getOauthToken();
            if (!empty($get_oauth_token_response) && !empty($get_oauth_token_response['access'])){
                $certishopping_api->setToken($get_oauth_token_response['access']);
            }
        }else{
            $certishopping_api->setToken($access);
        }
        try{
            $product = wc_get_product( $post_id);
            $product_data = wc_certishopping_generateProductObject($product);
            $bulk_products_list = $certishopping_api->UpdateProductList($product_data);
        }catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }

    }

   }
 
}

