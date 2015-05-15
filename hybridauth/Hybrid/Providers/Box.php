<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Dropbox provider adapter based on OAuth2 protocol
 *
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Dropbox.html
 */

class Hybrid_Providers_Box extends Hybrid_Provider_Model_OAuth2
{
	/**
	* IDp wrappers initializer
	*/
	function initialize()
	{
		parent::initialize();

		// Provider apis end-points
		$this->api->api_base_url  = "https://api.box.com/2.0";
		$this->api->authorize_url = "https://app.box.com/api/oauth2/authorize";
		$this->api->token_url     = "https://api.box.com/oauth2";

	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed
		$this->refreshToken();

		$response = $this->api->api( "/users/me" );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}
		# store the user profile.
		$this->user->profile->identifier		=	(property_exists($response,'id'))?$response->id:"";
		$this->user->profile->profileURL		=	"";
		$this->user->profile->webSiteURL		=	"";
		$this->user->profile->photoURL			=	"";
		$this->user->profile->displayName		=	"";
		$this->user->profile->description		=	"";
		$this->user->profile->firstName			=	(property_exists($response,'name'))?array_shift(explode(" ", $response->name)):"";
		$this->user->profile->lastName			=	(property_exists($response,'name'))?array_pop(explode(" ", $response->name)):"";
		$this->user->profile->gender				=	"";
		$this->user->profile->language			=	"";
		$this->user->profile->age						=	"";
		$this->user->profile->birthDay			=	"";
		$this->user->profile->birthMonth		=	"";
		$this->user->profile->birthYear			=	"";
		$this->user->profile->email					=	(property_exists($response,'login'))?$response->login:"";
		$this->user->profile->emailVerified	=	"";
		if ( property_exists($response,'email_verified') ) {
			if ( $response->email_verified ) {
				$this->user->profile->emailVerified	=	$this->user->profile->email;
			}
		}
		$this->user->profile->phone					=	(property_exists($response,'phone'))?$response->phone:"";
		$this->user->profile->address				=	(property_exists($response,'address'))?$response->address:"";
		$this->user->profile->country				=	(property_exists($response,'country'))?$response->country:"";
		$this->user->profile->region				=	"";
		$this->user->profile->city					=	"";
		$this->user->profile->zip						=	"";

		return $this->user->profile;
	}
}
