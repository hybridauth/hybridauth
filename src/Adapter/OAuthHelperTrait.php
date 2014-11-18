<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception\HttpClientFailureException; 
use Hybridauth\Exception\HttpRequestFailedException; 

/**
 * 
 */
trait OAuthHelperTrait
{
	/**
	* {@inheritdoc}
	*/
	function isAuthorized()
	{
		return (bool) $this->token( 'access_token' );
	}

	/**
	* {@inheritdoc}
	*/
	function disconnect()
	{
		$this->clearTokens();

		return true;
	}

	/**
	* Return oauth access tokens
	*
	* @param array $tokenNames
	*
	* @return array
	*/
	function getAccessToken( $tokenNames = [] )
	{
		if( ! $tokenNames )
		{
			$tokenNames = [
				'access_token',
				'access_token_secret',
				'token_type',
				'refresh_token',
				'expires_in',
				'expires_at'
			];
		}

		$tokens = [];

		foreach( $tokenNames as $name )
		{
			if( $this->token( $name ) )
			{
				$tokens[ $name ] = $this->token( $name );
			}
		}

		return $tokens;
	}

	/**
	* Reset adapter access tokens
	*
	* @param array $tokens
	*/
	function setAccessToken( $tokens = [] )
	{
		$this->clearTokens();

		foreach( $tokens as $token => $value )
		{
			$this->token( $token , $value );
		}
	}

	/**
	* Get or Set a token
	*
	* This method provide a common way for providers adapter to store data internally.
	* These tokens can be either OAuth tokens or any useful data (i.e., user_id, auth_nonce, etc.)
	*
	* @param string $token
	* @param mixed  $value
	*
	* @return mixed
	*/
	function token( $token, $value = null )
	{
		if( $value === null ){
			return $this->storage->get( $this->providerId . '.token.' . $token );
		}

		// we only store necessary data
		if( empty( $value ) ){
			$this->deleteToken( $token );
		}
		else{
			$this->storage->set( $this->providerId . '.token.' . $token, $value );
		}

		return null;
	}

	/**
	* Delete all tokens of the instantiated adapter
	*/
	function clearTokens()
	{
		$this->storage->deleteMatch( $this->providerId . '.' );
	}

	/**
	* Delete a stored token 
	*
	* @param string $token
	*/
	protected function deleteToken( $token )
	{
		$this->storage->delete( $this->providerId . '.token.' . $token );
	}

	/**
	* Validate Signed API Requests responses
	*
	* Since the specifics of error responses is beyond the scope of RFC6749 and OAuth Core specifications,
	* Hybridauth will consider any HTTP status code that is different than '200 OK' as an ERROR.
	*
	* @throws HttpClientFailureException
	* @throws HttpRequestFailedException
	*/
	protected function validateApiResponse()
	{
		if( $this->httpClient->getResponseClientError() )
		{
			throw new HttpClientFailureException( 'HTTP client error: ' . $this->httpClient->getResponseClientError() . '.' );
		}

		if( 200 != $this->httpClient->getResponseHttpCode() )
		{
			throw new HttpRequestFailedException( 'HTTP error ' . $this->httpClient->getResponseHttpCode() . '. Raw Provider API response: ' . $this->httpClient->getResponseBody() . '.' );
		}
	}

	/**
	* Override defaults endpoints
	*/
	protected function overrideEndpoints() 
	{
		$endpoints = $this->config->filter( 'endpoints' );

		$this->apiBaseUrl     = $endpoints->exists( 'api_base_url'     ) ? $endpoints->get( 'api_base_url'     ) : $this->apiBaseUrl     ;
		$this->authorizeUrl   = $endpoints->exists( 'authorize_url'    ) ? $endpoints->get( 'authorize_url'    ) : $this->authorizeUrl   ;
		$this->accessTokenUrl = $endpoints->exists( 'access_token_url' ) ? $endpoints->get( 'access_token_url' ) : $this->accessTokenUrl ;
	}
}
