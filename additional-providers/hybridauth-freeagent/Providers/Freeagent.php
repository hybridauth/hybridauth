<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Freeagent
 */
class Hybrid_Providers_Freeagent extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions  
	// (no scope) => public read-only access (includes public user profile info, public repo info, and gists).
	public $scope = "";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://api.freeagent.com/v2/";
		$this->api->authorize_url = "https://api.freeagent.com/v2/approve_app";
		$this->api->token_url     = "https://api.freeagent.com/v2/token_endpoint";

		if( $this->token( "access_token" ) ){
			$this->api->curl_header = array( 'Authorization: Bearer ' . $this->token( "access_token" ) );
		}
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{	

		$data = $this->api->get( "users/me" );

		if ( ! isset( $data->user ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ str_ireplace($this->api->api_base_url.'users/', '', $data->user->url); 
		$this->user->profile->displayName = @ trim($data->user->first_name . ' ' . $data->user->last_name);
		$this->user->profile->description = @ $data->user->role;
		$this->user->profile->email       = @ $data->user->email;

		if( ! $this->user->profile->displayName ){
			$this->user->profile->displayName = @ $data->user->email;
		}

		return $this->user->profile;
	}
}
