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
 * Hybrid_Providers_AOL class 
 */
class Hybrid_Providers_AOL extends Hybrid_Provider_Model
{ 
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["devid"] )
		{
			throw new Exception( "Your devid is required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "AOL/AOL_OpenAuth.php";  

		$this->api = new AOL_OpenAuth_Client( $this->config["keys"]["devid"], $this->endpoint ); 
		
		if( $this->token( "access_token" ) )
		{
			$this->api->set_auth_token( $this->token( "access_token" ) );
		} 
	}

   /**
	* begin login step 
	*/
	function loginBegin()
	{ 
		$this->api->require_login();
	}
 
   /**
	* finish login step 
	*/
	function loginFinish()
	{ 
		try{
			$uid = $this->api->get_loggedin_user();
		}
		catch( Exception $e ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid response.", 5 );
		}

		if ( ! $uid )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid response.", 5 );
		} 

		// Store tokens 
		$this->token( "access_token", $this->api->get_auth_token() );   
 
		// set user as logged in
		$this->setUserConnected();
	}

   /**
	* logout
	*/
	function logout()
	{ 
		$this->api->expire_session(); 

		parent::logout();
	}
   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{ 
			$data = $this->api->get_loggedin_user_infos(); 
			
			$data = json_decode( $data );
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
		} 
 
		if ( ! is_object( $data ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		} 

		$this->user->profile->identifier    = @ $data->response->data->userData->loginId;
		$this->user->profile->displayName  	= @ $data->response->data->userData->displayName; 

		return $this->user->profile;
	}
}
