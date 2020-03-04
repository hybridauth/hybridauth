<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data\Collection;
use Hybridauth\User\Profile;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Vkontakte provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'      => ['id' => '', 'secret' => ''],
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Vkontakte($config);
 *
 *   try {
 *       if (!$adapter->isConnected()) {
 *           $adapter->authenticate();
 *       }
 *
 *       $userProfile = $adapter->getUserProfile();
 *   }
 *   catch(\Exception $e) {
 *       print $e->getMessage() ;
 *   }
 */
class Vkontakte extends OAuth2
{

    const API_VERSION = '5.95';

    const URL = 'https://vk.com/';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.vk.com/method/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.vk.com/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.vk.com/oauth/token';

    /**
     * {@inheritdoc}
     */
    protected $scope = 'email,offline';

    /**
     * {@inheritdoc}
     */
    public function hasAccessTokenExpired()
    {
        // As we using offline scope, $expired will be false.
        $expired = $this->getStoredData('expires_in')
            ? $this->getStoredData('expires_at') <= time()
            : false;

        return $expired;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $data = parent::validateAccessTokenExchange($response);

        // Need to store user_id as token for later use.
        $this->storeData('user_id', $data->get('user_id'));
        $this->storeData('email', $data->get('email'));
    }

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $photoField = 'photo_' . ($this->config->get('photo_size') ?: 'max_orig');
        $parameters = [
            'user_ids' => $this->getStoredData('user_id'),
            // Required fields: id,first_name,last_name
            'fields' => 'screen_name,sex,has_photo,' . $photoField,
            'v' => static::API_VERSION,
            $this->accessTokenName => $this->getStoredData($this->accessTokenName),
        ];

        $response = $this->apiRequest('users.get', 'GET', $parameters);

        if (property_exists($response, 'error')) {
            throw new UnexpectedApiResponseException($response->error->error_msg);
        }

        $data = new Collection($response->response[0]);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->email       = $this->getStoredData('email');
        $userProfile->firstName   = $data->get('first_name');
        $userProfile->lastName    = $data->get('last_name');
        $userProfile->displayName = $data->get('screen_name');
        $userProfile->photoURL    = $data->get('has_photo') === 1 ? $data->get($photoField) : '';

        $screen_name = static::URL . ($data->get('screen_name') ?: 'id' . $data->get('id'));
        $userProfile->profileURL  = $screen_name;

        switch ($data->get('sex')) {
            case 1:
                $userProfile->gender = 'female';
                break;

            case 2:
                $userProfile->gender = 'male';
                break;
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $contacts = [];

        $parameters = [
            'user_id' => $this->getStoredData('user_id'),
            'fields' => 'uid,name,photo_200_orig',
            'v' => static::API_VERSION,
            $this->accessTokenName => $this->getStoredData($this->accessTokenName),
        ];

        $response = $this->apiRequest('friends.get', 'GET', $parameters);

        $data = new Data\Collection($response);
        if (!$data->exists('response')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }
        if (!$data->filter('response')->filter('items')->isEmpty()) {
            foreach ($data->filter('response')->filter('items')->toArray() as $item) {
                $contacts[] = $this->fetchUserContact($item);
            }
        }

        return $contacts;
    }

    /**
     * Parse the user contact.
     *
     * @param array $item
     *
     * @return \Hybridauth\User\Contact
     */
    protected function fetchUserContact($item)
    {
        $userContact = new User\Contact();
        $data = new Data\Collection($item);

        $userContact->identifier = $data->get('id');
        $userContact->displayName = sprintf('%s %s', $data->get('first_name'), $data->get('last_name'));
        $userContact->profileURL = static::URL . ($data->get('screen_name') ?: 'id' . $data->get('id'));
        $userContact->photoURL = $data->get('photo_200_orig');

        return $userContact;
    }
}
