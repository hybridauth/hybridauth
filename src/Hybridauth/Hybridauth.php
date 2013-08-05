<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/*
*****************************************************************************************************
*   THIS SECTION WILL BE KEPT DURING THE ALPHA-BETA STAGE OF HYBRIDAUTH 3.0 AND WILL BE REMOVED.
*****************************************************************************************************
*
* Why working on Hybridauth 3:
*
*	Hybridauth 2 is small piece of software that works and get the job done, however, it's core is
*	hard to understand and to extend. In short, it had a bad design.
*
*	Hybridauth 3 is an attempt to address these issues along other non-functional requirements such
*	as usability, maintainability, compatibility, stability, reliability, etc.
*
*
* Please, don't hesitate to:
*
*	- Report bugs and issues.
*	- Contribute: Code, Reviews, Ideas and Design.
*	- Point out stupidity, smells and inconsistencies in the code.
*	- Criticize.
*
*
* If you want to contribute, please consider these general guide lines:
*
*	- Don't hesitate to delete code that doesn't make sense or looks redundant.
*	- Feel free to create new classes when needed.
*	- Use 'if' and 'foreach' as little as possible.
*	- No 'switch'. No 'for'.
*	- Avoid over-commenting.
*	- Avoid naive getters and setters when possible.
*		why 'naive'? well Venkat Subramaniam sums things up quite well:
*		http://youtu.be/LH75sJAR0hc?t=9m00s
*		.. let us keep PHP concise and to-the-point.
*
*
* Coding Style :
*
*	- Redable code.
*	- Use tabs(8 chars):
*		as devlopers we read and look at code 1/3 of the day and using clear indentations could make
*		life a bit easier.
*	- ..
*
*****************************************************************************************************/

namespace Hybridauth;

use Hybridauth\Exception;
use Hybridauth\Storage\Session;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Adapter\AdapterFactory;

/**
* Hybridauth class provide a simple way to authenticate users via OpenID and OAuth.
*
* Generally, Hybridauth is the only class you should instanciate and use throughout your application.
*/
final class Hybridauth
{
	protected $config  = array();
	protected $storage = null;

	// --------------------------------------------------------------------

	/**
	* Initialize HybridAuth. ...
	*/
	function __construct( $config = null, StorageInterface $storage = null )
	{
		// set config
		$this->config = $config;

		if( $this->config && ! is_array( $this->config ) ){
			$this->config = include $this->config;
		}

		// setup storage manager
		$this->storage = $storage ? $storage : new Session();

		// checks for errors
		if( $this->storage->get( "error.status" ) ){
			$e = $this->storage->get( "error.exception" );
			$m = $this->storage->get( "error.message"   );
			$c = $this->storage->get( "error.code"      );

			$this->storage->deleteMatch( "error." );

			if( $e ){
				throw $e;
			}

			throw new Exception( $m, $c );
		}
	}

	// --------------------------------------------------------------------

	/**
	* Try to authenticate the user with a given provider.
	*
	*
	* If the user is already connected we just return and instance of provider adapter,
	* ELSE, try to authenticate and authorize the user with the provider.
	*
	* $params is generally an array with required info in order for this provider and HybridAuth to work,
	* like :
	*		hauth_return_to: URL to call back after authentication is done
	*		openid_identifier: The OpenID identity provider identifier
	*/
	function authenticate( $providerId, $parameters = array() )
	{
		return $this->getAdapter( $providerId )->authenticate( $parameters );
	}

	// --------------------------------------------------------------------

	/**
	* Return the adapter instance for an authenticated provider
	*/
	function getAdapter( $providerId = null )
	{
		$adapterFactory = new AdapterFactory( $this->config, $this->storage );

		return $adapterFactory->setup( $providerId );
	}

	// --------------------------------------------------------------------

	/**
	* Return true if current user is connected with a given provider
	*/
	function isConnectedWith( $providerId )
	{
		return $this->getAdapter( $providerId )->isAuthorized();
	}

	// --------------------------------------------------------------------

	/**
	* Return a list of authenticated providers
	*/
	function getConnectedProviders()
	{
		$idps = array();

		foreach( $this->config ["providers"] as $idpid => $params ){
			if( $this->isConnectedWith( $idpid ) ){
				$idps[] = $idpid;
			}
		}

		return $idps;
	}

	// --------------------------------------------------------------------

	/**
	* Return a list of enabled providers as well as a flag if you are connected.
	*/
	function getEnabledProviders()
	{
		$idps = array();

		foreach( $this->config ["providers"] as $idpid => $params ){
			if( $params['enabled'] ){
				$idps[$idpid] = array( 'connected' => false );

				if($this->isConnectedWith( $idpid ) ){
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
	function logoutAllProviders()
	{
		$idps = $this->getConnectedProviders();

		foreach( $idps as $idp ){
			$adapter = $this->getAdapter( $idp );

			$adapter->logout();
		}
	}

	function getStorageData() {
		return $this->storage->dump();
	}

	function restoreStorageData($data) {
		return $this->storage->load($data);
	}

	function storage() {
		return $this->storage;
	}

	// --------------------------------------------------------------------

	public static function registerAutoloader()
	{
		spl_autoload_register( __NAMESPACE__ . "\\Hybridauth::autoload" );
	}

	// --------------------------------------------------------------------

	public static function autoload( $className )
	{
		$thisClass = str_replace( __NAMESPACE__ . '\\', '', __CLASS__ );
		$baseDir   = __DIR__;

		if( substr( $baseDir, -strlen( $thisClass ) ) === $thisClass ){
			$baseDir = substr( $baseDir, 0, -strlen( $thisClass ) );
		}

		$className = ltrim( $className, '\\' );
		$fileName  = $baseDir;
		$namespace = '';
		$lastNsPos = strripos( $className, '\\' );

		if( $lastNsPos ){
			$namespace = substr( $className, 0, $lastNsPos );
			$className = substr( $className, $lastNsPos + 1 );
			$fileName .= str_replace( '\\', DIRECTORY_SEPARATOR, $namespace ) . DIRECTORY_SEPARATOR;
		}

		$fileName .= str_replace( '_', DIRECTORY_SEPARATOR, $className ) . '.php';

		if( file_exists( $fileName ) ){
			require $fileName;
		}
	}
}
