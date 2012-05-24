<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Instagram
 *1 get your credentials here : http://instagr.am/developer/clients/manage/
 *2 set redirect url to http://mywebsite.com/path_to_hybridauth/?hauth.done=Instagram
 *3 set in config: 
 "Instagram" => array ( "enabled" => true, "keys" => array ( "id" => "YOUR CLIENT ID", "secret" => "YOUR CLIENT SECRET" ) )
 * see http://instagr.am/developer/endpoints/ for other API endpoints
 */
class Hybrid_Providers_Instagram extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions  
	// (no scope) => public read-only access ().
	public $scope = "basic";
	//"likes comments";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://api.instagram.com/v1/";
		$this->api->authorize_url = "https://api.instagram.com/oauth/authorize/";
		$this->api->token_url     = "https://api.instagram.com/oauth/access_token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile(){
		print_r($data);	
		$data = $this->api->api("users/self/" ); 

		if ( $data->meta->code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalide response.", 6 );
		} else {
			
		}

		$this->user->profile->identifier  = @ $data->data->id; 
		$this->user->profile->displayName = @ $data->data->username;
		$this->user->profile->description = @ $data->data->bio;
		$this->user->profile->photoURL    = @ $data->data->profile_picture;
		
		$this->user->profile->webSiteURL  = @ $data->data->website;
		// not supported
		$this->user->profile->profileURL  = @ 'https://instagr.am/accounts/edit/'; 

		return $this->user->profile;
	}
}