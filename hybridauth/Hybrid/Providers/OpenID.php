<?php
/**
* HybridAuth
* 
* A Social-Sign-On PHP Library for authentication through identity providers like Facebook,
* Twitter, Google, Yahoo, LinkedIn, MySpace, Windows Live, Tumblr, Friendster, OpenID, PayPal,
* Vimeo, Foursquare, AOL, Gowalla, and others.
*
* Copyright (c) 2009-2011 (http://hybridauth.sourceforge.net) 
*/

/**
 * Hybrid_Providers_OpenID class, wrapper for OpenID
 */
class Hybrid_Providers_OpenID extends Hybrid_Provider_Model
{
	var $openidIdentifier = ""; 

   /**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		if( isset( $this->params["openid_identifier"] ) )
		{
			$this->openidIdentifier = $this->params["openid_identifier"];
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "OpenID/LightOpenID.php"; 

		$this->api = new LightOpenID( parse_url( Hybrid_Auth::$config["base_url"], PHP_URL_HOST) ); 
	}
 
   	/**
	* begin login step 
	*/
	function loginBegin( )
	{ 
		$this->api->identity  = $this->openidIdentifier;
		$this->api->returnUrl = $this->endpoint;
		$this->api->required  = ARRAY( 
									'namePerson/first'	 ,
									'namePerson/last'	 ,
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

		# redirect the user 
		Hybrid_Auth::redirect( $this->api->authUrl() );
	}
	
   	/**
	* finish login step 
	*/
	function loginFinish( )
	{
		# if user don't garant acess of their data to your site, halt with an Exception
		if( $this->api->mode == 'cancel')
		{
			throw new Exception( "Authentification failed! User has canceled authentication!", 5 );
		} 

		# if something goes wrong
		if( ! $this->api->validate() )
		{
			throw new Exception( "Authentification failed. Invalid request recived!", 5 );
		}

		$response = $this->api->getAttributes();

		# fetch recived user data
		$this->user->profile->identifier  = $this->api->identity;

		$this->user->profile->firstName   = @ $response["namePerson/first"];
		$this->user->profile->lastName    = @ $response["namePerson/last"];
		$this->user->profile->displayName = @ $response["namePerson"];
		$this->user->profile->email       = @ $response["contact/email"];
		$this->user->profile->language    = @ $response["pref/language"];
		$this->user->profile->country     = @ $response["contact/country/home"]; 
		$this->user->profile->zip         = @ $response["contact/postalCode/home"]; 
		$this->user->profile->gender      = @ strtolower( $response["person/gender"] ); 
		$this->user->profile->photoURL    = @ $response["media/image/default"] ; 

		$this->user->profile->birthDay    = @ $response["birthDate/birthDay"] ; 
		$this->user->profile->birthMonth  = @ $response["birthDate/birthMonth"] ; 
		$this->user->profile->birthYear   = @ $response["birthDate/birthDate"] ;  

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

		// then store it
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user", $this->user );
	}

   	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$this->user = Hybrid_Auth::storage()->get( "hauth_session.{$this->providerId}.user" ) ;

		if ( ! is_object( $this->user ) )
		{
			throw new Exception( "User profile request failed! User is not connected to {$this->providerId} or his session has expired.", 6 );
		} 

		return $this->user->profile;
	}
}
