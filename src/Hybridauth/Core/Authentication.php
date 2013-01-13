<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
 * Hybrid_Auth class
 * 
 * Hybrid_Auth class provide a simple way to authenticate users via OpenID and OAuth.
 * 
 * Generally, Hybrid_Auth is the only class you should instanciate and use throughout your application.
 */
class Hybridauth_Core_Authentication
{
	const VERSION         = "3.0.0-dev";

	public static $config = array();

	protected $storage    = null; 

	protected $logger     = null; 

	// --------------------------------------------------------------------

	/**
	* Try to start a new session of none then initialize Hybrid_Auth
	* 
	* Hybrid_Auth constructor will require either a valid config array or
	* a path for a configuration file as parameter. To know more please 
	* refer to the Configuration section:
	* http://hybridauth.sourceforge.net/userguide/Configuration.html
	*/
	public function __construct( $config, Hybridauth_Core_Storage_Interface $storage = null, Hybridauth_Core_Common_LoggerInterface $logger = null )
	{ 
		if( ! is_array( $config ) && ! file_exists( $config ) ){
			throw new Hybridauth_Core_Exception( "Hybriauth config does not exist on the given path.", 1 );
		}

		if( ! is_array( $config ) ){
			$config = include $config;
		}

		// build some need'd paths 
		if( ! isset( $config["base_path"] ) ){
			$config["base_path"]    = realpath( dirname( __FILE__ ) )  . "/"; 
		}

		$config["path_libraries"]   = $config["base_path"] . "thirdparty/";
		$config["path_resources"]   = $config["base_path"] . "resources/";
		$config["path_providers"]   = $config["base_path"] . "Providers/";

		// reset debug mode
		if( ! isset( $config["debug_mode"] ) ){
			$config["debug_mode"] = false;
			$config["debug_file"] = null;
		}

		// start session storage mng 
        if ( $storage !== null ) {
            $this->storage = $storage ;
        }
		else{
			$this->storage = new Hybridauth_Core_Storage_Session();
		}

		// start log mng 
        if ( $logger !== null ) {
            $this->logger = $logger ;
        }
		else{
			$this->logger = new Hybridauth_Core_Common_Logger( $config["debug_mode"], $config["debug_file"] );
		}

		// sotre given config
		HybridAuth::$config = $config;

		// PHP Curl extension [http://www.php.net/manual/en/intro.curl.php]
		if ( ! function_exists('curl_init') ) { 
			throw new Hybridauth_Core_Exception('Hybridauth Library needs the CURL PHP extension.');
		}

		// PHP JSON extension [http://php.net/manual/en/book.json.php]
		if ( ! function_exists('json_decode') ) { 
			throw new Hybridauth_Core_Exception('Hybridauth Library needs the JSON PHP extension.');
		}

		// if an error was stored on endpoint
		if( $this->storage->get( "hauth_session.error.status" ) ){ 
			$m = $this->storage->get( "hauth_session.error.message" );
			$c = $this->storage->get( "hauth_session.error.code" );
			$t = $this->storage->get( "hauth_session.error.trace" );
			$p = $this->storage->get( "hauth_session.error.previous" ); 

			// clear errors
			$this->storage->deleteMatch( "hauth_session.error." ); 

			// try to provide the previous exception if possible PHP >= 5.3.0) 
			if ( version_compare( PHP_VERSION, '5.3.0', '>=' ) && ($p instanceof Exception || $p instanceof Hybridauth_Core_Exception) ){
				throw new Hybridauth_Core_Exception( $m, $c, $p );
			}
			else{
				throw new Hybridauth_Core_Exception( $m, $c );
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	* Get hybridauth session data. 
	*/
	public function getSessionData()
	{ 
		return $this->storage->getSessionData();
	}

	// --------------------------------------------------------------------

	/**
	* restore hybridauth session data. 
	*/
	public function restoreSessionData( $sessiondata = NULL )
	{ 
		$this->storage->restoreSessionData( $sessiondata );
	}

	// --------------------------------------------------------------------

	/**
	* Try to authenticate the user with a given provider. 
	*
	* If the user is already connected we just return and instance of provider adapter,
	* ELSE, try to authenticate and authorize the user with the provider. 
	*
	* $params is generally an array with required info in order for this provider and HybridAuth to work,
	*  like :
	*          hauth_return_to: URL to call back after authentication is done
	*        openid_identifier: The OpenID identity provider identifier
	*           google_service: can be "Users" for Google user accounts service or "Apps" for Google hosted Apps
	*/
	public function authenticate( $providerId, $params = NULL )
	{ 
		$adapter = new Hybridauth_Core_Provider_Adapter( $this->storage, $this->logger );

		$provider = $adapter->setup( $providerId, $params );

		// if user not connected to $providerId then try setup a new adapter and start the login process for this provider
		if( ! $this->storage->get( "hauth_session.$providerId.is_logged_in" ) ){   
			$provider->authenticate();
		}

		// else, then return the adapter instance for the given provider
		else{
			return $this->getAdapter( $providerId );
		}
	}

	// --------------------------------------------------------------------

	/**
	* Return the adapter instance for an authenticated provider
	*/ 
	public function getAdapter( $providerId = NULL )
	{
		$adapter = new Hybridauth_Core_Provider_Adapter( $this->storage, $this->logger );
		
		return $adapter->setup( $providerId );
	}

	// --------------------------------------------------------------------

	/**
	* Check if the current user is connected to a given provider
	*/
	public function isConnectedWith( $providerId )
	{
		return (bool) $this->storage->get( "hauth_session.{$providerId}.is_logged_in" );
	}

	// --------------------------------------------------------------------

	/**
	* Return array listing all authenticated providers
	*/ 
	public function getConnectedProviders()
	{
		$idps = array();

		foreach( $this->config["providers"] as $idpid => $params ){
			if( $this->isConnectedWith( $idpid ) ){
				$idps[] = $idpid;
			}
		}

		return $idps;
	}

	// --------------------------------------------------------------------

	/**
	* Return array listing all enabled providers as well as a flag if you are connected.
	*/ 
	public function getProviders()
	{
		$idps = array();

		foreach( $this->config["providers"] as $idpid => $params ){
			if($params['enabled']) {
				$idps[$idpid] = array( 'connected' => false );

				if( $this->isConnectedWith( $idpid ) ){
					$idps[$idpid]['connected'] = true;
				}
			}
		}

		return $idps;
	}

	// --------------------------------------------------------------------

	/**
	* A generic function to logout all connected provider at once 
	*/ 
	public function logoutAllProviders()
	{
		$idps = $this->getConnectedProviders();

		foreach( $idps as $idp ){
			$adapter = $this->getAdapter( $idp );

			$adapter->logout();
		}
	}
}
