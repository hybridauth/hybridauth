<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Storage;

/**
 * HybridAuth storage manager interface
 */
interface StorageInterface
{
	/**
	* Store Hybridauth Config
	*
	* @param string $key
	* @param string $value
	*
	* @return mixed
	*/
	function config( $key, $value = null );

	/**
	* Retrieve a item from storage
	*
	* @param string $key
	*
	* @return mixed
	*/
	function get( $key );

	/**
	* Add or Update an item to storage
	*
	* @param string $key
	* @param string $value
	*/
	function set( $key, $value );

	/**
	* Clear all items in storage
	*/
	function clear();

	/**
	* Delete an item from storage
	*
	* @param string $key
	*/
	function delete( $key );

	/**
	* Delete a item from storage
	*
	* @param string $key
	*/
	function deleteMatch( $key );
}
