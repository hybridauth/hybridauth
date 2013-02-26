<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Windows;

/**
* Windows adapter
* 
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Windows.html
*/
class Adapter extends \Hybridauth\Adapter\Template\OAuth2
{
	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->endpoints->baseUri         = "https://apis.live.net/v5.0/";
		$this->api->endpoints->authorizeUri    = "https://login.live.com/oauth20_authorize.srf";
		$this->api->endpoints->requestTokenUri = "https://login.live.com/oauth20_token.srf";

		if( $this->api->scope === null ){
			$this->api->scope  = "wl.basic wl.emails wl.signin wl.share wl.birthday";
		}
	}

	// --------------------------------------------------------------------

	/**
	* Get user profile 
	*/
	function getUserProfile()
	{
		$response = $this->api->get( "me" ); 
		$response = json_decode( $response );

		if ( ! $response || ! isset( $response->id ) || isset( $response->error ) ){
			throw new
				\Hybridauth\Exception(
					"User profile request failed! {$this->providerId} returned an invalid response.", 
					\Hybridauth\Exception::USER_PROFILE_REQUEST_FAILED, 
					null,
					$this
				);
		}

		$profile = new \Hybridauth\User\Profile();

		$profile->providerId  = $this->providerId;
		$profile->identifier  = ( property_exists( $response, 'id'          ) ) ? $response->id              : "";
		$profile->firstName   = ( property_exists( $response, 'first_name'  ) ) ? $response->first_name      : "";
		$profile->lastName    = ( property_exists( $response, 'last_name'   ) ) ? $response->last_name       : "";
		$profile->displayName = ( property_exists( $response, 'name'        ) ) ? $response->name            : "";
		$profile->profileURL  = ( property_exists( $response, 'link'        ) ) ? $response->link            : "";
		$profile->gender      = ( property_exists( $response, 'gender'      ) ) ? $response->gender          : "";
		$profile->email       = ( property_exists( $response, 'emails'      ) ) ? $response->emails->account : "";
		$profile->birthDay    = ( property_exists( $response, 'birth_day'   ) ) ? $response->birth_day       : "";
		$profile->birthMonth  = ( property_exists( $response, 'birth_month' ) ) ? $response->birth_month     : "";
		$profile->birthYear   = ( property_exists( $response, 'birth_year'  ) ) ? $response->birth_year      : "";

		return $profile;
	}

	// --------------------------------------------------------------------

	/**
	* Get users contacts
	*/
	function getUserContacts() 
	{
		$response = $this->api->get( 'me/contacts' );
		$response = json_decode( $response );

		if( ! $response ){
			throw new
				\Hybridauth\Exception( "User contacts request failed! {$this->providerId} returned an error" );
		}

		if ( ! $response->data && ( $response->error != 0 ) )
		{
			return array();
		}

		$contacts = array();

		foreach( $response->data as $item ) {
			$uc = new \Hybridauth\User\Contact();

			$uc->providerId   = $this->providerId;
			$uc->identifier   = ( property_exists( $item, 'id'   ) ) ? $item->id   : "";
			$uc->displayName  = ( property_exists( $item, 'name' ) ) ? $item->name : "";

			$contacts[] = $uc;
		}

		return $contacts;
	}
}
