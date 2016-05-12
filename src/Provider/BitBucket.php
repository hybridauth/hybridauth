<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2015 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 *
 */
class BitBucket extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    public $scope = 'user:email';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.bitbucket.org/2.0/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://bitbucket.org/site/oauth2/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://bitbucket.org/site/oauth2/access_token';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user');

        $data = new Data\Collection($response);

        if (! $data->exists('uuid')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('uuid');
        $userProfile->username    = $data->get('username');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->email       = $data->get('email');
        $userProfile->webSiteURL  = $data->get('website');
        $userProfile->region      = $data->get('location');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('username');

        if (empty($userProfile->email) && strpos($this->scope, 'user:email') !== false) {
            $userProfile = $this->requestUserEmail($userProfile);
        }

        return $userProfile;
    }

    /**
    *
    * https://developer.github.com/v3/users/emails/
    */
    protected function requestUserEmail($userProfile)
    {
        try {
            $response = $this->apiRequest('user/emails');

            foreach ($response as $idx => $item) {
                if (! empty($item->primary) && $item->primary == 1) {
                    $userProfile->email = $item->email;

                    if (! empty($item->verified) && $item->verified == 1) {
                        $userProfile->emailVerified = $userProfile->email;
                    }

                    break;
                }
            }
        } // user email is not mandatory so keep it quite
        catch (\Exception $e) {
        }

        return $userProfile;
    }
}
