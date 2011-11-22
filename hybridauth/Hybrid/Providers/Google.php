<?php
/**
* HybridAuth
* 
* A Social-Sign-On PHP Library for authentication through identity providers like Facebook,
* Twitter, Google, Yahoo, LinkedIn, MySpace, Windows Live, Tumblr, Friendster, OpenID, PayPal,
* Vimeo, Foursquare, AOL, Gowalla, and others.
*
* Copyright (c) 2009-2011 (http://hybridauth.sourceforge.net) 
*/

/**
 * Hybrid_Providers_Google 
 */
class Hybrid_Providers_Google extends Hybrid_Providers_Protocols_OAuth2
{
	// default permissions 
	public $scope = "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/plus.me";

   /**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider apis end-points
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
		// ask google api for user infos
		$response = $this->api->api( "https://www.googleapis.com/oauth2/v1/userinfo" ); 

		if ( ! isset( $response->id ) || isset( $response->error ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalide response.", 6 );
		}

		$this->user->profile->identifier    = @ $response->id;
		$this->user->profile->firstName  	= @ $response->given_name;
		$this->user->profile->lastName  	= @ $response->family_name;
		$this->user->profile->displayName  	= @ $response->name;
		$this->user->profile->photoURL  	= @ $response->picture;
		$this->user->profile->profileURL    = "https://profiles.google.com/" . $this->user->profile->identifier;
		$this->user->profile->gender        = @ $response->gender; 
		$this->user->profile->email         = @ $response->email;
		$this->user->profile->language      = @ $response->locale;

		// if user hava a Google+ account and consented then update his name, profile url and avatar
		$response = $this->api->api( "https://www.googleapis.com/plus/v1/people/" . $response->id ); 

		if ( is_object( $response ) && ! isset( $response->error ) ){
			if( isset( $response->displayName ) && ! empty( $response->displayName ) ) 
			$this->user->profile->displayName  	= @ $response->displayName;
			$this->user->profile->profileURL  	= @ $response->url;
			$this->user->profile->photoURL  	= @ $response->photoURL->url;

			$this->token( "googleplus_user_id" , $response->id );
		} 

		return $this->user->profile;
	}
}
