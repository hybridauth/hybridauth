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
 * Pinterest OAuth2 provider adapter.
 */
class Pinterest extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'user_accounts:read';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.pinterest.com/v5/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.pinterest.com/oauth/';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.pinterest.com/v5/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.pinterest.com/docs/api/v5/';

    protected function initialize()
    {
        parent::initialize();

        unset(
            $this->tokenExchangeParameters['client_id'],
            $this->tokenExchangeParameters['client_secret']
        );

        $authorization = 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret);

        $this->tokenExchangeHeaders['Authorization'] = $authorization;
        $this->tokenRefreshHeaders['Authorization'] = $authorization;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user_account');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('id');
        $userProfile->description = $data->get('about');
        $userProfile->photoURL = $data->get('profile_image');
        $userProfile->displayName = $data->get('business_name') ?: $data->get('username');
        $userProfile->profileURL = "https://www.pinterest.com/{$data->get('username')}/";
        $userProfile->webSiteURL = $data->get('website_url');

        $userProfile->data = [
            'username' => $data->get('username'),
            'account_type' => $data->get('account_type'),
            'monthly_views' => $data->get('monthly_views'),
            'pin_count' => $data->get('pin_count'),
            'board_count' => $data->get('board_count'),
            'following_count' => $data->get('following_count'),
            'follower_count' => $data->get('follower_count'),
        ];

        return $userProfile;
    }
}
