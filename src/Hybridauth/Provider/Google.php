<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception;
use Hybridauth\Adapter\Template\OAuth2\OAuth2Template;
use Hybridauth\Entity\Profile;

/**
* Google adapter extending OAuth2 Template
*
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
*/
class Google extends OAuth2Template
{
	/**
	* Internal: Initialize Google adapter. This method isn't intended for public consumption.
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
			: 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email https://www.google.com/m8/feeds/';

		$this->letApplicationScope( $scope );

		$this->letEndpointBaseUri( '' );
		$this->letEndpointRedirectUri( $this->getHybridauthEndpointUri() );
		$this->letEndpointAuthorizeUri( 'https://accounts.google.com/o/oauth2/auth' );
		$this->letEndpointRequestTokenUri( 'https://accounts.google.com/o/oauth2/token' );
		$this->letEndpointTokenInfoUri( 'https://www.googleapis.com/oauth2/v1/tokeninfo' );

		$this->letEndpointAuthorizeUriAdditionalParameters( array( 'access_type' => 'offline' ) );
	}

	// --------------------------------------------------------------------

	/**
	* Returns user profile
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Google" )->getUserProfile();
	*/
	function getUserProfile()
	{
		$response = $this->signedRequest( "https://www.googleapis.com/oauth2/v1/userinfo" );
		$response = json_decode( $response );

		// Provider Errors shall not pass silently
		if( ! $response || ! isset( $response->id ) ){
			throw new
				Exception(
					'User profile request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::AUTHENTIFICATION_FAILED,
					$this
				);
		}

		$parser = function( $property ) use( $response )
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$profile = new Profile($this);

		$profile->setIdentifier ( $parser( 'id'          ) );
		$profile->setFirstName  ( $parser( 'given_name'  ) );
		$profile->setLastName   ( $parser( 'family_name' ) );
		$profile->setDisplayName( $parser( 'name'        ) );
		$profile->setPhotoURL   ( $parser( 'picture'     ) );
		$profile->setProfileURL ( $parser( 'link'        ) );
		$profile->setGender     ( $parser( 'gender'      ) );
		$profile->setEmail      ( $parser( 'email'       ) );
		$profile->setLanguage   ( $parser( 'locale'      ) );

		if( $parser( 'birthday' ) ){
			list( $y, $m, $d ) = explode( '-', $response->birthday );

			$profile->setBirthDay  ( $d );
			$profile->setBirthMonth( $m );
			$profile->setBirthYear ( $y );
		}

		if( $parser( 'verified_email' ) ){
			$profile->setEmailVerified( $profile->getEmail() );
		}

		return $profile;
	}

	// --------------------------------------------------------------------

	/**
	* Returns user contacts list
	*
	* Examples:
	*
	*	$data = $hybridauth->authenticate( "Google" )->getUserContacts( array( "max-results" => 10 ) );
	*/
	function getUserContacts( $args = array() )
	{
		// refresh tokens if needed
		$this->refreshToken();

		$url = "https://www.google.com/m8/feeds/contacts/default/full?"
				. http_build_query( array_merge( array('alt' => 'json'), $args ) );

		$response = $this->signedRequest( $url );
		$response = json_decode( $response );

		if( ! $response || isset( $response->error ) ){
			throw new
				Exception(
					'User contacts request failed: Provider returned an invalid response. ' .
					'HTTP client state: (' . $this->httpClient->getState() . ')',
					Exception::USER_PROFILE_REQUEST_FAILED,
					$this
				);
		}

		$contacts = array();

		if( isset( $response->feed ) && is_array( $response->feed ) ){
			foreach( $response->feed->entry as $idx => $entry ){
				$profile = new Profile($this);

				$email       = isset( $entry->{'gd$email'} [0]->address ) ? (string) $entry->{'gd$email'} [0]->address : '';
				$displayName = isset( $entry->title->{'$t'} ) ? (string) $entry->title->{'$t'} : '';

				$profile->setIdentifier ( $email       );
				$profile->setDisplayName( $displayName );
				$profile->setEmail      ( $email       );

				$contacts[] = $profile;
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
	*	$data = $hybridauth->authenticate( "Google" )->setUserStatus( _STATUS_ );
	*/
	function setUserStatus( $status )
	{
		throw new Exception( "Unsupported", Exception::UNSUPPORTED_FEATURE, null, $this );
 	}
}
