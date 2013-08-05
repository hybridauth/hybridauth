<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template\OAuth2;

use Hybridauth\Exception;
use Hybridauth\Http\Util;
use Hybridauth\Http\Client;

use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;

use Hybridauth\Adapter\Template\OAuth2\Application;
use Hybridauth\Adapter\Template\OAuth2\Endpoints;
use Hybridauth\Adapter\Template\OAuth2\Tokens;

class OAuth2Template extends AbstractAdapter implements AdapterInterface
{
	protected $application = null;
	protected $endpoints   = null;
	protected $tokens      = null;
	protected $httpClient  = null;

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function initialize()
	{
		$this->application = new Application();
		$this->endpoints   = new Endpoints();
		$this->tokens      = new Tokens();
		$this->httpClient  = new Client();

		// http client
		if ( isset( $this->hybridauthConfig["http_client"] ) && $this->hybridauthConfig["http_client"] ){
			$this->httpClient = new $this->hybridauthConfig["http_client"];
		}
		else{
			$curl_options = isset( $this->hybridauthConfig["curl_options"] ) ? $this->hybridauthConfig["curl_options"] : array();

			$this->httpClient = new Client( $curl_options );
		}

		// tokens
		$tokens = $this->getTokens();

		if( $tokens ){
			$this->storeTokens( $tokens );
		}
	}

	// --------------------------------------------------------------------

	/**
	* begin login step
	*/
	function loginBegin()
	{
		// app credentials
		if ( ! $this->getApplicationId() || ! $this->getApplicationSecret() ){
			throw new
				Exception(
					'Application credentials are missing. Check your hybridauth configuration file. ' .
					'For more information refer to http://hybridauth.sourceforge.net/userguide/Configuration.html',
					Exception::MISSING_APPLICATION_CREDENTIALS,
					$this
				);
		}

		$parameters = $this->getEndpointAuthorizeUriAdditionalParameters();

		$url = $this->generateAuthorizeUri( $parameters );

		Util::redirect( $url );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step
	*/
	function loginFinish( $requestAccessTokenParameters = array(), $requestAccessTokenMethod = 'POST' )
	{
		$code  = ( array_key_exists( 'code' , $_REQUEST ) ) ? $_REQUEST['code']  : "";
		$error = ( array_key_exists( 'error', $_REQUEST ) ) ? $_REQUEST['error'] : "";

		if ( $error ){
			throw new
				Exception(
					'Authentication failed: Provider returned an invalid authorization code. ' .
					'Recived error: ' . $error. '. ',
					Exception::AUTHENTIFICATION_FAILED,
					$this
				);
		}

		$requestAccessTokenParameters['code'] = $code;

		$this->requestAccessToken( $requestAccessTokenParameters, $requestAccessTokenMethod );

		// store tokens
		$this->storeTokens( $this->tokens );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function generateAuthorizeUri( $parameters = array() )
	{
		$defaults = array(
			"client_id"     => $this->getApplicationId(),
			"scope"         => $this->getApplicationScope(),
			"redirect_uri"  => $this->getEndpointRedirectUri(),
			"response_type" => "code"
		);

		$parameters = array_merge( $defaults, (array) $parameters );

		return $this->endpoints->authorizeUri . "?" . http_build_query( $parameters );
	}

	// --------------------------------------------------------------------

	/**
	* Exchanges authorization code for an access grant.
	*/
	function requestAccessToken( $parameters = array(), $method = 'POST' )
	{
		$defaults = array(
			"client_id"     => $this->getApplicationId(),
			"client_secret" => $this->getApplicationSecret(),
			"redirect_uri"  => $this->getEndpointRedirectUri(),
			"grant_type"    => "authorization_code"
		);

		$parameters = array_merge( $defaults, (array) $parameters );

		if( $method == 'POST' ){
			$this->httpClient->post( $this->endpoints->requestTokenUri, $parameters );
		}
		else{
			$this->httpClient->get( $this->endpoints->requestTokenUri, $parameters );
		}

		$response = $this->parseRequestResult( $this->httpClient->getResponseBody() );

		if( ! isset( $response->access_token ) || ! $response->access_token ){
			throw new
				Exception(
					'Authentication failed: Provider returned an invalid access token. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::AUTHENTIFICATION_FAILED,
					$this
				);
		}

		if( isset( $response->access_token  ) ) $this->tokens->accessToken          = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->tokens->refreshToken         = $response->refresh_token;
		if( isset( $response->expires_in    ) ) $this->tokens->accessTokenExpiresIn = $response->expires_in;

		// calculate when the access token expire
		if( isset($response->expires_in) ){
			$this->tokens->accessTokenExpiresAt = time() + $response->expires_in;
		}

		$this->storeTokens( $this->tokens );

		return $response;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function refreshAccessToken( $parameters = array(), $method = 'POST', $force = false )
	{
		// have an access token?
		if( ! $force && ! $this->getTokens()->accessToken ){
			return false;
		}

		// have to refresh?
		if( ! $force && ! ( $this->getTokens()->refreshToken && $this->getTokens()->accessTokenExpiresIn ) ){
			return false;
		}

		// expired?
		if( ! $force && $this->getTokens()->accessTokenExpiresIn > time() ){
			return false;
		}

		$defaults = array(
			"client_id"     => $this->getApplicationId(),
			"client_secret" => $this->getApplicationSecret(),
			"grant_type"    => "refresh_token"
		);

		$parameters = array_merge( $defaults, (array) $parameters );

		if( $method == 'POST' ){
			$this->httpClient->post( $this->endpoints->requestTokenUri, $parameters );
		}
		else{
			$this->httpClient->get( $this->endpoints->requestTokenUri, $parameters );
		}

		$response = $this->parseRequestResult( $this->httpClient->getResponseBody() );

		if( $response === false ){
			return;
		}

		// error?
		if( ! isset( $response->access_token ) || ! $response->access_token ){
			throw new
				Exception(
					'Authentication failed: Provider returned an invalid refresh token. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::AUTHENTIFICATION_FAILED,
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
		$this->storeTokens( $this->getTokens() );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function isAuthorized()
	{
		return $this->getTokens()->accessToken != null;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function signedRequest( $uri, $method = 'GET', $parameters = array() )
	{
		if ( strrpos($uri, 'http://') !== 0 && strrpos($uri, 'https://') !== 0 ){
			$uri = $this->endpoints->baseUri . $uri;
		}

		if( ! isset($parameters[ 'access_token' ] ) ) {
			$parameters[ 'access_token' ] = $this->getTokens()->accessToken;
		}

		switch( $method ){
			case 'GET'  : $this->httpClient->get ( $uri, $parameters ); break;
			case 'POST' : $this->httpClient->post( $uri, $parameters ); break;
		}

		return $this->httpClient->getResponseBody();
	}
}
