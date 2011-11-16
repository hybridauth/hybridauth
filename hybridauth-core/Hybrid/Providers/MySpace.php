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
 * Hybrid_Providers_MySpace class, wrapper for MySpaceID  
 */
class Hybrid_Providers_MySpace extends Hybrid_Provider_Model
{
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		} 

		require_once Hybrid_Auth::$config["path_libraries"] . "MySpaceID/Auth_OpenID_CryptUtil.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "MySpaceID/OAuth.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "MySpaceID/MySpace.php";  

		// If we have an access token, set it
		if ( $this->token( "access_token" ) && $this->token( "access_token_secret" ) )
		{
			$this->api = new MySpace
						(
							$this->config["keys"]["key"], $this->config["keys"]["secret"],
							$this->token( "access_token" ), $this->token( "access_token_secret" )
						); 
		}
	}

   /**
	* begin login step 
	*/
	function loginBegin()
	{
		# init new MySpace obj with key + secret
		$this->api = new MySpace( $this->config["keys"]["key"], $this->config["keys"]["secret"] ); 

		# reqest a token from myspaceid api
		try{ 
			$tokz = $this->api->getRequestToken( $this->endpoint );
		}
		catch( MySpaceException $e ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an error while requesting a request token.", 5 );
		}

		if ( ! isset( $tokz["oauth_token"] ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid request token.", 5 );
		}

		$this->token( "request_token" , $tokz['oauth_token'] );
		$this->token( "request_secret", $tokz['oauth_token_secret'] ); 

		# redirect user to MySpace authorisation web page
		Hybrid_Auth::redirect( $this->api->getAuthorizeURL( $tokz['oauth_token'] ) );
	}

   /**
	* finish login step 
	*/
	function loginFinish()
	{ 
		$oauth_verifier = @ $_REQUEST['oauth_verifier']; 

		if ( ! $oauth_verifier )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid Access Token.", 5 );
		}

		try{
			$this->api = new MySpace
						(
							$this->config["keys"]["key"], $this->config["keys"]["secret"],
							$this->token( "request_token" ), $this->token( "request_secret" ),
							TRUE, 
							$oauth_verifier
						);

			$tokz = $this->api->getAccessToken();
		}
		catch( MySpaceException $e ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an error while requesting an access token.", 5 );
		}

		if ( ! is_string($tokz->key) || ! is_string($tokz->secret) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		$this->token( "access_token"  , $tokz->key ); 
		$this->token( "access_token_secret" , $tokz->secret );

		// set user as logged in
		$this->setUserConnected(); 
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{ 
			$data = $this->api->getProfile( $this->api->getCurrentUserId() );
		}
		catch( MySpaceException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile.", 6 );
		}

		if ( ! is_object( $data ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalide response.", 6 );
		} 

		$this->user->profile->identifier    = $this->api->getCurrentUserId();
		$this->user->profile->displayName  	= @ $data->basicprofile->name;
		$this->user->profile->description  	= @ $data->aboutme;
		$this->user->profile->gender     	= @ $data->basicprofile->gender;
		$this->user->profile->photoURL   	= @ $data->basicprofile->image;
		$this->user->profile->profileURL 	= @ $data->basicprofile->webUri;
		$this->user->profile->age 			= @ $data->age;
		$this->user->profile->country 		= @ $data->country;
		$this->user->profile->region 		= @ $data->region;
		$this->user->profile->city 			= @ $data->city;
		$this->user->profile->zip 			= @ $data->postalcode;

		return $this->user->profile;
	}

   /**
	* load the user contacts
	*/
	function getUserContacts()
	{
		try{ 
			$response  = $this->api->getFriends( $this->api->getCurrentUserId() ); 
		}
		catch( MySpaceException $e ){
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error" );
		}

		$contacts = ARRAY();

		foreach( $response->Friends as $item ){ 
			$uc = new Hybrid_User_Contact();

			$uc->identifier   = @ $item->userId;
			$uc->displayName  = @ $item->name;
			$uc->profileURL   = @ $item->webUri;
			$uc->photoURL     = @ $item->image;
			$uc->description  = @ $item->status; 

			$contacts[] = $uc;
		}

		return $contacts;
 	}

   /**
	* update user status
	*/
	function setUserStatus( $status )
	{
		try{ 
			$response = $this->api->updateStatus( $this->api->getCurrentUserId(), $status ); 
		}
		catch( MySpaceException $e ){
			throw new Exception( "Update user status update failed! {$this->providerId} returned an error" );
		}
 
		if ( ! $response  )
		{
			throw new Exception( "Update user status update failed! {$this->providerId} returned an error" );
		} 
 	}

   /**
	* load the user latest activity  
	*    - timeline : all the stream
	*    - me       : the user activity only  
	*/
	function getUserActivity( $stream )
	{  
		try{ 
			if( $stream == "me" ){
				$response  = $this->api->getUserStatus( $this->api->getCurrentUserId() ); 
			}                                                          
			else{                                                      
				$response  = $this->api->getFriendsStatus( $this->api->getCurrentUserId() ); 
			} 
		}
		catch( MySpaceException $e ){
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error" );
		}

		$activities = ARRAY();
		
		if( $stream == "me" ){
			// todo
		}                                                          
		else{                                                      
			foreach( $response->FriendsStatus as $item ){
				$ua = new Hybrid_User_Activity();

				$ua->id                 = @ $item->statusId;
				$ua->date               = NULL;
				$ua->text               = @ $item->status;

				$ua->user->identifier   = @ $item->user->userId;
				$ua->user->displayName  = @ $item->user->name;
				$ua->user->profileURL   = @ $item->user->uri;
				$ua->user->photoURL     = @ $item->user->image;

				$activities[] = $ua;
			}
		} 

		return $activities;
 	}
}
