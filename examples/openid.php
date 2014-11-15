<?php
	require 'vendor/autoload.php';

	$config = array(
		'callback' => 'http://localhost/hybridauth/examples/openid.php',

		'openid_identifier' => 'https://open.login.yahooapis.com/openid20/www.yahoo.com/xrds'
	);

	$openid = new Hybridauth\Provider\OpenID( $config );

	try {
		$openid->authenticate();

		$userProfile = $openid->getUserProfile();
	}
	catch( Exception $e ){
		echo $e->debug( $openid ) ;

		$openid->disconnect();
	}

	$openid->disconnect();
