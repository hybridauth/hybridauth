<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/
 
	session_start(); 

	// config and includes
   	$config = dirname(__FILE__)     . "/config.php";
   	require_once( dirname(__FILE__) . "/../src/Hybridauth/Autoloader.php" );
   	require_once( dirname(__FILE__) . "/../src/Hybridauth/Hybridauth.php" );

	try{
		// hybridauth EP
		$hybridauth = new Hybridauth( $config );

		// automatically try to login with a given idp
		$adapter = $hybridauth->authenticate( "Twitter"  );
		// $adapter = $hybridauth->authenticate( "Facebook" );
		// $adapter = $hybridauth->authenticate( "Google"   );
		// $adapter = $hybridauth->authenticate( "OpenID", array( "openid_identifier" => "openid.stackexchange.com/" ) );
		// $adapter = $hybridauth->authenticate( "OpenID", array( "openid_identifier" => "https://open.login.yahooapis.com/openid20/www.yahoo.com/xrds" ) );

		// get the user profile 
		$user_profile = $adapter->getUserProfile();

		// access user profile data
		echo "Ohai there! U are connected with: <b>{$adapter->id}</b><br />";
		echo "As: <b>{$user_profile->displayName}</b><br />";
		echo "And your provider user identifier is: <b>{$user_profile->identifier}</b><br />";  

		// or even inspect it
		echo "<pre>" . print_r( $user_profile, true ) . "</pre><br />";

		echo "Logging out.."; 
		$adapter->logout(); 
	}
	catch( Hybridauth_Core_Exception $e ){
		// Display the recived error, 
		// to know more please refer to Exceptions handling section on the userguide
		switch( $e->getCode() ){ 
			case 0 : echo "Unspecified error."; break;
			case 1 : echo "Hybridauth configuration error."; break;
			case 2 : echo "Provider not properly configured."; break;
			case 3 : echo "Unknown or disabled provider."; break;
			case 4 : echo "Missing provider application credentials."; break;
			case 5 : echo "Authentication failed. The user has canceled the authentication or the provider refused the connection."; break;
			case 6 : echo "User profile request failed. Most likely the user is not connected to the provider and he should to authenticate again.";break;
			case 7 : echo "User not connected to the provider."; break;
			case 8 : echo "Provider does not support this feature."; break;
		} 

		// well, basically your should not display this to the end user, just give him a hint and move on..
		echo "<br /><br /><b>Original error message:</b> " . $e->getMessage();

		echo "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString() . "</pre>"; 

		// get the previous exception if possible - PHP 5.3.0+  
		if ( method_exists($e,'getPrevious') && $e->getPrevious() ) {
			echo "<h4>Previous exception</h4> " . $e->getPrevious()->getMessage() . "<pre>" . $e->getPrevious()->getTraceAsString() . "</pre>";
		} 
	}
	catch( Exception $e ){
		echo '<b>Caught an unknown exception:</b> '.  $e->getMessage() . "<br />";
		
		echo "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString() . "</pre>";  
	}

	// keep rolling
	session_destroy();
