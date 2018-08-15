<?php
/*!
 * Hybridauth
 * https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
 *  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
 */

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\User;

/**
 * Microsoft Graph provider adapter.
 */
class MicrosoftGraph extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    public $scope = 'openid user.read contacts.read';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://graph.microsoft.com/v1.0/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.microsoft.com/en-us/graph/docs/concepts/php';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('me');

        $data = new Data\Collection($response);

        if (!$data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier    = $data->get('id');
        $userProfile->displayName   = $data->get('displayName');
        $userProfile->firstName     = $data->get('givenName');
        $userProfile->lastName      = $data->get('surname');
        $userProfile->email         = $data->get('mail');
        $userProfile->language      = $data->get('preferredLanguage');

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        $apiUrl   = 'me/contacts?$top=50';
        $contacts = [];

        do {
            $response = $this->apiRequest($apiUrl);
            $data     = new Data\Collection($response);
            if (!$data->exists('value')) {
                throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
            }
            foreach ($data->filter('value')->toArray() as $entry) {
                $entry = new Data\Collection($entry);
                $userContact              = new User\Contact();
                $userContact->identifier  = $entry->get('id');
                $userContact->displayName = $entry->get('displayName');
                if (!empty($entry->get('emailAddresses'))) {
                    $userContact->email = $entry->get('emailAddresses')[0]->address;
                }
                // only add to collection if we have usefull data
                if (!empty($userContact->displayName) || !empty($userContact->email)) {
                    $contacts[] = $userContact;
                }
            }

            if ($data->exists('@odata.nextLink')) {
                $apiUrl = $data->get('@odata.nextLink');

                $pagedList = true;
            } else {
                $pagedList = false;
            }
        } while ($pagedList);

        return $contacts;
    }
}
