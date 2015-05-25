<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2015 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_WarGaming
 */
class Hybrid_Providers_WarGaming extends Hybrid_Provider_Model_OpenID
{
  var $openidIdentifier = "http://ru.wargaming.net/id/";

  /**
   * finish login step
   */
  function loginFinish()
  {
    parent::loginFinish();

    $this->user->profile->profileURL = $this->user->profile->identifier;
    // https://ru.wargaming.net/id/5069690-Steel_Master/
    $this->user->profile->identifier = preg_replace( '/^[^0-9]+([0-9]+)-.+$/', '$1', $this->user->profile->identifier );

    // restore the user profile
    Hybrid_Auth::storage()->set( "hauth_session.{$this->providerId}.user", $this->user );
  }
}
