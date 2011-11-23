<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * HybridAuth storage manager
 */
class Hybrid_Storage 
{
	function __construct()
	{
		if( ! session_id() ){
			throw new Exception( "Hybriauth require the use of 'session_start()' at the start of your script", 1 );
		}
	}

	public function get($key, $expiration = false) 
	{
		$key = strtolower( $key );  

		if( isset( $_SESSION["HA::STORE"][$key] ) ){ 
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

		if( isset( $_SESSION["HA::STORE"][$key] ) ){ 
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
		return serialize( $_SESSION["HA::STORE"] );
	}

	function restoreSessionData( $sessiondata = NULL )
	{ 
		$_SESSION["HA::STORE"] = unserialize( $sessiondata );
	}
}
