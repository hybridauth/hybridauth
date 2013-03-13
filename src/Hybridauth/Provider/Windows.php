<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception;
use Hybridauth\Adapter\Template\OAuth2\OAuth2Template;
use Hybridauth\Entity\Profile;

/**
* Windows adapter extending OAuth2 Template
*
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Windows.html
*/
class Windows extends OAuth2Template
{
	/**
	* Internal: Initialize Windows adapter. This method isn't intended for public consumption.
	*
	* Basically on initializers we feed defaults values to \OAuth2\Template::initialize()
	*
	* let*() methods are similar to set, but 'let' will not overwrite the value if its already set
	*/
	function initialize()
	{
		parent::initialize();

		$this->letApplicationId( $this->getAdapterConfig( 'keys', 'id' ) );
		$this->letApplicationSecret( $this->getAdapterConfig( 'keys', 'secret' ) );

		$scope = $this->getAdapterConfig( 'scope' ) 
			? $this->getAdapterConfig( 'scope' ) 
			: 'wl.basic wl.emails wl.signin wl.share wl.birthday';

		$this->letApplicationScope( $scope );

		$this->letEndpointRedirectUri( $this->getHybridauthEndpointUri() );
		$this->letEndpointBaseUri( 'https://apis.live.net/v5.0/' );
		$this->letEndpointAuthorizeUri( 'https://login.live.com/oauth20_authorize.srf' );
		$this->letEndpointRequestTokenUri( 'https://login.live.com/oauth20_token.srf' );
	}

	// --------------------------------------------------------------------

	function loginFinish()
	{
		parent::loginFinish( array(), 'POST' );
	}

	// --------------------------------------------------------------------

	/**
	* Returns user profile
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Windows" )->getUserProfile();
	*/
	function getUserProfile()
	{
		$response = $this->signedRequest( "me" );
		$response = json_decode( $response );

		if( ! isset( $response->id ) || isset( $response->error ) ){
			throw new
				Exception(
					'User profile request failed: Provider returned an invalid response. ' .
					'HTTP client state:(' . $this->httpClient->getState() . ')',
					Exception::USER_PROFILE_REQUEST_FAILED,
					$this
				);
		}

		$parser = function($property) use($response)
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$profile = new Profile();

		$profile->setIdentifier ( $parser( 'id'         ) );
		$profile->setFirstName  ( $parser( 'first_name' ) );
		$profile->setLastName   ( $parser( 'last_name'  ) );
		$profile->setDisplayName( $parser( 'name'       ) ); 
		$profile->setProfileURL ( $parser( 'link'       ) );
		$profile->setWebSiteURL ( $parser( 'website'    ) );
		$profile->setGender     ( $parser( 'gender'     ) ); 
		$profile->setLanguage   ( $parser( 'locale'     ) );

		$profile->setEmail      ( $response->emails->account ); //< this 

		$profile->setBirthDay   ( $parser( 'birth_day'   ) );
		$profile->setBirthMonth ( $parser( 'birth_month' ) );
		$profile->setBirthYear  ( $parser( 'birth_year'  ) );

		return $profile;
	}

	// --------------------------------------------------------------------

	/**
	* Returns user contacts list
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Windows" )->getUserContacts();
	*/
	function getUserContacts()
	{
		$response = $this->signedRequest( 'me/contacts' );
		$response = json_decode( $response );

		// Provider Errors shall not pass silently
		if( ! $response || ! isset( $response->data ) ){
			throw new
				Exception(
					'User contacts request failed: Provider returned an invalid response. ' .
					'HTTP client state:(' . $this->httpClient->getState() . ')',
					Exception::USER_CONTACTS_REQUEST_FAILED,
					$this
				);
		}

		$parser = function($property) use($response)
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$contacts = array();

		if( isset( $response->data ) && is_array( $response->data ) ){
			foreach( $response->data as $item ){
				$uc = new Profile();

				$profile->setIdentifier ( $parser( 'id'   ) );
				$profile->setDisplayName( $parser( 'name' ) );

				$contacts [] = $uc;
			}
		}

		return $contacts;
	}

	// --------------------------------------------------------------------

	/**
	* Updates user status
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Windows" )->setUserStatus( _STATUS_ );
	*/
	function setUserStatus( $status )
	{
		/// ToDo

		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
 	}
}
