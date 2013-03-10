<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template\OpenID;

use Hybridauth\Exception;
use Hybridauth\Http\Util;
use Hybridauth\Adapter\AbstractAdapter;
use Hybridauth\Adapter\AdapterInterface;
use Hybridauth\Adapter\Template\OpenID\LightOpenID;

/**
* OpenID adapter
* 
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_OpenID.html
*/
class OpenIDTemplate extends AbstractAdapter implements AdapterInterface
{
	/* Openid provider identifier */
	protected $openidIdentifier = null;

	// --------------------------------------------------------------------

	/**
	* adapter initializer 
	*/
	function initialize()
	{
		$proxy = isset( $this->hybridauthConfig['curl_options'] ) && isset( $this->hybridauthConfig['curl_options'][ CURLOPT_PROXY ] )
				? $this->hybridauthConfig['curl_options'][ CURLOPT_PROXY ]
				: '' ;

		$this->api = new LightOpenID( parse_url( $this->hybridauthConfig["base_url"], PHP_URL_HOST), $proxy );
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		if( empty( $this->openidIdentifier ) ){
			throw new
				Exception(
					"Missing 'openid_identifier'",
					Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		$this->api->identity  = $this->openidIdentifier;
		$this->api->returnUrl = $this->hybridauthEndpoint;
		$this->api->required  = ARRAY( 
			'namePerson/first'       ,
			'namePerson/last'        ,
			'namePerson/friendly'    ,
			'namePerson'             ,

			'contact/email'          ,

			'birthDate'              ,
			'birthDate/birthDay'     ,
			'birthDate/birthMonth'   ,
			'birthDate/birthYear'    ,

			'person/gender'          ,
			'pref/language'          , 

			'contact/postalCode/home',
			'contact/city/home'      ,
			'contact/country/home'   , 

			'media/image/default'    ,
		);

		# redirect the user to the provider authentication url
		Util::redirect( $this->api->authUrl() );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		# user canceled?
		if( $this->api->mode == 'cancel'){
			throw new
				Exception(
					"Authentication failed! User has canceled authentication",
					Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		# something wrong?
		if( ! $this->api->validate() ){
			throw new
				Exception(
					"Authentication failed. Invalid request recived",
					Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		# fetch data
		$response = $this->api->getAttributes();

		$parser = function( $property ) use ( $response )
		{
			return array_key_exists( $property, $response ) ? $response[ $property ] : null;
		};

		$profile = new \Hybridauth\Entity\Profile();

		$profile->setIdentifier ( $this->api->identity );

		$profile->setFirstName  ( $parser( 'namePerson/first'        ) );
		$profile->setLastName   ( $parser( 'namePerson/last'         ) );
		$profile->setDisplayName( $parser( 'namePerson'              ) );
		$profile->setPhotoURL   ( $parser( 'media/image/default'     ) );

		$profile->setEmail      ( $parser( 'contact/email'           ) );
		$profile->setLanguage   ( $parser( 'pref/language'           ) );

		$profile->setBirthDay   ( $parser( 'birthDate/birthDay'      ) );
		$profile->setBirthMonth ( $parser( 'birthDate/birthMonth'    ) );
		$profile->setBirthYear  ( $parser( 'birthDate/birthDate'     ) );

		$profile->setCountry    ( $parser( 'contact/country/home'    ) );
		$profile->setZip        ( $parser( 'contact/postalCode/home' ) );

		$gender = $parser( 'gender' );

		if( strtolower( $gender ) == "f" ){
			$gender = 'female';
		}

		if( strtolower( $gender ) == "m" ){
			$gender = 'male';
		} 

		$profile->setGender( $gender );

		if( ! $profile->getDisplayName() ){
			$profile->setDisplayName(  $profile->setLastName() . " " . $profile->setFirstName() );

			if( $parser( 'namePerson/friendly' ) ){
				$profile->setDisplayName(  $profile->setLastName() . " " . $profile->setFirstName() );
			}
		}

		if( ! $profile->getBirthDay() ){
			if( $dob = $parser( 'birthDate' ) ){
				list( $y, $m, $d ) = $dob;

				$profile->setBirthDay   ( $d );
				$profile->setBirthMonth ( $m );
				$profile->setBirthYear  ( $d );
			}
		}

		// with openid providers we get the user profile only once, so store it 
		$this->storage->set( "{$this->providerId}.user", $profile );
	}

	// --------------------------------------------------------------------

	function isAuthorized()
	{
		return $this->storage->get( "{$this->providerId}.user" ) != null;
	}

	// ====================================================================

	function getOpenidIdentifier()
	{
		return $this->openidIdentifier;
	}

	// --------------------------------------------------------------------

	function setOpenidIdentifier( $openidIdentifier )
	{
		$this->openidIdentifier = $openidIdentifier;
	}

	// --------------------------------------------------------------------

	function letOpenidIdentifier( $openidIdentifier )
	{
		if( $this->getOpenidIdentifier() ){
			return;
		}

		$this->setOpenidIdentifier( $openidIdentifier );
	}
}
