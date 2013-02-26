<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Api\OAuth2;

class Api implements \Hybridauth\Adapter\Api\ApiInterface
{
	public $application = null;
	public $endpoints   = null;
	public $scope       = null;
	public $tokens      = null;
	public $httpClient  = null;

	// --------------------------------------------------------------------

	public function __construct()
	{
		$this->application = new \Hybridauth\Adapter\Api\Application();
		$this->endpoints   = new \Hybridauth\Adapter\Api\Endpoints();
		$this->tokens      = new \Hybridauth\Adapter\Api\OAuth2\Tokens(); 
		$this->httpClient  = new \Hybridauth\Http\Client();
	}

	// --------------------------------------------------------------------

	public function generateAuthorizeUri( $extras = array() )
	{
		$args = array(
			"client_id"     => $this->application->id,
			"redirect_uri"  => $this->endpoints->redirectUri,
			"scope"         => $this->scope,
			"response_type" => "code"
		);

		if( count($extras) ){
			foreach( $extras as $k=>$v ){
				$args[$k] = $v;
			}
		}

		return $this->endpoints->authorizeUri . "?" . http_build_query( $args );
	}

	// --------------------------------------------------------------------

	public function authenticate( $code )
	{
		$args = array(
			"client_id"     => $this->application->id,
			"client_secret" => $this->application->secret,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->endpoints->redirectUri,
			"code"          => $code
		);

		$this->httpClient->post( $this->endpoints->requestTokenUri, $args );

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

	public function get( $uri, $args = array() ) 
	{
		return $this->_signedRequest( $uri, 'GET', $args );
	}

	// --------------------------------------------------------------------

	public function post( $uri, $args = array() ) 
	{
		return $this->_signedRequest( $uri, 'POST', $args );
	}

	// --------------------------------------------------------------------

	public function refreshAccessToken( $extras = array() )
	{
		// have an access token?
		if( ! $this->tokens->accessToken ){
			return false;
		}

		// have to refresh?
		if( ! ( $this->tokens->refreshToken && $this->api->tokens->accessTokenExpiresIn ) ){
			return false;
		}

		// expired?
		if( $this->tokens->accessTokenExpiresIn > time() ){
			return false;
		}

		$args = array(
			"client_id"     => $this->application->id,
			"client_secret" => $this->application->secret,
			"grant_type"    => "refresh_token"
		);

		foreach( $extras as $k=>$v ){
			$args[$k] = $v; 
		}

		$this->httpClient->post( $this->endpoints->requestTokenUri, $args );

		return $this->_parseRequestResult( $this->httpClient->getResponseBody() );
	}

	// --------------------------------------------------------------------

	/**
	* Format and sign an oauth for provider api 
	*/
	private function _signedRequest( $uri, $method = 'GET', $args = array() )
	{
		if ( strrpos($uri, 'http://') !== 0 && strrpos($uri, 'https://') !== 0 ){
			$uri = $this->endpoints->baseUri . $uri;
		}

		$args[ 'access_token' ] = $this->tokens->accessToken;

		switch( $method ){
			case 'GET'  : $this->httpClient->get ( $uri, $args ); break;
			case 'POST' : $this->httpClient->post( $uri, $args ); break;
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
				\Hybridauth\Exception(
					"Provider returned and error. HTTP Error (" . $this->httpClient->getResponseStatus() . ")",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
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
