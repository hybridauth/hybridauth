<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
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
class Hybrid_Provider_Model_OpenID extends Hybrid_Provider_Model
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

		// include LightOpenID lib
		require_once Hybrid_Auth::$config["path_libraries"] . "OpenID/LightOpenID.php"; 

		$this->api = new LightOpenID( parse_url( Hybrid_Auth::$config["base_url"], PHP_URL_HOST) ); 
	}

	// --------------------------------------------------------------------

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		if( empty( $this->openidIdentifier ) ){
			throw new Exception( "OpenID adapter require the identity provider identifier 'openid_identifier' as an extra parameter.", 4 );
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
		Hybrid_Auth::redirect( $this->api->authUrl() );
	}

	// --------------------------------------------------------------------

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		# if user don't garant acess of their data to your site, halt with an Exception
		if( $this->api->mode == 'cancel'){
			throw new Exception( "Authentification failed! User has canceled authentication!", 5 );
		}

		# if something goes wrong
		if( ! $this->api->validate() ){
			throw new Exception( "Authentification failed. Invalid request recived!", 5 );
		}

		# fetch recived user data
		$response = $this->api->getAttributes();

		# sotre the user profile
		$this->user->profile->identifier  = $this->api->identity;

		$this->user->profile->firstName   = @ $response["namePerson/first"];
		$this->user->profile->lastName    = @ $response["namePerson/last"];
		$this->user->profile->displayName = @ $response["namePerson"];
		$this->user->profile->email       = @ $response["contact/email"];
		$this->user->profile->language    = @ $response["pref/language"];
		$this->user->profile->country     = @ $response["contact/country/home"]; 
		$this->user->profile->zip         = @ $response["contact/postalCode/home"]; 
		$this->user->profile->gender      = @ strtolower( $response["person/gender"] ); 
		$this->user->profile->photoURL    = @ $response["media/image/default"]; 

		$this->user->profile->birthDay    = @ $response["birthDate/birthDay"]; 
		$this->user->profile->birthMonth  = @ $response["birthDate/birthMonth"]; 
		$this->user->profile->birthYear   = @ $response["birthDate/birthDate"];  

		if( ! $this->user->profile->displayName ) {
			$this->user->profile->displayName = trim( $this->user->profile->lastName . " " . $this->user->profile->firstName ); 
		}

		if( isset( $response['namePerson/friendly'] ) && ! empty( $response['namePerson/friendly'] ) && ! $this->user->profile->displayName ) { 
			$this->user->profile->displayName = @ $response["namePerson/friendly"] ; 
		}

		if( isset( $response['birthDate'] ) && ! empty( $response['birthDate'] ) && ! $this->user->profile->birthDay ) {
			list( $birthday_year, $birthday_month, $birthday_day ) = @ explode( '-', $response['birthDate'] );

			$this->user->profile->birthDay      = (int) $birthday_day;
			$this->user->profile->birthMonth    = (int) $birthday_month;
			$this->user->profile->birthYear     = (int) $birthday_year;
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
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user", $this->user );
	}

	// --------------------------------------------------------------------

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// try to get the user profile from stored data
		$this->user = Hybrid_Auth::storage()->get( "hauth_session.{$this->providerId}.user" ) ;

		// if not found
		if ( ! is_object( $this->user ) ){
			throw new Exception( "User profile request failed! User is not connected to {$this->providerId} or his session has expired.", 6 );
		} 

		return $this->user->profile;
	}
}
