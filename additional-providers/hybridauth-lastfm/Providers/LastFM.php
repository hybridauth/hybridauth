<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_LastFM class, wrapper for Vimeo  
 */
class Hybrid_Providers_LastFM extends Hybrid_Provider_Model
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

		require_once Hybrid_Auth::$config["path_libraries"] . "LastFM/LastFM.php"; 

		$this->api = new LastFM( array( 'api_key' => $this->config["keys"]["key"], 'api_secret' => $this->config["keys"]["secret"] ) );

		if( $this->token( "access_token" ) )
		{
			$this->api->setSessionKey( $this->token( "access_token" ) );
		}
	}

	/**
	* begin login step 
	*/
	function loginBegin()
	{ 
 		# redirect to Authorize url
		Hybrid_Auth::redirect( $this->api->getLoginUrl( $this->endpoint ) );
	}
 
	/**
	* finish login step 
	*/
	function loginFinish()
	{ 
		$token = @ $_REQUEST['token'];

		if ( ! $token )
		{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid Token.", 5 );
		}

		try{
			$response = $this->api->fetchSession( $token );
		}
		catch( LastFMException $e ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an error while requesting and access token. $e.", 6 );
		}

		if( isset( $response['sk'] ) && isset( $response['name'] ) ){
			$this->token( "access_token" , $response['sk'] );
			
			// let set the user name as access_token_secret ...
			$this->token( "user_name" , $response['name'] );

			// set user as logged in
			$this->setUserConnected();
		}
		else{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid access Token.", 5 );
		}
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{
			$response = $this->api->api( "user.getInfo", array( "token" => $this->token( "access_token" ), "user" => $this->token( "user_name" ) ) ); 
		}
		catch( LastFMInvalidSessionException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile. Invalid session key - Please re-authenticate. $e.", 6 );
		}
		catch( LastFMException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile. $e", 6 );
		}

		// fetch user profile
		$this->user->profile->identifier    = @ (string) $response["user"]["id"]; 
		$this->user->profile->firstName  	= @ (string) $response["user"]["name"];  
		$this->user->profile->displayName  	= @ (string) $response["user"]["realname"];
		$this->user->profile->photoURL  	= @ (string) $response["user"]["image"][2]["#text"]; 
		$this->user->profile->profileURL    = @ (string) $response["user"]["url"];  
		
		$this->user->profile->country       = @ (string) $response["user"]["country"];  
		$this->user->profile->gender        = @ (string) $response["user"]["gender"];  
		$this->user->profile->age           = @ (int) $response["user"]["age"];  

		if( $this->user->profile->gender == "f" ){
			$this->user->profile->gender = "female";
		}

		if( $this->user->profile->gender == "m" ){
			$this->user->profile->gender = "male";
		} 

		return $this->user->profile;
	}
}
