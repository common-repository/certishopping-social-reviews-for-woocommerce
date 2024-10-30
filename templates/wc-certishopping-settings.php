<?php
 
function wc_display_certishopping_admin_page()
{

    if (function_exists('current_user_can') && !current_user_can('manage_options')) {
        die(__(''));
    }
    if (wc_certishopping_compatible()) {
        if (isset($_POST['log_in_button'])) {
            wc_display_certishopping_settings();
        } elseif (isset($_POST['certishopping_settings'])) {
            check_admin_referer('certishopping_settings_form');
            wc_proccess_certishopping_settings();
            wc_display_certishopping_settings();
        } elseif (isset($_POST['certishopping_register'])) {
            check_admin_referer('certishopping_registration_form');
            $success = wc_proccess_certishopping_register();
            if ($success) {
                wc_display_certishopping_settings($success);
            }
        } elseif (isset($_POST['certishopping_past_orders'])) {
            wc_certishopping_send_past_orders();
            wc_display_certishopping_settings();
        }elseif(isset($_POST['certishopping_past_products_list'])) {
            wc_certishopping_woocommerce_products_list();
            wc_display_certishopping_settings();
        } 
        else {
            $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
            wc_display_certishopping_settings();
        }
    } else {
        if (version_compare(phpversion(), '5.2.0') < 0) {
            $message =('Certishopping plugin requires PHP 5.2.0 above.');
            return sprintf( $message, phpversion() );
        }
        if (!function_exists('curl_init')) {
            return ('Certishopping plugin requires cURL library.');
        }
    }
}

function wc_display_certishopping_settings($success_type = false)
{
  $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
        $app_key = $certishopping_settings['app_key'];
        $partner_id = $certishopping_settings['partner_id'];
        $secret = $certishopping_settings['secret'];
        $language = $certishopping_settings['language'];
        $widget_tab_name = $certishopping_settings['widget_tab_name'];
            if (empty($certishopping_settings['app_key'])) {
                if ($success_type == 'b2c') {
                    wc_certishopping_display_message('We have sent you a confirmation email. Please check and click on the link to get your app key and secret token to fill out below.', true);
                } else {
                    wc_certishopping_display_message("".__(' Set your API key in order the Certishopping plugin to work correctly','woo-certishopping-reviews'). "" .false);
                }
            }
        $google_tracking_params = '&utm_source=certishopping_plugin_woocommerce&utm_medium=header_link&utm_campaign=woocommerce_customize_link'; 
        $dashboard_link = "<a href='https://certishopping.com/fr/dashboard' target='_blank'>".__('Certishopping Dashboard.','woo-certishopping-reviews')."</a>";
        $account_link = "<a href='https://certishopping.com/fr/account/signup' target='_blank'>".__('log in here.','woo-certishopping-reviews')."</a>";
        $read_only = isset($_POST['log_in_button']) || $success_type == 'b2c' ? '' : 'readonly';
        $read_only = '';
       //$cradentials_location_explanation = isset($_POST['log_in_button']) ? "<tr valign='top'><th scope='row'><p class='description'>To get your api key and secret token <a href='https://www.certishopping.com/?login=true' target='_blank'>log in here</a> and go to your account settings.</p></th></tr>" : '';
        $certishopping_settings['show_submit_past_orders'] = true;
        $certishopping_settings['show_submit_products_list'] = true;
        $submit_past_orders_button = $certishopping_settings['show_submit_past_orders'] ? "<input type='submit' style='float: left;' name='certishopping_past_orders' value='".__('Submit past orders','woo-certishopping-reviews')."' class='button-secondary past-orders-btn' " . disabled(true, empty($app_key) || empty($secret), false) . ">" : '';
        $submit_past_products_list = $certishopping_settings['show_submit_products_list'] ? "<input type='submit'  name='certishopping_past_products_list' value='".__('Synchronize','woo-certishopping-reviews')."' class='button-bulk-product past-orders-btn' " . disabled(true, empty($app_key) || empty($secret), false) . ">" : '';
        $i = 0;
        $settings_html ="";
        $list_status = wc_get_order_statuses();
        $statusChoosen =[];
        if(isset($certishopping_settings['command_status'])){
            $statusChoosen = $certishopping_settings['command_status'];
        }
        $list_status = (($list_status)) ? $list_status : array();
        $list_id = array(1,2,3,4,5,6,7,8,9,10,11,12,13);
        foreach ($list_status as $key => $status){ 
            if(is_array($statusChoosen)){
                if(in_array($key , $statusChoosen))
                {
                    
                    $settings_html .='<input id="certishopping_order_status_'.$i.'" type="checkbox" name="certishopping_order_status[]" checked="cheched" value='.$key .'   />';
                    $settings_html .='<label class="export_label_checkbox" for="certishopping_order_status_'.$i.'"> '.$status.'</label>';
                    $settings_html .='<br/>';
                }else{
                        $settings_html .='<input id="certishopping_order_status_'.$i.'" type="checkbox" name="certishopping_order_status[]" value='.$key .' />';
                        $settings_html .='<label class="export_label_checkbox" for="certishopping_order_status_'.$i.'"> '.$status.'</label>';
                        $settings_html .='<br/>';

                }



                    
                   
            }else{
                $settings_html .='<input id="certishopping_order_status_'.$i.'" type="checkbox" name="certishopping_order_status[]" value='.$key .' />';
                $settings_html .='<label class="export_label_checkbox" for="certishopping_order_status_'.$i.'"> '.$status.'</label>';
                $settings_html .='<br/>';

            }
            $i = $i + 1;
         }
      
        echo "<div class='wrap'>
          <div class='certishopping_logo_admin'>
          <h2 class='certishopping_settings-admin' >".__('Certishopping Settings','woo-certishopping-reviews')."</h2> </div>   
         <form  method='post' id='certishopping_settings_form' autocomplete='off' >
          <table class='form-table' id='certi-table-connect'>" . wp_nonce_field('certishopping_settings_form') ."
          <div class='alert alert-info'>
          ".__('To customize the look and feel of the widget and to edit your Mail After Purchase settings,just head to the','woo-certishopping-reviews')."". $dashboard_link ."</tr>
          </div>
         <fieldset>
          <tr valign='top'>
          <th class='certi-key' scope='row'>
     
          </th>
          <td>
          <div class='alert alert-acces'>
          " .__('To get your api key and secret token','woo-certishopping-reviews'). " ". $account_link ."</tr>
          </div>
          </td>
          </tr>
          <tr valign='top'>
          <th class='certi-key' scope='row'>
       
          <div>
          ".__('App key:','woo-certishopping-reviews')."
          </div>
          </th>
          <td>
          <div class='y-input'>
           <input class='certi-app-input' id='app_key' type='text' name='certishopping_app_key'  value=' ".esc_attr($app_key)."' $read_only/>
          </div>
          </td>
          </tr>
           <tr valign='top'>
          <th class='certi-key' scope='row'>
          <div> ".__('Secret Key: ','woo-certishopping-reviews')."</div>
          </th>
          <td>
        <div class='y-input'>
          <input class='certi-app-input' id='secret' type='text'  name='certishopping_oauth_token' value=' ".esc_attr($secret)."' $read_only />
          </div>
          </td>
         </tr>
         <tr valign='top'>
          <th class='certi-key' scope='row'>
          <div>
          ".__('Your website ID:','woo-certishopping-reviews')." 
          </div>
          </th>
          <td>
          <div class='y-input'>
          <input class='certi-app-input' id='partner_id' disabled='disabled' type='text' name='certishopping_partner_id' value='".esc_attr($partner_id)."' $read_only />
          </div>
          </td>
         </tr>
         <tr style=' float: right;'>
         <td>
          <input type='submit' name='certishopping_settings' value='".__('Update','woo-certishopping-reviews')."' class='button-primary-certi' id='save_certishopping_settings'/>
         </td>
         </tr>
          </table>
          <table class='form-table'>
        <tr valign='top'>
        <th scope='row'>
          <div> ".__('Select language','woo-certishopping-reviews')."</div>
          </th>
         <td>
          <select name='certishopping_language' class='certishopping-language'>
          <option value='fr' " . selected('fr', $certishopping_settings['language'], false) . "> ".__('French','woo-certishopping-reviews')."</option>
         <option value='en' " . selected('en', $certishopping_settings['language'], false) . ">".__('English','woo-certishopping-reviews')."</option> 
          </select>
         </td>
         </tr> 
       
            
           <tr valign='top'>	
           <th scope='row'>
           <div> ".__('Select star rating location','woo-certishopping-reviews')."</div>
           </th>
           <td>
           <select name='certishopping_star_rating_location' class='certishopping-star-rating-location' id='star_rating_location'>
           <option value='single_product' " . selected('single_product', $certishopping_settings['star_rating_location'], false) . "> ".__('single product','woo-certishopping-reviews')."</option>
           <option value='shortcode' " . selected('shortcode', $certishopping_settings['star_rating_location'], false) . ">".__('shortcode','woo-certishopping-reviews')."</option>
         
           </select>
          </td>
          </tr>
          <tr valign='top' class='certishopping-widget-location-shortcode'>
          <th scope='row'>
          <div> ".__('It only concerns the product page :','woo-certishopping-reviews')."</div>
          </th>   
          <td>
          <p class='certi-description'>
          <span>".__('You can insert this shortcode to choose a location other than the one proposed by default','woo-certishopping-reviews')."</span>
          <span> <code>[certishopping_star_rating]</code></span>
          </p>
          </td>           																	
           </tr>
           <tr valign='top'>	
          <th scope='row'>
          <div> ".__('Select widget location','woo-certishopping-reviews')."</div>
          </th>
          <td>
          <select name='certishopping_widget_location' class='certishopping-widget-location' id='widget_location'>
          <option value='footer' " . selected('footer', $certishopping_settings['widget_location'], false) . "> ".__('Page footer','woo-certishopping-reviews')."</option>
         <option value='tab' " . selected('tab', $certishopping_settings['widget_location'], false) . ">".__('Tab','woo-certishopping-reviews')."</option>
         <option value='other' " . selected('other', $certishopping_settings['widget_location'], false) . "> ".__('Other','woo-certishopping-reviews')."</option>
          </select>
         </td>
         </tr>
         <tr valign='top' class='certishopping-widget-tab-name'>
         <th scope='row'>
          <div>".__('Select tab name: ','woo-certishopping-reviews')."</div>
        </th>
          <td>
          <div>
          <input type='text' name='certishopping_widget_tab_name' id='certishopping_widget_tab_name' value='".esc_html($widget_tab_name)."' />
           </div>
          </td>
          </tr>
         <tr valign='top' class='certishopping-widget-location-other-explain'>
         <th>
         <div> ".__('It only concerns the product page :','woo-certishopping-reviews')."</div>
         </th>
         <td scope='row'>
         <p class='certi-description'>
         <span>
         ".__('You can insert this shortcode to choose a location other than the one proposed by default','woo-certishopping-reviews')."
         </span>
         <span>
         <code>[certishopping_show_product_widget]</code>
         </span>
         </p>
         <br>
            <br>
         <p class='certi-description'>
         <span> 
         ".__('In order to locate the widget in a custom location open','woo-certishopping-reviews')."
         </span> 
         <span> 
         wp-content/plugins/woocommerce/templates/content-single-product.php
         </span>
         <span>
         ".__('and add the following line','woo-certishopping-reviews')."
         </span> 
         <span> 
         <code>wc_certishopping_show_widget();</code>
         </span>
         <span>
         ".__('in the requested location.','woo-certishopping-reviews')."
         </span>

       

         </td>              																	
          </tr>
        
          <tr valign='top'>
          <th scope='row'>
           <div>".__('Custom CSS Style','woo-certishopping-reviews')."</div>
           </th>
          <td>
           <textarea cols='50' rows='10' name='certishopping_custom_css' >" . $certishopping_settings['custom_css'] . "</textarea>
          </td>
         </tr>
         <tr valign='top'>
         <th scope='row'>
                <div>".__('Manual Collection Status','woo-certishopping-reviews')." </div>         
         </th>
         </tr>
		  <tr valign='top'>
           <th scope='row'>
           <div>".__('Order Status:','woo-certishopping-reviews')."</div>
          </th>
		    <td>
            $settings_html

           </select>
           	</td>
		   </tr>
          <tr valign='top'>
            <th scope='row'>
          <div>".__('Nb Orders:','woo-certishopping-reviews')."</div>
           </th>
           <td>
           <select name='certishopping_order_total_to_send' class='certishopping-order-total-to-send' >
           <option value='10' " . selected('10', $certishopping_settings['certishopping_order_total_to_send'], false) . ">10</option>
           <option value='50' " . selected('50', $certishopping_settings['certishopping_order_total_to_send'], false) . ">50</option>
           <option value='100' " . selected('100', $certishopping_settings['certishopping_order_total_to_send'], false) . ">100</option>
          <option value='200' " . selected('200', $certishopping_settings['certishopping_order_total_to_send'], false) . ">200</option>
           <option value='300' " . selected('300', $certishopping_settings['certishopping_order_total_to_send'], false) . ">300</option>
           <option value='400' " . selected('400', $certishopping_settings['certishopping_order_total_to_send'], false) . ">400</option>
           <option value='1000' " . selected('1000', $certishopping_settings['certishopping_order_total_to_send'], false) . ">1000</option>
           </select>
          </td>
          </tr>
          <tr valign='top'>
          <th scope='row'>
          <div>".__('Auto Synchronize your woocommerce product catalog with google shopping','woo-certishopping-reviews')."   </div>
          </th>
          <td>
          <div> <input type='checkbox' name='certi_auto_synchronize' value='1' " . checked(1, $certishopping_settings['auto_synchronize'], false) . "/>
          </td>
          </tr>
           <tr>
            <td>
         	
               <div class='clear' ></div>

           </td>
           <td>
          $submit_past_orders_button
          <input type='submit' name='certishopping_settings' value='".__('Update','woo-certishopping-reviews')."' class='button-primary' id='save_certishopping_settings'/>

           </td>
        </tr>
  
       
        </fieldset>
           </table>


        <div class='certi-sync '>
        <div class='certi-feed'> 
            <div class='certi-message'>".__('Synchronize your woocommerce product catalog with google shopping','woo-certishopping-reviews')."</div>     
           <div class='certi-button-bulk'> $submit_past_products_list </div>
        </div>
        
      </div>
           <div class='certi-configuration '> <span class='certi-conf'> ".__('To configure the design of your site widget just head to the Certishopping Dashboard.','woo-certishopping-reviews'). " <a href='https://certishopping.com/widgets'> https://certishopping.com/widgets </a></span>  </div>
          </br>  		
      
          	</form>

              <iframe name='certishopping_export_reviews_frame' style='display: none;'></iframe>
              <form action='' method='get' target='certishopping_export_reviews_frame' style='display: none;'>
                <input type='hidden' name='download_exported_reviews' value='true' />
                <input type='submit' value='Export Reviews' class='button-primary' id='export_reviews_submit'/>
              </form> 		
            <hr style='margin: 30px auto;' >";
        //printf($settings_html);

}

function wc_proccess_certishopping_settings()
{
        $current_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
        $app_key =$_POST['certishopping_app_key'];
        if(isset($app_key) && ($app_key != NULL)){
            $app_key ='';  
        }
        $secret=$_POST['certishopping_oauth_token'];
        if(isset($secret) && ($secret != NULL)){
            $secret ='';  
        }
        $widget_location=$_POST['certishopping_widget_location'];
        if(isset($widget_location) && ($widget_location != NULL)){
            $widget_location ='';  
        }
        $star_rating_location=$_POST['certishopping_star_rating_location'];
        if(!isset($star_rating_location)){
            $star_rating_location ='single_product';  
        }  
        $widget_tab_name=$_POST['certishopping_widget_tab_name'];
        if(isset($widget_tab_name) && ($widget_tab_name != NULL)){
            $widget_tab_name ='';  
        }
        $order_total_to_send = intval($_POST['certishopping_order_total_to_send']);
        if(isset($order_total_to_send) && ($order_total_to_send != NULL)){
            $order_total_to_send ='';  
        }
        $order_status = '';
        if (isset($_POST['certishopping_order_status'])) {
            $order_status = $_POST['certishopping_order_status'];
        }
        // $order_status = $_POST['certishopping_order_status'];
        // if(isset($order_status) && ($order_status != NULL)){
        //     $order_status ='';  
        // }
        $auto_synchronize = '';
        if (isset($_POST['auto_synchronize'])) {
            $auto_synchronize = $_POST['auto_synchronize'];
        }
            $new_settings = array(  
                'app_key' =>sanitize_text_field(trim($_POST['certishopping_app_key'])),
                'secret' => sanitize_text_field(trim($_POST['certishopping_oauth_token'])),
                'partner_id' => NULL,
                'access' =>NULL,
                'widget_location' => sanitize_text_field($_POST['certishopping_widget_location']),
                'star_rating_location' => sanitize_text_field($_POST['certishopping_star_rating_location']),
                'language' => sanitize_text_field($_POST['certishopping_language']),
                'widget_tab_name' =>sanitize_text_field($_POST['certishopping_widget_tab_name']),
                'command_status' => isset($_POST['certishopping_order_status']) ? $_POST['certishopping_order_status'] : array(),
                'certishopping_order_status' => isset($_POST['certishopping_order_status']) ? $_POST['certishopping_order_status'] : array(),
                'certishopping_order_total_to_send' => sanitize_text_field($_POST['certishopping_order_total_to_send']),
                'disable_native_review_system' => isset($_POST['disable_native_review_system']) ? true : false,
                'show_submit_past_orders' => $current_settings['show_submit_past_orders'],
                'show_submit_products_list'  => $current_settings['show_submit_products_list'],
                'custom_css' => sanitize_text_field($_POST['certishopping_custom_css']),
                'auto_synchronize' => isset($_POST['certi_auto_synchronize']) ? true : false,
            );
        $certishopping_settings = get_option('certishopping_settings', wc_certishopping_get_degault_settings());
        $certishopping_api = new Certishopping(trim($_POST['certishopping_app_key']), trim($_POST['certishopping_oauth_token']));
        $get_oauth_token_response = $certishopping_api->getOauthToken();
        if (!empty($get_oauth_token_response) && !empty($get_oauth_token_response["access"])) {
            $next_year = date("Y-m-d", strtotime("+6 month"));
            $new_settings['access'] = $get_oauth_token_response["access"];
            $new_settings['expired_at'] =  $next_year;

        } else {
            $new_settings['access'] = '';
        }

        if (!empty($get_oauth_token_response) && !empty($get_oauth_token_response['site_id'])) {
            $new_settings['partner_id'] = $get_oauth_token_response['site_id'];
        } else {
            $new_settings['partner_id'] = '';
        }
        update_option('certishopping_settings', $new_settings);
            if ($current_settings['disable_native_review_system'] != $new_settings['disable_native_review_system']) {
                if ($new_settings['disable_native_review_system'] == false) {
                    update_option('woocommerce_enable_review_rating', get_option('native_star_ratings_enabled'));
                } else {
                    update_option('woocommerce_enable_review_rating', 'no');
                }
            }
}
function wc_certishopping_display_message($messages = array(), $is_error = false)
{
        $class = $is_error ? 'error' : 'updated fade';
            if (isset($_REQUEST['format']) && $_REQUEST['format'] == "json") {
                header('Content-Type: application/json');
                sprintf( json_encode(['status' => !$is_error, 'messages' => $messages]));
                exit();
            }
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    printf( '<div id="message" class='.esc_html($class).'><p><strong>'.esc_html($message).'</strong></p></div>');
                }
            } elseif (is_string($messages)) {
                printf( '<div id="message" class='.esc_html($class).'><p><strong>'.esc_html($messages).'</strong></p></div>');
            }
}