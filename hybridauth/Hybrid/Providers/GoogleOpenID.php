<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_Google OpenID based
 * 
 * Provided as a way to keep backward compatibility for Google OpenID based on HybridAuth <= 2.0.8 
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
 */

/**
 * To replace the default google adapter, we should use this config instead:
 *
 *	"Google" => array ( 
 *		"enabled" => true, 
 *		"wrapper" => array( "path" => "Providers/GoogleOpenID.php", "class" => "Hybrid_Providers_Google" )
 *	)
 */
class Hybrid_Providers_Google extends Hybrid_Provider_Model_OpenID
{
	var $openidIdentifier = "https://www.google.com/accounts/o8/id"; 

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
