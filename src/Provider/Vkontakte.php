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
 *   $adapter = new Hybridauth\Provider\Vkontakte($config);
 *   try {
 *       if (!$adapter->isConnected()) {
 *           $adapter->authenticate();
 *       }
 *       $userProfile = $adapter->getUserProfile();
 *   } catch (\Exception $e) {
 *       print $e->getMessage() ;
 *   }
 */
class Vkontakte extends OAuth2
{
    const API_VERSION = '5.101';
    
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.vk.com/method/';

    /**
     * {@inheritdoc}
     * @see https://vk.com/dev/authcode_flow_user
     */
    protected $authorizeUrl = 'https://oauth.vk.com/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://oauth.vk.com/access_token';

    /**
     * {@inheritdoc}
     * @todo Overwrite methods setUserStatus/getUserActivity(status), getUserPages/setPageStatus(pages), 
     *       getUserContacts(friends)
     */
    protected $scope = 'offline,email,status,pages,friends';
    
	/**
	 * {@inheritdoc}
     * As we using offline scope, expires_in contains 0 - the token is unlimited.
     * @see https://vk.com/dev/permissions
	 */
	public function hasAccessTokenExpired()
	{
		return ! $this->getStoredData('expires_at');
	}

    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $data = parent::validateAccessTokenExchange($response);

        // Need to store user_id, email as token for use later.
        $this->storeData('user_id', $data->get('user_id'));
        $this->storeData('email', $data->get('email'));
    }

    /**
     * {@inheritdoc}
     * @see https://vk.com/dev/users.get  
     */
    public function getUserProfile()
    {
        $photoField = 'photo_' . ($this->config->get('photo_size') ?: 'max_orig');
        $parameters = [
            'user_ids' => $this->getStoredData('user_id'),
            // Required fields: id, first_name, last_name, is_closed, can_access_closed
            'fields' => 'screen_name,sex,bdate,city,country,has_photo,' . $photoField,
            'v' => self::API_VERSION,
            'name_case' => 'nom',
            $this->accessTokenName => $this->getStoredData($this->accessTokenName),
        ];

        $response = $this->apiRequest('users.get', 'GET', $parameters);

        if (property_exists($response, 'error')) {
            throw new UnexpectedApiResponseException($response->error->error_msg);
        }

        $data = new Collection($response->response[0]);

        if (! $data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile;

        $userProfile->identifier  = $data->get('id');
        $userProfile->email       = $this->getStoredData('email');
        $userProfile->firstName   = $data->get('first_name');
        $userProfile->lastName    = $data->get('last_name');
        $userProfile->displayName = $data->get('screen_name');
        $userProfile->photoURL    = $data->get('has_photo') ? $data->get($photoField) : '';
        if ($data->get('bdate')) {
            list($userProfile->birthDay, $userProfile->birthMonth, $userProfile->birthYear) = explode('.', $data->get('bdate'));
        }
        if ($country = $data->get('country')) {
            $userProfile->country = $country['title'];
        }
        if ($city = $data->get('city')) {
            $userProfile->city = $city['title'];
        }
        
        $screen_name = 'https://vk.com/' . ($data->get('screen_name') ?: 'id' . $data->get('id'));
        $userProfile->profileURL = $screen_name;
        $userProfile->gender = $data->get('sex') == 1 ? 'female' : 'male';

        return $userProfile;
    }
}
