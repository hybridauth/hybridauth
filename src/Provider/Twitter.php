<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth1;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Data;
use Hybridauth\User;

/**
* Hybrid_Providers_Twitter provider adapter based on OAuth1 protocol
*/
final class Twitter extends OAuth1
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
    public function getUserProfile()
    {
        $response = $this->apiRequest('account/verify_credentials.json');

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->displayName = $data->get('screen_name');
        $userProfile->description = $data->get('description');
        $userProfile->firstName   = $data->get('name');
        $userProfile->webSiteURL  = $data->get('url');
        $userProfile->region      = $data->get('location');

        $userProfile->profileURL  = $data->exists('screen_name')       ? ('http://twitter.com/' . $data->get('screen_name'))         : '';
        $userProfile->photoURL    = $data->exists('profile_image_url') ? str_replace('_normal', '', $data->get('profile_image_url')) : '';

        return $userProfile;
    }

    /**
    * {@inheritdoc}
    */
    public function getUserContacts()
    {
        $contacts = [];

        $parameters = [ 'cursor' => '-1' ];

        $response = $this->apiRequest('friends/ids.json', 'GET', $parameters);

        $data = new Data\Collection($response);

        if (! $data->exists('ids')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        if ($data->filter('ids')->isEmpty()) {
            return $contacts;
        }

        // 75 id per time should be okey
        $contactsIds = array_chunk((array) $data->get('ids'), 75);

        foreach ($contactsIds as $chunk) {
            $parameters = [ 'user_id' => implode(',', $chunk) ];

            try {
                $response = $this->apiRequest('users/lookup.json', 'GET', $parameters);
                
                $data = (new Parser($response))->toCollection();
            } catch (Exception $e) {
                continue;
            }

            foreach ($data->all() as $item) {
                $contacts[] = $this->fetchUserContacts($item);
            }
        }

        return $contacts;
    }

    /**
    *
    */
    protected function fetchUserContacts($item)
    {
        $userContact = new User\Contact();

        $userContact->identifier  = $item->get('id');
        $userContact->displayName = $item->get('name');
        $userContact->photoURL    = $item->get('profile_image_url');
        $userContact->description = $item->get('description');

        $userContact->profileURL  = $item->exists('screen_name') ? ('http://twitter.com/' . $item->get('screen_name')) : '';

        return $userContact;
    }

    /**
    * {@inheritdoc}
    */
    public function setUserStatus($status)
    {
        if (is_array($status) && isset($status[ 'message' ]) && isset($status[ 'picture' ])) {
            // @fixme;
            return $this->apiRequest('statuses/update_with_media.json', 'POST', array( 'status' => $status[ 'message' ], 'media[]' => file_get_contents($status[ 'picture' ]) ));
        }
        
        $response = $this->apiRequest('statuses/update.json', 'POST', [ 'status' => $status ]);

        return $response;
    }

    /**
    * {@inheritdoc}
    */
    public function getUserActivity($stream)
    {
        $activities = [];

        $apiUrl = $stream == 'me' ? 'statuses/user_timeline.json' : 'statuses/home_timeline.json';

        $response = $this->apiRequest($apiUrl);

        $data = new Data\Collection($response);

        if ($data->isEmpty()) {
            return $activities;
        }

        foreach ($data->all() as $item) {
            $activities[] = $this->fetchUserActivity($item);
        }

        return $activities;
    }

    /**
    *
    */
    protected function fetchUserActivity($item)
    {
        $userActivity = new User\Activity();

        $userActivity->id   = $item->get('id');
        $userActivity->date = $item->get('created_at');
        $userActivity->text = $item->get('text');

        $userActivity->user->identifier   = $item->filter('user')->get('id');
        $userActivity->user->displayName  = $item->filter('user')->get('name');
        $userActivity->user->photoURL     = $item->filter('user')->get('profile_image_url');

        $userActivity->user->profileURL   = $item->filter('user')->get('screen_name') ? ('http://twitter.com/' . $item->filter('user')->get('screen_name')) : '';

        return $userActivity;
    }
}
