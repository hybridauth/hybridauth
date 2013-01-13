<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
 * Hybrid_Provider_Adapter is the basic class which HybridAuth will use
 * to connect users to a given provider. 
 * 
 * Basically Hybrid_Provider_Adapterwill create a bridge from your php 
 * application to the provider api.
 * 
 * HybridAuth will automatically load Hybrid_Provider_Adapter and create
 * an instance of it for each authenticated provider.
 */
class Hybridauth_Core_Provider_Adapter
{
	/* Provider ID (or unique name) */
	public $id         = null ;

	/* Provider adapter specific config */
	public $config     = null ;

	/* Provider adapter extra parameters */
	public $params     = null ; 

	/* Provider adapter wrapper path */
	public $wrapper    = null ;

	/* Provider adapter instance */
	protected $storage = null; 

	protected $logger  = null; 

	// --------------------------------------------------------------------

	function __construct(Hybridauth_Core_Storage_Interface $storage, Hybridauth_Core_Common_LoggerInterface $logger)
	{ 
        $this->storage = $storage;
		$this->logger  = $logger;
	}

	// --------------------------------------------------------------------

	/**
	* Setup an adapter for a given provider
	*/ 
	public function setup( $providerId, $params = NULL )
	{ 
		if( ! $params ){ 
			$params = $this->storage->get( "hauth_session.$providerId.id_provider_params" );
		}

		if( ! $params ){ 
			$params = ARRAY();
		}

		if( ! isset( $params["hauth_return_to"] ) ){
			$params["hauth_return_to"] = Hybridauth_Core_Common_HTTP::getCurrentUrl(); 
		}

		$this->factory( $providerId, $params );

		return $this;
	} 

	/**
	* create a new adapter switch IDp name or ID
	*
	* @param string  $id      The id or name of the IDp
	* @param array   $params  (optional) required parameters by the adapter 
	*/
	function factory( $id, $params = NULL )
	{
		# init the adapter config and params
		$this->id     = $id;
		$this->params = $params; 
		$this->config = $this->getConfigById( $this->id );

		# check the IDp id
		if( ! $this->id ){
			throw new Hybridauth_Core_Exception( "No provider ID specified.".$this->id, 2 ); 
		}

		$this->id = $this->getProviderCiId( $this->id );

		# check the IDp config
		if( ! $this->config ){
			throw new Hybridauth_Core_Exception( "Unknown Provider ID, check your configuration file.", 3 ); 
		}

		# check the IDp adapter is enabled
		if( ! $this->config["enabled"] ){
			throw new Hybridauth_Core_Exception( "The provider '{$this->id}' is not enabled.", 3 );
		}

		# include the adapter wrapper
		if( isset( $this->config["wrapper"] ) && is_array( $this->config["wrapper"] ) ){
			require_once $this->config["wrapper"]["path"];

			if( ! class_exists( $this->config["wrapper"]["class"] ) ){
				throw new Hybridauth_Core_Exception( "Unable to load the adapter class.", 3 );
			}

			$this->wrapper = $this->config["wrapper"]["class"];
		}
		else{
			$this->wrapper = "Hybridauth_Provider_{$this->id}";
		}

		# create the adapter instance, and pass the current params and config
		$this->adapter = new $this->wrapper( $this->id, $this->config, $this->params, $this->storage, $this->logger );

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	* Hybrid_Provider_Adapter::authenticate(), prepare the user session and the authentication request
	* for index.php
	*/
	function authenticate()
	{
		if( ! $this->adapter ){
			throw new Hybridauth_Core_Exception( "Hybrid_Provider_Adapter::authenticate() should not directly used." );
		}

		// clear all unneeded params
		foreach( HybridAuth::$config["providers"] as $idpid => $params ){
			$this->storage->delete( "hauth_session.{$idpid}.hauth_return_to"    );
			$this->storage->delete( "hauth_session.{$idpid}.hauth_endpoint"     );
			$this->storage->delete( "hauth_session.{$idpid}.id_provider_params" );
		}

		// make a fresh start
		$this->logout();

		# get hybridauth base url
		$HybridAuth_URL_BASE = HybridAuth::$config["base_url"];

		# we make use of session_id() as storage hash to identify the current user
		# using session_regenerate_id() will be a problem, but ..
		$this->params["hauth_token"] = session_id();

		# set request timestamp
		$this->params["hauth_time"]  = time();

		# for default HybridAuth endpoint url hauth_login_start_url
		# 	auth.start  required  the IDp ID
		# 	auth.time   optional  login request timestamp
		$this->params["login_start"] = $HybridAuth_URL_BASE . ( strpos( $HybridAuth_URL_BASE, '?' ) ? '&' : '?' ) . "hauth.start={$this->id}&hauth.time={$this->params["hauth_time"]}";

		# for default HybridAuth endpoint url hauth_login_done_url
		# 	auth.done   required  the IDp ID
		$this->params["login_done"]  = $HybridAuth_URL_BASE . ( strpos( $HybridAuth_URL_BASE, '?' ) ? '&' : '?' ) . "hauth.done={$this->id}";

		$this->storage->set( "hauth_session.{$this->id}.hauth_return_to"    , $this->params["hauth_return_to"] );
		$this->storage->set( "hauth_session.{$this->id}.hauth_endpoint"     , $this->params["login_done"] ); 
		$this->storage->set( "hauth_session.{$this->id}.id_provider_params" , $this->params );

		// store config to be used by the end point 
		$this->storage->config( "CONFIG", HybridAuth::$config );

		// move on
		Hybridauth_Core_Common_HTTP::redirect( $this->params["login_start"] );
	}

	// --------------------------------------------------------------------

	/**
	* let hybridauth forget all about the user for the current provider
	*/
	function logout()
	{
		$this->adapter->logout();
	}

	// --------------------------------------------------------------------

	/**
	* return true if the user is connected to the current provider
	*/ 
	public function isUserConnected()
	{
		return $this->adapter->isUserConnected();
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
			throw new Hybridauth_Core_Exception( "User not connected to the provider {$this->id}.", 7 );
		} 

		if ( ! method_exists( $this->adapter, $name ) ){
			throw new Hybridauth_Core_Exception( "Call to undefined function Hybrid_Providers_{$this->id}::$name()." );
		}

		if( count( $arguments ) ){
			return $this->adapter->$name( $arguments[0] ); 
		} 
		else{
			return $this->adapter->$name(); 
		}
	}

	// --------------------------------------------------------------------

	/**
	* If the user is connected, then return the access_token and access_token_secret
	* if the provider api use oauth
	*/
	public function getAccessToken()
	{
		if( ! $this->adapter->isUserConnected() ){
			throw new Hybridauth_Core_Exception( "User not connected to the provider.", 7 );
		}

		return
			ARRAY(
				"access_token"        => $this->adapter->token( "access_token" )       , // OAuth access token
				"access_token_secret" => $this->adapter->token( "access_token_secret" ), // OAuth access token secret
				"refresh_token"       => $this->adapter->token( "refresh_token" )      , // OAuth refresh token
				"expires_in"          => $this->adapter->token( "expires_in" )         , // OPTIONAL. The duration in seconds of the access token lifetime
				"expires_at"          => $this->adapter->token( "expires_at" )         , // OPTIONAL. Timestamp when the access_token expire. if not provided by the social api, then it should be calculated: expires_at = now + expires_in
			);
	}

	// --------------------------------------------------------------------

	/**
	* Naive getter of the current connected IDp API client
	*/
	function api()
	{
		if( ! $this->adapter->isUserConnected() ){
			throw new Hybridauth_Core_Exception( "User not connected to the provider.", 7 );
		}

		return $this->adapter->api;
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
		Hybridauth_Core_Common_HTTP::redirect( $callback_url );
	}

	// --------------------------------------------------------------------

	/**
	* return the provider config by id
	*/
	function getConfigById( $id )
	{ 
		if( isset( HybridAuth::$config["providers"][$id] ) ){
			return HybridAuth::$config["providers"][$id];
		}

		return NULL;
	}

	// --------------------------------------------------------------------

	/**
	* return the provider config by id; insensitive
	*/
	function getProviderCiId( $id )
	{
		foreach( HybridAuth::$config["providers"] as $idpid => $params ){
			if( strtolower( $idpid ) == strtolower( $id ) ){
				return $idpid;
			}
		}

		return NULL;
	}
}
