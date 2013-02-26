<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Google;

/**
* Google adapter
* 
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
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
		$this->api->endpoints->authorizeUri    = "https://accounts.google.com/o/oauth2/auth";
		$this->api->endpoints->requestTokenUri = "https://accounts.google.com/o/oauth2/token";
		$this->api->endpoints->tokenInfoUri    = "https://www.googleapis.com/oauth2/v1/tokeninfo";

		if( $this->api->scope === null ){
			$this->api->scope  = "https://www.googleapis.com/auth/userinfo.profile ";
			$this->api->scope .= "https://www.googleapis.com/auth/userinfo.email ";
			$this->api->scope .= "https://www.google.com/m8/feeds/";
		}

		$this->api->endpoints->authorizeUriParameters = array( "access_type" => "offline" );
	}

	// --------------------------------------------------------------------

	/**
	* Get user profile 
	*/
	function getUserProfile()
	{
		// refresh tokens
		$this->refreshAccessToken();

		// request user infos
		$response = $this->api->get( "https://www.googleapis.com/oauth2/v1/userinfo" );
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
		$profile->identifier  = ( property_exists( $response, 'id'          ) ) ? $response->id          : "";
		$profile->firstName   = ( property_exists( $response, 'given_name'  ) ) ? $response->given_name  : "";
		$profile->lastName    = ( property_exists( $response, 'family_name' ) ) ? $response->family_name : "";
		$profile->displayName = ( property_exists( $response, 'name'        ) ) ? $response->name        : "";
		$profile->photoURL    = ( property_exists( $response, 'picture'     ) ) ? $response->picture     : "";
		$profile->profileURL  = ( property_exists( $response, 'link'        ) ) ? $response->link        : "";
		$profile->gender      = ( property_exists( $response, 'gender'      ) ) ? $response->gender      : ""; 
		$profile->email       = ( property_exists( $response, 'email'       ) ) ? $response->email       : "";
		$profile->language    = ( property_exists( $response, 'locale'      ) ) ? $response->locale      : "";

		if( property_exists( $response,'birthday' ) ){ 
			list($birthday_year, $birthday_month, $birthday_day) = explode( '-', $response->birthday );

			$profile->birthDay   = (int) $birthday_day;
			$profile->birthMonth = (int) $birthday_month;
			$profile->birthYear  = (int) $birthday_year;
		}

		if( property_exists( $response, 'verified_email' ) && $response->verified_email ){ 
			$profile->emailVerified = $profile->email ;
		}

		return $profile;
	}

	// --------------------------------------------------------------------

	/**
	* Get gmail contacts 
	*/
	function getUserContacts()
	{
		// refresh tokens
		$this->refreshAccessToken();

		if( ! isset( $this->config['contacts_param'] ) ){
			$this->config['contacts_param'] = array( "max-results" => 500 );
		}

		$response = $this->api->get(
						"https://www.google.com/m8/feeds/contacts/default/full?" . 
							http_build_query( array_merge( array( 'alt' => 'json' ), 
							$this->config['contacts_param'] ) )
					);

		$response = json_decode( $response );

		if( ! $response ){
			throw new
				\Hybridauth\Exception( "User contacts request failed! {$this->providerId} returned an error" );
		}

		if( ! isset( $response->feed ) || ! $response->feed ){
			return array();
		}

		$contacts = array(); 

		foreach( $response->feed->entry as $idx => $entry ){
			$uc = new \Hybridauth\User\Contact();

			$uc->providerId  = $this->providerId;
			$uc->email       = isset($entry->{'gd$email'}[0]->address) ? (string) $entry->{'gd$email'}[0]->address : ''; 
			$uc->displayName = isset($entry->title->{'$t'}) ? (string) $entry->title->{'$t'} : '';  
			$uc->identifier  = $uc->email;

			$contacts[] = $uc;
		}  

		return $contacts;
 	}
}
