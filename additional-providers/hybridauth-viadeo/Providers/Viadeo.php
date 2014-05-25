<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Viadeo
 */
class Hybrid_Providers_Viadeo extends Hybrid_Provider_Model
{ 
   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		if ( ! $this->config["keys"]["id"] || ! $this->config["keys"]["secret"] ){
			throw new Exception( "Your application id and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "Viadeo/ViadeoAPI.php"; 

		if( $this->token( "access_token" ) ){
			$this->api = new ViadeoAPI( $this->token( "access_token" ) );
		}
		else{
			$this->api = new ViadeoAPI();
		}

		$this->api->init(array(  
			'store'            => true,  
			'client_id'        => $this->config["keys"]["id"],  
			'client_secret'    => $this->config["keys"]["secret"]
		));  
	}

   /**
	* begin login step 
	*/
	function loginBegin()
	{ 
		try { 
			$this->api->setRedirectURI( $this->endpoint );

			$url = $this->api->getAuthorizationURL(); 

			Hybrid_Auth::redirect( $url );
		}
		catch ( ViadeoException $e ){
			throw new Exception( "Authentication failed! An error occurred during {$this->providerId} authentication.", 5 );
		}
	}
 
   /**
	* finish login step 
	*/
	function loginFinish()
	{
		try { 
			$this->api->setRedirectURI( $this->endpoint );

			$this->api->setAccessTokenFromCode();
		}
		catch ( ViadeoException $e ){
			throw new Exception( "Authentication failed! An error occurred during {$this->providerId} authentication", 5 );
		}

		if ( ! $this->api->isAuthenticated() )
		{
			throw new Exception( "Authentication failed! An error occurred during {$this->providerId} authentication", 5 );
		} 

		// Store tokens 
		$this->token( "access_token", $this->api->getAccessToken() );  

		// set user as logged in
		$this->setUserConnected();
	}

   /**
	* logout
	*/
	function logout()
	{ 
		$this->api->disconnect();

		parent::logout();
	}

   /**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{
			$data = $this->api->get("/me")->execute();
		}
		catch( ViadeoAPIException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error while requesting the user profile. $e.", 6 );
		}

		if ( ! is_object( $data ) )
		{
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		} 

		$this->user->profile->identifier    = @ $data->id;
		$this->user->profile->displayName  	= @ $data->name;
		$this->user->profile->firstName     = @ $data->first_name;
		$this->user->profile->lastName     	= @ $data->last_name;
		$this->user->profile->profileURL 	= @ $data->link; 
		$this->user->profile->description 	= @ $data->headline; 
		$this->user->profile->photoURL      = @ $data->picture_large; 
		$this->user->profile->gender        = @ $data->gender; 

		if( $this->user->profile->gender == "F" ){
			$this->user->profile->gender = "female";
		}
		elseif( $this->user->profile->gender == "M" ){
			$this->user->profile->gender = "male";
		}

		$this->user->profile->country 		= @ $data->location->country;
		$this->user->profile->region 		= @ $data->location->area;
		$this->user->profile->city 		    = @ $data->location->city;
		$this->user->profile->zip 		    = @ $data->location->zipcode;
		
		return $this->user->profile;
	}
	
	/**
	* load the user contacts
	* Note : you must select a maximum number of contacts to retrieve below, with the "limit" parameter
	*/
	function getUserContacts()
	{
		$contacts    = ARRAY();
		
		try{
			$data = $this->api->get("/me/contacts?limit=500&user_detail=partial")->execute();
		}
		catch( ViadeoAPIException $e ){
			throw new Exception( "Contacts request failed! Error message provided by {$this->providerId} : ".$e->getMessage(), 6 ); //User profile request failed! {$this->providerId} returned an error while requesting the user profile. $e.
		}

		if( ! $data || $data->count==0 ){
			return ARRAY();
		}

		$contacts = ARRAY();
 
		foreach( $data->data as $item ){
			$uc = new Hybrid_User_Contact();

			$uc->identifier  = (isset($item->id))?$item->id:"";
			$uc->displayName  = (isset($item->name))?$item->name:"";
			$uc->profileURL  = (isset($item->link))?$item->link:"";
			$uc->photoURL  = (isset($item->picture_large))?$item->picture_large:"";
			$uc->description  = (isset($item->headline))?$item->headline:"";

			$contacts[] = $uc;
		}
	}
}
