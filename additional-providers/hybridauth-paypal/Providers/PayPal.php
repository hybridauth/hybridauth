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

require_once Hybrid_Auth::$config["path_providers"] . "/OpenID.php"; 

/**
 * Hybrid_Providers_PayPal class 
 */
class Hybrid_Providers_PayPal extends Hybrid_Providers_OpenID
{
	var $openidIdentifier = "https://www.paypal.com/webapps/auth/server"; 
}
