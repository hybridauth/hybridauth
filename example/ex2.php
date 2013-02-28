<?php
$config = dirname(__FILE__)     . "/config.php";

require_once( dirname(__FILE__) . "/../src/Hybridauth/Hybridauth.php" );

\Hybridauth\Hybridauth::registerAutoloader();

$access_token = 'ya29.AHES6ZQFdAM6TMYcue4Vcy1AneMNovwovwi_dAgJ9Mn8j77T'; //< set yours

/*
	or:
		$tokens = new \Hybridauth\Adapter\Authentication\OAuth2\Tokens();
		$tokens->accessToken = 'ya29.AHES6ZQFdAM6TMYcue4Vcy1AneMNovwovwi_dAgJ9Mn8j77T';

		$hybridauth->getAdapter( "Google" )->getApi( $tokens )->getUserProfile();
*/

try{
	$hybridauth = new \Hybridauth\Hybridauth( $config );

	$adapter = $hybridauth->getAdapter( "Google" );

	$user_profile = $adapter->getApi( $access_token )->getUserProfile();

	echo "<pre>" . print_r( $user_profile, true ) . "</pre>";

	echo $adapter->debug();

	$adapter->disconnect();
}
catch( \Hybridauth\Exception $e ){
	echo $e->debug();
}
