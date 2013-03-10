<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

/**
* HybridAuth Default EndPoint (A.K.A internal callback)
*
* http://hybridauth.sourceforge.net/userguide/HybridAuth_endpoint_URL.html
* http://hybridauth.sourceforge.net/userguide/tuts/change-hybridauth-endpoint-url.html
*
* Examples
*
*	1. Basic
*
* 		require_once( 'Hybridauth.php' );
*
*		( new \Hybridauth\Endpoint() )->process();
*
*
*	2. Using a custom sotrage
*
* 		require_once( 'Hybridauth.php' );
*
*		( new \Hybridauth\Endpoint( $myCustomeStorageClassInstanceImplementingStorageInterface ) )
*			->process();
*/

require_once( 'HybridAuth/Hybridauth.php' );

\Hybridauth\Hybridauth::registerAutoloader();

$endpoint = new \Hybridauth\Endpoint();
$endpoint->process();
