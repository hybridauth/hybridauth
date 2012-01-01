<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Facebook provider adapter based on OAuth2 protocol
 * 
 * Hybrid_Providers_Facebook use the Facebook PHP SDK created by Facebook
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Facebook.html
 */
class Hybrid_Providers_Facebook extends Hybrid_Provider_Model
{
	// default permissions, and alot of them. You can change them from the configuration by setting the scope to what you want/need
	public $scope = "email, user_about_me, user_birthday, user_hometown, user_website, offline_access, read_stream, publish_stream, read_friendlists";
	
	public $display = "page";

	/**
	* IDp wrappers initializer 
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

		// override requested display
		if( isset( $this->config["display"] ) && ! empty( $this->config["display"] ) ){
			$this->display = $this->config["display"];
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
		// get the login url 
		$url = $this->api->getLoginUrl( array( 'scope' => $this->scope, 'display' => $this->display, 'redirect_uri' => $this->endpoint ) );

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
		if ( ! isset( $data["id"] ) ){ 
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		# store the user profile.
		$this->user->profile->identifier    = (array_key_exists('id',$data))?$data['id']:"";
		$this->user->profile->displayName   = (array_key_exists('name',$data))?$data['name']:"";
		$this->user->profile->firstName     = (array_key_exists('first_name',$data))?$data['first_name']:"";
		$this->user->profile->lastName      = (array_key_exists('last_name',$data))?$data['last_name']:"";
		$this->user->profile->photoURL      = "https://graph.facebook.com/" . $this->user->profile->identifier . "/picture?type=square";
		$this->user->profile->profileURL    = (array_key_exists('link',$data))?$data['link']:""; 
		$this->user->profile->webSiteURL    = (array_key_exists('website',$data))?$data['website']:""; 
		$this->user->profile->gender        = (array_key_exists('gender',$data))?$data['gender']:"";
		$this->user->profile->description   = (array_key_exists('bio',$data))?$data['bio']:"";
		$this->user->profile->email         = (array_key_exists('email',$data))?$data['email']:"";
		$this->user->profile->emailVerified = (array_key_exists('email',$data))?$data['email']:"";
		$this->user->profile->region        = (array_key_exists("name",$data['hometown']))?$data['hometown']["name"]:"";

		if( isset( $data['birthday'] ) ) {
			list($birthday_month, $birthday_day, $birthday_year) = (array_key_exists('birthday',$data))?$data['birthday']:"";

			$this->user->profile->birthDay   = $birthday_day;
			$this->user->profile->birthMonth = $birthday_month;
			$this->user->profile->birthYear  = $birthday_year;
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

			$uc->identifier  = (array_key_exists("id",$item))?$item["id"]:"";
			$uc->displayName = (array_key_exists("name",$item))?$item["name"]:"";
			$uc->profileURL  = "https://www.facebook.com/profile.php?id=" . $uc->identifier;
			$uc->photoURL    = "https://graph.facebook.com/" . $uc->identifier . "/picture?type=square"; 

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
			$parameters = $status;
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

			$ua->id                 = (array_key_exists("id",$item))?$item["id"]:"";
			$ua->date               = (array_key_exists("created_time",$item))?$item["created_time"]:"";

			if( $item["type"] == "video" ){
				$ua->text           = (array_key_exists("link",$item))?$item["link"]:"";
			}

			if( $item["type"] == "link" ){
				$ua->text           = (array_key_exists("link",$item))?$item["link"]:"";
			}

			if( empty( $ua->text ) && isset( $item["story"] ) ){
				$ua->text           = (array_key_exists("link",$item))?$item["link"]:"";
			}

			if( empty( $ua->text ) && isset( $item["message"] ) ){
				$ua->text           = (array_key_exists("message",$item))?$item["message"]:"";
			}

			if( ! empty( $ua->text ) ){
				$ua->user->identifier   = (array_key_exists("id",$item["from"]))?$item["from"]["id"]:"";
				$ua->user->displayName  = (array_key_exists("name",$item["from"]))?$item["from"]["name"]:"";
				$ua->user->profileURL   = (property_exists($ua->user,'identifier'))?$ua->user->identifier:"";
				$ua->user->photoURL     = (property_exists($ua->user,'identifier . "/picture?type=square"'))?$ua->user->identifier . "/picture?type=square":"";

				$activities[] = $ua;
			}
		}

		return $activities;
 	}
}
