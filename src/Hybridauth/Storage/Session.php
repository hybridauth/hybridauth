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
	private $store_var = null;
	private $config_var = null;

	function __construct($store_var = 'HA::STORE', $config_var = 'HA::CONFIG')
	{
		if ( ! session_id() ){
			if ( ! session_start() ){
				throw new Exception( "Hybridauth requires the use of 'session_start()' at the start of your script, which appears to be disabled." );
			}
		}
		$this->store_var = $store_var;
		$this->config_var = $config_var;
	}

	// --------------------------------------------------------------------

	function config( $key, $value = null )
	{
		$key = strtolower ( $key );

		if ( $value ){
			$_SESSION[$this->config_var][$key] = serialize( $value );
		}
		elseif( isset( $_SESSION [$this->config_var][$key] ) ){
			return unserialize( $_SESSION [$this->config_var][$key] );
		}

		return null;
	}

	// --------------------------------------------------------------------

	function get( $key )
	{
		$key = static::key($key); ;

		if ( isset( $_SESSION[$this->store_var], $_SESSION[$this->store_var][$key] ) ){
			return unserialize( $_SESSION[$this->store_var][$key] );
		}

		return null;
	}

	// --------------------------------------------------------------------

	function set( $key, $value )
	{
		$key = static::key($key);
		if( ! isset($_SESSION[$this->store_var]) ) {
			$_SESSION[$this->store_var] = array();
		}

		$_SESSION[$this->store_var][$key] = serialize ( $value );
	}

	// --------------------------------------------------------------------

	function delete( $key )
	{
		$this->deleteMatch($key . '$');
	}

	// --------------------------------------------------------------------

	function deleteMatch( $key )
	{
		$key = static::key($key);

		if ( isset( $_SESSION[$this->store_var] ) && count( $_SESSION [$this->store_var] ) ){
			$f = $_SESSION[$this->store_var];

			foreach ( $f as $k => $v ) {
				if ( preg_match( '/^' . $key . '/', $k ) ) {
					unset( $f[$k] );
				}
			}

			$_SESSION[$this->store_var] = $f;
		}
	}

    // --------------------------------------------------------------------

    function dump() {
    	return $this->dumpMatch('.*');
    }

    // --------------------------------------------------------------------

    function dumpMatch( $key ) {
    	$key = static::key($key);
    	$return = array();
		if ( isset( $_SESSION[$this->store_var] ) && count( $_SESSION [$this->store_var] ) ){
			foreach ( $_SESSION[$this->store_var] as $k => $v ) {
				if ( preg_match( '/^' . $key . '/', $k ) ) {
					$return[$k] = $v;
				}
			}
		}

		return serialize($return);
    }

    // --------------------------------------------------------------------

    function load( $data ) {
    	$unserialized = unserialize($data);
    	$falseCheck = 'b:0;'; //serialize(false);
    	if ( is_array($unserialized) ) {
	    	foreach ( $unserialized as $k => $v ) {
		    		$this->set(static::stripKeyPrefix($k), ($v == $falseCheck || unserialize($v) !== false) ? unserialize($v) : $v);
	    	}
    	}
    }

	protected static function key($key) {
		return 'hauth_session.' . strtolower( $key );
	}

	protected static function stripKeyPrefix($key) {
		$pos = strpos($key, 'hauth_session.');
		return $pos === false ? $key : substr($key,$pos+14/*strlen('hauth_session.')*/);
	}
}
