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
 * Yahoo OAuth2 provider adapter.
 */
class Yahoo extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $scope = 'sdpp-w';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://social.yahooapis.com/v1/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://api.login.yahoo.com/oauth2/request_auth';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://api.login.yahoo.com/oauth2/get_token';

    /**
    * {@inheritdoc}
    */
    protected $apiDocumentation = 'https://developer.yahoo.com/oauth2/guide/';

    /**
    * Currently authenticated user
    */
    protected $userId = null;

    /**
    * {@inheritdoc}
    */
    protected function initialize()
    {
        parent::initialize();

        $this->tokenExchangeHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId .  ':' . $this->clientSecret)
        ];
    }

    /**
     * Returns current user id
     *
     * @return int
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws \Hybridauth\Exception\InvalidAccessTokenException
     * @throws \Hybridauth\Exception\UnexpectedApiResponseException
     */
    protected function getCurrentUserId()
    {
        if ($this->userId) {
            return $this->userId;
        }

        $response = $this->apiRequest('me/guid', 'GET', [ 'format' => 'json']);

        $data = new Data\Collection($response);

        if (! $data->filter('guid')->exists('value')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        return $this->userId =  $data->filter('guid')->get('value');
    }

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        // Retrieve current user guid if needed
        $this->getCurrentUserId();

        $response = $this->apiRequest('user/'  . $this->userId . '/profile', 'GET', [ 'format' => 'json']);

        $data = new Data\Collection($response);

        if (! $data->exists('profile')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $data = $data->filter('profile');

        $userProfile->identifier  = $data->get('guid');
        $userProfile->firstName   = $data->get('givenName');
        $userProfile->lastName    = $data->get('familyName');
        $userProfile->displayName = $data->get('nickname');
        $userProfile->photoURL    = $data->filter('image')->get('imageUrl');
        $userProfile->profileURL  = $data->get('profileUrl');
        $userProfile->language    = $data->get('lang');
        $userProfile->address     = $data->get('location');

        if ('F' == $data->get('gender')) {
            $userProfile->gender = 'female';
        } elseif ('M' == $data->get('gender')) {
            $userProfile->gender = 'male';
        }

        // E-mail is returned only with sdpp-w scope ( Read/Write (Public and Private) )
        foreach ($data->filter('emails')->toArray() as $item) {
            $item = new Data\Collection($item);

            if ($item->get('primary')) {
                $userProfile->email = $item->get('handle');
                $userProfile->emailVerified = $item->get('handle');
            }
        }

        return $userProfile;
    }
}
