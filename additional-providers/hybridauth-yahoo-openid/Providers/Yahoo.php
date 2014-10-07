<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_Yahoo OpenID based
 * 
 * Provided as a way to keep backward compatibility for Yahoo OpenID based on HybridAuth <= 2.1.0
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Yahoo.html
 */
class Hybrid_Providers_Yahoo extends Hybrid_Provider_Model_OpenID
{
	var $openidIdentifier = "https://open.login.yahooapis.com/openid20/www.yahoo.com/xrds"; 

	/**
	* finish login step 
	*/
	function loginFinish()
	{
		parent::loginFinish();

		$this->user->profile->emailVerified = $this->user->profile->email;

		// restore the user profile
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user", $this->user );
	}
}
