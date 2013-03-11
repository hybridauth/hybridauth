<?php
$config = dirname( __FILE__ ) . "/config.php";

require_once(dirname( __FILE__ ) . "/../src/Hybridauth/Hybridauth.php");

\Hybridauth\Hybridauth::registerAutoloader();

try {
	$hybridauth = new \Hybridauth\Hybridauth( $config );

	$adapter = $hybridauth->getAdapter( "Google" );

	$adapter->setApplicationId( '**.apps.googleusercontent.com' );
	$adapter->setApplicationSecret( '**' );
	$adapter->setApplicationScope( 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email' );

	$adapter->setEndpointAuthorizeUri( 'https://accounts.google.com/o/oauth2/auth' );
	$adapter->setEndpointRequestTokenUri( 'https://accounts.google.com/o/oauth2/token' );
	$adapter->setEndpointTokenInfoUri( 'https://www.googleapis.com/oauth2/v1/tokeninfo' );

	$adapter->setEndpointAuthorizeUriAdditionalParameters( array( 'access_type' => 'offline' ) );

	$adapter->authenticate();

	$user_profile = $adapter->getUserProfile();

	echo "<pre>" . print_r( $user_profile, true ) . "</pre>";

	echo $adapter->debug();

	$adapter->disconnect();
}
catch( \Hybridauth\Exception $e ){
	echo $e;
}
