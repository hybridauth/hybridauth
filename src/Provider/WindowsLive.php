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
class WindowsLive extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    public $scope = 'wl.basic wl.contacts_emails wl.emails wl.signin wl.share wl.birthday';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://apis.live.net/v5.0/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://login.live.com/oauth20_authorize.srf';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://login.live.com/oauth20_authorize.srf';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me');

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier    = $data->get('id');
        $userProfile->displayName   = $data->get('name');
        $userProfile->firstName     = $data->get('first_name');
        $userProfile->lastName      = $data->get('last_name');
        $userProfile->gender        = $data->get('gender');
        $userProfile->profileURL    = $data->get('link');
        $userProfile->email         = $data->filter('emails')->get('preferred');
        $userProfile->emailVerified = $data->filter('emails')->get('account');
        $userProfile->birthDay      = $data->get('birth_day');
        $userProfile->birthMonth    = $data->get('birth_month');
        $userProfile->birthYear     = $data->get('birth_year');

        return $userProfile;
    }

    /**
    * {@inheritdoc}
    */
    protected function getUserContacts()
    {
        $contacts = [];

        $response = $this->apiRequest('me/contacts');

        if ($data->get('errcode')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $data = new Data\Collection($response);

        foreach ($data->filter('data')->all() as $idx => $entry) {
            $userContact = new User\Contact();

            $userContact->identifier  = $entry->get('id');
            $userContact->displayName = $entry->get('name');
            $userContact->email       = $entry->filter('emails')->get('preferred');

            $contacts[] = $userContact;
        }

        return $contacts;
    }
}
