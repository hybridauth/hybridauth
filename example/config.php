<?php
/*!
* This file is part of the HybridAuth PHP Library (hybridauth.sourceforge.net | github.com/hybridauth/hybridauth)
*
* This branch contains work in progress toward the next HybridAuth 3 release and may be unstable.
*/

return 
	array(
		"base_url" => "http://localhost/hybridauth-git/hybridauth/",  

		"providers" => array ( 
			"Google" => array ( 
				"enabled" => true,
				"keys"    => array ( "id" => "", "secret" => "" ), 
			),
		),

		// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
		"debug_mode" => false,

		"debug_file" => "",

		// tweak default Http client curl settings
			// http://www.php.net/manual/fr/function.curl-setopt.php  
		"curl_options" => array(
			"CURLOPT_USERAGENT"      => null,
			"CURLOPT_PROXY"          => null,
			// etc..
		),

		// allows the use of a third party client 
			// eg. Zend\Http, Wtf\HttpFoundation
		"http_client" => ""
	);
