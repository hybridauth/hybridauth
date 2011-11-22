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
* Hybrid_Providers_Twitter 
*/
class Hybrid_Providers_Twitter extends Hybrid_Providers_Protocols_OAuth1
{
   /**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// setup provider apis endpoints
		$this->api->api_endpoint_url  = "https://api.twitter.com/1/";
		$this->api->authorize_url     = "https://api.twitter.com/oauth/authorize";
		$this->api->request_token_url = "https://api.twitter.com/oauth/request_token";
		$this->api->access_token_url  = "https://api.twitter.com/oauth/access_token";
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'account/verify_credentials.json' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
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
		$response  = $this->api->get( 'friends/ids.json', $parameters ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if( ! $response || ! count( $response->ids ) ){
			return ARRAY();
		}

		// 75 id per time
		$contactsids = array_chunk ( $response->ids, 75 );

		$contacts    = ARRAY(); 

		foreach( $contactsids as $chunk ){ 
			$parameters = array( 'user_id' => implode( ",", $chunk ) ); 
			$response   = $this->api->get( 'users/lookup.json', $parameters ); 

			// check the last HTTP status code returned
			if ( $this->api->http_code != 200 )
			{
				throw new Exception( "User contacts request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
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
		$response  = $this->api->post( 'statuses/update.json', $parameters ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "Update user status failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
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
			$response  = $this->api->get( 'statuses/user_timeline.json' ); 
		}                                                          
		else{
			$response  = $this->api->get( 'statuses/home_timeline.json' ); 
		}

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
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
