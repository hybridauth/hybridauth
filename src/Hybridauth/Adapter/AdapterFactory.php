<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter;

class AdapterFactory
{
	public $id                 = null;

	public $hybridauthConfig   = null;
	public $providerConfig     = null;
	public $providerParameters = null;
	public $providerInstance   = null;

	protected $storage         = null;

	// --------------------------------------------------------------------

	function __construct( $config, \Hybridauth\Storage\StorageInterface $storage = null )
	{
        $this->hybridauthConfig = $config;

        $this->storage = $storage; 
	}

	// --------------------------------------------------------------------

	/**
	* create a new adapter switch IDp name or ID
	*
	* @param string  $id      The id or name of the IDp
	* @param array   $params  (optional) required parameters by the adapter 
	*/
	function factory( $id, $providerParameters = null )
	{
		# init the adapter config and params
		$this->providerParameters = $providerParameters;
		$this->providerConfig     = $this->getConfigById( $id );
		$this->id                 = $this->getProviderCiId( $id );

		# check the IDp config
		if( ! $this->providerConfig ){
			throw new
				\Hybridauth\Exception( "Unknown Provider", \Hybridauth\Exception::UNKNOWN_OR_DISABLED_PROVIDER );
		}

		# check the IDp adapter is enabled
		if( ! (bool) $this->providerConfig["enabled"] ){
			throw new
				\Hybridauth\Exception( "Provider Disabled",  \Hybridauth\Exception::UNKNOWN_OR_DISABLED_PROVIDER );
		}

		# include the adapter wrapper
		$providerClassName = "\\Hybridauth\\Provider\\" . $this->id . "\\Adapter";

		if( isset( $this->providerConfig["wrapper"] ) && $this->providerConfig["wrapper"] ){
			if( isset( $this->providerConfig["wrapper"]["path"] ) && $this->providerConfig["wrapper"]["path"] ){
				require_once $this->providerConfig["wrapper"]["path"];
			}

			$providerClassName = $this->providerConfig["wrapper"]["class"];
		}

		# create the adapter instance, and pass the current params and config
		$this->providerInstance = new $providerClassName(
				$this->id                 ,
				$this->hybridauthConfig   ,
				$this->providerConfig     ,
				$this->providerParameters ,
				$this->storage
			);

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	* Setup an adapter for a given provider
	*/
	public function setup( $providerId, $providerParameters = array() )
	{
		if( ! $providerParameters ){
			$providerParameters = $this->storage->get( "hauth_session.$providerId.id_provider_params" );
		}

		if( ! isset( $providerParameters["hauth_return_to"] ) ){
			$providerParameters["hauth_return_to"] = \Hybridauth\Http\Util::getCurrentUrl(); 
		}

		$this->factory( $providerId, $providerParameters );

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	* Hybrid_Provider_Adapter::authenticate(), prepare the user session and the authentication request
	* for index.php
	*/
	function authenticate()
	{
		// clear all unneeded params
		foreach( $this->hybridauthConfig["providers"] as $idpid => $params ){
			$this->storage->delete( "hauth_session.{$idpid}.hauth_return_to"    );
			$this->storage->delete( "hauth_session.{$idpid}.hauth_endpoint"     );
			$this->storage->delete( "hauth_session.{$idpid}.id_provider_params" );
		}

		// make a fresh start
		$this->logout();

		# get hybridauth base url
		$base_url = $this->hybridauthConfig["base_url"];

		# we make use of session_id() as storage hash to identify the current user
		# using session_regenerate_id() will be a problem, but ..
		$this->providerParameters["hauth_token"] = session_id();

		# set request timestamp
		$this->providerParameters["hauth_time"]  = time();

		# hauth.start
		$this->providerParameters["login_start"] = $base_url . ( strpos( $base_url, '?' ) ? '&' : '?' ) . "hauth.start={$this->id}&hauth.time={$this->providerParameters["hauth_time"]}";

		# hauth.done
		$this->providerParameters["login_done"]  = $base_url . ( strpos( $base_url, '?' ) ? '&' : '?' ) . "hauth.done={$this->id}";

		$this->storage->set( "hauth_session.{$this->id}.hauth_return_to"    , $this->providerParameters["hauth_return_to"] );
		$this->storage->set( "hauth_session.{$this->id}.hauth_endpoint"     , $this->providerParameters["login_done"] ); 
		$this->storage->set( "hauth_session.{$this->id}.id_provider_params" , $this->providerParameters );

		// store config to be used by the end point.
		$this->storage->config( "CONFIG", $this->hybridauthConfig );

		// move on
		\Hybridauth\Http\Util::redirect( $this->providerParameters["login_start"] );
	}

	// --------------------------------------------------------------------

	/**
	* let hybridauth forget all about the user for the current provider
	*/
	function logout()
	{
		$this->providerInstance->logout();
	}

	// --------------------------------------------------------------------

	/**
	* return true if the user is connected to the current provider
	*/ 
	public function isUserConnected()
	{
		return $this->providerInstance->isUserConnected();
	}

	// --------------------------------------------------------------------

	/**
	* handle :
	*   getUserProfile()
	*   getUserContacts()
	*   getUserActivity() 
	*   setUserStatus() 
	*/ 
	public function __call( $name, $arguments ) 
	{
		if ( ! $this->isUserConnected() ){
			throw new
				\Hybridauth\Exception( "User not connected to the provider {$this->id}.", \Hybridauth\Exception::USER_NOT_CONNECTED );
		}

		if ( ! method_exists( $this->providerInstance, $name ) ){
			throw new
				\Hybridauth\Exception( "Call to undefined function Hybridauth\\Provider\\{$this->id}\\Adapter::$name()" );
		}

		if( count( $arguments ) ){
			return $this->providerInstance->$name( $arguments[0] ); 
		} 
		else{
			return $this->providerInstance->$name(); 
		}
	}

	// --------------------------------------------------------------------

	/**
	* If the user is connected, then return the access_token and access_token_secret
	* if the provider api use oauth
	*/
	public function getAccessToken()
	{
		if( ! $this->providerInstance->isUserConnected() ){
			throw new
				\Hybridauth\Exception( "User not connected to the provider.", \Hybridauth\Exception::USER_NOT_CONNECTED );
		}

		return
			array(
				"access_token"        => $this->providerInstance->token( "access_token" )       , // OAuth access token
				"access_token_secret" => $this->providerInstance->token( "access_token_secret" ), // OAuth access token secret
				"refresh_token"       => $this->providerInstance->token( "refresh_token" )      , // OAuth refresh token
				"expires_in"          => $this->providerInstance->token( "expires_in" )         , // OPTIONAL. The duration in seconds of the access token lifetime
				"expires_at"          => $this->providerInstance->token( "expires_at" )         , // OPTIONAL. Timestamp when the access_token expire. if not provided by the social api, then it should be calculated: expires_at = now + expires_in
			);
	}

	// --------------------------------------------------------------------

	public function setAccessToken( $tokens = array() )
	{
		if( isset( $tokens["access_token"]        ) ) $this->providerInstance->token( "access_token", $tokens["access_token"] );
		if( isset( $tokens["access_token_secret"] ) ) $this->providerInstance->token( "access_token_secret", $tokens["access_token_secret"] );
		if( isset( $tokens["refresh_token"]       ) ) $this->providerInstance->token( "refresh_token", $tokens["refresh_token"] );
		if( isset( $tokens["expires_in"]          ) ) $this->providerInstance->token( "expires_in", $tokens["expires_in"] );
		if( isset( $tokens["expires_at"]          ) ) $this->providerInstance->token( "expires_at", $tokens["expires_at"] );
	}

	// --------------------------------------------------------------------

	/**
	* Naive getter of the current connected IDp API client
	*/
	function api()
	{
		if( ! $this->providerInstance->isUserConnected() ){
			throw new
				\Hybridauth\Exception( "User not connected to the provider.", \Hybridauth\Exception::USER_NOT_CONNECTED );
		}

		return $this->providerInstance->api;
	}

	// --------------------------------------------------------------------

	/**
	* redirect the user to hauth_return_to (the callback url)
	*/
	function returnToCallbackUrl()
	{
		// get the stored callback url
		$callback_url = $this->storage->get( "hauth_session.{$this->id}.hauth_return_to" );

		// remove some unneed'd stored data 
		$this->storage->delete( "hauth_session.{$this->id}.hauth_return_to"    );
		$this->storage->delete( "hauth_session.{$this->id}.hauth_endpoint"     );
		$this->storage->delete( "hauth_session.{$this->id}.id_provider_params" );

		// back to home
		\Hybridauth\Http\Util::redirect( $callback_url );
	}

	// --------------------------------------------------------------------

	/**
	* return the provider config by id
	*/
	function getConfigById( $id )
	{ 
		if( isset( $this->hybridauthConfig["providers"][$id] ) ){
			return $this->hybridauthConfig["providers"][$id];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	* return the provider config by id; insensitive
	*/
	function getProviderCiId( $id )
	{
		foreach( $this->hybridauthConfig["providers"] as $idpid => $params ){
			if( strtolower( $idpid ) == strtolower( $id ) ){
				return $idpid;
			}
		}

		return null;
	}
}
