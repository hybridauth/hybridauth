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
 *
 */
class Mailru extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'http://www.appsmail.ru/platform/api';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://connect.mail.ru/oauth/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://connect.mail.ru/oauth/token';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenName = 'session_key';

    /**
    * Mailru requires extra signature when requesting protected resources
    *
    * Omit session_key from url. parent::apiRequest() will append the access token anyway.
    *
    * {@inheritdoc}
    */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [])
    {
        $signature = md5('client_id=' . $this->clientId . 'format=jsonmethod=' .
                            $url . 'secure=1session_key='. $this->token('access_token')
                                . $this->clientSecret);

        $url = 'format=json&client_id=' . $this->clientId . '&method=' . $url . '&secure=1&sig=' .$signature;

        return parent::apiRequest($url, $method, $parameters, $headers);
    }

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users.getInfo');

        $data = new Data\Collection($response[0]);

        if (! $data->exists('uid')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier    = $data->get('uid');
        $userProfile->firstName     = $data->get('first_name');
        $userProfile->lastName      = $data->get('last_name');
        $userProfile->displayName   = $data->get('nick');
        $userProfile->photoURL      = $data->get('pic');
        $userProfile->profileURL    = $data->get('link');
        $userProfile->gender        = $data->get('sex');
        $userProfile->email         = $data->get('email');
        $userProfile->emailVerified = $data->get('email');

        return $userProfile;
    }
}
