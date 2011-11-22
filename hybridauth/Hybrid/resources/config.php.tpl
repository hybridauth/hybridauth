<?php 
// ----------------------------------------------------------------------------------------
//	HybridAuth Config file: http://hybridauth.sourceforge.net/userguide/Configuration.html
// ----------------------------------------------------------------------------------------

return 
	array(
		"base_url" => "#GLOBAL_HYBRID_AUTH_URL_BASE#", 

		"providers" => array ( 
			// openid providers
			"OpenID" => array (
				"enabled" => #OPENID_ADAPTER_STATUS#
			),

			"Google" => array ( 
				"enabled" => #GOOGLE_ADAPTER_STATUS#,
				"keys"    => array ( "id" => "#GOOGLE_APPLICATION_APP_ID#", "secret" => "#GOOGLE_APPLICATION_SECRET#" ),
				"scope"   => ""
			),

			"Yahoo" => array ( 
				"enabled" => #YAHOO_ADAPTER_STATUS# 
			),

			"Facebook" => array ( 
				"enabled" => #FACEBOOK_ADAPTER_STATUS#,
				"keys"    => array ( "id" => "#FACEBOOK_APPLICATION_APP_ID#", "secret" => "#FACEBOOK_APPLICATION_SECRET#" ),
				"scope"   => ""
			),

			"Twitter" => array ( 
				"enabled" => #TWITTER_ADAPTER_STATUS#,
				"keys"    => array ( "key" => "#TWITTER_APPLICATION_KEY#", "secret" => "#TWITTER_APPLICATION_SECRET#" ) 
			),

			// windows live
			"Live" => array ( 
				"enabled" => #LIVE_ADAPTER_STATUS#,
				"keys"    => array ( "id" => "#LIVE_APPLICATION_APP_ID#", "secret" => "#LIVE_APPLICATION_SECRET#" ) 
			),

			"MySpace" => array ( 
				"enabled" => #MYSPACE_ADAPTER_STATUS#,
				"keys"    => array ( "key" => "#MYSPACE_APPLICATION_KEY#", "secret" => "#MYSPACE_APPLICATION_SECRET#" ) 
			),

			"LinkedIn" => array ( 
				"enabled" => #LINKEDIN_ADAPTER_STATUS#,
				"keys"    => array ( "key" => "#LINKEDIN_APPLICATION_KEY#", "secret" => "#LINKEDIN_APPLICATION_SECRET#" ) 
			),
		),

		// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
		"debug_mode" => false,

		"debug_file" => "",
	);
