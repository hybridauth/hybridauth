<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

// ------------------------------------------------------------------------
//	HybridAuth EndPoint (A.K.A internal callback)
// ------------------------------------------------------------------------

require_once( "HybridAuth/Autoloader.php" ); 
require_once( "HybridAuth/Hybridauth.php" ); 

$endpoint = new Hybridauth_Core_Endpoint();
$endpoint->process();
