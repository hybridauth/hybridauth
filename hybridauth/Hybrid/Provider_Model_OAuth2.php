<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

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
class Hybrid_Provider_Model_OAuth2 extends Hybrid_Provider_Model
{
	// default permissions 
	public $scope = "";

	// --------------------------------------------------------------------

	/**
	* adapter initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] ){
			throw new Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", 4 );
		}

 		// override requested scope
		if( isset( $this->config["scope"] ) && ! empty( $this->config["scope"] ) ){
			$this->scope = $this->config["scope"];
		}

		// include OAuth2 client
		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth2Client.php";

		// create a new OAuth2 client instance
		$this->api = new OAuth2Client( $this->config["keys"]["id"], $this->config["keys"]["secret"], $this->endpoint );

		// If we have an access token, set it
		if( $this->token( "access_token" ) ){
			$this->api->access_token  = $this->token( "access_token" );
			$this->api->refresh_token = $this->token( "refresh_token" );
		}
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		// redirect the user to the provider authentication url
		Hybrid_Auth::redirect( $this->api->authorizeUrl( array( "scope" => $this->scope ) ) ); 
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		$error = @ trim( strip_tags( $_REQUEST['error'] ) );

		// check for errors
		if ( $error ){ 
			throw new Exception( "Authentification failed! {$this->providerId} returned an error: $error", 5 );
		}

		// try to authenicate user
		$code = @ trim( strip_tags( $_REQUEST['code'] ) );

		try{
			$this->api->authenticate( $code ); 
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		}

		// check if authenticated
		if ( ! $this->api->authenticated() ){ 
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		// store tokens
		$this->token( "access_token" , $this->api->access_token  );
		$this->token( "refresh_token", $this->api->refresh_token );

		// set user connected locally
		$this->setUserConnected();
	}
}
