<?php
	// config and whatnot
    $config = dirname(__FILE__) . '/../../hybridauth/config.php';
    require_once( "../../hybridauth/Hybrid/Auth.php" );

	try{
		$hybridauth = new Hybrid_Auth( $config );
 
		$provider = $_GET["provider"];       // selected provider name 

		// call back the requested provider adapter instance 
		$adapter = $hybridauth->getAdapter( $provider );

		// logout the user from $provider
		$adapter->logout(); 

		// return to login page
		$hybridauth->redirect( "login.php" );
    }
	catch( Exception $e ){
		// Display the received error,
		// to know more please refer to Exceptions handling section on the userguide
		switch( $e->getCode() ){ 
			case 0 : echo "Unspecified error."; break;
			case 1 : echo "Hybriauth configuration error."; break;
			case 2 : echo "Provider not properly configured."; break;
			case 3 : echo "Unknown or disabled provider."; break;
			case 4 : echo "Missing provider application credentials."; break; 
		} 

		echo "<br /><br /><b>Original error message:</b> " . $e->getMessage();

		echo "<hr /><h3>Trace</h3> <pre>" . $e->getTraceAsString() . "</pre>"; 
	}
