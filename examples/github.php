<?php
	require 'vendor/autoload.php';

	$config = array(
		'callback'  => 'http://localhost/hybridauth/examples/github.php',

		'keys' => array(
			'id'     => '',
			'secret' => ''
		),
	);

	$github = new Hybridauth\Provider\GitHub( $config );

	try {
		$github->authenticate();

		$userProfile = $github->getUserProfile();

		$tokens = $github->getAccessToken();
	}
	catch( Exception $e ){
		echo $e->debug( $github ) ;

		$github->disconnect();
	}

	$github->disconnect();
