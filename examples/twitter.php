<?php
	require 'vendor/autoload.php';

	$config = array(
		'callback'  => 'http://localhost/hybridauth/examples/twitter.php',

		'keys' => array(
			'key'    => '',
			'secret' => ''
		),

	/*
		// optional
			'tokens' => array(
				'access_token'        => 'your-access-token',
				'access_token_secret' => 'your-access-token-secret',
			),

		// optional
			'endpoints' => array(
				'api_base_url'      => 'https://api.twitter.com/1.1/',
				'authorize_url'     => 'https://api.twitter.com/oauth/authenticate',
				'request_token_url' => 'https://api.twitter.com/oauth/request_token',
				'access_token_url'  => 'https://api.twitter.com/oauth/access_token',
			)
	*/
	);

	$twitter = new Hybridauth\Provider\Twitter( $config );

	try {
		$twitter->authenticate();

		$userProfile = $twitter->getUserProfile();

		print_r( $userProfile ); 

		$tokens = $twitter->getAccessToken();
	}
	catch( Exception $e ){
		echo $e->debug( $twitter ) ;

		$twitter->disconnect();
	}

	$twitter->disconnect();
