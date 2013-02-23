<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Api;

class OAuth2 extends \Hybridauth\Api\AbstractApi
{
	public function __construct()
	{
		$this->application = new \Hybridauth\Api\Application();
		$this->endpoints   = new \Hybridauth\Api\Endpoints();
		$this->tokens      = new \Hybridauth\Api\Tokens();
		
		$this->client      = new \Hybridauth\Http\Client();

		$this->tokens->accessToken          = null;
		$this->tokens->refreshToken         = null;
		$this->tokens->accessTokenExpiresIn = null;
		$this->tokens->accessTokenExpiresAt = null;
	}

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

	public function authenticate( $code )
	{
		$args = array(
			"client_id"     => $this->application->id,
			"client_secret" => $this->application->secret,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->endpoints->redirectUri,
			"code"          => $code
		);

		$this->client->post( $this->endpoints->requestTokenUri, $args );

		if( $this->client->response->statusCode != 200 ){
			throw new
				\Hybridauth\Exception( "The Authorization Service has return and error.", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}

		$response = json_decode( $this->client->response->body );

		if( isset( $response->access_token  ) ) $this->tokens->accessToken            = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->tokens->refreshToken           = $response->refresh_token; 
		if( isset( $response->expires_in    ) ) $this->tokens->accessTokenExpiresIn   = $response->expires_in; 

		// calculate when the access token expire
		if( isset($response->expires_in)) {
			$this->tokens->accessTokenExpiresAt = time() + $response->expires_in;
		}

		return $response;
	}
	

	/** 
	* Format and sign an oauth for provider api 
	*/
	public function api( $uri, $method = 'GET', $params = array() ) 
	{
		if ( strrpos($uri, 'http://') !== 0 && strrpos($uri, 'https://') !== 0 ) {
			$uri = $this->endpoints->baseUri . $uri;
		}

		$params['access_token'] = $this->tokens->accessToken; 

		switch( $method ){
			case 'GET'  : $this->client->get ( $uri, $params ); break; 
			case 'POST' : $this->client->post( $uri, $params ); break;
		}

		$response = $this->client->response;

		$response = json_decode( $response->body );

		return $response; 
	}
}
