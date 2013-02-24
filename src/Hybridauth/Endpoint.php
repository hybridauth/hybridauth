<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth;

/**
 * Hybridauth_Core_Endpoint class
 * 
 * Hybridauth_Core_Endpoint class provides a simple way to handle the OpenID and OAuth endpoint.
 */
class Endpoint
{
	protected $request  = null;
	protected $initDone = false;
	protected $storage  = null;
	protected $logger   = null;

	// --------------------------------------------------------------------

	public function __construct( \Hybridauth\Storage\StorageInterface $storage = null, \Hybridauth\Logger\LoggerInterface $logger = null )
	{
		// Storage
		$this->storage = $storage ? $storage : new \Hybridauth\Storage\Session();

		// LogWriter
		$this->logger = $logger ? $logger: new \Hybridauth\Logger\LogWriter(); // fixme!
	}

	// --------------------------------------------------------------------

	/**
	* Process the current request
	*
	* $request - The current request parameters. Leave as NULL to default to use $_REQUEST.
	*/
	public function process( $request = NULL )
	{
		// Setup request variable
		$this->request = $request;

		if ( is_null($this->request) ){
			// Fix a strange behavior when some provider call back ha endpoint
			// with /index.php?hauth.done={provider}?{args}... 
			// >here we need to recreate the $_REQUEST
			if ( strrpos( $_SERVER["QUERY_STRING"], '?' ) ){
				$_SERVER["QUERY_STRING"] = str_replace( "?", "&", $_SERVER["QUERY_STRING"] );

				parse_str( $_SERVER["QUERY_STRING"], $_REQUEST );
			}

			$this->request = $_REQUEST;
		}

		// If we get a hauth.start
		if ( isset( $this->request["hauth_start"] ) && $this->request["hauth_start"] ){
			$this->_processAuthStart();
		}
		// Else if hauth.done
		elseif ( isset( $this->request["hauth_done"] ) && $this->request["hauth_done"] ){
			$this->_processAuthDone();
		}
	}

	// --------------------------------------------------------------------

	private function _authInit()
	{
		if ( ! $this->initDone){
			$this->initDone = TRUE;

			# Init Hybrid_Auth
			try{
				// Check if Hybrid_Auth session already exist
				if ( ! $this->storage->config( "CONFIG" ) ){ 
					header( "HTTP/1.0 404 Not Found" );

					die( "You cannot access this page directly." );
				}

				new Hybridauth( $this->storage->config( "CONFIG" ), $this->storage, $this->logger ); 
			}
			catch( \Hybridauth\Exception $e ){
				header( "HTTP/1.0 404 Not Found" );

				die( "Oophs. Error!" );
			}
		}
	}

	// --------------------------------------------------------------------

	private function _processAuthStart()
	{
		$adapterFactory = new \Hybridauth\Adapter\AdapterFactory( $this->storage->config( "CONFIG" ), $this->storage, $this->logger );

		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_start"] ) );

		# check if page accessed directly
		if( ! $this->storage->get( "hauth_session.$provider_id.hauth_endpoint" ) ){
			header( "HTTP/1.0 404 Not Found" );

			die( "You cannot access this page directly." );
		}

		# define:hybrid.endpoint.php step 2.
		$adapter = $adapterFactory->setup( $provider_id );

		# if REQUESTed hauth_idprovider is wrong, session not created, etc.
		if( ! $adapter ){
			header( "HTTP/1.0 404 Not Found" );

			die( "Invalid parameter! Please return to the login page and try again." );
		}

		try { 
			$adapter->providerInstance->loginBegin();
		}
		catch ( \Hybridauth\Exception $e ){
			$this->storage->set( "hauth_session.error.status"  , 1 );
			$this->storage->set( "hauth_session.error.message" , $e->getMessage() );
			$this->storage->set( "hauth_session.error.code"    , $e->getCode() );

			$adapter->returnToCallbackUrl();
		}
	}

	// --------------------------------------------------------------------

	private function _processAuthDone()
	{
		$adapterFactory = new \Hybridauth\Adapter\AdapterFactory( $this->storage->config( "CONFIG" ), $this->storage, $this->logger ); 

		$this->_authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_done"] ) );

		$adapter = $adapterFactory->setup( $provider_id );

		if( ! $adapter ) { 
			$adapter->providerInstance->setUserUnconnected();

			header("HTTP/1.0 404 Not Found"); 
			die( "Invalid parameter! Please return to the login page and try again." );
		}

		try {
			$adapter->providerInstance->loginFinish();
		}
		catch( \Hybridauth\Exception $e ){
			$this->storage->set( "hauth_session.error.status"  , 1 );
			$this->storage->set( "hauth_session.error.message" , $e->getMessage() );
			$this->storage->set( "hauth_session.error.code"    , $e->getCode() );

			$adapter->providerInstance->setUserUnconnected();
		}

		$adapter->returnToCallbackUrl();
	}
}
