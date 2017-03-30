<?php

/**
 * Hybrid_Providers_GitLab - GitLab.com provider adapter based on the OAuth2 protocol.
 */
class Hybrid_Providers_GitLab extends Hybrid_Provider_Model_OAuth2
{
	// default permissions
	// (no scope) => public read-only access (includes public user profile info, public repo info, and gists).
	public $scope = "api";

	/**
	* IDp wrappers initializer
	*/
	function initialize()
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://gitlab.com/";
		$this->api->authorize_url = "https://gitlab.com/oauth/authorize";
		$this->api->token_url     = "https://gitlab.com/oauth/token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$data = $this->api->api( "api/v3/user" );

		if ( ! isset( $data->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->id;
		$this->user->profile->displayName = @ $data->name;
		$this->user->profile->description = @ $data->bio;
		$this->user->profile->photoURL    = @ $data->avatar_url;
		$this->user->profile->email       = @ $data->email;
		$this->user->profile->webSiteURL  = @ $data->website_url;

		if( empty($this->user->profile->displayName) ){
			$this->user->profile->displayName = @ $data->username;
		}

		return $this->user->profile;
	}
}
