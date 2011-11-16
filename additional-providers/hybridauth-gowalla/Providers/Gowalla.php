<?php
/**
* HybridAuth
* 
* A Social-Sign-On PHP Library for authentication through identity providers like Facebook,
* Twitter, Google, Yahoo, LinkedIn, MySpace, Windows Live, Tumblr, Friendster, OpenID, PayPal,
* Vimeo, Foursquare, AOL, Gowalla, and others.
*
* Copyright (c) 2009-2011 (http://hybridauth.sourceforge.net) 
*/

/**
 * Hybrid_Providers_Gowalla class, wrapper for Gowalla  
 */
class Hybrid_Providers_Gowalla extends Hybrid_Provider_Model
{ 
	var $redirect_uri = NULL;  
	
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "Gowalla/Gowalla.php";  

		$this->redirect_uri = $this->endpoint . "&";

		$this->api = new Gowalla( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri );
	}

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		// authenticate app
		$this->api->authenticate();
	}

	/**
	* finish login step 
	*/ 
	function loginFinish()
	{ 
		$code  = @$_REQUEST['code'];

		$response = $this->api->requestToken( $code );
 
		if ( ! $response )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid Token.", 5 );
		}

		$this->token( "access_token" , $response['access_token'] );   
 
		$this->api = new Gowalla( $this->config["keys"]["key"], $this->config["keys"]["secret"], $this->redirect_uri, $this->token( "access_token" ) );  

		// set user as logged in
		$this->setUserConnected();
 	}
	
   /**
	* load the user profile from the IDp api client 
	*/
	function getUserProfile()
	{
		$response = $this->api->getMe();

		if ( ! $response )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->providerUID         	= @ (string) $response["username"]; 
		$this->user->profile->firstName  	= @ (string) $response["first_name"]; 
		$this->user->profile->lastName  	= @ (string) $response["last_name"]; 
		$this->user->profile->displayName  	= trim( $this->user->profile->firstName . " " . $this->user->profile->lastName );

		if( isset( $response["url"] ) ){
			$this->user->profile->profileURL = @ "http://gowalla.com" . ( (string) $response["url"] ); 
		}

		$this->user->profile->webSiteURL 	= @ (string) $response["website"]; 
		$this->user->profile->photoURL   	= @ (string) $response["image_url"]; 

		return $this->user->profile;
	}
}
