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
 * Vkontakte OAuth2 provider adapter.
 */
class Vkontakte extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.vk.com/method/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'http://api.vk.com/oauth/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://api.vk.com/oauth/token';

    /**
    * Need to store user_id as token for later use
    *
    * {@inheritdoc}
    */
    protected function validateAccessTokenExchange($response)
    {
        $data = parent::validateAccessTokenExchange($response);

        $this->storeData('user_id', $data->get('user_id'));
        $this->storeData('email', $data->get('email'));
    }

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $parameters = [
            'uid'    => $this->getStoredData('user_id'),
            'fields' => 'first_name,last_name,nickname,screen_name,sex,' .
                            'bdate,timezone,photo_rec,photo_big,photo_max_orig'
        ];

        $response = $this->apiRequest('users.get', 'GET', $parameters);

        $data = new Data\Collection($response->response[0]);

        if (! $data->exists('uid')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('uid');
        $userProfile->email       = $this->getStoredData('email');
        $userProfile->firstName   = $data->get('first_name');
        $userProfile->lastName    = $data->get('last_name');
        $userProfile->displayName = $data->get('screen_name');
        $userProfile->photoURL    = $data->get('photo_max_orig');

        $userProfile->profileURL  = $data->get('screen_name')
                                        ? 'http://vk.com/' . $data->get('screen_name')
                                        : '';

        switch ($data->get('sex')) {
            case 1: $userProfile->gender = 'female'; break;
            case 2: $userProfile->gender =   'male'; break;
        }

        return $userProfile;
    }
}
