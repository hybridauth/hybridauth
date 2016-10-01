<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * A service client for the Amazon ID OAuth 2 flow.
 *
 * The sole purpose of this subclass is to make sure the POST params
 * for cURL are provided as an urlencoded string rather than an array.
 * This is because Amazon requires COntent-Type header to be application/x-www-form-urlencoded,
 * which cURL overrides to multipart/form-data when POST fields are provided as an array
 *
 * The only difference from Oauth2CLient in authenticate() method is http_build_query()
 * wrapped around $params. request() and parseRequestResult() methods are exact copies
 * from Oauth2Client. They are copied here because private scope does not allow calling them
 * from subclass.
 *
 * @link http://stackoverflow.com/questions/5224790/curl-post-format-for-curlopt-postfields
 *
 */
class AmazonOAuth2Client extends OAuth2Client {

	public function authenticate( $code ) {

		$params = array(
			"client_id"     => $this->client_id,
			"client_secret" => $this->client_secret,
			"grant_type"    => 'authorization_code',
			"redirect_uri"  => $this->redirect_uri,
			"code"          => $code,
		);

		$response = $this->request( $this->token_url, http_build_query($params), $this->curl_authenticate_method );

		$response = $this->parseRequestResult( $response );

		if ( ! $response || ! isset( $response->access_token ) ){
			throw new Exception( "The Authorization Service has return: " . $response->error );
		}

		if( isset( $response->access_token  ) ) $this->access_token            = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->refresh_token           = $response->refresh_token;
		if( isset( $response->expires_in    ) ) $this->access_token_expires_in = $response->expires_in;

		// calculate when the access token expire
		if( isset($response->expires_in)) {
			$this->access_token_expires_at = time() + $response->expires_in;
		}

		return $response;
	}

	private function request( $url, $params=false, $type="GET" )
	{
		Hybrid_Logger::info( "Enter OAuth2Client::request( $url )" );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request params: ", serialize( $params ) );

		if( $type == "GET" ){
			$url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query($params, '', '&');
		}

		$this->http_info = array();
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL            , $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
		curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , $this->curl_ssl_verifyhost );
		curl_setopt($ch, CURLOPT_HTTPHEADER     , $this->curl_header );

		if ($this->curl_compressed){
			curl_setopt($ch, CURLOPT_ENCODING, "gzip,deflate");
		}

		if($this->curl_proxy){
			curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
		}

		if( $type == "POST" ){
			curl_setopt($ch, CURLOPT_POST, 1);
			if($params) curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		}
		if( $type == "DELETE" ){
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		}
		if( $type == "PATCH" ){
			curl_setopt($ch, CURLOPT_POST, 1);
			if($params) curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
		}
		$response = curl_exec($ch);
		if( $response === false ) {
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
