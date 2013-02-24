<?php
$config = dirname(__FILE__)     . "/config.php";

require_once( dirname(__FILE__) . "/../src/Hybridauth/Hybridauth.php" );

\Hybridauth\Hybridauth::registerAutoloader();

try{ 
	$hybridauth = new \Hybridauth\Hybridauth( $config );

	$adapter = $hybridauth->authenticate( "Google" );
	// $adapter = $hybridauth->authenticate( "Facebook" );

	// get the user profile 
	$user_profile = $adapter->getUserProfile();

	// access user profile data
	echo "Ohai there! U are connected with: <b>{$user_profile->provider}</b><br />";
	echo "As: <b>{$user_profile->displayName}</b><br />";
	echo "And your provider user identifier is: <b>{$user_profile->identifier}</b><br />";  

	// or even inspect it
	echo "<pre>" . print_r( $user_profile, true ) . "</pre><br />";

	echo $adapter->debug();

	echo "Logging out.."; 
	$adapter->logout();
}
catch( Hybridauth_Exception $e ){
	echo $e->debug();
}
catch( Exception $e ){
	echo '<b>Caught an unknown exception:</b> '.  $e->getMessage() . "<br />";

	echo "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString() . "</pre>";
}
