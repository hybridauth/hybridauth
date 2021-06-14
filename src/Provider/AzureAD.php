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
 * AzureAD provider
 */
class AzureAD extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    public $scope = 'openid profile email offline_access https://graph.microsoft.com/User.Read';

    /**
    * {@inheritdoc}
    */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'access_type' => 'offline'
        ];

        $this->tokenRefreshParameters += [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret
        ];
    }

    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);
        if ($collection->exists('id_token')) {
            $idToken = $collection->get('id_token');
            //get payload from id_token
            $parts = explode('.', $idToken);
            list($headb64, $payload) = $parts;
            // JWT token is base64url encoded
            $data = base64_decode(str_pad(strtr($payload, '-_', '+/'), strlen($payload) % 4, '=', STR_PAD_RIGHT));
            $this->storeData('user_data', $data);
        } else {
            throw new Exception('No id_token was found.');
        }
        return $collection;
    }

    public function getUserProfile()
    {
        $userData = $this->getStoredData('user_data');
        $user = json_decode($userData);
        $data = new Data\Collection($user);

        $userProfile = new User\Profile();
        $userProfile->identifier = $data->get('sub');
        $userProfile->displayName = $data->get('name') ?: $data->get('preferred_username');
        $userProfile->photoURL = $data->get('picture');
        $userProfile->email = $data->get('preferred_username');

        $userInfoUrl = "https://graph.microsoft.com/oidc/userinfo";
        if (!empty($userInfoUrl) && !isset(
            $userProfile->displayName,
            $userProfile->photoURL,
            $userProfile->email,
            $userProfile->data['groups']
        )) {
            $profile = new Data\Collection($this->apiRequest($userInfoUrl));
            if (empty($userProfile->displayName)) {
                $userProfile->displayName = $profile->get('name') ?: $profile->get('nickname');
            }
            if (empty($userProfile->photoURL)) {
                $userProfile->photoURL = $profile->get('picture') ?: $profile->get('avatar');
                if (preg_match('#<img.+src=["\'](.+?)["\']#', $userProfile->photoURL, $m)) {
                    $userProfile->photoURL = $m[1];
                }
            }
            if (empty($userProfile->email)) {
                $userProfile->email = $profile->get('preferred_username');
            }
        }

        return $userProfile;
    }
}
