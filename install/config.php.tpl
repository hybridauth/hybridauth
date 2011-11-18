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
//	HybridAuth Config file
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
		"base_url"       => "#GLOBAL_HYBRID_AUTH_URL_BASE#", 
 
		"providers"      => array (
			// openid
			"OpenID" => array ( // no keys required for OpenID based providers
					"enabled" => #OPENID_ADAPTER_STATUS#
			),

			// google
			"Google" => array ( 
					"enabled" => #GOOGLE_ADAPTER_STATUS# 
			),

			// yahoo
			"Yahoo"  => array ( 
					"enabled" => #YAHOO_ADAPTER_STATUS# 
			),

			// facebook
			"Facebook" => array ( 
					"enabled" => #FACEBOOK_ADAPTER_STATUS#,
					"keys"    => array ( "id" => "#FACEBOOK_APPLICATION_APP_ID#", "secret" => "#FACEBOOK_APPLICATION_SECRET#" ) 
			),

			// twitter 
			"Twitter" => array ( 
					"enabled" => #TWITTER_ADAPTER_STATUS#,
					"keys"    => array ( "key" => "#TWITTER_APPLICATION_KEY#", "secret" => "#TWITTER_APPLICATION_SECRET#" ) 
			),

			// myspace
			"MySpace" => array ( 
					"enabled" => #MYSPACE_ADAPTER_STATUS#,
					"keys"    => array ( "key" => "#MYSPACE_APPLICATION_KEY#", "secret" => "#MYSPACE_APPLICATION_SECRET#" ) 
			),

			// windows live
			"Live"    => array ( 
					"enabled" => #LIVE_ADAPTER_STATUS#,
					"keys"    => array ( "id" => "#LIVE_APPLICATION_KEY#", "secret" => "#LIVE_APPLICATION_SECRET#" ) 
			),

			// linkedin
			"LinkedIn" => array ( 
					"enabled" => #LINKEDIN_ADAPTER_STATUS#,
					"keys"    => array ( "key" => "#LINKEDIN_APPLICATION_KEY#", "secret" => "#LINKEDIN_APPLICATION_SECRET#" ) 
			),
		),

		// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
		"debug_mode"            => false,

		"debug_file"            => "",
	);
