<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------

// start a new session 
session_start();

require_once( "Hybrid/Auth.php" );

# if windows_live_channel requested, we return our windows_live WRAP_CHANNEL_URL
if( isset( $_REQUEST["get"] ) && $_REQUEST["get"] == "windows_live_channel" )
{
	echo 
		file_get_contents( dirname(__FILE__) . "/Hybrid/resources/windows_live_channel.html" ); 

	die();
}

# if openid_policy requested, we return our policy document  
if( isset( $_REQUEST["get"] ) && $_REQUEST["get"] == "openid_policy")
{
	echo 
		file_get_contents( dirname(__FILE__) . "/Hybrid/resources/openid_policy.html" ); 

	die();
}

# if openid_xrds requested, we return our XRDS document 
if( isset( $_REQUEST["get"] ) && $_REQUEST["get"] == "openid_xrds" )
{
	header("Content-Type: application/xrds+xml");

	echo str_replace
		(
			"{RETURN_TO_URL}", 
			Hybrid_Auth::getCurrentUrl( false ) ,
			file_get_contents( dirname(__FILE__) . "/Hybrid/resources/openid_xrds.xml" )
		); 

	die();
} 

# if we get a hauth.start or hauth.done
if( isset( $_REQUEST["hauth_start"] ) || isset( $_REQUEST["hauth_done"] ) )
{
	# init Hybrid_Auth
	try{ 
		// check if Hybrid_Auth session already exist
		if( ! isset( $_SESSION["HA::CONFIG"] ) ): 
			header("HTTP/1.0 404 Not Found");

			die( "Sorry, this page cannot be accessed directly!" );
		endif; 

		Hybrid_Auth::initialize( unserialize( $_SESSION["HA::CONFIG"] ) ); 
	}
	catch( Exception $e )
	{ 
		Hybrid_Logger::error( "Endpoint: Error while trying to init Hybrid_Auth" ); 

		header("HTTP/1.0 404 Not Found");

		die( "Oophs. Error!" );
	}

	Hybrid_Logger::info( "Enter Endpoint" ); 

	# define:endpoint step 3.
	# yeah, why not a switch!
	if( isset( $_REQUEST["hauth_start"] ) && $_REQUEST["hauth_start"] )
	{
		$provider_id = trim( strip_tags( $_REQUEST["hauth_start"] ) );

		# check if page accessed directly
		if( ! Hybrid_Auth::storage()->get( "hauth_session.$provider_id.hauth_endpoint" ) )
		{
			Hybrid_Logger::error( "Endpoint: hauth_endpoint parameter is not defined on hauth_start, halt login process!" );

			header("HTTP/1.0 404 Not Found");

			die( "Sorry, this page cannot be accessed directly!" );
		}

		# define:hybrid.endpoint.php step 2.
		$hauth = Hybrid_Auth::setup( $provider_id );

		# if REQUESTed hauth_idprovider is wrong, session not created, or shit happen, etc. 
		if( ! $hauth )
		{
			Hybrid_Logger::error( "Endpoint: Invalide parameter on hauth_start!" ); 
			
			header("HTTP/1.0 404 Not Found");

			die( "Invalide parameter! Please return to the login page and try again." );
		}

		try
		{ 
			Hybrid_Logger::info( "Endpoint: call adapter [{$provider_id}] loginBegin()" );
			
			$hauth->adapter->loginBegin();
		}
		catch( Exception $e )
		{
			Hybrid_Logger::error( "Exception:" . $e->getMessage(), $e );

			Hybrid_Error::setError( $e->getMessage(), $e->getCode(), $e->getTraceAsString(), $e );

			$hauth->returnToCallbackUrl();
		}

		die();
	}

	# define:endpoint step 3.1 and 3.2
	if( isset( $_REQUEST["hauth_done"] ) && $_REQUEST["hauth_done"] ) 
	{
		// Fix a strange behavior when some provider call back ha endpoint
		// with /index.php?hauth.done={provider}?oauth_token={oauth_token} 
		// By RP Lin
		if ( strrpos( $_REQUEST["hauth_done"], 'oauth_token' ) )
		{
			$arr = explode( 'oauth_token', $_REQUEST["hauth_done"] );
			$_REQUEST["hauth_done"]  = substr( $arr[0], 0, -1 ); // remove ?
			$_REQUEST["oauth_token"] = substr( $arr[1], 1 );     // remove =
		}

		$provider_id = trim( strip_tags( $_REQUEST["hauth_done"] ) );

		$hauth = Hybrid_Auth::setup( $provider_id );
		
		if( ! $hauth )
		{
			Hybrid_Logger::error( "Endpoint: Invalide parameter on hauth_done!" ); 

			$hauth->adapter->setUserUnconnected();

			header("HTTP/1.0 404 Not Found"); 

			die( "Invalide parameter! Please return to the login page and try again." );
		}

		try
		{
			Hybrid_Logger::info( "Endpoint: call adapter [{$provider_id}] loginFinish() " );
			
			$hauth->adapter->loginFinish(); 
		}
		catch( Exception $e )
		{
			Hybrid_Logger::error( "Exception:" . $e->getMessage(), $e );

			Hybrid_Error::setError( $e->getMessage(), $e->getCode(), $e->getTraceAsString(), $e );

			$hauth->adapter->setUserUnconnected(); 
		}

		Hybrid_Logger::info( "Endpoint: job done. retrun to callback url." );

		$hauth->returnToCallbackUrl();

		die();
	}
}
else{
	# Else, 
	# We advertise our XRDS document, something supposed to be done from the Realm URL page 
	echo str_replace
		(
			"{X_XRDS_LOCATION}",
			Hybrid_Auth::getCurrentUrl( false ) . "?get=openid_xrds&v=" . Hybrid_Auth::$version,
			file_get_contents( dirname(__FILE__) . "/Hybrid/resources/openid_realm.html" )
		); 

	die();
}
