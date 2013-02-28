<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter;

abstract class AbstractAdapter
{
	/* IDp ID (or unique name) */
	protected $providerId         = null;

	protected $hybridauthConfig   = null;
	protected $config             = null;
	protected $parameters         = null;

	protected $hybridauthEndpoint = null;
	protected $storage            = null;

	private $_authService         = null;
	private $_apiBinding          = null;

	// --------------------------------------------------------------------

	/**
	* common providers adapter constructor
	*/
	function __construct(
		$providerId,
		$hybridauthConfig,
		$config,
		$parameters = null,
		\Hybridauth\Storage\StorageInterface $storage = null 
	)
	{
        $this->storage = $storage;

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
		$this->hybridauthEndpoint = $this->storage->get( "hauth_session.$providerId.hauth_endpoint" );

		// initialize the current provider adapter
		$this->initialize();
	}

	// --------------------------------------------------------------------

	final public function registerAuthenticationService( $service, $options = array() )
	{
		$this->_authService = new $service;

		$this->_authService->hybridauthEndpoint = $this->hybridauthEndpoint;
		$this->_authService->hybridauthConfig   = $this->hybridauthConfig;
		$this->_authService->providerId         = $this->providerId;
		$this->_authService->config             = $this->config;
		$this->_authService->parameters         = $this->parameters;
		$this->_authService->storage            = $this->storage;

		$this->_authService->initialize( $options );
	}

	// --------------------------------------------------------------------

	final public function registerApiBinding( $bind, $class )
	{
		if( ! $this->_apiBinding ){
			$this->_apiBinding  = new \Hybridauth\Adapter\ApiBinding();
		}

		$this->_apiBinding->bind( $bind, $class );

		// $this->_apiBinding[ $bind ] = $class;
	}

	// --------------------------------------------------------------------

	final public function getAuthenticationService()
	{
		return $this->_authService;
	}

	// --------------------------------------------------------------------

	final public function getApi( $tokensOrAccessToken = null, $accessSecretToken = null )
	{
		if( is_object( $tokensOrAccessToken ) ){
			$this->_authService->tokens = $tokensOrAccessToken;

			$this->_authService->storeTokens( $tokensOrAccessToken );
		}
		elseif( $tokensOrAccessToken !== null ){
			$this->_authService->tokens->accessToken       = $tokensOrAccessToken;
			$this->_authService->tokens->accessSecretToken = $accessSecretToken;

			$this->_authService->storeTokens( $this->_authService->tokens );
		}

		$this->_apiBinding->api = $this->_authService;

		return $this->_apiBinding;
	}

	// --------------------------------------------------------------------

	public function isAuthorized()
	{
		return $this->_authService->isAuthorized();
	}

	// --------------------------------------------------------------------

   	/**
	* generic logout, just erase current provider adapter stored data to let HybridAuth all forget about it
	*/
	function disconnect()
	{
		$this->storage->deleteMatch( "hauth_session.{$this->providerId}." );

		return TRUE;
	}

	// --------------------------------------------------------------------

	// Shamelessly Borrowered from Slimframework, but to be removed/moved
	public function debug()
	{
		$title   = 'Hybridauth Adapter Debug';

		$html = sprintf('<h1>%s</h1>', $title);
		$html .= sprintf('<pre>%s</pre>', print_r( $this, 1 ) );
		$html .= '<h2>Session</h2>';
		$html .= sprintf('<pre>%s</pre>', print_r( $_SESSION, 1 ) );
		$html .= '<h2>Backtrace</h2>';
		$html .= sprintf('<pre>%s</pre>', print_r( debug_backtrace(), 1 ) );

		return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:38px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html);
	}
}
