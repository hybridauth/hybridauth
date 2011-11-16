<?php
/**
* HybridAuth
* 
* A Social-Sign-On PHP Library for authentication through identity providers like Facebook,
* Twitter, Google, Yahoo, LinkedIn, MySpace, Windows Live, Tumblr, Friendster, OpenID, PayPal,
* Vimeo, Foursquare, AOL, Gowalla, and others.
*
* Copyright (c) 2009-2011 (http://hybridauth.sourceforge.net) 
*/

// ------------------------------------------------------------------------
//	HybridAuth Config
// ------------------------------------------------------------------------

/**
 * - "base_url" is the url to HybridAuth EndPoint 'index.php'
 * - "providers" is the list of providers supported by HybridAuth
 * - "enabled" can be true or false; if you dont want to use a specific provider then set it to 'false'
 * - "keys" are your application credentials for this provider 
 * 		for example :
 *     		'id' is your facebook application id
 *     		'key' is your twitter application consumer key
 *     		'secret' is your twitter application consumer secret 
 * - To enable Logging, set debug_mode to true, then provide a path of a writable file on debug_file
 *  
 * Note: The HybridAuth Config file is not required, to know more please visit:
 *       http://hybridauth.sourceforge.net/userguide/Configuration.html
 */

return 
	array( 
		// set on "base_url" the url that point to HybridAuth Endpoint (where the index.php is found) 
		"base_url"       => "http://localhost/hybridauth/2.0.8/hybridauth/",
		// "base_url"       => "http://hauth.sx33.net/20/hybridauth/",
 
		"providers"      => array (
			// openid
			"OpenID" 		=> 	array ( 
									"enabled" 	=> true // no keys required for OpenID based providers
								),

			// google
			"Google" 		=> 	array ( 
									"enabled" 	=> true 
								),

			// yahoo
			"Yahoo"             => 	array ( 
									"enabled" 	=> true 
								),
 
			// facebook
			"Facebook" 			=> array ( // 'id' is your facebook application id
									"enabled" 	=> true,
									"keys"	 	=> array ( "id" => "185915804256", "secret" => "e23fb1aae9fa44b27113284a7c6a49d7" ) 
								),

			// twitter 
			"Twitter"   	    => 	array ( 
									"enabled" 	=> true,
									"keys"	 	=> array ( "key" => "3Xq2hqLhP6lTU2Qh0RUeA", "secret" => "ugsQgG9d5Mh1IIZygtrpRcmwNSiuyT7giVdDqHLA" ) 
									// "keys"	 	=> array ( "key" => "tRGbOY4RlTRSJXDgifjRkg", "secret" => "zWCgOlgcjIIPtJgJoloJcWxovku7IyWhDe69pDjJAM" ) 
									// "keys"	 	=> array ( "key" => "M3BMFHMxDQmfwNdvYrpQ", "secret" => "JkrVeiExyjIcL5xdysL6pbqI1CqwrRAHq9h7WJwp0" ) 
								),

			// myspace
			"MySpace" 	        => 	array ( // 'key' is your twitter application consumer key
									"enabled" 	=> true,
									"keys"	 	=> array ( "key" => "c85b177d77d84c57a7f7d83d65db8015", "secret" => "e1c47b515c31436ab1752ba58a02a0fb7d581a531667441c99c299addcb90e25" )
								),

			// windows live
			"Live"  			=> array ( 
									"enabled" 	=> true,
									"keys"	 	=> array ( "id" => "000000004005E70C", "secret" => "XMcnx1G1GvKLZEIPjXxulSKakpn8pgzj" ) 
								),

			// linkedin
			"LinkedIn"          => 	array ( 
									"enabled" 	=> true,
									"keys"	 	=> array ( "key" => "QC_e8WQKkEuQ4G8BK3Cpuu3sOZwynbfbdGD8PBh7uh6qEUN-aslbDyOR8he9GAyJ", "secret" => "Yl5ZluTn2jw2qwhtsdGmRaprGzFzyIAnse8EpWH0fU_2e7bPj05idxum8mRZPEzI" )
								),
		),

		// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
		"debug_mode"            => false ,
		
		"debug_file"            => "C:\\xampp\\htdocs\\ha\\130\\temp\\log.log", 
	);
