<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Api;

class OAuth2 extends \Hybridauth\Adapter\Api\AbstractApi
{
	public function __construct()
	{
		$this->application = new \Hybridauth\Adapter\Api\Application();
		$this->endpoints   = new \Hybridauth\Adapter\Api\Endpoints();
		$this->tokens      = new \Hybridauth\Adapter\Api\Tokens();
		$this->httpClient  = new \Hybridauth\Http\Client();

		$this->tokens->accessToken          = null;
		$this->tokens->refreshToken         = null;
		$this->tokens->accessTokenExpiresIn = null;
		$this->tokens->accessTokenExpiresAt = null;
	}

	// --------------------------------------------------------------------

	public function generateAuthorizeUri( $extras = array() )
	{
		$args = array(
			"client_id"     => $this->application->id,
			"redirect_uri"  => $this->endpoints->redirectUri,
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

		// default ha client uses curl
		if( $this->httpClient->getResponseError() ){
			throw new
				\Hybridauth\Exception(
					"Error CURL (" . $this->httpClient->getResponseError() . "). For more information refer to http://curl.haxx.se/libcurl/c/libcurl-errors.html",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		if( $this->httpClient->getResponseStatus() != 200 ){
			throw new
				\Hybridauth\Exception( "The Authorization Service has return and error", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
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

	/** 
	* Format and sign an oauth for provider api 
	*/
	public function api( $uri, $method = 'GET', $params = array() ) 
	{
		if ( strrpos($uri, 'http://') !== 0 && strrpos($uri, 'https://') !== 0 ){
			$uri = $this->endpoints->baseUri . $uri;
		}

		$params['access_token'] = $this->tokens->accessToken;

		switch( $method ){
			case 'GET'  : $this->httpClient->get ( $uri, $params ); break;
			case 'POST' : $this->httpClient->post( $uri, $params ); break;
		}

		$response = json_decode( $this->httpClient->getResponseBody() );

		return $response;
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
