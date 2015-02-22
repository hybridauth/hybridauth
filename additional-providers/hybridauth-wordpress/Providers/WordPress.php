<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2015 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_WordPress
 */
class Hybrid_Providers_WordPress extends Hybrid_Provider_Model_OAuth2
{ 
	// Permissions
	public $scope = "auth";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();
		
		// Provider api end-points
		$this->api->api_base_url  = "https://public-api.wordpress.com/rest/v1/";
		$this->api->authorize_url = "https://public-api.wordpress.com/oauth2/authorize";	   
		$this->api->token_url     = "https://public-api.wordpress.com/oauth2/token";
		
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
	
		// WordPress requires the token to be passed as a Bearer within the Header
		$this->api->curl_header = array( 'Authorization: Bearer ' . $this->api->access_token );
		
		$data = $this->api->get( "me" );
		
		if ( ! isset( $data->ID ) ) {
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->ID; 
		$this->user->profile->username    = @ $data->username;
		$this->user->profile->displayName = @ $data->display_name;
		$this->user->profile->photoURL    = @ $data->avatar_URL;
		$this->user->profile->profileURL  = @ $data->profile_URL; 
		$this->user->profile->email       = @ $data->email;
		$this->user->profile->language    = @ $data->language;

		if ( ! $this->user->profile->displayName ) {
			$this->user->profile->displayName = @ $data->username;
		}

		return $this->user->profile;
	}
}
