<?php
/*!
* This file is part of the HybridAuth PHP Library(hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Google\Api;

use Hybridauth\Exception;
use Hybridauth\Adapter\AbstractApiOperations;
use Hybridauth\Entity\Profile;

class Contacts extends AbstractApiOperations
{
	function initialize() // fixme!
	{
		$this->refreshAccessToken();
	}

	// --------------------------------------------------------------------

	function getUserContacts()
	{
		$response = $this->get( "https://www.google.com/m8/feeds/contacts/default/full?alt=json" );
		$response = json_decode( $response );

		if( ! $response || isset( $response->error ) ){
			throw new Exception( "User contacts request failed! Provider returned an invalid response" );
		}

		if( ! isset( $response->feed ) || ! $response->feed ){
			return array();
		}

		$contacts = array();

		foreach( $response->feed->entry as $idx => $entry ){
			$email = isset( $entry->{'gd$email'} [0]->address ) ? ( string ) $entry->{'gd$email'} [0]->address : '';
			$displayName = isset( $entry->title->{'$t'} ) ? ( string ) $entry->title->{'$t'} : '';

			$profile = new Profile();

			$profile->setIdentifier( $email );
			$profile->setDisplayName( $displayName );
			$profile->setEmail( $email );
			$profile->setProfileURL( 'https://graph.facebook.com/' . $profile->getIdentifier() . '/picture?width=150&height=150' );

			$contacts [] = $profile;
		}

		return $contacts;
	}
}
