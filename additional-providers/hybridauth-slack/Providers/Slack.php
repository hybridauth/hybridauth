<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_Slack provider adapter based on OAuth2 protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Slack.html
 */

class Hybrid_Providers_Slack extends Hybrid_Provider_Model_OAuth2
{ 
	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider apis end-points
		$this->api->api_base_url  = "https://slack.com/api/";
		$this->api->authorize_url = "https://slack.com/oauth/authorize";
		$this->api->token_url     = "https://slack.com/api/oauth.access"; 
		$this->api->sign_token_name = "token";

	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed 
		$this->refreshToken();

		try{
			$authTest = $this->api->api( "auth.test" );
			$response = $this->api->get( "users.info", array( "user" => $authTest->user_id ) );
		}
		catch( SlackException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		}

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->user->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}
		# store the user profile.  
		$this->user->profile->identifier		=	@$response->user->id;
		$this->user->profile->profileURL		=	"";
		$this->user->profile->webSiteURL		=	"";
		$this->user->profile->photoURL			=	@$response->user->profile->image_original;
		$this->user->profile->displayName		=	@$response->user->profile->real_name;
		$this->user->profile->description		=	"";
		$this->user->profile->firstName			=	@$response->user->profile->first_name;
		$this->user->profile->lastName			=	@$response->user->profile->last_name;
		$this->user->profile->gender			=	"";
		$this->user->profile->language			=	"";
		$this->user->profile->age				=	"";
		$this->user->profile->birthDay			=	"";
		$this->user->profile->birthMonth		=	"";
		$this->user->profile->birthYear			=	"";
		$this->user->profile->email				=	@$response->user->profile->email;
		$this->user->profile->emailVerified	    =	"";
		$this->user->profile->phone				=	"";
		$this->user->profile->address			=	"";
		$this->user->profile->country			=	"";
		$this->user->profile->region			=	"";
		$this->user->profile->city				=	"";
		$this->user->profile->zip				=	"";

		return $this->user->profile;
	}
}
