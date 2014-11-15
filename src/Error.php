<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth;

use Hybridauth\Storage\StorageInterface;

/**
 * Errors handler
 * 
 * HybridAuth Endpoint errors are stored in Hybrid::storage and not displayed directly to the end user.
 */
final class Error
{
	/**
	* @var object storage instance
	*/
	protected $storage = null;

	/**
	* @param Hybrid_Storage_Interface $storage
	*/
	function __construct( StorageInterface $storage = null )
	{
		$this->storage = $storage;
	}

	/**
	* Store an error in storage
	*
	* @param string $message
	* @param integer $code
	*/
	function setError( $message, $code = 0 )
	{
		$this->storage->set( 'hauth_session.error.status'  , 1        );
		$this->storage->set( 'hauth_session.error.message' , $message );
		$this->storage->set( 'hauth_session.error.code'    , $code    );
	}

	/**
	* Clear the last error
	*/
	function clearError()
	{
		$this->storage->delete( 'hauth_session.error.status'  );
		$this->storage->delete( 'hauth_session.error.message' );
		$this->storage->delete( 'hauth_session.error.code'    );
	}

	/**
	* Returns true when an error is found on storage.
	* 
	* @return boolean
	*/
	function hasError()
	{ 
		return (bool) $this->storage->get( 'hauth_session.error.status' );
	}

	/**
	* Return stored error message
	*
	* @return string
	*/
	function getErrorMessage()
	{ 
		return $this->storage->get( 'hauth_session.error.message' );
	}

	/**
	* Return stored error message
	*
	* @return integer
	*/
	function getErrorCode()
	{ 
		return $this->storage->get( 'hauth_session.error.code' );
	}
}
