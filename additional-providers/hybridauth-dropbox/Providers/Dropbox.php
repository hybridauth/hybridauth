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

class Hybrid_Providers_Dropbox extends Hybrid_Provider_Model_OAuth2
{ 
	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider apis end-points
		$this->api->api_base_url  = "https://api.dropbox.com/1/";
		$this->api->authorize_url = "https://www.dropbox.com/1/oauth2/authorize";
		$this->api->token_url     = "https://api.dropbox.com/1/oauth2/token"; 

	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed 
		$this->refreshToken();

		try{
			$response = $this->api->api( "account/info" );
		}
		catch( DropboxException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		}

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->uid ) ){
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}
		# store the user profile.  
		$this->user->profile->identifier		=	(property_exists($response,'uid'))?$response->uid:"";
		$this->user->profile->profileURL		=	"";
		$this->user->profile->webSiteURL		=	"";
		$this->user->profile->photoURL			=	"";
		$this->user->profile->displayName		=	(property_exists($response,'display_name'))?$response->display_name:"";
		$this->user->profile->description		=	"";
		$this->user->profile->firstName			=	(property_exists($response,'name_details'))?(property_exists($response->name_details,'given_name'))?$response->name_details->given_name:"":"";
		$this->user->profile->lastName			=	(property_exists($response,'name_details'))?(property_exists($response->name_details,'surname'))?$response->name_details->surname:"":"";
		$this->user->profile->gender				=	"";
		$this->user->profile->language			=	"";
		$this->user->profile->age						=	"";
		$this->user->profile->birthDay			=	"";
		$this->user->profile->birthMonth		=	"";
		$this->user->profile->birthYear			=	"";
		$this->user->profile->email					=	(property_exists($response,'email'))?$response->email:"";
		$this->user->profile->emailVerified	=	"";
		if ( property_exists($response,'email_verified') ) {
			if ( $response->email_verified ) {
				$this->user->profile->emailVerified	=	$this->user->profile->email;
			}
		}
		$this->user->profile->phone					=	"";
		$this->user->profile->address				=	"";
		$this->user->profile->country				=	(property_exists($response,'country'))?$response->country:"";
		$this->user->profile->region				=	"";
		$this->user->profile->city					=	"";
		$this->user->profile->zip						=	"";

		return $this->user->profile;
	}
}
