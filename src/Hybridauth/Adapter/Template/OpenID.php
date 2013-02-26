<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template;

/**
* OpenID adapter
* 
* http://hybridauth.sourceforge.net/userguide/IDProvider_info_OpenID.html
*/
class OpenID extends \Hybridauth\Adapter\AdapterTemplate implements \Hybridauth\Adapter\AdapterInterface
{
	/* Openid provider identifier */
	public $openidIdentifier = ""; 

	// --------------------------------------------------------------------

	/**
	* adapter initializer 
	*/
	function initialize()
	{
		if( isset( $this->parameters["openid_identifier"] ) ){
			$this->openidIdentifier = $this->parameters["openid_identifier"];
		}

		// An error was occurring when proxy wasn't set. Not sure where proxy was meant to be set/initialized.
		$proxy = isset( $this->hybridauthConfig['curl_options'] ) && isset( $this->hybridauthConfig['curl_options'][ CURLOPT_PROXY ] )
				? $this->hybridauthConfig['curl_options'][ CURLOPT_PROXY ]
				: '' ;

		$this->api = new \Hybridauth\Adapter\Api\OpenID\Api( parse_url( $this->hybridauthConfig["base_url"], PHP_URL_HOST), $proxy );
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		if( empty( $this->openidIdentifier ) ){
			throw new
				\Hybridauth\Exception(
					"Missing 'openid_identifier'",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
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
		\Hybridauth\Http\Util::redirect( $this->api->authUrl() );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		# if user don't garant acess of their data to your site, halt with an Exception
		if( $this->api->mode == 'cancel'){
			throw new
				\Hybridauth\Exception(
					"Authentication failed! User has canceled authentication",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		# if something goes wrong
		if( ! $this->api->validate() ){
			throw new
				\Hybridauth\Exception(
					"Authentication failed. Invalid request recived",
					\Hybridauth\Exception::AUTHENTIFICATION_FAILED,
					null,
					$this
				);
		}

		# fetch recived user data
		$response = $this->api->getAttributes();

		$profile = new \Hybridauth\User\Profile();

		# sotre the user profile
		$profile->providerId  = $this->providerId;
		$profile->identifier  = $this->api->identity;
		$profile->firstName   = ( array_key_exists( "namePerson/first"        ,$response ) )?$response["namePerson/first"]        : "";
		$profile->lastName    = ( array_key_exists( "namePerson/last"         ,$response ) )?$response["namePerson/last"]         : "";
		$profile->displayName = ( array_key_exists( "namePerson"              ,$response ) )?$response["namePerson"]              : "";
		$profile->email       = ( array_key_exists( "contact/email"           ,$response ) )?$response["contact/email"]           : "";
		$profile->language    = ( array_key_exists( "pref/language"           ,$response ) )?$response["pref/language"]           : "";
		$profile->country     = ( array_key_exists( "contact/country/home"    ,$response ) )?$response["contact/country/home"]    : "";
		$profile->zip         = ( array_key_exists( "contact/postalCode/home" ,$response ) )?$response["contact/postalCode/home"] : "";
		$profile->gender      = ( array_key_exists( "person/gender"           ,$response ) )?$response["person/gender"]           : "";
		$profile->photoURL    = ( array_key_exists( "media/image/default"     ,$response ) )?$response["media/image/default"]     : "";
		$profile->birthDay    = ( array_key_exists( "birthDate/birthDay"      ,$response ) )?$response["birthDate/birthDay"]      : "";
		$profile->birthMonth  = ( array_key_exists( "birthDate/birthMonth"    ,$response ) )?$response["birthDate/birthMonth"]    : "";
		$profile->birthYear   = ( array_key_exists( "birthDate/birthDate"     ,$response ) )?$response["birthDate/birthDate"]     : "";

		if( ! $profile->displayName ) {
			$profile->displayName = trim( $profile->lastName . " " . $profile->firstName ); 
		}

		if( isset( $response['namePerson/friendly'] ) && ! empty( $response['namePerson/friendly'] ) && ! $profile->displayName ) { 
			$profile->displayName = ( array_key_exists( "namePerson/friendly", $response ) ) ? $response["namePerson/friendly"] : "" ; 
		}

		if( isset( $response['birthDate'] ) && ! empty( $response['birthDate'] ) && ! $profile->birthDay ){
			list( $birthday_year, $birthday_month, $birthday_day ) = ( array_key_exists( 'birthDate', $response ) ) ? $response['birthDate'] : "";

			$profile->birthDay   = (int) $birthday_day;
			$profile->birthMonth = (int) $birthday_month;
			$profile->birthYear  = (int) $birthday_year;
		}

		if( ! $profile->displayName ){
			$profile->displayName = trim( $profile->firstName . " " . $profile->lastName );
		}

		if( strtolower( $profile->gender ) == "f" ){
			$profile->gender = "female";
		}

		if( strtolower( $profile->gender ) == "m" ){
			$profile->gender = "male";
		} 

		// set user as logged in
		$this->setUserConnected();

		// with openid providers we get the user profile only once, so store it 
		$this->storage->set( "hauth_session.{$this->providerId}.user", $profile );
	}

	// --------------------------------------------------------------------

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// try to get the user profile from stored data
		$user = $this->storage->get( "hauth_session.{$this->providerId}.user" ) ;

		// if not found
		if ( ! is_object( $user ) ){
			throw new
				\Hybridauth\Exception(
					"User profile request failed! User is not connected to {$this->providerId} or his session has expired",
					\Hybridauth\Exception::USER_PROFILE_REQUEST_FAILED,
					null,
					$this
				);
		} 

		return $user;
	}
}
