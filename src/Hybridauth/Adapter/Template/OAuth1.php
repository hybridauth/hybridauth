<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template;

class OAuth1 extends \Hybridauth\Adapter\AdapterTemplate implements \Hybridauth\Adapter\AdapterInterface
{
	/**
	* adapter initializer 
	*/
	function initialize()
	{
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] ){
			throw new
				\Hybridauth\Exception(
					"Application credentials are missing",
					\Hybridauth\Exception::MISSING_APPLICATION_CREDENTIALS
				);
		}

		// OAuth1 API
		$this->api = new \Hybridauth\Adapter\Api\OAuth1\Api();

		$this->api->application->key    = $this->config["keys"]["key"];
		$this->api->application->secret = $this->config["keys"]["secret"];

		$this->api->endpoints->redirectUri = $this->hybridauthEndpoint;

		if ( isset( $this->hybridauthConfig["http_client"] ) && $this->hybridauthConfig["http_client"] ){
			$this->api->httpClient = new $this->hybridauthConfig["http_client"];
		}
		else{
			$curl_options = isset( $this->hybridauthConfig["curl_options"] ) ? $this->hybridauthConfig["curl_options"] : array();

			$this->api->httpClient = new \Hybridauth\Http\Client( $curl_options );
		}

		// stored access tokens?
		$tokens = $this->getStoredTokens( $this->api->tokens );

		if( $tokens ){
			$this->api->tokens = $tokens;
		}

		$this->api->initialize();
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		$this->api->requestAuthToken();

		if ( ! $this->api->tokens || ! $this->api->tokens->requestToken ){
			throw new
				\Hybridauth\Exception(
					"Authentication failed! {$this->providerId} returned invalid oauth_token",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		$parameters = $this->api->endpoints->authorizeUriParameters;
		$optionals  = isset( $this->config["authorize_uri_options"] ) ? $this->config["authorize_uri_options"] : array();

		if( $optionals ){
			foreach ($optionals as $k => $v ){
				$parameters[ $k ] = $v;
			}
		}

		// store tokens
		$this->storeTokens( $this->api->tokens );

		$url = $this->api->generateAuthorizeUri( $parameters );

		\Hybridauth\Http\Util::redirect( $url );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		$oauth_token    = ( array_key_exists( 'oauth_token'   , $_REQUEST ) ) ? $_REQUEST['oauth_token']    : "";
		$oauth_verifier = ( array_key_exists( 'oauth_verifier', $_REQUEST ) ) ? $_REQUEST['oauth_verifier'] : "";

		if ( ! $oauth_token || ! $oauth_verifier ){
			throw new
				\Hybridauth\Exception(
					"Authentication failed! {$this->providerId} returned an invalid oauth verifier",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		// 1.0a
		if ( $oauth_verifier ){
			$this->api->tokens->oauthVerifier = $oauth_verifier;
		}

		$this->api->requestAccessToken();

		// check if authenticated
		if ( ! $this->api->tokens || ! $this->api->tokens->accessToken ){
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
}
