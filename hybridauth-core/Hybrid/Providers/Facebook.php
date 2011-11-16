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
 * Hybrid_Providers_Facebook class, wrapper for Facebook Connect
 */
class Hybrid_Providers_Facebook extends Hybrid_Provider_Model
{
	// default permissions
	var $scope = "email, user_about_me, user_birthday, user_hometown, user_website, read_stream, publish_stream, read_friendlists";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] )
		{
			throw new Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "Facebook/base_facebook.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "Facebook/facebook.php";

		$this->api = new Facebook( ARRAY( 'appId' => $this->config["keys"]["id"], 'secret' => $this->config["keys"]["secret"] ) ); 

		$this->api->getUser();
	}

   /**
	* begin login step
	* 
	* simply call Facebook::require_login(). 
	*/
	function loginBegin()
	{
		// override requested scope
		if( isset( $this->config["scope"] ) && ! empty( $this->config["scope"] ) )
		{
			$this->scope = $this->config["scope"];
		}

		// get the login url 
		$url = $this->api->getLoginUrl( array( 'scope' => $this->scope, 'redirect_uri' => $this->endpoint ) );

		// redirect to facebook
		Hybrid_Auth::redirect( $url ); 
	}

	/**
	* finish login step 
	*/
	function loginFinish()
	{ 
		// in case we get error_reason=user_denied&error=access_denied
		if ( isset( $_REQUEST['error'] ) && $_REQUEST['error'] == "access_denied" ){ 
			throw new Exception( "Authentification failed! The user denied your request.", 5 );
		}

		// try to get the UID of the connected user from fb, should be > 0 
		if ( ! $this->api->getUser() ){
			throw new Exception( "Authentification failed! {$this->providerId} returned an invalide user id.", 5 );
		}

		// set user as logged in
		$this->setUserConnected();

		// try to detect the access token for facebook
		if( isset( $_SESSION["fb_" . $this->api->getAppId() . "_access_token" ] ) ){
			$this->token( "access_token", $_SESSION["fb_" . $this->api->getAppId() . "_access_token" ] );
		}
	}

   /**
	* logout
	*/
	function logout()
	{ 
		$this->api->destroySession();

		parent::logout();
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// request user profile from fb api
		try{ 
			$data = $this->api->api('/me'); 
		}
		catch( FacebookApiException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		} 

		// if the provider identifier is not recived, we assume the auth has failed
		if ( ! isset( $data["id"] ) )
		{ 
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		# store the user profile.  
		$this->user->profile->identifier    = @ $data['id'];
		$this->user->profile->displayName   = @ $data['name'];
		$this->user->profile->firstName     = @ $data['first_name'];
		$this->user->profile->lastName     	= @ $data['last_name'];
		$this->user->profile->photoURL      = "https://graph.facebook.com/" . $this->user->profile->identifier . "/picture?type=square";
		$this->user->profile->profileURL 	= @ $data['link']; 
		$this->user->profile->webSiteURL 	= @ $data['website']; 
		$this->user->profile->gender     	= @ $data['gender'];
		$this->user->profile->description  	= @ $data['bio'];
		$this->user->profile->email      	= @ $data['email'];
		$this->user->profile->region      	= @ $data['hometown']["name"];

		if( isset( $data['birthday'] ) ) {
			list($birthday_month, $birthday_day, $birthday_year) = @ explode('/', $data['birthday'] );

			$this->user->profile->birthDay      = $birthday_day;
			$this->user->profile->birthMonth    = $birthday_month;
			$this->user->profile->birthYear     = $birthday_year;
		}

		return $this->user->profile;
 	}

   /**
	* load the user contacts
	*/
	function getUserContacts()
	{
		try{ 
			$response = $this->api->api('/me/friends'); 
		}
		catch( FacebookApiException $e ){
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error: $e" );
		} 
 
		if( ! $response || ! count( $response["data"] ) ){
			return ARRAY();
		}

		$contacts = ARRAY();
 
		foreach( $response["data"] as $item ){
			$uc = new Hybrid_User_Contact();

			$uc->identifier   = @ $item["id"];
			$uc->displayName  = @ $item["name"];
			$uc->profileURL   = "https://www.facebook.com/profile.php?id=" . $uc->identifier;
			$uc->photoURL     = "https://graph.facebook.com/" . $uc->identifier . "/picture?type=square"; 

			$contacts[] = $uc;
		}

		return $contacts;
 	}

   /**
	* update user status
	*/
	function setUserStatus( $status )
	{
		$parameters = array();

		if( is_array( $status ) ){
			if( isset( $status[0] ) && ! empty( $status[0] ) ) $parameters["message"] = $status[0]; // status content
			if( isset( $status[1] ) && ! empty( $status[1] ) ) $parameters["link"]    = $status[1]; // item link
			if( isset( $status[2] ) && ! empty( $status[2] ) ) $parameters["picture"] = $status[2]; // picture link
		}
		else{
			$parameters["message"] = $status; 
		}

		try{ 
			$response = $this->api->api( "/me/feed", "post", $parameters );
		}
		catch( FacebookApiException $e ){
			throw new Exception( "Update user status failed! {$this->providerId} returned an error: $e" );
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
				$response = $this->api->api( '/me/feed' ); 
			}
			else{
				$response = $this->api->api('/me/home'); 
			}
		}
		catch( FacebookApiException $e ){
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error: $e" );
		} 

		if( ! $response || ! count(  $response['data'] ) ){
			return ARRAY();
		}

		$activities = ARRAY();

		foreach( $response['data'] as $item ){
			if( $stream == "me" && $item["from"]["id"] != $this->api->getUser() ){
				continue;
			}
		
			$ua = new Hybrid_User_Activity();

			$ua->id                 = @ $item["id"];
			$ua->date               = @ $item["created_time"];

			if( $item["type"] == "video" ){
				$ua->text           = @ $item["name"] . " " . $item["link"];
			}

			if( $item["type"] == "link" ){
				$ua->text           = @ $item["caption"] . " " . $item["link"];
			}

			if( empty( $ua->text ) && isset( $item["story"] ) ){
				$ua->text           = @ $item["story"] . " " . $item["link"];
			}

			if( empty( $ua->text ) && isset( $item["message"] ) ){
				$ua->text           = @ $item["message"];
			}

			if( ! empty( $ua->text ) ){
				$ua->user->identifier   = @ $item["from"]["id"];
				$ua->user->displayName  = @ $item["from"]["name"];
				$ua->user->profileURL   = @ "https://www.facebook.com/profile.php?id=" . $ua->user->identifier;
				$ua->user->photoURL     = @ "https://graph.facebook.com/" . $ua->user->identifier . "/picture?type=square";

				$activities[] = $ua;
			}
		}

		return $activities;
 	}
}
