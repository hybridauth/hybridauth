<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2015, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/
// ------------------------------------------------------------------------
//	HybridAuth End Point
// ------------------------------------------------------------------------
$_REQUEST['hauth_done'] = 'Live';
require_once( "Hybrid/Auth.php" );
require_once( "Hybrid/Endpoint.php" );
Hybrid_Endpoint::process();
