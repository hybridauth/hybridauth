<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2015 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Dribbble
 */
class Hybrid_Providers_Dribbble extends Hybrid_Provider_Model_OAuth2
{ 

	public $scope = "";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();
		
		// Provider api end-points
		$this->api->api_base_url  = "https://api.dribbble.com/v1/";
		$this->api->authorize_url = "https://dribbble.com/oauth/authorize";	   
		$this->api->token_url     = "https://dribbble.com/oauth/token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		
		$data = $this->api->get( "user" );
		
		if ( ! isset( $data->id ) ) {
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->id; 
		$this->user->profile->username    = @ $data->username;
		$this->user->profile->displayName = @ $data->name;
		$this->user->profile->photoURL    = @ $data->avatar_url;
		$this->user->profile->profileURL  = @ $data->html_url; 
		$this->user->profile->description = @ $data->bio;
		$this->user->profile->location    = @ $data->location;
		$this->user->profile->webSiteURL  = @ $data->links->web;

		if ( ! $this->user->profile->displayName ) {
			$this->user->profile->displayName = @ $data->username;
		}

		return $this->user->profile;
	}
	
}
