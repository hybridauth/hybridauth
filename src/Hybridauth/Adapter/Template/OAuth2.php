<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template;

/**
 * To implement an OAuth 2 based service provider, Hybrid_Provider_Model_OAuth2
 * can be used to save the hassle of the authentication flow. 
 * 
 * Each class that inherit from Hybrid_Provider_Model_OAuth2 have to implemenent
 * at least 2 methods:
 *   Hybrid_Providers_{provider_name}::initialize()     to setup the provider api end-points urls
 *   Hybrid_Providers_{provider_name}::getUserProfile() to grab the user profile
 *
 * Hybrid_Provider_Model_OAuth2 use OAuth2Client v0.1 which can be found on
 * Hybrid/thirdparty/OAuth/OAuth2Client.php
 */
class OAuth2 extends \Hybridauth\Adapter\Template\AdapterTemplate
{
	// default permissions 
	public $scope = null;

	// --------------------------------------------------------------------

	/**
	* adapter initializer 
	*/
	function initialize()
	{
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] ){
			throw new
				\Hybridauth\Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", \Hybridauth\Exception::MISSING_APPLICATION_CREDENTIALS );
		}

 		// override requested scope
		if( isset( $this->config["scope"] ) && ! empty( $this->config["scope"] ) ){
			$this->scope = $this->config["scope"];
		}

		// create a new OAuth2 client instance
		$this->api = new \Hybridauth\Adapter\Api\OAuth2();

		$this->api->application->id     = $this->config["keys"]["id"];
		$this->api->application->secret = $this->config["keys"]["secret"];

		$this->api->endpoints->redirectUri = $this->endpoint;

		if ( isset( $this->hybridauthConfig["http_client"] ) && is_object( $this->hybridauthConfig["http_client"] ) ){
			$this->api->client = $this->hybridauthConfig["http_client"];
		}
		else{
			$this->api->client = new \Hybridauth\Http\Client();

			$this->api->client->curlOptions = $this->hybridauthConfig["curl_options"];
		}

		// stored access tokens?
		if( $this->token( "access_token" ) ){
			$this->api->tokens->accessToken          = $this->token( "access_token"  );
			$this->api->tokens->refreshToken         = $this->token( "refresh_token" );
			$this->api->tokens->accessTokenExpiresIn = $this->token( "expires_in"    );
			$this->api->tokens->accessTokenExpiresAt = $this->token( "expires_at"    );
		}
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		$url = $this->api->generateAuthorizeUri( array( "scope" => $this->scope ) );

		\Hybridauth\Http\Util::redirect( $url );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		$error = (array_key_exists('error',$_REQUEST))?$_REQUEST['error']:"";

		// check for errors
		if ( $error ){ 
			throw new
				\Hybridauth\Exception( "Authentication failed! {$this->providerId} returned an error: $error", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}

		// try to authenicate user
		$code = (array_key_exists('code',$_REQUEST))?$_REQUEST['code']:"";

		$this->api->authenticate( $code );

		// check if authenticated
		if ( ! $this->api->tokens->accessToken ){ 
			throw new
				\Hybridauth\Exception( "Authentication failed! {$this->providerId} returned an invalid access token.", \Hybridauth\Exception::AUTHENTIFICATION_FAILED, null, $this );
		}

		// store tokens
		$this->token( "access_token" , $this->api->tokens->accessToken          );
		$this->token( "refresh_token", $this->api->tokens->refreshToken         );
		$this->token( "expires_in"   , $this->api->tokens->accessTokenExpiresIn );
		$this->token( "expires_at"   , $this->api->tokens->accessTokenExpiresAt );

		// set user connected locally
		$this->setUserConnected();
	}
	
	function refreshToken()
	{
		// have an access token?
		if( $this->api->tokens->accessToken ){

			// have to refresh?
			if( $this->api->tokens->refreshToken && $this->api->tokens->accessTokenExpiresAt ){

				// expired? 
				/*
				if( $this->api->tokens->accessTokenExpiresAt <= time() ){ 
					$response = $this->api->refreshToken( array( "refresh_token" => $this->api->refresh_token ) );

					if( ! isset( $response->access_token ) || ! $response->access_token ){
						// set the user as disconnected at this point and throw an \Hybridauth\Exception
						$this->setUserUnconnected();

						throw new
							\Hybridauth\Exception( "The Authorization Service has return an invalid response while requesting a new access token. " . (string) $response->error ); 
					}

					// set new access_token
					$this->api->access_token = $response->access_token;

					if( isset( $response->refresh_token ) ) 
					$this->api->refresh_token = $response->refresh_token; 

					if( isset( $response->expires_in ) ){
						$this->api->access_token_expires_in = $response->expires_in;

						// even given by some idp, we should calculate this
						$this->api->tokens->accessTokenExpiresAt = time() + $response->expires_in; 
					}
				} */
			}

			// re store tokens
			$this->token( "access_token" , $this->api->tokens->accessToken          );
			$this->token( "refresh_token", $this->api->tokens->refreshToken         );
			$this->token( "expires_in"   , $this->api->tokens->accessTokenExpiresIn );
			$this->token( "expires_at"   , $this->api->tokens->accessTokenExpiresAt ); 
		}
	}
}
