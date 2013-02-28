<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Facebook\Api;

class User
{
	function getUserProfile( $options = array() )
	{
		// request user infos
		$response = $this->api->get( "https://graph.facebook.com/me" );
		$response = json_decode( $response );

		if ( ! isset( $response->id ) || isset( $response->error ) ){
			throw new
				\Hybridauth\Exception( 
					"User profile request failed! {$this->providerId} returned an invalid response.", 
					\Hybridauth\Exception::USER_PROFILE_REQUEST_FAILED, 
					null,
					$this
				);
		}

		$profile = new \Hybridauth\Entity\Profile();

		$profile->providerId    = $this->api->providerId;
		$profile->identifier    = ( property_exists( $response, 'id'        ) ) ? $response->id         : "";
		$profile->displayName   = ( property_exists( $response, 'name'      ) ) ? $response->name       : "";
		$profile->firstName     = ( property_exists( $response, 'first_name') ) ? $response->first_name : "";
		$profile->lastName      = ( property_exists( $response, 'last_name' ) ) ? $response->last_name  : "";
		$profile->profileURL    = ( property_exists( $response, 'link'      ) ) ? $response->link       : ""; 
		$profile->webSiteURL    = ( property_exists( $response, 'website'   ) ) ? $response->website    : ""; 
		$profile->gender        = ( property_exists( $response, 'gender'    ) ) ? $response->gender     : "";
		$profile->description   = ( property_exists( $response, 'bio'       ) ) ? $response->bio        : "";
		$profile->email         = ( property_exists( $response, 'email'     ) ) ? $response->email      : ""; 
		$profile->region        = ( property_exists( $response, 'hometown'    ) && property_exists( $response->hometown, 'name' ) ) ? $response->hometown->name : "";
		$profile->photoURL      = "https://graph.facebook.com/" . $profile->identifier . "/picture?width=150&height=150";

		if( property_exists($response,'birthday') ) {
			list($birthday_month, $birthday_day, $birthday_year) = explode( "/", $response->birthday );

			$profile->birthDay   = (int) $birthday_day;
			$profile->birthMonth = (int) $birthday_month;
			$profile->birthYear  = (int) $birthday_year;
		}

		if( property_exists( $response, 'verified' ) && $response->verified ){ 
			$profile->emailVerified = $profile->email ;
		}

		return $profile;
	}
}
