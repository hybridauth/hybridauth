<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Google\Api;

class Contacts
{
	function getUserContacts( $options = array() )
	{
		$response = $this->api->get( "https://www.google.com/m8/feeds/contacts/default/full?alt=json" );
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
			$profile = new \Hybridauth\Entity\Profile();

			// $profile->providerId  = $this->providerId;
			$profile->email       = isset($entry->{'gd$email'}[0]->address) ? (string) $entry->{'gd$email'}[0]->address : ''; 
			$profile->displayName = isset($entry->title->{'$t'}) ? (string) $entry->title->{'$t'} : '';  
			$profile->identifier  = $profile->email;

			$contacts[] = $profile;
		}  

		return $contacts;
	}
}
