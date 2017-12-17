<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth1;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Twitter provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback'  => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'      => [ 'key' => '', 'secret' => '' ], // OAuth1 uses 'key' not 'id'
 *       'authorize' => true
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Twitter( $config );
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $contacts = $adapter->getUserContacts(['screen_name' =>'andypiper']); // get those of @andypiper
 *       $activity = $adapter->getUserActivity('me');
 *   }
 *   catch( Exception $e ){
 *       echo $e->getMessage() ;
 *   }
 */
class Twitter extends OAuth1
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.twitter.com/1.1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://api.twitter.com/oauth/authenticate';

    /**
     * {@inheritdoc}
     */
    protected $requestTokenUrl = 'https://api.twitter.com/oauth/request_token';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.twitter.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://dev.twitter.com/web/sign-in/implementing';

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl($parameters = [])
    {
        if ($this->config->get('authorize') === true) {
            $this->authorizeUrl = 'https://api.twitter.com/oauth/authorize';
        }

        return parent::getAuthorizeUrl($parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('account/verify_credentials.json?include_email=true');

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier    = $data->get('id');
        $userProfile->displayName   = $data->get('screen_name');
        $userProfile->description   = $data->get('description');
        $userProfile->firstName     = $data->get('name');
        $userProfile->email         = $data->get('email');
        $userProfile->emailVerified = $data->get('email');
        $userProfile->webSiteURL    = $data->get('url');
        $userProfile->region        = $data->get('location');

        $userProfile->profileURL    = $data->exists('screen_name')
                                        ? ('http://twitter.com/' . $data->get('screen_name'))
                                        : '';

        $userProfile->photoURL      = $data->exists('profile_image_url')
                                        ? str_replace('_normal', '', $data->get('profile_image_url'))
                                        : '';

        $userProfile->data = [
          'followed_by' => $data->get('friends_count'),
          'follows' => $data->get('followers_count'),
        ];

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts($parameters = [])
    {
        $parameters = [ 'cursor' => '-1'] + $parameters;

        $response = $this->apiRequest('friends/ids.json', 'GET', $parameters);

        $data = new Data\Collection($response);

        if (! $data->exists('ids')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        if ($data->filter('ids')->isEmpty()) {
            return [];
        }

        $contacts = [];

        // 75 id per time should be okey
        $contactsIds = array_chunk((array) $data->get('ids'), 75);

        foreach ($contactsIds as $chunk) {
            $parameters = [ 'user_id' => implode(',', $chunk) ];

            try {
                $response = $this->apiRequest('users/lookup.json', 'GET', $parameters);

                if ($response && count($response)) {
                    foreach ($response as $item) {
                        $contacts[] = $this->fetchUserContact($item);
                    }
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $contacts;
    }

    /**
     *
     */
    protected function fetchUserContact($item)
    {
        $item = new Data\Collection($item);

        $userContact = new User\Contact();

        $userContact->identifier  = $item->get('id');
        $userContact->displayName = $item->get('name');
        $userContact->photoURL    = $item->get('profile_image_url');
        $userContact->description = $item->get('description');

        $userContact->profileURL  = $item->exists('screen_name')
                                        ? ('http://twitter.com/' . $item->get('screen_name'))
                                        : '';

        return $userContact;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserStatus($status)
    {
        if (is_string($status)) {
            $status = ['status' => $status];
        }

        // Prepare request parameters.
        $params = [];
        if (isset($status['status'])) {
            $params['status'] = $status['status'];
        }
        if (isset($status['picture'])) {
            $media = $this->apiRequest('https://upload.twitter.com/1.1/media/upload.json', 'POST', [
                'media' => base64_encode(file_get_contents($status['picture'])),
            ]);
            $params['media_ids'] = $media->media_id;
        }

        $response = $this->apiRequest('statuses/update.json', 'POST', $params);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream = 'me')
    {
        $apiUrl = ($stream == 'me')
                    ? 'statuses/user_timeline.json'
                    : 'statuses/home_timeline.json';

        $response = $this->apiRequest($apiUrl);

        if (!$response) {
            return [];
        }

        $activities = [];

        foreach ($response as $item) {
            $activities[] = $this->fetchUserActivity($item);
        }

        return $activities;
    }

    /**
     *
     */
    protected function fetchUserActivity($item)
    {
        $item = new Data\Collection($item);

        $userActivity = new User\Activity();

        $userActivity->id   = $item->get('id');
        $userActivity->date = $item->get('created_at');
        $userActivity->text = $item->get('text');

        $userActivity->user->identifier   = $item->filter('user')->get('id');
        $userActivity->user->displayName  = $item->filter('user')->get('name');
        $userActivity->user->photoURL     = $item->filter('user')->get('profile_image_url');

        $userActivity->user->profileURL   = $item->filter('user')->get('screen_name')
                                                ? ('http://twitter.com/' . $item->filter('user')->get('screen_name'))
                                                : '';

        return $userActivity;
    }
}
