<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception; 

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
	* Validate Signed API Requests responses
	*
	* Since the specifics of error responses is beyond the scope of RFC6749 and OAuth Core specifications,
	* Hybridauth will consider any HTTP status code that is different than '200 OK' as an ERROR.
	*
	* @throws Exception
	*/
	protected function validateApiResponse()
	{
		if( $this->httpClient->getResponseClientError() )
		{
			throw new Exception( 'HTTP client error: ' . $this->httpClient->getResponseClientError() . '.' );
		}

		if( 200 != $this->httpClient->getResponseHttpCode() )
		{
			throw new Exception( 'HTTP error ' . $this->httpClient->getResponseHttpCode() . '. Raw Provider API response: ' . $this->httpClient->getResponseBody() . '.' );
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
