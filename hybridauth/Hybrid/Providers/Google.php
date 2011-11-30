<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Google provider adapter based on OAuth2 protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
 */
class Hybrid_Providers_Google extends Hybrid_Provider_Model_OAuth2
{
	// default permissions 
	public $scope = "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->authorize_url  = "https://accounts.google.com/o/oauth2/auth";
		$this->api->token_url      = "https://accounts.google.com/o/oauth2/token";
		$this->api->token_info_url = "https://www.googleapis.com/oauth2/v1/tokeninfo";
	}

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		Hybrid_Auth::redirect( $this->api->authorizeUrl( array( "scope" => $this->scope, "access_type" => "offline" ) ) ); 
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed
		$this->refreshToken();

		// ask google api for user infos
		$response = $this->api->api( "https://www.googleapis.com/oauth2/v1/userinfo" ); 

		if ( ! isset( $response->id ) || isset( $response->error ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalide response.", 6 );
		}

		$this->user->profile->identifier  = @ $response->id;
		$this->user->profile->firstName   = @ $response->given_name;
		$this->user->profile->lastName    = @ $response->family_name;
		$this->user->profile->displayName = @ $response->name;
		$this->user->profile->photoURL    = @ $response->picture;
		$this->user->profile->profileURL  = "https://profiles.google.com/" . $this->user->profile->identifier;
		$this->user->profile->gender      = @ $response->gender; 
		$this->user->profile->email       = @ $response->email;
		$this->user->profile->language    = @ $response->locale;

		return $this->user->profile;
	}
}
