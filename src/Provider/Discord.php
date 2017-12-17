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
 * Discord OAuth2 provider adapter.
 */
class Discord extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    public $scope = 'identify email';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://discordapp.com/api/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://discordapp.com/api/oauth2/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://discordapp.com/api/oauth2/token';

    /**
    * {@inheritdoc}
    */
    protected $apiDocumentation = 'https://discordapp.com/developers/docs/topics/oauth2';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/@me');

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->displayName = $data->get('username') ?: $data->get('login');
        $userProfile->email       = $data->get('email');

        if ($data->get('verified')) {
            $userProfile->emailVerified = $data->get('email');
        }

        if ($data->get('avatar')) {
            $userProfile->photoURL = 'https://cdn.discordapp.com/avatars/' . $data->get('id') . '/' . $data->get('avatar') . '.png';
        }

        return $userProfile;
    }
}
