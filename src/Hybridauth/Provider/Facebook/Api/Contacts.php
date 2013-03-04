<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Facebook\Api;

use Hybridauth\Exception;
use Hybridauth\Adapter\AbstractApiOperations;
use Hybridauth\Entity\Profile;

class Contacts extends AbstractApiOperations
{
	function getUserContacts()
	{
		$response = $this->api->get( 'https://graph.facebook.com/me/friends' );
		$response = json_decode( $response );

		if( ! $response ){
			throw new Exception( "User contacts request failed! Provider returned an error" );
		}

		if( ! isset( $response->data ) || ! $response->data ){
			return array();
		}

		$parser = function($property) use($response)
		{
			return property_exists( $response, $property ) ? $response->$property : null;
		};

		$contacts = array();

		foreach( $response->data as $item ){
			$uc = new Profile();

			$profile->setIdentifier( $parser( 'id' ) );
			$profile->setDisplayName( $parser( 'name' ) ); 
			$profile->setProfileURL( 'https://www.facebook.com/profile.php?id=' . $profile->getIdentifier() );
			$profile->setPhotoURL( 'https://graph.facebook.com/' . $profile->getIdentifier() . '/picture?width=150&height=150' );

			$contacts [] = $uc;
		}

		return $contacts;
	}
}
