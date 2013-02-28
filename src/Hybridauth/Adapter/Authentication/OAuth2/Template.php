<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Authentication\OAuth2;

class Template implements \Hybridauth\Adapter\AuthenticationInterface
{
	public $application = null;
	public $endpoints   = null;
	public $scope       = null;
	public $tokens      = null;
	public $httpClient  = null;

	// --------------------------------------------------------------------

	public function __construct()
	{
		$this->application = new \Hybridauth\Adapter\Authentication\OAuth2\Application();
		$this->endpoints   = new \Hybridauth\Adapter\Authentication\OAuth2\Endpoints();
		$this->tokens      = new \Hybridauth\Adapter\Authentication\OAuth2\Tokens();
		$this->httpClient  = new \Hybridauth\Http\Client();
	}

	// --------------------------------------------------------------------

	function initialize( $options = array() )
	{
		// app credentials
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] ){
			throw new
				\Hybridauth\Exception(
					"Application credentials are missing",
					\Hybridauth\Exception::MISSING_APPLICATION_CREDENTIALS
				);
		}

		$this->application->id     = $this->config["keys"]["id"];
		$this->application->secret = $this->config["keys"]["secret"];

		// http client
		if ( isset( $this->hybridauthConfig["http_client"] ) && $this->hybridauthConfig["http_client"] ){
			$this->httpClient = new $this->hybridauthConfig["http_client"];
		}
		else{
			$curl_options = isset( $this->hybridauthConfig["curl_options"] ) ? $this->hybridauthConfig["curl_options"] : array();

			$this->httpClient = new \Hybridauth\Http\Client( $curl_options );
		}

		// tokens
		$tokens = $this->getStoredTokens( $this->tokens );

		if( $tokens ){
			$this->tokens = $tokens;
		}

		// end-points
		$this->endpoints->redirectUri     = $this->hybridauthEndpoint;
		$this->endpoints->baseUri         = isset( $options['api_base_uri']      ) ? $options['api_base_uri']      : '';
		$this->endpoints->authorizeUri    = isset( $options['authorize_uri']     ) ? $options['authorize_uri']     : '';
		$this->endpoints->requestTokenUri = isset( $options['request_token_uri'] ) ? $options['request_token_uri'] : '';
		$this->endpoints->tokenInfoUri    = isset( $options['token_info_uri']    ) ? $options['token_info_uri']    : '';

		$this->endpoints->authorizeUriParameters = isset( $options['authorize_uri_args'] ) ? $options['authorize_uri_args'] : array();

		$this->scope = isset( $options['scope'] ) ? $options['scope'] : '';
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		$parameters = $this->endpoints->authorizeUriParameters;
		$optionals  = isset( $this->config["authorize_uri_args"] ) ? $this->config["authorize_uri_args"] : array();

		$parameters = array_merge( $parameters, (array) $optionals );

		$url = $this->generateAuthorizeUri( $parameters );

		\Hybridauth\Http\Util::redirect( $url );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish( $code = null, $parameters = array(), $method = 'POST' )
	{
		if( ! $code ){
			$code  = ( array_key_exists( 'code', $_REQUEST  ) ) ? $_REQUEST['code']  : "";
			$error = ( array_key_exists( 'error', $_REQUEST ) ) ? $_REQUEST['error'] : "";

			if ( $error ){
				throw new
					\Hybridauth\Exception(
						"Authentication failed! {$this->providerId} returned an error: $error",
						\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
						null,
						$this
					);
			}
		}

		$this->requestAccessToken( $code, $parameters, $method );

		// check if authenticated
		if ( ! $this->tokens->accessToken ){
			throw new
				\Hybridauth\Exception(
					"Authentication failed! {$this->providerId} returned an invalid access token",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		// store tokens
		$this->storeTokens( $this->tokens );
	}

	// --------------------------------------------------------------------

	public function generateAuthorizeUri( $parameters = array() )
	{
		$defaults = array(
			"client_id"     => $this->application->id,
			"redirect_uri"  => $this->endpoints->redirectUri,
			"scope"         => $this->scope,
			"response_type" => "code"
		);

		$parameters = array_merge( $defaults, (array) $parameters );

		return $this->endpoints->authorizeUri . "?" . http_build_query( $parameters );
	}

	// --------------------------------------------------------------------

	public function getStoredTokens()
	{
		return $this->storage->get( "hauth_session.{$this->providerId}.tokens" );
	}

	// --------------------------------------------------------------------

	public function storeTokens( \Hybridauth\Adapter\Authentication\OAuth2\TokensInterface $tokens )
	{
		$this->storage->set( "hauth_session.{$this->providerId}.tokens", $tokens );
	}

	// --------------------------------------------------------------------

	public function isAuthorized()
	{
		return $this->tokens->accessToken != null;
	}

	// --------------------------------------------------------------------

	function refreshAccessToken( $parameters = array(), $method = 'POST', $force = false )
	{
		// have an access token?
		if( ! $force && ! $this->tokens->accessToken ){
			return false;
		}

		// have to refresh?
		if( ! $force && ! ( $this->tokens->refreshToken && $this->api->tokens->accessTokenExpiresIn ) ){
			return false;
		}

		// expired?
		if( ! $force && $this->tokens->accessTokenExpiresIn > time() ){
			return false;
		}

		$defaults = array(
			"client_id"     => $this->application->id,
			"client_secret" => $this->application->secret,
			"grant_type"    => "refresh_token"
		);

		$parameters = array_merge( $defaults, (array) $parameters );

		if( $method == 'POST' ){
			$this->httpClient->post( $this->endpoints->requestTokenUri, $parameters );
		}
		else{
			$this->httpClient->get( $this->endpoints->requestTokenUri, $parameters );
		}

		$response = $this->_parseRequestResult( $this->httpClient->getResponseBody() ); 

		if( $response === false ){
			return;
		}

		// error?
		if( ! isset( $response->access_token ) || ! $response->access_token ){
			// set the user as disconnected at this point and throw an exception
			$this->setUserUnconnected();

			throw new
				\Hybridauth\Exception(
					"Authentication failed! {$this->providerId} returned an invalid access/refresh token",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		// set new access_token
		$this->accessToken = $response->access_token;

		if( isset( $response->refresh_token ) ){
			$this->refreshToken = $response->refresh_token;
		}

		if( isset( $response->expires_in ) && (int) $response->expires_in ){
			$this->accessTokenExpiresIn = $response->expires_in;

			// even given by some idp, we should calculate this
			$this->accessTokenExpiresAt = time() + (int) $response->expires_in;
		}

		// overwrite stored tokens
		$this->storeTokens( $this->tokens );
	}

	// --------------------------------------------------------------------

	/**
	* Exchanges authorization code for an access grant.
	*/
	public function requestAccessToken( $code, $parameters = array(), $method = 'POST' )
	{
		$defaults = array(
			"client_id"     => $this->application->id,
			"client_secret" => $this->application->secret,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->endpoints->redirectUri,
			"code"          => $code
		);

		$parameters = array_merge( $defaults, (array) $parameters );

		if( $method == 'POST' ){
			$this->httpClient->post( $this->endpoints->requestTokenUri, $parameters );
		}
		else{
			$this->httpClient->get( $this->endpoints->requestTokenUri, $parameters );
		}

		// fixme!
		// default ha client uses curl
		if( $this->httpClient->getResponseError() ){
			throw new
				\Hybridauth\Exception(
					"Provider returned and error. CURL Error (" . $this->httpClient->getResponseError() . "). For more information refer to http://curl.haxx.se/libcurl/c/libcurl-errors.html",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		if( $this->httpClient->getResponseStatus() != 200 ){
			throw new
				\Hybridauth\Exception( "Provider returned and error. HTTP Error (" . $this->httpClient->getResponseStatus() . ")", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}

		$response = $this->_parseRequestResult( $this->httpClient->getResponseBody() );

		if( isset( $response->access_token  ) ) $this->tokens->accessToken          = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->tokens->refreshToken         = $response->refresh_token;
		if( isset( $response->expires_in    ) ) $this->tokens->accessTokenExpiresIn = $response->expires_in; 

		// calculate when the access token expire
		if( isset($response->expires_in) ){
			$this->tokens->accessTokenExpiresAt = time() + $response->expires_in;
		}

		return $response;
	}
 
	// --------------------------------------------------------------------

	public function get( $uri, $parameters = array() ) 
	{
		return $this->_signedRequest( $uri, 'GET', $parameters );
	}

	// --------------------------------------------------------------------

	public function post( $uri, $parameters = array() ) 
	{
		return $this->_signedRequest( $uri, 'POST', $parameters );
	}

	// --------------------------------------------------------------------

	private function _signedRequest( $uri, $method = 'GET', $parameters = array() )
	{
		if ( strrpos($uri, 'http://') !== 0 && strrpos($uri, 'https://') !== 0 ){
			$uri = $this->endpoints->baseUri . $uri;
		}

		$parameters[ 'access_token' ] = $this->tokens->accessToken;

		switch( $method ){
			case 'GET'  : $this->httpClient->get ( $uri, $parameters ); break;
			case 'POST' : $this->httpClient->post( $uri, $parameters ); break;
		}

		return $this->httpClient->getResponseBody();
	}

	// --------------------------------------------------------------------

	private function _parseRequestResult( $result )
	{
		if( json_decode( $result ) ){
			return json_decode( $result );
		}

		parse_str( $result, $ouput );

		$result = new \StdClass();

		foreach( $ouput as $k => $v ){
			$result->$k = $v;
		}

		return $result;
	}
}
