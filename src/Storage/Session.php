<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

namespace Hybridauth\Storage;

/**
 * HybridAuth storage manager
 */
class Session implements StorageInterface
{
	/**
	* Initiate a new session
	*
	* @throws Exception
	*/
	function __construct()
	{
		if( ! session_id() )
		{
			if( ! session_start() )
			{
				throw new Exception( "Hybridauth requires the use of 'session_start()' at the start of your script, which appears to be disabled.", 1 );
			}
		}
	}

	/**
	* {@inheritdoc}
	*/
	function config( $key, $value = null ) 
	{
		$key = strtolower( $key );  

		if( $value )
		{
			$_SESSION["HA::CONFIG"][$key] = serialize( $value );
		}
		elseif( isset( $_SESSION["HA::CONFIG"][$key] ) )
		{ 
			return unserialize( $_SESSION["HA::CONFIG"][$key] );
		}

		return null; 
	}

	/**
	* {@inheritdoc}
	*/
	function get( $key ) 
	{
		$key = 'hauth_session.' . strtolower( $key );  

		if( isset( $_SESSION["HA::STORE"], $_SESSION["HA::STORE"][$key] ) )
		{ 
			return unserialize( $_SESSION["HA::STORE"][$key] );  
		}

		return null; 
	}

	/**
	* {@inheritdoc}
	*/
	function set( $key, $value )
	{
		$key = 'hauth_session.' . strtolower( $key );

		$_SESSION["HA::STORE"][$key] = serialize( $value );
	}

	/**
	* {@inheritdoc}
	*/
	function clear()
	{ 
		$_SESSION["HA::STORE"] = array(); 
	}

	/**
	* {@inheritdoc}
	*/
	function delete( $key )
	{
		$key = 'hauth_session.' . strtolower( $key );  

		if( isset( $_SESSION["HA::STORE"], $_SESSION["HA::STORE"][$key] ) )
		{
		    $tmp = $_SESSION['HA::STORE'];

		    unset( $tmp[$key] );

		    $_SESSION["HA::STORE"] = $tmp;
		} 
	}

	/**
	* {@inheritdoc}
	*/
	function deleteMatch( $key )
	{
		$key = 'hauth_session.' . strtolower( $key ); 

		if( isset( $_SESSION["HA::STORE"] ) && count( $_SESSION["HA::STORE"] ) )
		{
		    $swap = $_SESSION['HA::STORE'];

		    foreach( $swap as $k => $v )
			{ 
				if( strstr( $k, $key ) )
				{
					unset( $swap[ $k ] ); 
				}
			}

			$_SESSION["HA::STORE"] = $swap;
		}
	}

	/**
	* {@inheritdoc}
	*/
	function getSessionData()
	{
		if( isset( $_SESSION["HA::STORE"] ) )
		{ 
			return serialize( $_SESSION["HA::STORE"] ); 
		}
	}

	/**
	* {@inheritdoc}
	*/
	function restoreSessionData( $sessiondata )
	{ 
		$_SESSION["HA::STORE"] = unserialize( $sessiondata );
	} 
}
