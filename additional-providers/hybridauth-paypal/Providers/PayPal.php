<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_PayPal class 
 */
class Hybrid_Providers_PayPal extends Hybrid_Provider_Model_OpenID
{
	var $openidIdentifier = "https://www.paypal.com/webapps/auth/server";
	
	/**
	* finish login step 
	*/
	function loginFinish()
	{
	  parent::loginFinish();
	  $this->user->profile->emailVerified = $this->user->profile->email;
	}
}
