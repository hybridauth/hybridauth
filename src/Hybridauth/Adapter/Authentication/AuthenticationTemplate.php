<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Adapter\Authentication;

class AuthenticationTemplate
{
	public final function getHybridauthEndpointUri()
	{
		return $this->hybridauthEndpoint;
	}

	// --------------------------------------------------------------------

	public final function getTokens()
	{
		return $this->tokens;
	}

	// --------------------------------------------------------------------

	public final function getApplicationId()
	{
		return $this->application->id;
	}

	// --------------------------------------------------------------------

	public final function getApplicationSecret()
	{
		return $this->application->secret;
	}

	// --------------------------------------------------------------------

	public final function getApplicationScope()
	{
		return $this->application->scope;
	}

	// --------------------------------------------------------------------

	public final function getEndpointBaseUri()
	{
		return $this->endpoints->baseUri;
	}

	// --------------------------------------------------------------------

	public final function getEndpointRedirectUri()
	{
		return $this->endpoints->redirectUri;
	}

	// --------------------------------------------------------------------

	public final function getEndpointAuthorizeUri()
	{
		return $this->endpoints->authorizeUri;
	}

	// --------------------------------------------------------------------

	public final function getEndpointRequestTokenUri()
	{
		return $this->endpoints->requestTokenUri;
	}

	// --------------------------------------------------------------------

	public final function getEndpointTokenInfoUri()
	{
		return $this->endpoints->tokenInfoUri;
	}

	// --------------------------------------------------------------------

	public final function getEndpointAuthorizeUriAdditionalParameters()
	{
		return $this->endpoints->authorizeUriParameters;
	}

	// ====================================================================

	public final function setTokens( $tokens )
	{
		$this->tokens = $tokens;
	}

	// --------------------------------------------------------------------

	public final function setApplicationId( $id )
	{
		$this->application->id = $id;
	}

	// --------------------------------------------------------------------

	public final function setApplicationSecret( $secret )
	{
		$this->application->secret = $secret;
	}

	// --------------------------------------------------------------------

	public final function setApplicationScope( $scope )
	{
		$this->application->scope = $scope;
	}

	// --------------------------------------------------------------------

	public final function setEndpointBaseUri( $uri )
	{
		$this->endpoints->baseUri = $uri;
	}

	// --------------------------------------------------------------------

	public final function setEndpointRedirectUri( $uri )
	{
		$this->endpoints->redirectUri = $uri;
	}

	// --------------------------------------------------------------------

	public final function setEndpointAuthorizeUri( $uri )
	{
		$this->endpoints->authorizeUri = $uri;
	}

	// --------------------------------------------------------------------

	public final function setEndpointRequestTokenUri( $uri )
	{
		$this->endpoints->requestTokenUri = $uri;
	}

	// --------------------------------------------------------------------

	public final function setEndpointTokenInfoUri( $uri )
	{
		$this->endpoints->tokenInfoUri = $uri;
	}

	// --------------------------------------------------------------------

	public final function setEndpointAuthorizeUriAdditionalParameters( $parameters = array() )
	{
		$this->endpoints->authorizeUriParameters = $parameters;
	}

	// ====================================================================

	/**
	* Set Application Id if not Null
	*/
	public final function letApplicationId( $id )
	{
		if( $this->getApplicationId() ){
			return;
		}

		$this->setApplicationId( $id );
	}

	// --------------------------------------------------------------------

	public final function letApplicationSecret( $secret )
	{
		if( $this->getApplicationSecret() ){
			return;
		}

		$this->setApplicationSecret( $secret );
	}

	// --------------------------------------------------------------------

	public final function letApplicationScope( $scope )
	{
		if( $this->getApplicationScope() ){
			return;
		}

		$this->setApplicationScope( $scope );
	}

	// --------------------------------------------------------------------

	public final function letEndpointBaseUri( $uri )
	{
		if( $this->getEndpointBaseUri() ){
			return;
		}

		$this->setEndpointBaseUri( $uri );
	}

	// --------------------------------------------------------------------

	public final function letEndpointRedirectUri( $uri )
	{
		if( $this->getEndpointRedirectUri() ){
			return;
		}

		$this->setEndpointRedirectUri( $uri );
	}

	// --------------------------------------------------------------------

	public final function letEndpointAuthorizeUri( $uri )
	{
		if( $this->getEndpointAuthorizeUri() ){
			return;
		}

		$this->setEndpointAuthorizeUri( $uri );
	}

	// --------------------------------------------------------------------

	public final function letEndpointRequestTokenUri( $uri )
	{
		if( $this->getEndpointRequestTokenUri() ){
			return;
		}

		$this->setEndpointRequestTokenUri( $uri );
	}

	// --------------------------------------------------------------------

	public final function letEndpointTokenInfoUri( $uri )
	{
		if( $this->getEndpointTokenInfoUri() ){
			return;
		}

		$this->setEndpointTokenInfoUri( $uri );
	}

	// --------------------------------------------------------------------

	public final function letEndpointAuthorizeUriAdditionalParameters( $parameters = array() )
	{
		if( $this->getEndpointAuthorizeUriAdditionalParameters() ){
			return;
		}

		$this->setEndpointAuthorizeUriAdditionalParameters( $parameters );
	}

	// ====================================================================

	protected function parseRequestResult( $result )
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
}
