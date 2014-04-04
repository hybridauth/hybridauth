<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

// A service client for the OAuth 2 flow.
// v0.1
class PaypalOAuth2Client extends OAuth2Client
{
    public $curl_header              = array(
        'Accept: application/json',
        'Accept-Language: en_US',
    );
	public $curl_useragent           = "OAuth/2 Simple PHP Client v0.1; HybridAuth http://hybridauth.sourceforge.net/";
    public $curl_log;

	public function authenticate( $code )
	{
		$params = array(
			"grant_type"    => "authorization_code",
			"code"          => $code,
			"redirect_uri"  => $this->redirect_uri,
		);
	
		$response = $this->request( $this->token_url, $params, $this->curl_authenticate_method );
		
		$response = $this->parseRequestResult( $response );

		if( ! $response || ! isset( $response->access_token ) ){
			throw new Exception( "The Authorization Service has return: " . $response->message );
		}

		if( isset( $response->access_token  ) )  $this->access_token           = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->refresh_token           = $response->refresh_token; 
		if( isset( $response->expires_in    ) ) $this->access_token_expires_in = $response->expires_in; 
		
		// calculate when the access token expire
		if( isset($response->expires_in)) {
			$this->access_token_expires_at = time() + $response->expires_in;
		}

		return $response;  
	}


	// -- tokens

	public function tokenInfo($accesstoken)
	{
		$params['access_token'] = $this->access_token;
		$response = $this->request( $this->token_info_url, $params, "POST" );
		return $this->parseRequestResult( $response );
	}

	public function refreshToken( $params = array() )
	{
		$params = array(
			"grant_type"    => "refresh_token",
		    "refresh_token" => $this->refresh_token,
		);
		$response = $this->request( $this->token_url, $params, "POST" );
		return $this->parseRequestResult( $response );
	}

	// -- utilities

	private function request( $url, $params=false, $type="GET" )
	{
        $params = http_build_query($params, '', '&');
		Hybrid_Logger::info( "Enter OAuth2Client::request( $url )" );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request params: ", $params );

		if( $type == "GET" ){
			$url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . $params;
		}

		$this->http_info = array();
		$ch = curl_init();

        $headers = $this->curl_header;
        if($type == "POST" ){
            //$headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }

		curl_setopt($ch, CURLOPT_URL            , $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
		curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , $this->curl_ssl_verifyhost );
		curl_setopt($ch, CURLOPT_HTTPHEADER     , $headers );
        curl_setopt($ch, CURLOPT_USERPWD        , $this->client_id.':'.$this->client_secret );
        // logging
        if ($this->curl_log !== null) {
            $fp = fopen($this->curl_log, 'a');
            curl_setopt($ch, CURLOPT_STDERR     , $fp );
            curl_setopt($ch, CURLOPT_VERBOSE    , 1 );
        }

		if($this->curl_proxy){
			curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
		}

		if( $type == "POST" ){
			curl_setopt($ch, CURLOPT_POST, 1); 
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params );
		}

		$response = curl_exec($ch);
        if ($this->curl_log !== null)
            fclose($fp);
		if( $response === FALSE ) {
				Hybrid_Logger::error( "OAuth2Client::request(). curl_exec error: ", curl_error($ch) );
		}
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request info: ", serialize( curl_getinfo($ch) ) );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request result: ", serialize( $response ) );

		$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ch));

		curl_close ($ch);

		return $response; 
	}

	private function parseRequestResult( $result )
	{
		if( json_decode( $result ) ) return json_decode( $result );

		parse_str( $result, $output );

		$result = new StdClass();

		foreach( $output as $k => $v )
			$result->$k = $v;

		return $result;
	}
}
