<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Google\Api;

use Hybridauth\Exception;
use Hybridauth\Adapter\Api\AbstractApiOperations;
use Hybridauth\Entity\Profile;

class User extends AbstractApiOperations
{
	function initialize() // fixme!
	{
		$this->refreshAccessToken();
	}

	// --------------------------------------------------------------------

	function getUserProfile()
	{
		$response = $this->get( "https://www.googleapis.com/oauth2/v1/userinfo" );
		$response = json_decode( $response );

		if( ! $response || ! isset( $response->id ) || isset( $response->error ) ){
			throw new Exception( "User profile request failed! Provider returned an invalid response", Exception::USER_PROFILE_REQUEST_FAILED, null, $this );
		}

		$parser = function($property) use($response)
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$profile = new Profile();

		$profile->setIdentifier( $parser( 'id' ) );
		$profile->setFirstName( $parser( 'given_name' ) );
		$profile->setLastName( $parser( 'family_name' ) );
		$profile->setDisplayName( $parser( 'name' ) );
		$profile->setPhotoURL( $parser( 'picture' ) );
		$profile->setProfileURL( $parser( 'link' ) );
		$profile->setGender( $parser( 'gender' ) );
		$profile->setEmail( $parser( 'email' ) );
		$profile->setLanguage( $parser( 'locale' ) );

		if( $parser( 'birthday' ) ){
			list( $y, $m, $d ) = explode( '-', $response->birthday );

			$profile->setBirthDay( $d );
			$profile->setBirthMonth( $m );
			$profile->setBirthYear( $y );
		}

		if( $parser( 'verified_email' ) ){
			$profile->setEmailVerified( $profile->getEmail() );
		}

		return $profile;
	}
}
