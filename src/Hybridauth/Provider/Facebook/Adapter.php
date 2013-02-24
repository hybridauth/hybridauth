<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Facebook;

/**
* Facebook adapter 
* 
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Facebook.html
*/
class Adapter extends \Hybridauth\Adapter\Template\OAuth2 
{
	// default permissions 
	public $scope = "email, user_about_me, user_birthday, user_hometown, user_website, read_stream, offline_access, publish_stream, read_friendlists";

	// --------------------------------------------------------------------

	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// Provider api end-points
		$this->api->endpoints->authorizeUri    = "https://www.facebook.com/dialog/oauth";
		$this->api->endpoints->requestTokenUri = "https://graph.facebook.com/oauth/access_token"; 
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		$parameters = array("scope" => $this->scope, "redirect_uri" => $this->endpoint, "display" => "page");
		$optionals  = array("scope", "redirect_uri", "display");

		foreach ($optionals as $parameter){
			if( isset( $this->config[$parameter] ) && ! empty( $this->config[$parameter] ) ){
				$parameters[$parameter] = $this->config[$parameter];
			}
		}

		$url = $this->api->generateAuthorizeUri( $parameters );

		\Hybridauth\Http\Util::redirect( $url ); 
	}

	// --------------------------------------------------------------------

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// ask google api for user infos
		$response = $this->api->api( "https://graph.facebook.com/me" );

		if ( ! isset( $response->id ) || isset( $response->error ) ){
			throw new
				\Hybridauth\Exception( 
					"User profile request failed! {$this->providerId} returned an invalid response.", 
					\Hybridauth\Exception::USER_PROFILE_REQUEST_FAILED, 
					null,
					$this
				);
		}

		$profile = new \Hybridauth\User\Profile();

		$profile->provider      = $this->providerId;
		$profile->identifier    = ( property_exists( $response, 'id'        ) ) ? $response->id         : "";
		$profile->displayName   = ( property_exists( $response, 'name'      ) ) ? $response->name       : "";
		$profile->firstName     = ( property_exists( $response, 'first_name') ) ? $response->first_name : "";
		$profile->lastName      = ( property_exists( $response, 'last_name' ) ) ? $response->last_name  : "";
		$profile->profileURL    = ( property_exists( $response, 'link'      ) ) ? $response->link       : ""; 
		$profile->webSiteURL    = ( property_exists( $response, 'website'   ) ) ? $response->website    : ""; 
		$profile->gender        = ( property_exists( $response, 'gender'    ) ) ? $response->gender     : "";
		$profile->description   = ( property_exists( $response, 'bio'       ) ) ? $response->bio        : "";
		$profile->email         = ( property_exists( $response, 'email'     ) ) ? $response->email      : ""; 
		$profile->region        = ( property_exists( $response, 'hometown'    ) && property_exists( $response->hometown, 'name' ) ) ? $response->hometown->name : "";
		$profile->photoURL      = "https://graph.facebook.com/" . $profile->identifier . "/picture?width=150&height=150";

		if( property_exists($response,'birthday') ) {
			list($birthday_month, $birthday_day, $birthday_year) = explode( "/", $response->birthday );

			$profile->birthDay   = (int) $birthday_day;
			$profile->birthMonth = (int) $birthday_month;
			$profile->birthYear  = (int) $birthday_year;
		}

		if( property_exists( $response, 'verified' ) && $response->verified ){ 
			$profile->emailVerified = $profile->email ;
		}

		return $profile;
	}

	// --------------------------------------------------------------------

	/**
	* Get users contacts
	*/
	function getUserContacts()
	{
		try{
			$response = $this->api->api( 'https://graph.facebook.com/me/friends' ); 
		}
		catch( FacebookApiException $e ){
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error" );
		}

		if( ! $response || ! count( $response->data ) ){
			return array();
		}

		$contacts = array();
 
		foreach( $response->data as $item ){
			$uc = new \Hybridauth\User\Contact();

			$uc->identifier  = ( property_exists( $item, 'id' ) ) ? $item->id : ""; 
			$uc->displayName = ( property_exists( $item, 'name' ) ) ? $item->name : ""; 
			$uc->profileURL  = "https://www.facebook.com/profile.php?id=" . $uc->identifier;
			$uc->photoURL    = "https://graph.facebook.com/" . $uc->identifier . "/picture?width=150&height=150";

			$contacts[] = $uc;
		}

		return $contacts;
 	}
}
