<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

return 
	array(
	// required
		"base_url" => "http://localhost/hybridauth-git/hybridauth/",

	// required
		"providers" => array(
			"OpenID" => array(
				"enabled" => true
			),
			"Google" => array(
				"enabled" => true,
				"keys"    => array( "id" => '', "secret" => '' ),
			),
			"Facebook" => array(
				"enabled" => true,
				"keys"    => array( "id" => '', "secret" => '' ),
			),
			"Windows" => array(
				"enabled" => true,
				"keys"    => array( "id" => '', "secret" => '' ),
			),
			"Twitter" => array(
				"enabled" => true,
				"keys"    => array( "key" => '', "secret" => '' ),
			),
		),

	// optional
		// dev mode
		"debug_mode" => true,

	// optional
		// tweak default Http client curl settings
		// http://www.php.net/manual/fr/function.curl-setopt.php  
		"curl_options" => array(
			// setting custom certificates
			// http://curl.haxx.se/docs/caextract.html
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_CAINFO         => dirname(__FILE__) . '/ca-bundle.crt',

			// setting proxies 
			# CURLOPT_PROXY          => '*.*.*.*:*',

			// custom user agent
			# CURLOPT_USERAGENT      => "", 

			// etc..
		),

	// optional
		// allows the use of a third party client 
		// eg. Zend\Http, Wtf\HttpFoundation
		// should be a class implementing Hybridauth\Http\ClientInterface
		"http_client" => null
	);
