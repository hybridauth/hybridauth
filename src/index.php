<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
* HybridAuth Default EndPoint (A.K.A internal callback)
*
* http://hybridauth.sourceforge.net/userguide/tuts/change-hybridauth-endpoint-url.html
*/

require_once( 'HybridAuth/Hybridauth.php' );

\Hybridauth\Hybridauth::registerAutoloader();

$endpoint = new \Hybridauth\Endpoint();
$endpoint->process();
