<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
 * To implement an OpenID based service provider, Hybrid_Provider_Model_OpenID
 * can be used to save the hassle of the authentication flow. 
 * 
 * Each class that inherit from Hybrid_Provider_Model_OAuth2 have only to define
 * the provider identifier : <code>public $openidIdentifier = ""; </code>
 *
 * Hybrid_Provider_Model_OpenID use LightOpenID lib which can be found on
 * Hybrid/thirdparty/OpenID/LightOpenID.php
 */
class Hybridauth_Core_Provider_Model_OpenID extends Hybridauth_Core_Provider_Model
{
	/* Openid provider identifier */
	public $openidIdentifier = ""; 

	// --------------------------------------------------------------------

	/**
	* adapter initializer 
	*/
	function initialize()
	{
		if( isset( $this->params["openid_identifier"] ) ){
			$this->openidIdentifier = $this->params["openid_identifier"];
		}

		// An error was occurring when proxy wasn't set. Not sure where proxy was meant to be set/initialized.
		Hybridauth_Core_Authentication::$config['proxy'] = isset(Hybridauth_Core_Authentication::$config['proxy'])?Hybridauth_Core_Authentication::$config['proxy']:'';
		
		$this->api = new Hybridauth_Core_Provider_Protocol_OpenID( parse_url( Hybridauth_Core_Authentication::$config["base_url"], PHP_URL_HOST), Hybridauth_Core_Authentication::$config["proxy"] ); 
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		if( empty( $this->openidIdentifier ) ){
			throw new Hybridauth_Core_Exception( "OpenID adapter require the identity provider identifier 'openid_identifier' as an extra parameter.", 4 );
		}

		$this->api->identity  = $this->openidIdentifier;
		$this->api->returnUrl = $this->endpoint;
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
		Hybridauth_Core_Common_HTTP::redirect( $this->api->authUrl() );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		# if user don't garant acess of their data to your site, halt with an Exception
		if( $this->api->mode == 'cancel'){
			throw new Hybridauth_Core_Exception( "Authentication failed! User has canceled authentication!", 5 );
		}

		# if something goes wrong
		if( ! $this->api->validate() ){
			throw new Hybridauth_Core_Exception( "Authentication failed. Invalid request recived!", 5 );
		}

		# fetch recived user data
		$response = $this->api->getAttributes();

		# sotre the user profile
		$this->user->profile->identifier  = $this->api->identity;

		$this->user->profile->firstName   = (array_key_exists("namePerson/first",$response))?$response["namePerson/first"]:"";
		$this->user->profile->lastName    = (array_key_exists("namePerson/last",$response))?$response["namePerson/last"]:"";
		$this->user->profile->displayName = (array_key_exists("namePerson",$response))?$response["namePerson"]:"";
		$this->user->profile->email       = (array_key_exists("contact/email",$response))?$response["contact/email"]:"";
		$this->user->profile->language    = (array_key_exists("pref/language",$response))?$response["pref/language"]:"";
		$this->user->profile->country     = (array_key_exists("contact/country/home",$response))?$response["contact/country/home"]:""; 
		$this->user->profile->zip         = (array_key_exists("contact/postalCode/home",$response))?$response["contact/postalCode/home"]:""; 
		$this->user->profile->gender      = (array_key_exists("person/gender",$response))?$response["person/gender"]:""; 
		$this->user->profile->photoURL    = (array_key_exists("media/image/default",$response))?$response["media/image/default"]:""; 

		$this->user->profile->birthDay    = (array_key_exists("birthDate/birthDay",$response))?$response["birthDate/birthDay"]:""; 
		$this->user->profile->birthMonth  = (array_key_exists("birthDate/birthMonth",$response))?$response["birthDate/birthMonth"]:""; 
		$this->user->profile->birthYear   = (array_key_exists("birthDate/birthDate",$response))?$response["birthDate/birthDate"]:"";  

		if( ! $this->user->profile->displayName ) {
			$this->user->profile->displayName = trim( $this->user->profile->lastName . " " . $this->user->profile->firstName ); 
		}

		if( isset( $response['namePerson/friendly'] ) && ! empty( $response['namePerson/friendly'] ) && ! $this->user->profile->displayName ) { 
			$this->user->profile->displayName = (array_key_exists("namePerson/friendly",$response))?$response["namePerson/friendly"]:"" ; 
		}

		if( isset( $response['birthDate'] ) && ! empty( $response['birthDate'] ) && ! $this->user->profile->birthDay ) {
			list( $birthday_year, $birthday_month, $birthday_day ) = (array_key_exists('birthDate',$response))?$response['birthDate']:"";

			$this->user->profile->birthDay   = (int) $birthday_day;
			$this->user->profile->birthMonth = (int) $birthday_month;
			$this->user->profile->birthYear  = (int) $birthday_year;
		}

		if( ! $this->user->profile->displayName ){
			$this->user->profile->displayName = trim( $this->user->profile->firstName . " " . $this->user->profile->lastName );
		}

		if( $this->user->profile->gender == "f" ){
			$this->user->profile->gender = "female";
		}

		if( $this->user->profile->gender == "m" ){
			$this->user->profile->gender = "male";
		} 

		// set user as logged in
		$this->setUserConnected();

		// with openid providers we get the user profile only once, so store it 
		$this->storage->set( "hauth_session.{$this->providerId}.user", $this->user );
	}

	// --------------------------------------------------------------------

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// try to get the user profile from stored data
		$this->user = $this->storage->get( "hauth_session.{$this->providerId}.user" ) ;

		// if not found
		if ( ! is_object( $this->user ) ){
			throw new Exception( "User profile request failed! User is not connected to {$this->providerId} or his session has expired.", 6 );
		}

		return $this->user->profile;
	}
}
