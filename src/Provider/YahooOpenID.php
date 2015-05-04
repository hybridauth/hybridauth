<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;

final class YahooOpenID extends OpenID
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

        $userProfile = $this->storage->get($this->providerId.'.user');

        $userProfile->identifier    = $userProfile->email;
        $userProfile->emailVerified = $userProfile->email;

        // re store the user profile
        $this->storage->set($this->providerId.'.user', $userProfile);
    }
}
