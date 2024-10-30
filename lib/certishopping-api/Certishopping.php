<?php

/**
 * Certishopping PHP inetrface for api.certishopping.com
 *
 * @author vlad
 */
class Certishopping {

    const VERSION = '0.0.5';
	const TIMEOUT = 5;
    protected static $app_key, $secret, $token, $base_uri = 'https://certishopping.com';
    protected $request;

    public function __construct($app_key = null, $secret = null, $base_uri = null) {
        $this-> setAppKey($app_key);
       
        $this->setSecret($secret); 

        if ($base_uri != null) {
            self::$base_uri = $base_uri;
        }
    }

	protected function setRequestMethod($method, $vars) {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                if ($this->getToken()) {
                    curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);
                    curl_setopt($this->request, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded', 'Authorization: Bearer '.$this->getToken(), 'Content-length: '.strlen($vars)));               
                    curl_setopt($this->request, CURLOPT_POST, true);
                } else {
                    curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);
                    curl_setopt($this->request, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-length: '.strlen($vars)));             
                    curl_setopt($this->request, CURLOPT_POST, true);
                }
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }
    
	protected function setRequestOptions($url, $vars) {
        curl_setopt($this->request, CURLOPT_URL, $url);        
        
        # Set some default CURL options
        curl_setopt($this->request, CURLOPT_HEADER, false);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_USERAGENT, 'Certishopping-Php');
        curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->request, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($this->request, CURLOPT_CONNECTTIMEOUT ,self::TIMEOUT);
    }
	
    function request($method, $url, $vars = array()) {
    	if (!empty($vars)) $vars = self::cleanArray($vars);
    	$url = self::$base_uri . $url;

        $this->error = '';
        $this->request = curl_init();
        if (is_array($vars)) {
        	if($method == 'POST') {
        		$vars = json_encode($vars);
        	}
        	else {
        		$vars = http_build_query($vars, '', '&');	
        	}        	
        }
        
        $this->setRequestMethod($method, $vars);
        $this->setRequestOptions($url, $vars);

        $response = curl_exec($this->request);
        
        curl_close($this->request);
        
        return self::processResponse($response);
    }
 	
    protected function get($url, $vars = array()) {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }
	
    protected function delete($url, $vars = array()) {
        return $this->request('DELETE', $url, $vars);
    }
	
    protected function post($url, $vars = array()) {
        return $this->request('POST', $url, $vars);
    }
    
    protected function postWithToken($url, $vars = array()) {
        return $this->request('POST', $url, $vars);
    }
	
    protected function put($url, $vars = array()) {
        return $this->request('PUT', $url, $vars);
    }
    
    protected static function processResponse($response) {
		return json_decode($response, true);
    }

    public function getOauthToken(array $credentials_hash = array()) {
        $request = array();
        $request['app_key'] = self::$app_key;
        $request['secret_key'] = self::$secret;
        return $this->post('/api/token/', $request);
    }

    public function createPurchase(array $purchase_hash) {
        $request = self::buildRequest(
                        array(
                    'utoken' => 'utoken',
                    'email' => 'email',
                    'customer_name' => 'customer_name',
                    'order_date' => 'order_date',
                    'currency_iso' => 'currency_iso',
                    'order_id' => 'order_id',
                    'platform' => 'platform',
                    'products' => 'products'
                        ), $purchase_hash);
        $app_key = $this-> getAppKey($purchase_hash);
        return $this->post("/apps/$app_key/purchases", $request);
    }

    public function Manualbulkrequest( array $past_orders) {
        $url = self::$base_uri . '/api/woocommerce-webhooks/manual-requests/';
        $header = array(
            "cache-control: no-cache",
            "content-type: application/json",
            "Authorization: Bearer ".$this->getToken(),
        );
        $json_data = json_encode($past_orders);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return json_decode($response);


    }

  
    public function Autobulkrequest( array $past_orders) {
        $url = self::$base_uri . '/api/woocommerce-webhooks/auto-requests/';
        $header = array(
            "cache-control: no-cache",
            "content-type: application/json",
            "Authorization: Bearer ".$this->getToken(),
        );
        $json_data = json_encode($past_orders);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return json_decode($response);
    }

    public function AutoProductList (array $past_product){
        $url = self::$base_uri . '/api/woocommerce-webhooks/products/';
       $header = array(
           "cache-control: no-cache",
           "content-type: application/json",
           "Authorization: Bearer ".$this->getToken(),
       );
       $json_data = json_encode($past_product);
       $curl = curl_init($url);
       curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
       curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
       curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
       curl_setopt($curl, CURLOPT_ENCODING, "");
       curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
       curl_setopt($curl, CURLOPT_TIMEOUT, 30);
       curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
       curl_setopt($curl, CURLOPT_CUSTOMREQUEST,"POST");
       curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data); 
       curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
       $response = curl_exec($curl);
       $err = curl_error($curl);
       curl_close($curl);
       return json_decode($response);
   }

   public function UpdateProductList ($past_product){
       $url = self::$base_uri . '/api/woocommerce-webhooks/product/';
      $header = array(
          "cache-control: no-cache",
          "content-type: application/json",
          "Authorization: Bearer ".$this->getToken(),
      );
      $json_data = json_encode($past_product);
      $curl = curl_init($url);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_ENCODING, "");
      curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
      curl_setopt($curl, CURLOPT_TIMEOUT, 30);
      curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST,"POST");
      curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data); 
      curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
      $response = curl_exec($curl);
      $err = curl_error($curl);
      curl_close($curl);
      return json_decode($response);
  }

    public  function getReviewProduct($product_sku,$product_id,$partner_id){   
        $product_sku =urlencode(htmlspecialchars_decode($product_sku, ENT_QUOTES));    
        $product_id =urlencode(htmlspecialchars_decode($product_id, ENT_QUOTES));
        $url = "https://certishopping.com/api/seo/product/?sku=".$product_sku."&product_id=".$product_id."&site_id=".$partner_id; 
       
        // $json_data =NULL;
        try{
            
        $json_data =@file_get_contents($url);
        if ( $json_data === False){
       
            return NULL;
        }else{ $json_data = json_decode($json_data, true);}
       // echo '$json_data';print_r($json_data);
      }
       catch (Exception $e) { echo $e->getMessage(); }
       return  $json_data;
      }
      
    public function getPurchases(array $request_hash) {
        $request = self::buildRequest(array('utoken' => 'utoken', 'since_id' => 'since_id', 'since_date' => 'since_date', 'page' => 'page', 'count' => 'count'), $request_hash);
        if (!array_key_exists('page', $request)) {
            $request['page'] = 1;
        }
        if (!array_key_exists('count', $request)) {
            $request['count'] = 10;
        }
        $app_key = $this-> getAppKey($request_hash);
        return $this->get("/apps/$app_key/purchases", $request);
    }
 
    public function  setAppKey($app_key) {
        if ($app_key != null) {
            self::$app_key = $app_key;
           
        }

    }
     
    public function setSecret($secret) {
        if ($secret != null) {
            self::$secret = $secret;
           
        }

    }

    protected function  getAppKey($hash){
        if(!is_null($hash) && !empty($hash) && array_key_exists('app_key', $hash)){
            return $hash['app_key'];
        } elseif (self::$app_key != null) {
            return self::$app_key; 
        }else {
            die('app_key is mandatory for this request');
        }
    }

    public function getToken() {
        return self::$token;
    }

    public function setToken($token) {
        if ($token != null) {
            self::$token = $token;
        }
    }
    public function is_expired($expired_at) {
        if($expired_at){
            $today =  date("Y-m-d");
            $expired_at = strtotime($expired_at);
            $expired_at = date('Y-m-d',$expired_at);
            return $today >  $expired_at;
        }
        return true;
    }

    protected static function buildRequest(array $params, array $request_params) {
        $request = array();
        foreach ($params as $key => $value) {
            if (array_key_exists($key, $request_params)) {
                $request[$value] = $request_params[$key];
            }
        }
        return $request;
    }
    
    protected static function cleanArray(array $array){
        
        foreach( $array as $key => $value ) {
            if( is_array( $value ) ) {
                foreach( $value as $key2 => $value2 ) {
                    if( empty( $value2 ) ) 
                        unset( $array[ $key ][ $key2 ] );
                }
            }
            if( empty( $array[ $key ] ) )
                unset( $array[ $key ] );
        }
        return $array;
    }

}

?>
