<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth;

use Hybridauth\Exception;
use Hybridauth\Http\Util;
use Hybridauth\Storage\Session;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Adapter\AdapterFactory;

/**
* HybridAuth EndPoint
*
* http://hybridauth.sourceforge.net/userguide/HybridAuth_endpoint_URL.html
* http://hybridauth.sourceforge.net/userguide/tuts/change-hybridauth-endpoint-url.html
*
* Examples
*
*	1. Basic
*
* 		require_once( 'Hybridauth.php' );
*
*		( new \Hybridauth\Endpoint() )->process();
*
*
*	2. Using a custom sotrage
*
* 		require_once( 'Hybridauth.php' );
*
*		( new \Hybridauth\Endpoint( $myCustomeStorageClassInstanceImplementingStorageInterface ) )
*			->process();
*
*
*	3. Overwrite \Hybridauth\Endpoint::process()
*
* 		require_once( 'Hybridauth.php' );
*
*		$ep = new \Hybridauth\Endpoint()
*
*		if( isset( $_GET["hauth_start"] ) ){
*			$ep->processAdapterLoginBegin();
*		}
*
*		elseif( isset( $_GET["hauth_done"] ) ){
*			$ep->processAdapterLoginFinish();
*		}
*/
final class Endpoint
{
	protected $request = null;
	protected $storage = null;

	// --------------------------------------------------------------------

	function __construct( StorageInterface $storage = null )
	{
		$this->storage = $storage ? $storage : new Session();
	}

	// --------------------------------------------------------------------

	/**
	* Process the current request
	*
	* $request - The current request parameters. Leave as NULL to default to use $_REQUEST.
	*/
	function process( $request = null )
	{
		$this->request = $request;

		if( is_null( $this->request ) ){
			if ( strrpos( $_SERVER["QUERY_STRING"], '?' ) ){
				$_SERVER["QUERY_STRING"] = str_replace( "?", "&", $_SERVER["QUERY_STRING"] );

				parse_str( $_SERVER["QUERY_STRING"], $_REQUEST );
			}

			$this->request = $_REQUEST;
		}

		if( isset( $this->request["hauth_start"] ) ){
			$this->processAdapterLoginBegin();
		}

		elseif( isset( $this->request["hauth_done"] ) ){
			$this->processAdapterLoginFinish();
		}
	}

	// --------------------------------------------------------------------

	function processAdapterLoginBegin()
	{
		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_start"] ) );

		$adapterFactory = new AdapterFactory( $this->storage->config( "CONFIG" ), $this->storage );

		$adapter = $adapterFactory->setup( $provider_id );

		if( ! $adapter ){
			header( "HTTP/1.0 404 Not Found" );

			die( "Invalid parameter! Please return to the login page and try again." );
		}

		try{ 
			$adapter->loginBegin();
		}
		catch( Exception $e ){
			$this->storage->set( "error.status"   , 1 );
			$this->storage->set( "error.message"  , $e->getMessage() );
			$this->storage->set( "error.code"     , $e->getCode() );
			$this->storage->set( "error.exception", $e );

			$this->_returnToCallbackUrl( $provider_id );
		}
	}

	// --------------------------------------------------------------------

	function processAdapterLoginFinish()
	{
		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_done"] ) );

		$adapterFactory = new AdapterFactory( $this->storage->config( "CONFIG" ), $this->storage );

		$adapter = $adapterFactory->setup( $provider_id );

		if( ! $adapter ){
			header("HTTP/1.0 404 Not Found");

			die( "Invalid parameter! Please return to the login page and try again." );
		}

		try{
			$adapter->loginFinish();
		}
		catch( Exception $e ){
			$this->storage->set( "error.status"   , 1 );
			$this->storage->set( "error.message"  , $e->getMessage() );
			$this->storage->set( "error.code"     , $e->getCode() );
			$this->storage->set( "error.exception", $e );
		}

		$this->_returnToCallbackUrl( $provider_id );
	}

	// --------------------------------------------------------------------

	/**
	* Checks if enpoint accessed directly?
	*/
	private function _authInit()
	{
		if( ! $this->storage->config( "CONFIG" ) ){ 
			header( "HTTP/1.0 404 Not Found" );

			die( "You cannot access this page directly." );
		}
	}

	// --------------------------------------------------------------------

	/**
	* redirect the user to hauth_return_to (the callback url)
	*/
	private function _returnToCallbackUrl( $providerId )
	{
		$callback_url = $this->storage->get( "{$providerId}.hauth_return_to" );

		$this->storage->delete( "{$providerId}.hauth_return_to"    );
		$this->storage->delete( "{$providerId}.hauth_endpoint"     );
		$this->storage->delete( "{$providerId}.id_provider_params" );

		Util::redirect( $callback_url );
	}
}
