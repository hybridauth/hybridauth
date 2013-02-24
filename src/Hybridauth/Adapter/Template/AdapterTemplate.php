<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Template;

/**
 * Hybrid_Provider_Model provide a common interface for supported IDps on HybridAuth.
 *
 * Basically, each provider adapter has to define at least 4 methods:
 *   Hybrid_Providers_{provider_name}::initialize()
 *   Hybrid_Providers_{provider_name}::loginBegin()
 *   Hybrid_Providers_{provider_name}::loginFinish()
 *   Hybrid_Providers_{provider_name}::getUserProfile()
 *
 * HybridAuth also come with three others models
 *   Class Hybrid_Provider_Model_OpenID for providers that uses the OpenID 1 and 2 protocol.
 *   Class Hybrid_Provider_Model_OAuth1 for providers that uses the OAuth 1 protocol.
 *   Class Hybrid_Provider_Model_OAuth2 for providers that uses the OAuth 2 protocol.
 */
class AdapterTemplate
{
	/* IDp ID (or unique name) */
	public $providerId = null;

	public $hybridauthConfig   = null;
	public $config             = null;
	public $parameters         = null;

	public $endpoint      = null;

	protected $api        = null;

	protected $storage    = null;
	protected $logger     = null; 

	/**
	* common providers adapter constructor
	*/
	function __construct(
		$providerId,
		$hybridauthConfig,
		$config,
		$parameters = null,
		\Hybridauth\Storage\StorageInterface $storage = null,
		\Hybridauth\Logger\LoggerInterface $logger = null
	)
	{
        $this->storage = $storage;
		$this->logger  = $logger; 

		# init the IDp adapter parameters, get them from the cache if possible
		if( ! $parameters ){
			$this->parameters = $this->storage->get( "hauth_session.$providerId.id_provider_params" );
		}
		else{
			$this->parameters = $parameters;
		}

		$this->providerId = $providerId;

		$this->hybridauthConfig = $hybridauthConfig;

		$this->config = $config;

		// set HybridAuth endpoint for this provider
		$this->endpoint = $this->storage->get( "hauth_session.$providerId.hauth_endpoint" );

		// initialize the current provider adapter
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
	public function initialize()
	{}

	// --------------------------------------------------------------------

	/**
	* begin login
	*/
	public function loginBegin()
	{}

	// --------------------------------------------------------------------

	/**
	* finish login
	*/
	public function loginFinish()
	{}

	// --------------------------------------------------------------------

   	/**
	* generic logout, just erase current provider adapter stored data to let HybridAuth all forget about it
	*/
	function logout()
	{
		$this->clearTokens();

		return TRUE;
	}

	// --------------------------------------------------------------------

	/**
	* grab the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		throw new
			\Hybridauth\Exception( "Provider does not support this feature.", \Hybridauth\Exception::UNSUPPORTED_FEATURE ); 
	}

	// --------------------------------------------------------------------

	/**
	* load the current logged in user contacts list from the IDp api client  
	*/
	function getUserContacts()
	{
		throw new
			\Hybridauth\Exception( "Provider does not support this feature.", \Hybridauth\Exception::UNSUPPORTED_FEATURE ); 
	}

	// --------------------------------------------------------------------

	/**
	* return the user activity stream
	*/
	function getUserActivity( $stream ) 
	{
		throw new
			\Hybridauth\Exception( "Provider does not support this feature.", \Hybridauth\Exception::UNSUPPORTED_FEATURE ); 
	}

	// --------------------------------------------------------------------

	/**
	* return the user activity stream
	*/ 
	function setUserStatus( $status )
	{
		throw new
			\Hybridauth\Exception( "Provider does not support this feature.", \Hybridauth\Exception::UNSUPPORTED_FEATURE ); 
	}

	// --------------------------------------------------------------------

	/**
	* return true if the user is connected to the current provider
	*/ 
	public function isUserConnected()
	{
		return (bool) $this->storage->get( "hauth_session.{$this->providerId}.is_logged_in" );
	}

	// --------------------------------------------------------------------

	/**
	* set user to connected 
	*/ 
	public function setUserConnected()
	{
		$this->storage->set( "hauth_session.{$this->providerId}.is_logged_in", 1 );
	}

	// --------------------------------------------------------------------

	/**
	* set user to unconnected 
	*/ 
	public function setUserUnconnected()
	{
		$this->storage->set( "hauth_session.{$this->providerId}.is_logged_in", 0 ); 
	}

	// --------------------------------------------------------------------

	/**
	* get or set a token 
	*/ 
	public function token( $token, $value = null )
	{
		if( $value === null ){
			return $this->storage->get( "hauth_session.{$this->providerId}.token.$token" );
		}
		else{
			$this->storage->set( "hauth_session.{$this->providerId}.token.$token", $value );
		}
	}

	// --------------------------------------------------------------------

	/**
	* delete a stored token 
	*/ 
	public function deleteToken( $token )
	{
		$this->storage->delete( "hauth_session.{$this->providerId}.token.$token" );
	}

	// --------------------------------------------------------------------

	/**
	* clear all existen tokens for this provider
	*/ 
	public function clearTokens()
	{
		$this->storage->deleteMatch( "hauth_session.{$this->providerId}." );
	}

	// --------------------------------------------------------------------

	// Shamelessly Borrowered from Slimframework, but to be removed/moved
	public function debug()
	{
		$title   = 'Hybridauth Adapter Debug';

		$html = sprintf('<h1>%s</h1>', $title);
		$html .= sprintf('<pre>%s</pre>', print_r( $this, 1 ) );
		$html .= '<h2>Backtrace</h2>';
		$html .= sprintf('<pre>%s</pre>', print_r( debug_backtrace(), 1 ) );

		return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:38px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html);
	}
}
