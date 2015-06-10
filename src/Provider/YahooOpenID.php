<?php
/*!
* HybridAuth
* https://hybridauth.github.io | http://github.com/hybridauth/hybridauth
* (c) 2015 HybridAuth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;

class YahooOpenID extends OpenID
{
    /**
    * {@inheritdoc}
    */
    protected $openidIdentifier = 'https://open.login.yahooapis.com/openid20/www.yahoo.com/xrds';

    /**
    * {@inheritdoc}
    */
    public function authenticateFinish()
    {
        parent::authenticateFinish();

        $userProfile = $this->storage->get($this->providerId . '.user');

        $userProfile->identifier    = $userProfile->email;
        $userProfile->emailVerified = $userProfile->email;

        // re store the user profile
        $this->storage->set($this->providerId . '.user', $userProfile);
    }
}
