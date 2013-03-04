<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Facebook\Api;

use Hybridauth\Exception;
use Hybridauth\Adapter\Api\AbstractApiOperations;
use Hybridauth\Entity\Profile;

class User extends AbstractApiOperations
{
	function getUserProfile()
	{
		// request user infos
		$response = $this->get ( "https://graph.facebook.com/me" );
		$response = json_decode ( $response );

		if ( ! isset( $response->id ) || isset ( $response->error ) ){
			throw new Exception ( "User profile request failed! Provider returned an invalid response.", Exception::USER_PROFILE_REQUEST_FAILED, null, $this );
		}

		$parser = function($property) use($response)
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$profile = new \Hybridauth\Entity\Profile();

		$profile->setIdentifier( $parser( 'id' ) );
		$profile->setFirstName( $parser( 'first_name' ) );
		$profile->setLastName( $parser( 'last_name' ) );
		$profile->setDisplayName( $parser( 'name' ) ); 
		$profile->setProfileURL( $parser( 'link' ) );
		$profile->setWebSiteURL( $parser( 'website' ) );
		$profile->setGender( $parser( 'gender' ) );
		$profile->setDescription( $parser( 'bio' ) );
		$profile->setEmail( $parser( 'email' ) );
		$profile->setPhotoURL( 'https://graph.facebook.com/' . $profile->getIdentifier() . '/picture?width=150&height=150' );
		$profile->setLanguage( $parser( 'locale' ) );

		if( $parser( 'birthday' ) ){
			list ( $m, $d, $y ) = explode ( "/", $parser( 'birthday' ) );
			
			$profile->setBirthDay( $d );
			$profile->setBirthMonth( $m );
			$profile->setBirthYear( $y );
		}

		if( $parser( 'verified' ) ){
			$profile->setEmailVerified( $profile->getEmail() );
		}

		return $profile;
	}
}
