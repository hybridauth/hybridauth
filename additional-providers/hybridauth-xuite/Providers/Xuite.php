<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/** 
 * Xuite OAuth2 Class
 * 
 * @package             HybridAuth providers package 
 * @author              Happyman <happyman.eric@gmail.com>
 * @version             0.1
 * @license             BSD License
 */ 
/**
 *  Xuite API
 *  http://api.xuite.net/document/bin/xuite_dev/public/
 *  取得 API
 *  http://my.xuite.net/service/token/my/apiKeyAdd.php
 */

class Hybrid_Providers_Xuite extends Hybrid_Provider_Model_OAuth2
{
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = 'https://api.xuite.net/api.php';
		$this->api->authorize_url = 'https://my.xuite.net/service/account/authorize.php';
		$this->api->token_url     = 'https://my.xuite.net/service/account/token.php';

		$this->api->curl_authenticate_method  = "GET";

	}

	function xuite_api_param($config,$param) {
		$token = $config['keys']['secret'];
		$apikey = $config['keys']['id'];
		$param['api_key'] = $apikey;
		//$param['method'] = $method;
		ksort($param);
		foreach($param as $val) {
			$token .= $val;
		}
		$api_sig = md5($token);
		$url_get_str = sprintf("?api_sig=%s&%s",$api_sig,http_build_query($param, '', '&'));
		return $url_get_str;

	}
	
	// 無法使用原生的 api->api 是因為會多加上 access_token 參數, 而 xuite 要自己組
	function xuite_request( $url, $params=false, $type="GET" , $include_header = 0)
  {
    Hybrid_Logger::info( "Enter OAuth2Client::xuite_request( $url )" );
    Hybrid_Logger::debug( "OAuth2Client::xuite_request(). dump request url ", $url );
    Hybrid_Logger::debug( "OAuth2Client::xuite_request(). dump request params: ", print_r( $params , true) );

    if( $type == "GET" ){
      $url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query($params, '', '&');
    }

    $this->http_info = array();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL            , $url );
    curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
    // happyman
    if ($include_header == 1 )
      curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
    curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , $this->curl_ssl_verifyhost );
    curl_setopt($ch, CURLOPT_HTTPHEADER     , $this->curl_header );

    if($this->curl_proxy){
      curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
    }

    if( $type == "POST" ){
      curl_setopt($ch, CURLOPT_POST, 1);
      if($params) curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
    }

    $response = curl_exec($ch);
    if( $response === FALSE ) {
        Hybrid_Logger::error( "OAuth2Client::request(). curl_exec error: ", curl_error($ch) );
    }
    Hybrid_Logger::debug( "OAuth2Client::request(). dump request info: ", serialize( curl_getinfo($ch) ) );
    //happyman
    Hybrid_Logger::debug( "OAuth2Client::request(). dump request result: ", print_r( $response,true) );

    $this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $this->http_info = array_merge($this->http_info, curl_getinfo($ch));

    curl_close ($ch);
                                   
    return $response;
  }

	/**
	 * getUserProfile 
	 * 參考: http://api.xuite.net/document/bin/xuite_dev/public/front/index/id/47
	 * @param mixed $config 
	 * @access public
	 * @return void
	 */
	function getUserProfile($config)
	{

		$param['method'] = "xuite.my.private.getMe";
		$param['auth'] =  $this->api->access_token;
		$url_get_str = $this->api->api_base_url .  $this->xuite_api_param($config, $param);
		$rsp = $this->xuite_request(  $url_get_str); 
	  $data = json_decode($rsp);

		if ( ! isset( $data->ok ) || $data->ok != 1 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned error response.", 6 );
		}

		$this->user->profile->identifier    = $data->rsp->sn;
		$this->user->profile->firstName     = "";
		$this->user->profile->lastName      = "";
		$this->user->profile->displayName   = $data->rsp->nickname;
		$this->user->profile->gender        = "";

		//wl.basic
		$this->user->profile->profileURL    = $data->rsp->avatar_url;

		//wl.emails
		$this->user->profile->email         = $data->rsp->login_user_id . "@xuite.net";
		$this->user->profile->emailVerified = $this->user->profile->email;

		return $this->user->profile;
	}
}
