<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

trait AdapterTokensTrait
{
	/**
	* Return oauth access tokens
	*
	* @param array $tokensNames
	*
	* @return array
	*/
	function getAccessToken( $tokenNames = array() )
	{
		if( ! $tokenNames )
		{
			$tokenNames = array(
				'access_token',
				'access_token_secret',
				'token_type',
				'refresh_token',
				'expires_in',
				'expires_at'
			);
		}

		$tokens = array();

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
	function setAccessToken( $tokens = array() )
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
	* @param null $value
	* @return string|null
	*/
	function token( $token, $value = null )
	{
		if( $value === null )
		{
			return $this->storage->get( $this->providerId . '.token.' . $token );
		}

		// we only store necessary data
		if( empty( $value ) )
		{
			$this->deleteToken( $token );
		}
		else
		{
			$this->storage->set( $this->providerId . '.token.' . $token, $value );
		}

		return null;
	}

	/**
	* Delete all tokens
	*
	* @param array $tokens
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
}
