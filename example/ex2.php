<?php
$config = dirname( __FILE__ ) . "/config.php";

require_once(dirname( __FILE__ ) . "/../src/Hybridauth/Hybridauth.php");

\Hybridauth\Hybridauth::registerAutoloader();

$access_token = 'ya29.**'; // < set yours

try {
	$hybridauth = new \Hybridauth\Hybridauth( $config );

	$adapter = $hybridauth->getAdapter( "Google" );

	$tokens = new \Hybridauth\Adapter\Template\OAuth2\Tokens( $access_token );  

	$adapter->storeTokens( $tokens );

	$user_profile = $adapter->getUserProfile();

	echo "<pre>" . print_r( $user_profile, true ) . "</pre>";

	echo $adapter->debug();

	$adapter->disconnect();
}
catch( \Hybridauth\Exception $e ){
	echo $e;
}
