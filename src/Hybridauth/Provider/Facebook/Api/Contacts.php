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
		$response = $this->api->get( 'https://graph.facebook.com/me/friends' ); 
		$response = json_decode( $response );

		if( ! $response ){
			throw new
				\Hybridauth\Exception( "User contacts request failed! {$this->providerId} returned an error" );
		}

		if( ! isset( $response->data ) || ! $response->data ){
			return array();
		}

		$contacts = array();
 
		foreach( $response->data as $item ){
			$uc = new \Hybridauth\Entity\Profile();

			$uc->providerId  = $this->api->providerId;
			$uc->identifier  = ( property_exists( $item, 'id'   ) ) ? $item->id   : ""; 
			$uc->displayName = ( property_exists( $item, 'name' ) ) ? $item->name : ""; 
			$uc->profileURL  = "https://www.facebook.com/profile.php?id=" . $uc->identifier;
			$uc->photoURL    = "https://graph.facebook.com/" . $uc->identifier . "/picture?width=150&height=150";

			$contacts[] = $uc;
		}

		return $contacts;
	}
}
