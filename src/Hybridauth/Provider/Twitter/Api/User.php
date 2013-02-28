<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Twitter\Api;

class User
{
	function getUserProfile( $options = array() )
	{
		// request user infos
		$response = $this->api->get( "account/verify_credentials.json" );
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

		$profile = new \Hybridauth\Entity\Profile();

		$profile->providerId  = $this->api->providerId;
		$profile->identifier  = ( property_exists( $response, 'id'                ) ) ? $response->id                : "";
		$profile->firstName   = ( property_exists( $response, 'name'              ) ) ? $response->name              : ""; 
		$profile->displayName = ( property_exists( $response, 'screen_name'       ) ) ? $response->screen_name       : "";
		$profile->description = ( property_exists( $response, 'description'       ) ) ? $response->description       : ""; 
		$profile->photoURL    = ( property_exists( $response, 'profile_image_url' ) ) ? $response->profile_image_url : "";
		$profile->profileURL  = ( property_exists( $response, 'screen_name'       ) ) ? ("http://twitter.com/".$response->screen_name) : "";
		$profile->webSiteURL  = ( property_exists( $response, 'url'               ) ) ? $response->url               : ""; 
		$profile->region      = ( property_exists( $response, 'location'          ) ) ? $response->location          : "";

		return $profile;
	}
}
