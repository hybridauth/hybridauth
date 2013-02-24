<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

return 
	array(
		"base_url" => "http://localhost/hybridauth-git/hybridauth/",  

		"providers" => array(
			"Google" => array(
				"enabled" => true,
				"keys"    => array( "id" => "", "secret" => "" ), 
			),
			"Facebook" => array(
				"enabled" => true,
				"keys"    => array( "id" => "", "secret" => "" ), 
			),
		),

		// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
		"debug_mode" => true,

		"debug_file" => "", // prob will be removed

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

		// allows the use of a third party client 
		// eg. Zend\Http, Wtf\HttpFoundation
		// should be and instance of an object implementing Hybridauth\Http\ClientInterface
		"http_client" => null
	);
