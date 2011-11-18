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
* Providers Model Class
*/
abstract class Hybrid_Provider_Model
{
   	/**
	* the IDp api client (optional)
	*/
	public $api              = NULL; 

	/**
	* Hybrid_User obj, represents the current user
	*/
	public $user             = NULL;

   	/**
	* IDp adapter config on hybridauth.php
	*/
	public $config           = NULL;

   	/**
	* IDp adapter requireds params
	*/
	public $params           = NULL;

   	/**
	* IDp ID (or unique name)
	*/
	public $providerId       = NULL;

   	/**
	* Hybridauth Endpoint URL
	*/
	public $endpoint        = NULL; 

   	/**
	* common IDp wrappers constructor
	*/
	function __construct( $providerId, $config, $params = NULL )
	{
		# init the IDp adapter parameters, get them from the cache if possible
		if( ! $params ){
			$this->params = Hybrid_Auth::storage()->get( "hauth_session.$providerId.id_provider_params" );
		}
		else{
			$this->params = $params;
		}

		// idp id
		$this->providerId         = $providerId;

		// set HybridAuth endpoint for this provider
		$this->endpoint           = Hybrid_Auth::storage()->get( "hauth_session.$providerId.hauth_endpoint" );

		// idp config
		$this->config             = $config;

		// new user instance
		$this->user               = new Hybrid_User();  
		$this->user->providerId   = $providerId; 

		// initialize the adapter
		$this->initialize(); 
	}

	// --------------------------------------------------------------------

   	/**
	* IDp wrappers initializer
	*
	* The main job of wrappers initializer is to performs (depend on the IDp api client it self): 
	*     - include some libs nedded by this provider,
	*     - check IDp key and secret,
	*     - set some needed parameters (stored in $this->params) by this IDp api client
	*     - create and setup an instance of the IDp api client on $this->api 
	*/
	abstract protected function initialize(); 

	// --------------------------------------------------------------------

   	/**
	* begin login 
	*/
	abstract protected function loginBegin();

	// --------------------------------------------------------------------

   	/**
	* finish login
	*/
	abstract protected function loginFinish();

	// --------------------------------------------------------------------

   	/**
	* generic logout, just erase current provider adapter stored data to let Hybrid_Auth all forget about it
	*/
	function logout()
	{
		Hybrid_Logger::info( "Enter [{$this->providerId}]::logout()" );

		$this->clearTokens(); 

		return TRUE;
	}

	// --------------------------------------------------------------------

   	/**
	* grab the user profile from the IDp api client
	*/
	abstract protected function getUserProfile();

	// --------------------------------------------------------------------

   	/**
	* load the current logged in user contacts list from the IDp api client  
	*/
	function getUserContacts() 
	{
		Hybrid_Logger::error( "HybridAuth do not provide users contats list for {$this->providerId} yet." ); 
		
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

	// --------------------------------------------------------------------

   	/**
	* return the user activity stream  
	*/
	function getUserActivity( $stream ) 
	{
		Hybrid_Logger::error( "HybridAuth do not provide user's activity stream for {$this->providerId} yet." ); 
		
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

	// --------------------------------------------------------------------

   	/**
	* return the user activity stream  
	*/ 
	function setUserStatus( $status )
	{
		Hybrid_Logger::error( "HybridAuth do not provide user's activity stream for {$this->providerId} yet." ); 
		
		throw new Exception( "Provider does not support this feature.", 8 ); 
	}

	// --------------------------------------------------------------------

   	/**
	* return true if the user is connected to the current provider
	*/ 
	public function isUserConnected()
	{
		return 
			( bool) Hybrid_Auth::storage()->get( "hauth_session.{$this->providerId}.is_logged_in" );
	}

	// --------------------------------------------------------------------

   	/**
	* set user to connected 
	*/ 
	public function setUserConnected()
	{
		Hybrid_Logger::info( "Enter [{$this->providerId}]::setUserConnected()" );
		
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.is_logged_in", 1 );
	}

	// --------------------------------------------------------------------

   	/**
	* set user to unconnected 
	*/ 
	public function setUserUnconnected()
	{
		Hybrid_Logger::info( "Enter [{$this->providerId}]::setUserUnconnected()" );
		
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.is_logged_in", 0 ); 
	}

	// --------------------------------------------------------------------

   	/**
	* get or set a token 
	*/ 
	public function token( $token, $value = NULL )
	{
		if( $value === NULL ){
			return Hybrid_Auth::storage()->get( "hauth_session.{$this->providerId}.token.$token" );
		}
		else{
			Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.token.$token", $value );
		}
	}

	// --------------------------------------------------------------------

   	/**
	* clear all existen tokens for this provider
	*/ 
	public function clearTokens()
	{ 
		Hybrid_Auth::storage()->deleteMatch( "hauth_session.{$this->providerId}." );
	}
}
