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
    protected $scope = 'user:read:email';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.twitch.tv/helix/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://id.twitch.tv/oauth2/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://id.twitch.tv/oauth2/token';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenName = 'access_token';

    /**
    * {@inheritdoc}
    */
    protected $apiDocumentation = 'https://dev.twitch.tv/docs/authentication/';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users');

        $data = new Data\Collection($response);

        if (! $data->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data_arr = $data->get('data');
        $data_arr = (array) $data_arr[0];

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data_arr['id'];
        $userProfile->displayName = $data_arr['display_name'];
        $userProfile->photoURL    = $data_arr['profile_image_url'];
        $userProfile->email       = $data_arr['email'];
        $userProfile->description = strip_tags($data_arr['description']);

        $userProfile->profileURL = 'https://www.twitch.tv/' . $data_arr['display_name'];

        return $userProfile;
    }
}
