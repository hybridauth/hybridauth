<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
 * Hybridauth_Core_Endpoint class
 * 
 * Hybridauth_Core_Endpoint class provides a simple way to handle the OpenID and OAuth endpoint.
 */
class Hybridauth_Core_Endpoint
{ 
	protected $request    = null;

	protected $initDone   = false;

	protected $storage    = null; 
	
	protected $logger     = null; 

	// --------------------------------------------------------------------

	public function __construct( Hybridauth_Core_Storage_Interface $storage = null, Hybridauth_Core_Common_LoggerInterface $logger = null )
	{
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
			$this->logger = new Hybridauth_Core_Common_Logger();
		}
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
			if ( strrpos( $_SERVER["QUERY_STRING"], '?' ) ) {
				$_SERVER["QUERY_STRING"] = str_replace( "?", "&", $_SERVER["QUERY_STRING"] );

				parse_str( $_SERVER["QUERY_STRING"], $_REQUEST );
			}

			$this->request = $_REQUEST;
		}

		// If openid_policy requested, we return our policy document
		if ( isset( $this->request["get"] ) && $this->request["get"] == "openid_policy" ) {
			$this->processOpenidPolicy();
		}

		// If openid_xrds requested, we return our XRDS document
		if ( isset( $this->request["get"] ) && $this->request["get"] == "openid_xrds" ) {
			$this->processOpenidXRDS();
		}

		// If we get a hauth.start
		if ( isset( $this->request["hauth_start"] ) && $this->request["hauth_start"] ) {
			$this->processAuthStart();
		}
		// Else if hauth.done
		elseif ( isset( $this->request["hauth_done"] ) && $this->request["hauth_done"] ) {
			$this->processAuthDone();
		}
		// Else we advertise our XRDS document, something supposed to be done from the Realm URL page
		else {
			$this->processOpenidRealm();
		}
	}

	// --------------------------------------------------------------------

	/**
	* Process OpenID policy request
	*/
	public function processOpenidPolicy()
	{
		$output = file_get_contents( dirname(__FILE__) . "/Resources/openid_policy.html" ); 
		print $output;
		die();
	}

	// --------------------------------------------------------------------

	/**
	* Process OpenID XRDS request
	*/
	public function processOpenidXRDS()
	{
		header("Content-Type: application/xrds+xml");

		$output = str_replace
		(
			"{RETURN_TO_URL}",
			str_replace(
				array("<", ">", "\"", "'", "&"), array("&lt;", "&gt;", "&quot;", "&apos;", "&amp;"), 
				Hybridauth::getCurrentUrl( false )
			),
			file_get_contents( dirname(__FILE__) . "/Resources/openid_xrds.xml" )
		);
		print $output;
		die();
	}

	// --------------------------------------------------------------------

	/**
	* Process OpenID realm request
	*/
	public function processOpenidRealm()
	{
		$output = str_replace
		(
			"{X_XRDS_LOCATION}",
			htmlentities( Hybridauth::getCurrentUrl( false ), ENT_QUOTES, 'UTF-8' ) . "?get=openid_xrds&v=" . Hybridauth::VERSION,
			file_get_contents( dirname(__FILE__) . "/Resources/openid_realm.html" )
		); 
		print $output;
		die();
	}

	// --------------------------------------------------------------------

	/**
	* define:endpoint step 3.
	*/
	public function processAuthStart()
	{ 
		$adapter = new Hybridauth_Core_Provider_Adapter( $this->storage, $this->logger ); 

		$this->authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_start"] ) );

		# check if page accessed directly
		if( ! $this->storage->get( "hauth_session.$provider_id.hauth_endpoint" ) ) { 
			header( "HTTP/1.0 404 Not Found" );
			die( "You cannot access this page directly." );
		}

		# define:hybrid.endpoint.php step 2.
		$provider = $adapter->setup( $provider_id );

		# if REQUESTed hauth_idprovider is wrong, session not created, etc. 
		if( ! $provider ) { 
			header( "HTTP/1.0 404 Not Found" );
			die( "Invalid parameter! Please return to the login page and try again." );
		}

		try { 
			$provider->adapter->loginBegin();
		}
		catch ( Hybridauth_Core_Exception $e ) {  
			$this->storage->set( "hauth_session.error.status"  , 1                      );
			$this->storage->set( "hauth_session.error.message" , $e->getMessage()       );
			$this->storage->set( "hauth_session.error.code"    , $e->getCode()          );
			$this->storage->set( "hauth_session.error.trace"   , $e->getTraceAsString() );
			$this->storage->set( "hauth_session.error.previous", $e                     );

			$provider->returnToCallbackUrl();
		}

		die();
	}

	// --------------------------------------------------------------------

	/**
	* define:endpoint step 3.1 and 3.2
	*/
	public function processAuthDone()
	{ 
		$adapter = new Hybridauth_Core_Provider_Adapter( $this->storage, $this->logger ); 

		$this->authInit();

		$provider_id = trim( strip_tags( $this->request["hauth_done"] ) );

		$provider = $adapter->setup( $provider_id );

		if( ! $provider ) { 
			$provider->adapter->setUserUnconnected();

			header("HTTP/1.0 404 Not Found"); 
			die( "Invalid parameter! Please return to the login page and try again." );
		}

		try { 
			$provider->adapter->loginFinish(); 
		}
		catch( Hybridauth_Core_Exception $e ){ 
			$this->storage->set( "hauth_session.error.status"  , 1                      );
			$this->storage->set( "hauth_session.error.message" , $e->getMessage()       );
			$this->storage->set( "hauth_session.error.code"    , $e->getCode()          );
			$this->storage->set( "hauth_session.error.trace"   , $e->getTraceAsString() );
			$this->storage->set( "hauth_session.error.previous", $e                     );

			$provider->adapter->setUserUnconnected(); 
		}

		$provider->returnToCallbackUrl();
		die();
	}

	// --------------------------------------------------------------------

	public function authInit()
	{
		if ( ! $this->initDone) {
			$this->initDone = TRUE;

			# Init Hybrid_Auth
			try {
				// Check if Hybrid_Auth session already exist
				if ( ! $this->storage->config( "CONFIG" ) ) { 
					header( "HTTP/1.0 404 Not Found" );
					die( "You cannot access this page directly." );
				}

				new Hybridauth( $this->storage->config( "CONFIG" ), $this->storage, $this->logger ); 
			}
			catch ( Hybridauth_Core_Exception $e ){ 
				header( "HTTP/1.0 404 Not Found" );
				die( "Oophs. Error!" );
			}
		}
	}
}
