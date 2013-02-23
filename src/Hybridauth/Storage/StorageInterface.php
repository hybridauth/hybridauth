<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Storage;

/**
 * HybridAuth storage manager
 */
interface StorageInterface
{
	public function config($key, $value=null);
	
	public function get($key);

	public function set( $key, $value );

	function clear();

	function delete($key);

	function deleteMatch($key);

	function getSessionData();

	function restoreSessionData( $sessiondata = NULL );
}
