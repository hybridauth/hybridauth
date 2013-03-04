<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

namespace Hybridauth\Storage;

use Hybridauth\Exception;
use Hybridauth\Storage\StorageInterface;

/**
* HybridAuth storage manager
*/
class Session implements StorageInterface 
{
	function __construct()
	{
		if ( ! session_id() ){
			if ( ! session_start() ){
				throw new Exception( "Hybridauth requires the use of 'session_start()' at the start of your script, which appears to be disabled." );
			}
		}
	}

	// --------------------------------------------------------------------

	function config( $key, $value = null )
	{
		$key = strtolower ( $key );

		if ( $value ){
			$_SESSION['HA::CONFIG'][$key] = serialize( $value );
		}
		elseif( isset( $_SESSION ['HA::CONFIG'][$key] ) ){
			return unserialize( $_SESSION ['HA::CONFIG'][$key] );
		}

		return null;
	}

	// --------------------------------------------------------------------

	function get( $key )
	{
		$key = 'hauth_session.' . strtolower( $key );

		if ( isset( $_SESSION['HA::STORE'], $_SESSION ['HA::STORE'] [$key] ) ){
			return unserialize( $_SESSION['HA::STORE'][$key] );
		}

		return null;
	}

	// --------------------------------------------------------------------

	function set( $key, $value )
	{
		$key = 'hauth_session.' . strtolower ( $key );

		$_SESSION['HA::STORE'][$key] = serialize ( $value );
	}

	// --------------------------------------------------------------------

	function delete( $key )
	{
		$key = 'hauth_session.' . strtolower( $key );

		if ( isset( $_SESSION ['HA::STORE'], $_SESSION['HA::STORE'][$key] ) ){
			$f = $_SESSION ['HA::STORE'];

			unset( $f [$key] );

			$_SESSION['HA::STORE'] = $f;
		}
	}

	// --------------------------------------------------------------------

	function deleteMatch( $key )
	{
		$key = 'hauth_session.' . strtolower( $key );

		if ( isset( $_SESSION['HA::STORE'] ) && count( $_SESSION ['HA::STORE'] ) ){
			$f = $_SESSION['HA::STORE'];

			foreach ( $f as $k => $v ) {
				if ( strstr( $k, $key ) ) {
					unset( $f[$k] );
				}
			}

			$_SESSION['HA::STORE'] = $f;
		}
	}
}
