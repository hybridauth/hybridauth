<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Adapter;

trait AdapterTokensTrait
{
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
	* Store an array of tokens
	*
	* @param array $tokens
	*/
	function setTokens( $tokens = array() )
	{
		if( $tokens )
		{
			foreach( $tokens as $token => $value )
			{
				$this->token( $token , $value );
			}
		}
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
