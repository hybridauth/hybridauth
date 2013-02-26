<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template;

class OAuth2 extends \Hybridauth\Adapter\AdapterTemplate implements \Hybridauth\Adapter\AdapterInterface
{
	/**
	* adapter initializer 
	*/
	function initialize()
	{
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] ){
			throw new
				\Hybridauth\Exception(
					"Application credentials are missing",
					\Hybridauth\Exception::MISSING_APPLICATION_CREDENTIALS
				);
		}

		// create a new OAuth2 client instance
		$this->api = new \Hybridauth\Adapter\Api\OAuth2\Api();

		$this->api->application->id     = $this->config["keys"]["id"];
		$this->api->application->secret = $this->config["keys"]["secret"];

		$this->api->endpoints->redirectUri = $this->hybridauthEndpoint;

		if ( isset( $this->hybridauthConfig["http_client"] ) && $this->hybridauthConfig["http_client"] ){
			$this->api->httpClient = new $this->hybridauthConfig["http_client"];
		}
		else{
			$this->api->httpClient = new \Hybridauth\Http\Client( $this->hybridauthConfig["curl_options"] );
		}

 		// override requested scope
		if( isset( $this->config["scope"] ) ){
			$this->api->scope = $this->config["scope"];
		}

		// stored access tokens?
		$tokens = $this->getStoredTokens( $this->api->tokens );

		if( $tokens ){
			$this->api->tokens = $tokens;
		}
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		$parameters = $this->api->endpoints->authorizeUriParameters;
		$optionals  = isset( $this->config["authorize_uri_options"] ) ? $this->config["authorize_uri_options"] : array();

		if( $optionals ){
			foreach ($optionals as $k => $v ){
				$parameters[ $k ] = $v;
			}
		}

		$url = $this->api->generateAuthorizeUri( $parameters );

		\Hybridauth\Http\Util::redirect( $url );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish()
	{
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

		// try to authenicate user
		$code = (array_key_exists('code',$_REQUEST))?$_REQUEST['code']:"";

		$this->api->authenticate( $code );

		// check if authenticated
		if ( ! $this->api->tokens->accessToken ){
			throw new
				\Hybridauth\Exception(
					"Authentication failed! {$this->providerId} returned an invalid access token",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		// store tokens
		$this->storeTokens( $this->api->tokens );

		// set user connected locally
		$this->setUserConnected();
	}

	// --------------------------------------------------------------------

	function refreshAccessToken()
	{
		$response = $this->api->refreshAccessToken( array( "refresh_token" => $this->api->tokens->refreshToken ) );

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
		$this->api->accessToken = $response->access_token;

		if( isset( $response->refresh_token ) ){
			$this->api->refreshToken = $response->refresh_token;
		}

		if( isset( $response->expires_in ) && (int) $response->expires_in ){
			$this->api->accessTokenExpiresIn = $response->expires_in;

			// even given by some idp, we should calculate this
			$this->api->accessTokenExpiresAt = time() + (int) $response->expires_in;
		}

		// overwrite stored tokens
		$this->storeTokens( $this->api->tokens );
	}
}
