<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2013, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_Latch provider adapter based on OpenID protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Latch.html
 */
class Hybrid_Providers_Latch extends Hybrid_Provider_Model_OpenID
{
	var $openidIdentifier = "http://auth.latch-app.com/OpenIdServer/user.jsp";
	
	/**
	* finish login step 
	*/
	function loginFinish()
	{
		parent::loginFinish();

		$this->user->profile->identifier  = $this->user->profile->email;
		$this->user->profile->emailVerified = $this->user->profile->email;

		// restore the user profile
		Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user", $this->user );
	}	
}
