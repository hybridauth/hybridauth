<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Hybrid_Providers_Facebook provider adapter based on OAuth2 protocol
 *
 * ! This is an attempt to replace FACEBOOK SDK.
 * ! Methods tested so far: getUserProfile, getUserContacts, getUserActivity
 */
final class Facebook extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'email, public_profile, user_friends';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.facebook.com/v2.2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.facebook.com/dialog/oauth';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://graph.facebook.com/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile($callback = null)
    {
        $response = $this->apiRequest('me');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->displayName = $data->get('name');
        $userProfile->firstName   = $data->get('first_name');
        $userProfile->lastName    = $data->get('last_name');
        $userProfile->profileURL  = $data->get('link');
        $userProfile->webSiteURL  = $data->get('website');
        $userProfile->gender      = $data->get('gender');
        $userProfile->language    = $data->get('locale');
        $userProfile->description = $data->get('about');
        $userProfile->email       = $data->get('email');

        $userProfile->region = $data->filter('hometown')->get('name');

        $userProfile->photoURL = $this->apiBaseUrl.$userProfile->identifier."/picture?width=150&height=150";

        $userProfile->emailVerified = $data->get('verified') == 1 ? $userProfile->email : '';

        $userProfile = $this->fetchUserRegion($userProfile, $userProfile);

        $userProfile = $this->fetchBirthday($userProfile, $data->get('birthday'));

        return $userProfile;
    }

    /**
     *
     */
    protected function fetchUserRegion($userProfile)
    {
        if (!empty($userProfile->region)) {
            $regionArr = explode(',', $userProfile->region);

            if (count($regionArr) > 1) {
                $userProfile->city    = trim($regionArr[0]);
                $userProfile->country = trim($regionArr[1]);
            }
        }

        return $userProfile;
    }

    /**
     *
     */
    protected function fetchBirthday($userProfile, $birthday)
    {
        $result = (new Data\Parser())->parseBirthday($birthday, '/');

        $userProfile->birthDay   = (int)$result[0];
        $userProfile->birthMonth = (int)$result[1];
        $userProfile->birthYear  = (int)$result[2];

        return $userProfile;
    }

    /**
     * /v2.0/me/friends only returns the user's friends who also use the app.
     * In the cases where you want to let people tag their friends in stories published by your app,
     * you can use the Taggable Friends API.
     *
     * https://developers.facebook.com/docs/apps/faq#unable_full_friend_list
     */
    public function getUserContacts()
    {
        // $apiUrl = 'me/friends?fields=link,name';
        $contacts = [];

        // @fixme: delete this line. I'm using graph api v1 just for tests.
        $apiUrl = 'https://graph.facebook.com/me/friends?fields=link,name';

        do {
            $response = $this->apiRequest($apiUrl);

            $data = new Data\Collection($response);

            if (!$data->exists('data')) {
                throw new UnexpectedValueException('Provider API returned an unexpected response.');
            }

            if ($data->filter('data')->isEmpty()) {
                $pagedList = false;

                continue;
            }

            foreach ($data->filter('data')->all() as $item) {
                $contacts[] = $this->fetchUserContacts($item);
            }

            if ($data->filter('paging')->exists('next')) {
                $apiUrl = $data->filter('paging')->get('next');

                $pagedList = true;
            } else {
                $pagedList = false;
            }
        } while ($pagedList);

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

        $userContact->profileURL =
            $item->exists('link') ? $item->get('link') :
                'https://www.facebook.com/profile.php?id='.$userContact->identifier;

        $userContact->photoURL = $this->apiBaseUrl.$userContact->identifier."/picture?width=150&height=150";

        return $userContact;
    }

    /**
     * {@inheritdoc}
     */
    public function setUserStatus($status)
    {
        $status = is_string($status) ? ['message' => $status] : $status;

        $response = $this->apiRequest('/me/feed', 'POST', $status);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream)
    {
        $activities = [];

        $apiUrl = $stream == 'me' ? '/me/feed' : '/me/home';

        $response = $this->apiRequest($apiUrl);

        $data = new Data\Collection($response);

        if (!$data->exists('data')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        if ($data->filter('data')->isEmpty()) {
            return $activities;
        }

        foreach ($data->filter('data')->all() as $item) {
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
        $userActivity->date = $item->get('created_time');

        if ('video' == $item->get('type') || 'link' == $item->get('type')) {
            $userActivity->text = $item->get('link');
        }

        if (empty($userActivity->text) && $item->exists('story')) {
            $userActivity->text = $item->get('link');
        }

        if (empty($userActivity->text) && $item->exists('message')) {
            $userActivity->text = $item->get('message');
        }

        if (!empty($userActivity->text)) {
            $userActivity->user->identifier  = $item->filter('from')->get('id');
            $userActivity->user->displayName = $item->get('name');

            $userActivity->user->profileURL =
                'https://www.facebook.com/profile.php?id='.$userActivity->user->identifier;

            $userActivity->user->photoURL =
                $this->apiBaseUrl.$userActivity->user->identifier."/picture?width=150&height=150";
        }

        return $userActivity;
    }
}
