<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter;

class AdapterTemplate
{
	/* IDp ID (or unique name) */
	public $providerId            = null;

	public $hybridauthConfig      = null;
	public $config                = null;
	public $parameters            = null;

	protected $hybridauthEndpoint = null;
	protected $api                = null;
	protected $storage            = null;

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

   	/**
	* generic logout, just erase current provider adapter stored data to let HybridAuth all forget about it
	*/
	function logout()
	{
		$this->storage->delete( "hauth_session.{$this->providerId}.tokens" );

		$this->setUserUnconnected();

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

	public function getStoredTokens()
	{
		return $this->storage->get( "hauth_session.{$this->providerId}.tokens" );
	}

	// --------------------------------------------------------------------

	public function storeTokens( \Hybridauth\Adapter\Api\TokensInterface $tokens )
	{
		$this->storage->set( "hauth_session.{$this->providerId}.tokens", $tokens );
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
