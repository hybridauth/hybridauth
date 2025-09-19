<?php

/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * TwitchTV OAuth2 provider adapter.
 */
class TwitchTV extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'openid user:read:email';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://dev.twitch.tv/docs/authentication/';

    protected function configure()
    {
        parent::configure();

        $this->apiBaseUrl = 'https://id.twitch.tv/oauth2';
        $this->authorizeUrl = $this->apiBaseUrl . '/authorize';
        $this->accessTokenUrl = $this->apiBaseUrl . '/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->getStoredData('/userinfo');
        if (!$response) {
            $response = $this->apiRequest('/userinfo');
            $this->storeData('/userinfo', $response);
        }

        $data = new Data\Collection($response);

        if (!$data->exists('sub')) {
            $this->deleteStoredData('/userinfo');
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('sub');
        $userProfile->displayName = $data->get('preferred_username');
        $userProfile->email = $data->get('email');
        $userProfile->firstName = $data->get('name') ?? $data->get('nickname');
        $userProfile->emailVerified = $data->get('email_verified');

        $userProfile->photoURL = $data->get('picture');
        $userProfile->profileURL = $data->get('profile');

        return $userProfile;
    }
}
