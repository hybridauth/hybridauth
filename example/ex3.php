<?php
$config = dirname( __FILE__ ) . "/config.php";

require_once(dirname( __FILE__ ) . "/../src/Hybridauth/Hybridauth.php");

\Hybridauth\Hybridauth::registerAutoloader();

try {
	$hybridauth = new \Hybridauth\Hybridauth( $config );

	$adapter = $hybridauth->getAdapter( "Google" );

	$adapter->getAuthService()->setApplicationId( '***.apps.googleusercontent.com' );
	$adapter->getAuthService()->setApplicationSecret( '***' );
	$adapter->getAuthService()->setApplicationScope( 'https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email' );

	$adapter->getAuthService()->setEndpointAuthorizeUri( 'https://accounts.google.com/o/oauth2/auth' );
	$adapter->getAuthService()->setEndpointRequestTokenUri( 'https://accounts.google.com/o/oauth2/token' );
	$adapter->getAuthService()->setEndpointTokenInfoUri( 'https://www.googleapis.com/oauth2/v1/tokeninfo' );

	$adapter->getAuthService()->setEndpointAuthorizeUriAdditionalParameters( array( 'access_type' => 'offline' ) );

	$adapter->authenticate();

	$user_profile = $adapter->getApi()->getUserProfile();

	echo "<pre>" . print_r( $user_profile, true ) . "</pre>";

	echo $adapter->debug();

	$adapter->disconnect();
}
catch( \Hybridauth\Exception $e ){
	echo $e->debug();
}
