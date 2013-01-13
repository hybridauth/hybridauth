<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
 * HybridAuth storage manager
 */
class Hybridauth_Core_Storage_Session implements Hybridauth_Core_Storage_Interface
{
	function __construct()
	{ 
		if ( ! session_id() ){
			if( ! session_start() ){
				throw new Exception( "Hybridauth requires the use of 'session_start()' at the start of your script, which appears to be disabled.", 1 );
			}
		}

		$this->config( "php_session_id", session_id() );
		$this->config( "version", Hybridauth::VERSION );
	}

	public function config($key, $value=null) 
	{
		$key = strtolower( $key );  

		if( $value ){
			$_SESSION["HA::CONFIG"][$key] = serialize( $value ); 
		}
		elseif( isset( $_SESSION["HA::CONFIG"][$key] ) ){ 
			return unserialize( $_SESSION["HA::CONFIG"][$key] );  
		}

		return NULL; 
	}

	public function get($key) 
	{
		$key = strtolower( $key );  

		if( isset( $_SESSION["HA::STORE"], $_SESSION["HA::STORE"][$key] ) ){ 
			return unserialize( $_SESSION["HA::STORE"][$key] );  
		}

		return NULL; 
	}

	public function set( $key, $value )
	{
		$key = strtolower( $key ); 

		$_SESSION["HA::STORE"][$key] = serialize( $value ); 
	}

	function clear()
	{ 
		$_SESSION["HA::STORE"] = ARRAY(); 
	} 

	function delete($key)
	{
		$key = strtolower( $key );  

		if( isset( $_SESSION["HA::STORE"], $_SESSION["HA::STORE"][$key] ) ){
			unset( $_SESSION["HA::STORE"][$key] );
		} 
	}

	function deleteMatch($key)
	{
		$key = strtolower( $key ); 

		if( isset( $_SESSION["HA::STORE"] ) && count( $_SESSION["HA::STORE"] ) ) {
			foreach( $_SESSION["HA::STORE"] as $k => $v ){ 
				if( strstr( $k, $key ) ){
					unset( $_SESSION["HA::STORE"][ $k ] ); 
				}
			}
		}
	}

	function getSessionData()
	{
		if( isset( $_SESSION["HA::STORE"] ) ){ 
			return serialize( $_SESSION["HA::STORE"] ); 
		}

		return NULL; 
	}

	function restoreSessionData( $sessiondata = NULL )
	{ 
		$_SESSION["HA::STORE"] = unserialize( $sessiondata );
	} 
}
