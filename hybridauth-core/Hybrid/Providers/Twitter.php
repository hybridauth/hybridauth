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
* Hybrid_Providers_Twitter class, wrapper for Twitter auth/api 
*/
class Hybrid_Providers_Twitter extends Hybrid_Provider_Model
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

		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "TwitterCompatible/TwitterCompatibleClient.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "TwitterCompatible/Twitter.php";

		if( $this->token( "access_token" ) && $this->token( "access_token_secret" ) )
		{
			$this->api = new Twitter_Client
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
 	    $this->api = new Twitter_Client( $this->config["keys"]["key"], $this->config["keys"]["secret"] );

		$tokz = $this->api->getRequestToken( $this->endpoint ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an error: " . $this->api->lastErrorMessageFromStatus(), 5 );
		}

		if ( ! isset( $tokz["oauth_token"] ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid oauth token.", 5 );
		}

		$this->token( "request_token"       , $tokz["oauth_token"] ); 
		$this->token( "request_token_secret", $tokz["oauth_token_secret"] ); 

		# redirect user to twitter 
		Hybrid_Auth::redirect( $this->api->getAuthorizeURL( $tokz ) );
	}

   /**
	* finish login step 
	*/ 
	function loginFinish()
	{ 
		$oauth_token    = @ $_REQUEST['oauth_token']; 
		$oauth_verifier = @ $_REQUEST['oauth_verifier']; 

		if ( ! $oauth_token || ! $oauth_verifier )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid oauth verifier.", 5 );
		}

		$this->api = new Twitter_Client( 
							$this->config["keys"]["key"], $this->config["keys"]["secret"], 
							$this->token( "request_token" ), $this->token( "request_token_secret" ) 
						);

		$tokz = $this->api->getAccessToken( $oauth_verifier );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an error: " . $this->api->lastErrorMessageFromStatus(), 5 );
		}

		if ( ! isset( $tokz["oauth_token"] ) )
		{
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid access token.", 5 );
		}

		$this->token( "access_token"        , $tokz['oauth_token'] );
		$this->token( "access_token_secret" , $tokz['oauth_token_secret'] ); 

		// set user as logged in
		$this->setUserConnected();
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'account/verify_credentials' ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: " . $this->api->lastErrorMessageFromStatus(), 6 );
		}

		if ( ! is_object( $response ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		} 

		# store the user profile.  
		$this->user->profile->identifier    = @ $response->id;
		$this->user->profile->displayName  	= @ $response->screen_name;
		$this->user->profile->description  	= @ $response->description;
		$this->user->profile->firstName  	= @ $response->name; 
		$this->user->profile->photoURL   	= @ $response->profile_image_url;
		$this->user->profile->profileURL 	= @ 'http://twitter.com/' . $response->screen_name;
		$this->user->profile->webSiteURL 	= @ $response->url; 
		$this->user->profile->address 		= @ $response->location;

		return $this->user->profile;
 	}

   /**
	* load the user contacts
	*/
	function getUserContacts()
	{
		$parameters = array( 'cursor' => '-1' ); 
		$response  = $this->api->get( 'friends/ids', $parameters ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error: " . $this->api->lastErrorMessageFromStatus() );
		}

		if( ! $response || ! count( $response->ids ) ){
			return ARRAY();
		}

		// 75 id per time
		$contactsids = array_chunk ( $response->ids, 75 );

		$contacts    = ARRAY(); 

		foreach( $contactsids as $chunk ){ 
			$parameters = array( 'user_id' => implode( ",", $chunk ) ); 
			$response   = $this->api->get( 'users/lookup', $parameters ); 

			// check the last HTTP status code returned
			if ( $this->api->http_code != 200 )
			{
				throw new Exception( "User contacts request failed! {$this->providerId} returned an error: " . $this->api->lastErrorMessageFromStatus() );
			}

			if( $response && count( $response ) ){
				foreach( $response as $item ){ 
					$uc = new Hybrid_User_Contact();

					$uc->identifier   = @ $item->id;
					$uc->displayName  = @ $item->name;
					$uc->profileURL   = @ 'http://twitter.com/' . $item->screen_name;
					$uc->photoURL     = @ $item->profile_image_url;
					$uc->description  = @ $item->description; 

					$contacts[] = $uc;
				} 
			} 
		}

		return $contacts;
 	}

   /**
	* update user status
	*/ 
	function setUserStatus( $status )
	{
		$parameters = array( 'status' => $status ); 
		$response  = $this->api->post( 'statuses/update', $parameters ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "Update user status failed! {$this->providerId} returned an error: " . $this->api->lastErrorMessageFromStatus() );
		}
 	}

   /**
	* load the user latest activity  
	*    - timeline : all the stream
	*    - me       : the user activity only  
	*
	* by default return the timeline
	*/ 
	function getUserActivity( $stream )
	{
		if( $stream == "me" ){
			$response  = $this->api->get( 'statuses/user_timeline' ); 
		}                                                          
		else{
			$response  = $this->api->get( 'statuses/home_timeline' ); 
		}

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error: " . $this->api->lastErrorMessageFromStatus() );
		}

		if( ! $response ){
			return ARRAY();
		}

		$activities = ARRAY();

		foreach( $response as $item ){
			$ua = new Hybrid_User_Activity();

			$ua->id                 = @ $item->id;
			$ua->date               = @ strtotime( $item->created_at );
			$ua->text               = @ $item->text;

			$ua->user->identifier   = @ $item->user->id;
			$ua->user->displayName  = @ $item->user->name;
			$ua->user->profileURL   = @ 'http://twitter.com/' . $item->user->screen_name;
			$ua->user->photoURL     = @ $item->user->profile_image_url;
			
			$activities[] = $ua;
		}

		return $activities;
 	}
}
