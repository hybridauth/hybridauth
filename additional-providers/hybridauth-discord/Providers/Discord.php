<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Discord 
 */
class Hybrid_Providers_Discord extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions  
	public $scope = 'identify email';

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://discordapp.com/api/";
		$this->api->authorize_url = "https://discordapp.com/api/oauth2/authorize";
		$this->api->token_url     = "https://discordapp.com/api/oauth2/token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{

		$this->api->curl_header = array( 'Authorization: Bearer ' . $this->api->access_token );

		$data = $this->api->api( "users/@me" ); 

		if ( ! isset( $data->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->id; 
		$this->user->profile->displayName = @ $data->username;
		$this->user->profile->photoURL    = @ "https://cdn.discordapp.com/avatars/".$data->id."/".$data->avatar.".png";
		$this->user->profile->email       = @ $data->email;

		if( empty($this->user->profile->displayName) ){
			$this->user->profile->displayName = @ $data->login;
		}

		return $this->user->profile;
	}
}
