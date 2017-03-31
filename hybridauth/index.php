<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------

$config = require 'config.php';
require_once( "Hybrid/Auth.php" );
require_once( "Hybrid/Endpoint.php" );

if (isset($_REQUEST['hauth_start']) || isset($_REQUEST['hauth_done'])) {
  Hybrid_Endpoint::process();
} else {
  try {
    $hybridauth = new Hybrid_Auth( $config );
		$google = $hybridauth->authenticate( "Google");
		$user_profile = $google->getUserProfile();

		echo "Hi there! " . $user_profile->email; 
	} catch( Exception $e ){
		echo "Ooophs, we got an error: " . $e->getMessage();
	}
}
