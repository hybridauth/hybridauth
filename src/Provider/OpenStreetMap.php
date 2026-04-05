<?php
/*!
* HybridAuth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth1;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * OpenStreetMap OAuth1 provider adapter.
 */
class OpenStreetMap extends OAuth1
{
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.openstreetmap.org/api/0.6/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.openstreetmap.org/oauth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $requestTokenUrl = 'https://www.openstreetmap.org/oauth/request_token';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.openstreetmap.org/oauth/access_token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://wiki.openstreetmap.org/wiki/OAuth';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user/details');

        $data = new Data\Collection($response);
        $user = $data->get('user');

        $userProfile = new User\Profile();

        $userProfile->identifier    = (string) $user['id'];
        $userProfile->displayName   = (string) $user['display_name'];
        $userProfile->description   = (string) $user->get('description');
        $userProfile->photoURL      = (string) $user->get('img')['href'];
        $userProfile->profileURL    = 'https://www.openstreetmap.org/user/' . $userProfile->displayName;

        return $userProfile;
    }
}
