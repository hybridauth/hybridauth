<?php
// A service client for the OAuth 2 flow.
// v0.1
class OAuth2Client
{
	public $auth_url       = "";
	public $token_url      = "";
	public $token_info_url = "";

	public $client_id      = "" ;
	public $client_secret  = "" ;
    public $redirect_uri   = "" ;
	public $access_token   = "" ;
	public $refresh_token  = "" ;

	//--

	public $decode_json           = true;
	public $curl_time_out         = 30;
	public $curl_connect_time_out = 30;
	public $curl_ssl_verifypeer   = false;
	public $curl_useragent        = "OAuth/2 Simple PHP Client v0.1; HybridAuth http://hybridauth.sourceforge.net/";

	//--

	public function __construct( $client_id = false, $client_secret = false, $redirect_uri='' )
	{
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret; 
		$this->redirect_uri  = $redirect_uri; 
	}

	public function authorizeUrl( $extras = array() )
	{
		$params = array(
			"client_id"     => $this->client_id,
			"redirect_uri"  => $this->redirect_uri,
			"response_type" => "code"
		);

		if( count($extras) )
			foreach( $extras as $k=>$v )
				$params[$k] = $v;

		return $this->auth_url . "?" . http_build_query( $params );
	}

    public function authenticate( $code )
	{
		$params = array(
			"client_id"     => $this->client_id,
			"client_secret" => $this->client_secret,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->redirect_uri,
			"code"          => $code
		);

		$response = $this->request( $this->token_url, $params, "POST" );
		$response = json_decode( $response );

		if( ! $response || ! isset( $response->access_token ) ){
			throw new Exception( "The Authorization Service has return: " . $response->error );
		}

		if( isset($response->access_token ) )  $this->access_token  = $response->access_token;
		if( isset($response->refresh_token ) ) $this->refresh_token = $response->refresh_token; 

		return $response;  
    }

	public function authenticated()
	{
		if ( $this->access_token ){
			if ( $this->token_info_url && $this->refresh_token ){
				// check if this access token has expired, 
				$tokeninfo = $this->tokenInfo( $this->access_token );

				// if yes, access_token has expired, then ask for a new one
				if( $tokeninfo && isset( $tokeninfo->error ) ){
					$response = $this->refreshToken( $this->refresh_token );

					// if wrong response
					if( ! isset( $response->access_token ) || ! $response->access_token ){
						throw new Exception( "The Authorization Service has return an invalid response while requesting a new access token. given up!" ); 
					}

					// set new access_token
					$this->access_token = $response->access_token; 
				}
			}

			return true;
		}

		return false;
	}

	/** 
	* Format and sign an oauth for provider api 
	*/
	public function api( $url, $method = "GET", $parameters = array() ) 
	{
		$parameters['access_token'] = $this->access_token;

		switch( $method ){
			case 'GET'  : $response = $this->request( $url, $parameters, "GET"  ); break; 
			case 'POST' : $response = $this->request( $url, $parameters, "POST" ); break;
		}

		if( $this->decode_json ){
			$response = json_decode( $response ); 
		}

		return $response; 
	}

	/** 
	* GET wrappwer for provider apis request
	*/
	function get( $url, $parameters = array() )
	{
		return $this->api( $url, 'GET', $parameters ); 
	} 

	/** 
	* POST wreapper for provider apis request
	*/
	function post( $url, $parameters = array() )
	{
		return $this->api( $url, 'POST', $parameters ); 
	}

	// -- tokens

	public function tokenInfo($accesstoken)
	{
		$params['access_token'] = $this->access_token;
		return json_decode( $this->request( $this->token_info_url, $params ) );
	}

	public function refreshToken($refresh_token)
	{
		$params = array(
			"client_id"     => $this->client_id, 
			"client_secret" => $this->client_secret, 
			"refresh_token" => $refresh_token,
			"grant_type"    => "refresh_token"
		);

		return json_decode( $this->request( $this->token_url, $params, "POST" ) );
	}

	// -- http requests

	private function request( $url, $params=false, $type="GET" )
	{
		Hybrid_Logger::info( "Enter OAuth2Client::request( $url )" );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request params: ", serialize( $params ) );
		
		if( $type == "GET" ){
			$url = $url . "?" . http_build_query( $params );
		}

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL            , $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
		curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );

		if( $type == "POST" ){
			curl_setopt($ch, CURLOPT_POST, 1); 
			if($params) curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		}

		$result=curl_exec($ch);
		$info=curl_getinfo($ch);
		curl_close($ch);

		Hybrid_Logger::debug( "OAuth2Client::request(). dump request info: ", serialize( $info ) );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request result: ", serialize( $result ) );

		return $result;
	}
}
