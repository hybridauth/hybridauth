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
    protected $scope = 'user_read channel_read';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.twitch.tv/kraken/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://api.twitch.tv/kraken/oauth2/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://api.twitch.tv/kraken/oauth2/token';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenName = 'oauth_token';

    /**
    * {@inheritdoc}
    */
    protected $apiDocumentation = 'https://dev.twitch.tv/docs/v5/guides/authentication/';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user');

        $data = new Data\Collection($response);

        if (! $data->exists('_id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('_id');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL    = $data->get('logo');
        $userProfile->email       = $data->get('email');
        $userProfile->description = strip_tags($data->get('bio'));

        $userProfile->profileURL = 'http://www.twitch.tv/' . $data->get('name');

        $userProfile->displayName = $userProfile->displayName ?: $data->get('name');

        return $userProfile;
    }
}
