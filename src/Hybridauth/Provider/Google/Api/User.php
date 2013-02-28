<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Provider\Google\Api;

class User
{
	function getUserProfile( $options = array() )
	{
		// refresh tokens
		// $this->refreshAccessToken();

		// request user infos
		$response = $this->api->get( "https://www.googleapis.com/oauth2/v1/userinfo" );
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
		$profile->identifier  = ( property_exists( $response, 'id'          ) ) ? $response->id          : "";
		$profile->firstName   = ( property_exists( $response, 'given_name'  ) ) ? $response->given_name  : "";
		$profile->lastName    = ( property_exists( $response, 'family_name' ) ) ? $response->family_name : "";
		$profile->displayName = ( property_exists( $response, 'name'        ) ) ? $response->name        : "";
		$profile->photoURL    = ( property_exists( $response, 'picture'     ) ) ? $response->picture     : "";
		$profile->profileURL  = ( property_exists( $response, 'link'        ) ) ? $response->link        : "";
		$profile->gender      = ( property_exists( $response, 'gender'      ) ) ? $response->gender      : "";
		$profile->email       = ( property_exists( $response, 'email'       ) ) ? $response->email       : "";
		$profile->language    = ( property_exists( $response, 'locale'      ) ) ? $response->locale      : "";

		if( property_exists( $response,'birthday' ) ){
			list($birthday_year, $birthday_month, $birthday_day) = explode( '-', $response->birthday );

			$profile->birthDay   = (int) $birthday_day;
			$profile->birthMonth = (int) $birthday_month;
			$profile->birthYear  = (int) $birthday_year;
		}

		if( property_exists( $response, 'verified_email' ) && $response->verified_email ){ 
			$profile->emailVerified = $profile->email ;
		}

		return $profile;
	}
}
