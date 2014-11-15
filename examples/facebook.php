<?php
	require 'vendor/autoload.php';

	$config = array(
		'callback'  => 'http://localhost/hybridauth/examples/facebook.php',

		'keys' => array(
			'id'     => 'your-app-id', 
			'secret' => 'your-app-secret'
		)

	/*
		// optional
			'tokens' => array(
				'access_token' => 'your-facebook-access-token'
			),

		// optional
			'endpoints' => array(
				'api_base_url'      => 'https://graph.facebook.com/v2.2/',
				'authorize_url'     => 'https://www.facebook.com/dialog/oauth',
				'access_token_url'  => 'https://graph.facebook.com/oauth/access_token',
			)
	*/
	);

	$facebook = new Hybridauth\Provider\Facebook( $config );

	try {
		$facebook->authenticate();

		echo '<pre>';

		$userProfile = $facebook->getUserProfile();

		print_r( $userProfile );

		$tokens = $facebook->getAccessToken();

		print_r( $tokens );
	}
	catch( Exception $e ){
		echo $e->debug( $facebook ) ;
	}

	$facebook->disconnect();
