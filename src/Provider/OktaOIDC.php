<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2021 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Okta OpenId Connect provider adapter.
 *
 * Example:
 *         'OktaOIDC' => [
 *             'enabled' => true,
 *             'domain' => 'yourself.okta.com',
 *             'authorization_server' => 'default',
 *             'keys' => [
 *                 'id' => 'client-id',
 *                 'secret' => 'client-secret'
 *             ],
 *             'refresh_existing_users' => true,
 *             'groups_key' => 'aclgroups',
 *             'groups_to_profiles' => [
 *                 'admin' => ['Administrator'],
 *                 'poweruser' => ['Portal user', 'Portal power user'],
 *             ],
 *         ]
 *
 */
class OktaOIDC extends OAuth2
{

    /**
     * {@inheritdoc}
     */
    public $scope = 'openid profile email';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        if (!$this->config->exists('domain')) {
            throw new InvalidApplicationCredentialsException('You must define a domain');
        }
        $domain = $this->config->get('domain');
        $authorizationServer = $this->config->exists('authorization_server') ? '/oauth2/' . $this->config->get('authorization_server') : '';

        $this->apiBaseUrl = 'https://' . $domain . $authorizationServer;
        $this->authorizeUrl = $this->apiBaseUrl . '/v1/authorize';
        $this->accessTokenUrl = $this->apiBaseUrl . '/v1/token';
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
        $response = $this->getStoredData('/v1/userinfo');
        if (!$response) {
            $response = $this->apiRequest('/v1/userinfo');
            $this->storeData('/v1/userinfo', $response);
        }

        $data = new Data\Collection($response);

        if (!$data->exists('sub')) {
            $this->deleteStoredData('/v1/userinfo');
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('sub');
        $userProfile->displayName = $data->get('preferred_username');
        $userProfile->email = $data->get('email');
        $userProfile->firstName = $data->get('given_name');
        $userProfile->lastName = $data->get('family_name');
        $userProfile->emailVerified = $data->get('email_verified');

        $groupsKey = $this->config->get('groups_key') ?? 'groups';
        if ($data->exists($groupsKey)) {
            $userProfile->data['groups'] = $data->get($groupsKey);
        }

        return $userProfile;
    }
}
