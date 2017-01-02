<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*  Author: Ivan Kristianto
*  Github: https://github.com/ivankristianto/hybridauth
*/

/**
 * Hybrid_Providers_Envato
 */
class Hybrid_Providers_Envato extends Hybrid_Provider_Model_OAuth2
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
		$this->api->api_base_url  = "https://api.envato.com/";
		$this->api->authorize_url = "https://api.envato.com/authorization";
		$this->api->token_url     = "https://api.envato.com/token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$data = $this->api->api( "v1/market/private/user/account.json" );
		if ( ! isset( $data->account ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->account->surname;
		$this->user->profile->displayName = @ $data->account->firstname . ' ' . $data->account->surname;
		$this->user->profile->photoURL    = @ $data->account->image;
		$this->user->profile->profileURL  = @ $data->account->image;

		// request user emails from envato api
		try{
			$email = $this->api->api("v1/market/private/user/email.json");
			$this->user->profile->email       = @ $email->email;
		}
		catch( Exception $e ){
			throw new Exception( "User email request failed! {$this->providerId} returned an error: $e", 6 );
		}

		return $this->user->profile;
	}
}