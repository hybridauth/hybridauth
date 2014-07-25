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
	function config( $key, $value = null );

	// --------------------------------------------------------------------

	function get( $key );

	// --------------------------------------------------------------------

	function set( $key, $value );

	// --------------------------------------------------------------------

	function delete( $key );

	// --------------------------------------------------------------------

	function deleteMatch( $key );

    // --------------------------------------------------------------------

    function dump();

    // --------------------------------------------------------------------

    function dumpMatch( $key );

    // --------------------------------------------------------------------

    function load( $data );
}
