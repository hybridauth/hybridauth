<?php
//!! planned to be replaced Y! openid by the oauth1 adapter soon

/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
* Hybrid_Providers_Yahoo provider adapter based on OAuth1 protocol 
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

