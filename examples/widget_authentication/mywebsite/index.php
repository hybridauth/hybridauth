<?php
    # start a new PHP session
    session_start();

	// we need to know it
	$CURRENT_URL = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	
	// change the following paths if necessary 
	$config   = dirname(__FILE__) . '/../../../hybridauth/config.php';
	require_once( "../../../hybridauth/Hybrid/Auth.php" );

	try{
		$hybridauth = new Hybrid_Auth( $config );
	}
	catch( Exception $e ){
		echo "Ooophs, we got an error: " . $e->getMessage();
	}

	$provider = ""; 
	
	// handle logout request
	if( isset( $_GET["logout"] ) ){
		$provider = $_GET["logout"];

		$adapter = $hybridauth->getAdapter( $provider );

		$adapter->logout();
		
		header( "Location: index.php"  );
		
		die();
	}

	// if the user select a provider and authenticate with it 
	// then the widget will return this provider name in "connected_with" argument 
	elseif( isset( $_GET["connected_with"] ) && $hybridauth->isConnectedWith( $_GET["connected_with"] ) ){
		$provider = $_GET["connected_with"];
		
		$adapter = $hybridauth->getAdapter( $provider );
		
		$user_data = $adapter->getUserProfile();

		// include authenticated user view
		include "inc_authenticated_user.php";
		
		die();
	} // if user connected to the selected provider 

	// if not, include unauthenticated user view
	include "inc_unauthenticated_user.php";
