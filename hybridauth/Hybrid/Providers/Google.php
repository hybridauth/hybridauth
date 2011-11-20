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
 * Hybrid_Providers_Google class 
 */
class Hybrid_Providers_Google extends Hybrid_Provider_Model
{
	// default permissions 
	var $scope = "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/plus.me";

   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] ){
			throw new Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", 4 );
		}

 		// override requested scope
		if( isset( $this->config["scope"] ) && ! empty( $this->config["scope"] ) )
		{
			$this->scope = $this->config["scope"];
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "Google/Google.php";

		$this->api = new googleoauth( $this->config["keys"]["id"], $this->config["keys"]["secret"], $this->endpoint );

		// If we have an access token, set it
		if( $this->token( "access_token" ) && $this->token( "refresh_token" ) )
		{
			$this->api->accesstoken  = $this->token( "access_token" );
			$this->api->refreshtoken = $this->token( "refresh_token" );
		}
	}

   /**
	* begin login step 
	*/
	function loginBegin()
	{
		Hybrid_Auth::redirect( $this->api->loginurl( $this->scope ) ); 
	}
 
   /**
	* finish login step 
	*/
	function loginFinish()
	{
		try{ 
			$this->api->authenticate( false ); 
		}
		catch( Exception $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		} 

		if ( isset( $_REQUEST['error'] ) ){ 
			throw new Exception( "Authentification failed! {$this->providerId} returned an error.", 5 );
		}

		if ( ! $this->api->authenticated() ){ 
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		$this->token( "access_token" , $this->api->accesstoken  );
		$this->token( "refresh_token", $this->api->refreshtoken );

		$this->setUserConnected();
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// ask google api for user infos
		$response = $this->api->call( "https://www.googleapis.com/oauth2/v1/userinfo" );
		$response = json_decode( $response );

		if ( ! is_object( $response ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalide response.", 6 );
		}

		$this->user->profile->identifier    = @ $response->id;
		$this->user->profile->firstName  	= @ $response->given_name;
		$this->user->profile->lastName  	= @ $response->family_name;
		$this->user->profile->displayName  	= @ $response->name;
		$this->user->profile->photoURL  	= @ $response->picture;
		$this->user->profile->profileURL    = "https://profiles.google.com/" . $this->user->profile->identifier;
		$this->user->profile->gender        = @ $response->gender; 
		$this->user->profile->email         = @ $response->email;
		$this->user->profile->language      = @ $response->locale;

		// if user hava a Google+ account and consented then update his name, profile url and avatar
		$response = $this->api->call( "https://www.googleapis.com/plus/v1/people/" . $response->id );
		$response = json_decode( $response );

		if ( is_object( $response ) && ! isset( $response->error ) ){
			if( isset( $response->displayName ) && ! empty( $response->displayName ) ) 
			$this->user->profile->displayName  	= @ $response->displayName;
			$this->user->profile->profileURL  	= @ $response->url;
			$this->user->profile->photoURL  	= @ $response->photoURL->url;

			$this->token( "googleplus_user_id" , $response->id );
		} 

		return $this->user->profile;
	} 
	

   /**
	* load the user latest activity  
	*    - timeline : all the stream
	*    - me       : the user activity only  
	*/
	function _getUserActivity( $stream )
	{
		if ( ! $this->token( "googleplus_user_id" ) ){
			throw new Exception( "User do not have a Google Plus account or haven't consented to access his account." ); 
		}

		try{ 
			$response = $this->api->call( "https://www.googleapis.com/plus/v1/people/" . $this->token( "googleplus_user_id" ) . "/activities/public" );
			$response = json_decode( $response );			
		}
		catch( Exception $e ){
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error: $e" );
		} 

		echo "<pre>";
		print_r( $response );
		print_r( $this );
		
		$activities = ARRAY();
 
		return $activities;
 	}
}
