<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
 * To implement an OAuth 1 based service provider, Hybrid_Provider_Model_OAuth1
 * can be used to save the hassle of the authentication flow. 
 * 
 * Each class that inherit from Hybrid_Provider_Model_OAuth1 have to implemenent
 * at least 2 methods:
 *   Hybrid_Providers_{provider_name}::initialize()     to setup the provider api end-points urls
 *   Hybrid_Providers_{provider_name}::getUserProfile() to grab the user profile
 *
 * Hybrid_Provider_Model_OAuth1 use OAuth1Client v0.1 which can be found on
 * Hybrid/thirdparty/OAuth/OAuth1Client.php
 */
class Hybridauth_Core_Provider_Model_OAuth1 extends Hybridauth_Core_Provider_Model
{
	public $request_tokens_raw = null; // request_tokens as recived from provider

	public $access_tokens_raw  = null; // access_tokens as recived from provider

	// --------------------------------------------------------------------

	/**
	* adapter initializer 
	*/
	function initialize()
	{
		// 1 - check application credentials
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] ){
			throw new Hybridauth_Core_Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		// 3.1 - setup access_token if any stored
		if( $this->token( "access_token" ) ){
			$this->api = new Hybridauth_Core_Provider_Protocol_OAuth1( 
				$this->config["keys"]["key"], $this->config["keys"]["secret"],
				$this->token( "access_token" ), $this->token( "access_token_secret" ) 
			);
		}

		// 3.2 - setup request_token if any stored, in order to exchange with an access token
		elseif( $this->token( "request_token" ) ){
			$this->api = new Hybridauth_Core_Provider_Protocol_OAuth1( 
				$this->config["keys"]["key"], $this->config["keys"]["secret"], 
				$this->token( "request_token" ), $this->token( "request_token_secret" ) 
			);
		}

		// 3.3 - instanciate OAuth client with client credentials
		else{
			$this->api = new Hybridauth_Core_Provider_Protocol_OAuth1( $this->config["keys"]["key"], $this->config["keys"]["secret"] );
		}

		// Set curl proxy if exist
		if( isset( HybridAuth::$config["proxy"] ) ){
			$this->api->curl_proxy = HybridAuth::$config["proxy"];
		}
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		$tokens = $this->api->requestToken( $this->endpoint ); 

		// request tokens as recived from provider
		$this->request_tokens_raw = $tokens;
		
		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Hybridauth_Core_Exception( "Authentication failed! {$this->providerId} returned an error. ", 5 );
		}

		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Hybridauth_Core_Exception( "Authentication failed! {$this->providerId} returned an invalid oauth token.", 5 );
		}

		$this->token( "request_token"       , $tokens["oauth_token"] ); 
		$this->token( "request_token_secret", $tokens["oauth_token_secret"] ); 

		# redirect the user to the provider authentication url
		Hybridauth_Core_Common_HTTP::redirect( $this->api->authorizeUrl( $tokens ) );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/ 
	function loginFinish()
	{
		$oauth_token    = (array_key_exists('oauth_token',$_REQUEST))?$_REQUEST['oauth_token']:"";
		$oauth_verifier = (array_key_exists('oauth_verifier',$_REQUEST))?$_REQUEST['oauth_verifier']:"";

		if ( ! $oauth_token || ! $oauth_verifier ){
			throw new Hybridauth_Core_Exception( "Authentication failed! {$this->providerId} returned an invalid oauth verifier.", 5 );
		}

		// request an access token
		$tokens = $this->api->accessToken( $oauth_verifier );

		// access tokens as recived from provider
		$this->access_tokens_raw = $tokens;

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Hybridauth_Core_Exception( "Authentication failed! {$this->providerId} returned an error. ", 5 );
		}

		// we should have an access_token, or else, something has gone wrong
		if ( ! isset( $tokens["oauth_token"] ) ){
			throw new Hybridauth_Core_Exception( "Authentication failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		// we no more need to store requet tokens
		$this->deleteToken( "request_token"        );
		$this->deleteToken( "request_token_secret" );

		// sotre access_token for later user
		$this->token( "access_token"        , $tokens['oauth_token'] );
		$this->token( "access_token_secret" , $tokens['oauth_token_secret'] ); 

		// set user as logged in to the current provider
		$this->setUserConnected(); 
	}
}
