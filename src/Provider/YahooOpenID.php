<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2015 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OpenID;
use Hybridauth\Adapter\Result\AuthResult;

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
        $result = parent::authenticateFinish();

        if ($result->getType() != AuthResult::RESULT_TYPE_SUCCESS) {
            return $result;
        }

        $userProfile = $this->storage->get($this->providerId . '.user');

        $userProfile->identifier    = $userProfile->email;
        $userProfile->emailVerified = $userProfile->email;

        // re store the user profile
        $this->storage->set($this->providerId . '.user', $userProfile);

        return new AuthResult(AuthResult::RESULT_TYPE_SUCCESS, TRUE);
    }
}
