<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth;

/**
 * Hybrid_Auth class
 * 
 * Hybrid_Auth class provide a simple way to authenticate users via OpenID and OAuth.
 * 
 * Generally, Hybrid_Auth is the only class you should instanciate and use throughout your application.
 */
class Hybridauth
{
	protected $config  = array();

	protected $storage = null;

	// --------------------------------------------------------------------

	/**
	* Initialize HybridAuth
	*
	* http://hybridauth.sourceforge.net/userguide/Configuration.html
	*/
	function __construct( $config, \Hybridauth\Storage\StorageInterface $storage = null )
	{
		// sotre given config
		$this->config = $config;

		if( ! is_array( $this->config ) ){
			$this->config = include $this->config;
		}

		// Storage 
		$this->storage = $storage ? $storage : new \Hybridauth\Storage\Session();

		// if an error was stored on endpoint
		if( $this->storage->get( "hauth_session.error.status" ) ){
			$m = $this->storage->get( "hauth_session.error.message" );
			$c = $this->storage->get( "hauth_session.error.code"    );

			// clear errors
			$this->storage->deleteMatch( "hauth_session.error." );

			throw new \Hybridauth\Exception( $m, $c );
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
	*/
	public function authenticate( $providerId, $providerParameters = array() )
	{
		$adapter = $this->getAdapter( $providerId );

		// if user not connected to $providerId then try setup a new adapter and start the login process for this provider
		if( ! $this->storage->get( "hauth_session.$providerId.is_logged_in" ) ){

			// clear all unneeded params
			$this->storage->delete( "hauth_session.{$providerId}.hauth_return_to"    );
			$this->storage->delete( "hauth_session.{$providerId}.hauth_endpoint"     );
			$this->storage->delete( "hauth_session.{$providerId}.id_provider_params" );

			// make a fresh start
			$adapter->logout();

			# hybridauth base url
			$base_url = $this->config["base_url"];

			# request timestamp
			$providerParameters["hauth_time"]  = time();

			# hauth.start
			$providerParameters["login_start"] = $base_url . ( strpos( $base_url, '?' ) ? '&' : '?' ) . "hauth.start={$providerId}&hauth.time={$providerParameters["hauth_time"]}";

			# hauth.done
			$providerParameters["login_done"]  = $base_url . ( strpos( $base_url, '?' ) ? '&' : '?' ) . "hauth.done={$providerId}";

			if( ! isset( $providerParameters["hauth_return_to"] ) ){
				$providerParameters["hauth_return_to"] = \Hybridauth\Http\Util::getCurrentUrl(); 
			}

			$this->storage->set( "hauth_session.{$providerId}.hauth_return_to"    , $providerParameters["hauth_return_to"] );
			$this->storage->set( "hauth_session.{$providerId}.hauth_endpoint"     , $providerParameters["login_done"] ); 
			$this->storage->set( "hauth_session.{$providerId}.id_provider_params" , $providerParameters );

			// store config to be used by the end point.
			$this->storage->config( "CONFIG", $this->config );

			// move on
			\Hybridauth\Http\Util::redirect( $providerParameters["login_start"] );
		}

		// else, then return the adapter instance for the given provider
		else{
			return $adapter;
		}
	}

	// --------------------------------------------------------------------

	/**
	* Return the adapter instance for an authenticated provider
	*/ 
	public function getAdapter( $providerId = NULL )
	{
		$adapterFactory = new \Hybridauth\Adapter\AdapterFactory( $this->config, $this->storage );

		return $adapterFactory->setup( $providerId );
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
	public function getEnabledProviders()
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

	// --------------------------------------------------------------------

	public static function registerAutoloader()
	{
		spl_autoload_register(__NAMESPACE__ . "\\Hybridauth::autoload");
	}

	// --------------------------------------------------------------------

	public static function autoload($className)
	{
		$thisClass = str_replace(__NAMESPACE__.'\\', '', __CLASS__);

		$baseDir = __DIR__;

		if (substr($baseDir, -strlen($thisClass)) === $thisClass) {
			$baseDir = substr($baseDir, 0, -strlen($thisClass));
		}

		$className = ltrim($className, '\\');
		$fileName  = $baseDir;
		$namespace = '';

		if ($lastNsPos = strripos($className, '\\')) {
			$namespace = substr($className, 0, $lastNsPos);
			$className = substr($className, $lastNsPos + 1);
			$fileName .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}

		$fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

		if (file_exists($fileName)) {
			require $fileName;
		}
	}
}
