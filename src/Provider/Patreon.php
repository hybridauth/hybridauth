<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2020 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User\Profile;
use Hybridauth\Data\Collection;

/**
 * Patreon OAuth2 provider adapter.
 */
class Patreon extends OAuth2
{

    /**
     * {@inheritdoc}
     */
    public $scope = 'identity identity[email]';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://www.patreon.com/api';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.patreon.com/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.patreon.com/api/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->tokenRefreshParameters += [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('oauth2/v2/identity', 'GET', [
            'fields[user]' => 'created,first_name,last_name,email,full_name,is_email_verified,thumb_url,url',
        ]);

        $collection = new Collection($response);
        if (!$collection->exists('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $data = $collection->filter('data');
        $attributes = $data->filter('attributes');

        $userProfile->identifier = $data->get('id');
        $userProfile->email = $attributes->get('email');
        $userProfile->firstName = $attributes->get('first_name');
        $userProfile->lastName = $attributes->get('last_name');
        $userProfile->displayName = $attributes->get('full_name') ?: $data->get('id');
        $userProfile->photoURL = $attributes->get('thumb_url');
        $userProfile->profileURL = $attributes->get('url');

        $userProfile->emailVerified = $attributes->get('is_email_verified') ? $userProfile->email : '';

        return $userProfile;
    }
}
