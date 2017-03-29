<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;

/**
 * Yahoo OpenID provider adapter.
 */
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
