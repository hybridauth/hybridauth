<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter;

use Hybridauth\Http\Util;
use Hybridauth\Storage\StorageInterface;

abstract class AbstractAdapter
{
	protected $providerId         = null;

	protected $hybridauthConfig   = null;
	protected $config             = null;
	protected $parameters         = null;

	protected $hybridauthEndpoint = null;
	protected $storage            = null;

	// --------------------------------------------------------------------

	/**
	* AbstractAdapter constructor. ...
	*/
	function __construct(
		$providerId,
		$hybridauthConfig,
		$config,
		$parameters = null,
		StorageInterface $storage = null 
	)
	{
		$this->storage = $storage;

		$this->providerId = $providerId;

		# init the IDp adapter parameters, get them from the cache if possible
		if( ! $parameters ){
			$parameters = $this->storage->get( $providerId . '.id_provider_params' );
		}

		$this->setHybridauthConfig( $hybridauthConfig );

		$this->setAdapterParameters( $parameters ); 

		$this->setAdapterConfig( $config );

		$this->setHybridauthEndpointUri( $this->storage->get( $providerId . '.hauth_endpoint' ) );

		$this->initialize();
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function authenticate( $parameters = array() )
	{
		if( $this->isAuthorized() ){
			return $this;
		}

		foreach( $this->getHybridauthConfig( 'providers' ) as $idpid => $params ){
			$this->storage->delete( "{$idpid}.hauth_return_to"    );
			$this->storage->delete( "{$idpid}.hauth_endpoint"     );
			$this->storage->delete( "{$idpid}.id_provider_params" );
		}

		$this->storage->deleteMatch( "{$this->providerId}." );

		$base_url = $this->getHybridauthConfig( 'base_url' );

		$defaults = array(
			'hauth_return_to' => Util::getCurrentUrl(),
			'hauth_endpoint'  => $base_url . ( strpos( $base_url, '?' ) ? '&' : '?' ) . "hauth.done={$this->providerId}",
			'hauth_start_url' => $base_url . ( strpos( $base_url, '?' ) ? '&' : '?' ) . "hauth.start={$this->providerId}&hauth.time=" . time(),
		);

		$parameters = array_merge( $defaults, (array) $parameters );

		$this->storage->set( $this->providerId . ".hauth_return_to"    , $parameters["hauth_return_to"] );
		$this->storage->set( $this->providerId . ".hauth_endpoint"     , $parameters["hauth_endpoint"]  ); 
		$this->storage->set( $this->providerId . ".id_provider_params" , $parameters );

		// store config
		$this->storage->config( "CONFIG", $this->getHybridauthConfig() );

		// redirect user to start url
		Util::redirect( $parameters["hauth_start_url"] );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function isAuthorized()
	{
		return false;
	}

	// --------------------------------------------------------------------

   	/**
	* Erase adapter stored data
	*/
	function disconnect()
	{
		$this->storage->deleteMatch( "{$this->providerId}." );
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getHybridauthEndpointUri()
	{
		return $this->hybridauthEndpoint;
	}

	/**
	* ...
	*/
	public final function setHybridauthEndpointUri( $uri )
	{
		$this->hybridauthEndpoint = $uri;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getTokens()
	{
		return $this->storage->get( $this->providerId . '.tokens' ) ? $this->storage->get( $this->providerId . '.tokens' ) : $this->tokens;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function storeTokens( $tokens )
	{
		$this->tokens = $tokens;

		$this->storage->set( $this->providerId . '.tokens', $this->tokens );
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getApplicationId()
	{
		return $this->application->id;
	}

	/**
	* Set Application Key if not Null
	*/
	public final function letApplicationId( $id )
	{
		if( $this->getApplicationId() ){
			return;
		}

		$this->setApplicationId( $id );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setApplicationId( $id )
	{
		$this->application->id = $id;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getApplicationKey()
	{
		return $this->application->key;
	}

	// --------------------------------------------------------------------

	/**
	* Set Application Key if not Null
	*/
	public final function letApplicationKey( $key )
	{
		if( $this->getApplicationKey() ){
			return;
		}

		$this->setApplicationKey( $key );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setApplicationKey( $key )
	{
		$this->application->key = $key;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getApplicationSecret()
	{
		return $this->application->secret;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letApplicationSecret( $secret )
	{
		if( $this->getApplicationSecret() ){
			return;
		}

		$this->setApplicationSecret( $secret );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setApplicationSecret( $secret )
	{
		$this->application->secret = $secret;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getApplicationScope()
	{
		return $this->application->scope;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letApplicationScope( $scope )
	{
		if( $this->getApplicationScope() ){
			return;
		}

		$this->setApplicationScope( $scope );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setApplicationScope( $scope )
	{
		$this->application->scope = $scope;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getEndpointBaseUri()
	{
		return $this->endpoints->baseUri;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letEndpointBaseUri( $uri )
	{
		if( $this->getEndpointBaseUri() ){
			return;
		}

		$this->setEndpointBaseUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setEndpointBaseUri( $uri )
	{
		$this->endpoints->baseUri = $uri;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getEndpointRedirectUri()
	{
		return $this->endpoints->redirectUri;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letEndpointRedirectUri( $uri )
	{
		if( $this->getEndpointRedirectUri() ){
			return;
		}

		$this->setEndpointRedirectUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setEndpointRedirectUri( $uri )
	{
		$this->endpoints->redirectUri = $uri;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getEndpointAuthorizeUri()
	{
		return $this->endpoints->authorizeUri;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letEndpointAuthorizeUri( $uri )
	{
		if( $this->getEndpointAuthorizeUri() ){
			return;
		}

		$this->setEndpointAuthorizeUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setEndpointAuthorizeUri( $uri )
	{
		$this->endpoints->authorizeUri = $uri;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getEndpointRequestTokenUri()
	{
		return $this->endpoints->requestTokenUri;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letEndpointRequestTokenUri( $uri )
	{
		if( $this->getEndpointRequestTokenUri() ){
			return;
		}

		$this->setEndpointRequestTokenUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setEndpointRequestTokenUri( $uri )
	{
		$this->endpoints->requestTokenUri = $uri;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getEndpointAccessTokenUri()
	{
		return $this->endpoints->accessTokenUri;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letEndpointAccessTokenUri( $uri )
	{
		if( $this->getEndpointAccessTokenUri() ){
			return;
		}

		$this->setEndpointAccessTokenUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setEndpointAccessTokenUri( $uri )
	{
		$this->endpoints->accessTokenUri = $uri;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getEndpointTokenInfoUri()
	{
		return $this->endpoints->tokenInfoUri;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letEndpointTokenInfoUri( $uri )
	{
		if( $this->getEndpointTokenInfoUri() ){
			return;
		}

		$this->setEndpointTokenInfoUri( $uri );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setEndpointTokenInfoUri( $uri )
	{
		$this->endpoints->tokenInfoUri = $uri;
	}

	// ====================================================================

	/**
	* ...
	*/
	public final function getEndpointAuthorizeUriAdditionalParameters()
	{
		return $this->endpoints->authorizeUriParameters;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function setEndpointAuthorizeUriAdditionalParameters( $parameters = array() )
	{
		$this->endpoints->authorizeUriParameters = $parameters;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	public final function letEndpointAuthorizeUriAdditionalParameters( $parameters = array() )
	{
		if( $this->getEndpointAuthorizeUriAdditionalParameters() ){
			return;
		}

		$this->setEndpointAuthorizeUriAdditionalParameters( $parameters );
	}

	// ====================================================================

	/**
	* ...
	*/
	function getOpenidIdentifier()
	{
		return $this->openidIdentifier;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function letOpenidIdentifier( $openidIdentifier )
	{
		if( $this->getOpenidIdentifier() ){
			return;
		}

		$this->setOpenidIdentifier( $openidIdentifier );
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	function setOpenidIdentifier( $openidIdentifier )
	{
		$this->openidIdentifier = $openidIdentifier;
	}

	// ====================================================================

	/**
	* ...
	*/
	protected function getAdapterConfig( $key = null, $subkey = null )
	{
		if( ! $key ){
			return $this->config;
		}

		if( ! $subkey && isset( $this->config[ $key ] ) ){
			return $this->config[ $key ];
		}

		if( isset( $this->config[ $key ] ) && isset( $this->config[ $key ][ $subkey ] ) ){
			return $this->config[ $key ][ $subkey ];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	protected function setAdapterConfig( $config = array() )
	{
		$this->config = $config;
	}

	// ====================================================================

	/**
	* ...
	*/
	protected function getHybridauthConfig( $key = null, $subkey = null )
	{
		if( ! $key ){
			return $this->hybridauthConfig;
		}

		if( ! $subkey && isset( $this->hybridauthConfig[ $key ] ) ){
			return $this->hybridauthConfig[ $key ];
		}

		if( isset( $this->hybridauthConfig[ $key ] ) && isset( $this->config[ $key ][ $subkey ] ) ){
			return $this->hybridauthConfig[ $key ][ $subkey ];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	protected function setHybridauthConfig( $config = array() )
	{
		$this->hybridauthConfig = $config;
	}

	// ====================================================================

	/**
	* ...
	*/
	protected function getAdapterParameters( $key = null )
	{
		if( ! $key ){
			return $this->parameters;
		}

		if( isset( $this->parameters[ $key ] ) ){
			return $this->parameters[ $key ];
		}

		return null;
	}

	// --------------------------------------------------------------------

	/**
	* ...
	*/
	protected function setAdapterParameters( $parameters = array() )
	{
		$this->parameters = $parameters;
	}

	// ====================================================================

	/**
	* ...
	*/
	protected function parseRequestResult( $result, $parser = 'json_decode' )
	{
		if( json_decode( $result ) ){
			return json_decode( $result );
		}

		parse_str( $result, $ouput );

		$result = new \StdClass();

		foreach( $ouput as $k => $v ){
			$result->$k = $v;
		}

		return $result;
	}

	// --------------------------------------------------------------------

	/**
	* Shamelessly Borrowered from Slimframework, but to be removed/moved
	*/
	function debug()
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
