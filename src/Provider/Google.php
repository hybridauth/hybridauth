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
 * Google OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'     => [ 'id' => '', 'secret' => '' ],
 *       'scope'    => 'profile https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.profile.emails.read',
 *
 *        // google's custom auth url params
 *       'authorize_url_parameters' => [
 *              'approval_prompt' => 'force', // to pass only when you need to acquire a new refresh token.
 *              'access_type'     => ..,      // is set to 'offline' by default
 *              'hd'              => ..,
 *              'state'           => ..,
 *              // etc.
 *       ]
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Google( $config );
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *       $contacts = $adapter->getUserContacts(['max-results' => 75]);
 *   }
 *   catch( Exception $e ){
 *       echo $e->getMessage() ;
 *   }
 */
class Google extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    public $scope = 'profile https://www.googleapis.com/auth/plus.profile.emails.read';

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://www.googleapis.com/plus/v1/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://accounts.google.com/o/oauth2/auth';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://accounts.google.com/o/oauth2/token';

    /**
    * {@inheritdoc}
    */
    protected $apiDocumentation = 'https://developers.google.com/identity/protocols/OAuth2';

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

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('people/me');

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->firstName   = $data->filter('name')->get('givenName');
        $userProfile->lastName    = $data->filter('name')->get('familyName');
        $userProfile->displayName = $data->get('displayName');
        $userProfile->photoURL    = $data->get('image');
        $userProfile->profileURL  = $data->get('url');
        $userProfile->description = $data->get('aboutMe');
        $userProfile->gender      = $data->get('gender');
        $userProfile->language    = $data->get('language');
        $userProfile->email       = $data->get('email');
        $userProfile->phone       = $data->get('phone');
        $userProfile->country     = $data->get('country');
        $userProfile->region      = $data->get('region');
        $userProfile->zip         = $data->get('zip');

        $userProfile->emailVerified = $data->get('verified') ? $userProfile->email : '';

        if ($data->filter('image')->exists('url')) {
            $userProfile->photoURL = substr($data->filter('image')->get('url'), 0, -2) . 150;
        }

        if (! $userProfile->email && $data->exists('emails')) {
            $userProfile = $this->fetchUserEmail($userProfile, $data);
        }

        if (! $userProfile->profileURL && $data->exists('urls')) {
            $userProfile = $this->fetchUserProfileUrl($userProfile, $data);
        }

        if (! $userProfile->profileURL && $data->exists('urls')) {
            $userProfile = $this->fetchBirthday($userProfile, $data->get('birthday'));
        }

        return $userProfile;
    }

    /**
    * Fetch user email
    */
    protected function fetchUserEmail($userProfile, $data)
    {
        foreach ($data->get('emails') as $email) {
            if ('account' == $email->type) {
                $userProfile->email         = $email->value;
                $userProfile->emailVerified = $email->value;

                break;
            }
        }

        return $userProfile;
    }

    /**
    * Fetch user profile url
    */
    protected function fetchUserProfileUrl($userProfile, $data)
    {
        foreach ($data->get('urls') as $url) {
            if ($url->get('primary')) {
                $userProfile->webSiteURL = $url->get('value');

                break;
            }
        }

        return $userProfile;
    }

    /**
    * Fetch use birthday
    */
    protected function fetchBirthday($userProfile, $birthday)
    {
        $result = (new Data\Parser())->parseBirthday($birthday, '-');

        $userProfile->birthDay   = (int) $result[0];
        $userProfile->birthMonth = (int) $result[1];
        $userProfile->birthYear  = (int) $result[2];

        return $userProfile;
    }

    /**
    * {@inheritdoc}
    */
    public function getUserContacts($parameters = [])
    {
        $parameters = ['max-results' => 500] + $parameters;

        // Google Gmail and Android contacts
        if (false !== strpos($this->scope, '/m8/feeds/')) {
            return $this->getGmailContacts($parameters);
        }

        // Google social contacts
        if (false !== strpos($this->scope, '/auth/plus.login')) {
            return $this->getGplusContacts($parameters);
        }
    }

    /**
    * Retrieve Gmail contacts
    */
    protected function getGmailContacts($parameters = [])
    {
        $url = 'https://www.google.com/m8/feeds/contacts/default/full?'
                    . http_build_query(array_replace([ 'alt' => 'json', 'v' => '3.0' ], (array)$parameters));

        $response = $this->apiRequest($url);

        if (! $response) {
            return [];
        }

        $contacts = [];

        if (isset($response->feed->entry)) {
            foreach ($response->feed->entry as $idx => $entry) {
                $uc = new User\Contact();

                $uc->email = isset($entry->{'gd$email'}[0]->address)
                            ? (string) $entry->{'gd$email'}[0]->address
                            : '';

                $uc->displayName = isset($entry->title->{'$t'}) ? (string) $entry->title->{'$t'} : '';
                $uc->identifier  = ($uc->email != '') ? $uc->email : '';
                $uc->description = '';

                if (property_exists($response, 'website')) {
                    if (is_array($response->website)) {
                        foreach ($response->website as $w) {
                            if ($w->primary == true) {
                                $uc->webSiteURL = $w->value;
                            }
                        }
                    } else {
                        $uc->webSiteURL = $response->website->value;
                    }
                } else {
                    $uc->webSiteURL = '';
                }

                $contacts[] = $uc;
            }
        }

        return $contacts;
    }

    /**
    * Retrieve Google plus contacts
    */
    protected function getGplusContacts($parameters = [])
    {
        $contacts = [];

        $url = 'https://www.googleapis.com/plus/v1/people/me/people/visible?'
                    . http_build_query($parameters);

        $response = $this->apiRequest($url);

        $data = new Data\Collection($response);

        foreach ($data->get('items') as $item) {
            $userContact = new User\Contact();

            $userContact->identifier  = $item->get('id');
            $userContact->email       = $item->get('email');
            $userContact->displayName = $item->get('displayName');
            $userContact->description = $item->get('objectType');
            $userContact->photoURL    = $item->filter('image')->get('url');
            $userContact->profileURL  = $item->get('url');

            $contacts[] = $userContact;
        }

        return $contacts;
    }
}
