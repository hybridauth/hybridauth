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
 * LinkedIn OAuth2 provider adapter.
 */
class LinkedIn extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    public $scope = 'r_basicprofile r_emailaddress w_share';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.linkedin.com/v1/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.linkedin.com/uas/oauth2/authorization';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.linkedin.com/uas/oauth2/accessToken';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.linkedin.com/docs/oauth2';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $fields = [
            'id',
            'email-address',
            'first-name',
            'last-name',
            'headline',
            'location',
            'industry',
            'picture-url',
            'public-profile-url',
            'num-connections',
        ];

        $response = $this->apiRequest('people/~:(' . implode(',', $fields) . ')', 'GET', ['format' => 'json']);

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier    = $data->get('id');
        $userProfile->firstName     = $data->get('firstName');
        $userProfile->lastName      = $data->get('lastName');
        $userProfile->photoURL      = $data->get('pictureUrl');
        $userProfile->profileURL    = $data->get('publicProfileUrl');
        $userProfile->email         = $data->get('emailAddress');
        $userProfile->description   = $data->get('headline');
        $userProfile->country       = $data->filter('location')->get('name');

        $userProfile->emailVerified = $userProfile->email;

        $userProfile->displayName   = trim($userProfile->firstName . ' ' . $userProfile->lastName);

        $userProfile->data['connections'] = $data->get('numConnections');

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     *
     * @see https://developer.linkedin.com/docs/share-on-linkedin
     */
    public function setUserStatus($status)
    {
        $status = is_string($status) ? [ 'comment' => $status ] : $status;
        if (!isset($status['visibility'])) {
            $status['visibility']['code'] = 'anyone';
        }

        $headers = [
          'Content-Type' => 'application/json',
          'x-li-format' => 'json',
        ];

        $response = $this->apiRequest('people/~/shares?format=json', 'POST', $status, $headers);

        return $response;
    }
}
