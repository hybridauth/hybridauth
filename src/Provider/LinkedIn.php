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
class LinkedIn extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    public $scope = 'r_basicprofile r_emailaddress r_contactinfo';

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
    protected function initialize()
    {
        parent::initialize();

        $this->apiRequestHeaders = [
            'Authorization' => 'Bearer ' . $this->token('access_token')
        ];
    }

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $fields = [
            'id', 'email-address', 'first-name', 'last-name', 'headline','location', 'industry',
            'picture-url', 'public-profile-url',
        ];

        $response = $this->apiRequest('people/~:(' . implode(',', $this->fields) . ')?format=json');

        $data = new Data\Collection($response);

        if (! $data->exists('ID')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('ID');
        $userProfile->firstName   = $data->get('firstName');
        $userProfile->lastName    = $data->get('lastName');
        $userProfile->photoURL    = $data->get('pictureUrl');
        $userProfile->profileURL  = $data->get('publicProfileUrl');
        $userProfile->email       = $data->get('headline');
        $userProfile->bio         = $data->get('language');

        $userProfile->displayName = trim($userProfile->firstName . ' ' . $userProfile->lastName);

        return $userProfile;
    }
}
