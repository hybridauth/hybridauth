<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * By Sebastian Lasse - https://github.com/sebilasse
 */
class Instagram extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $scope = 'basic';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.instagram.com/v1/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://api.instagram.com/oauth/authorize/';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://api.instagram.com/oauth/access_token';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/self/');

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $data = $data->filter('id');

        $userProfile->identifier  = $data->get('id');
        $userProfile->description = $data->get('bio');
        $userProfile->photoURL    = $data->get('profile_picture');
        $userProfile->webSiteURL  = $data->get('website');
        $userProfile->displayName = $data->get('full_name');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('username');

        return $userProfile;
    }
}
