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
 * OpenStreetMap OAuth2 provider adapter.
 */
class OpenStreetMap extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'read_prefs';
    
    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.openstreetmap.org/api/0.6/';
    
    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://www.openstreetmap.org/oauth2/authorize';
    
    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://www.openstreetmap.org/oauth2/token';

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user/details');

        $data = new Data\Collection($response);
        $userData = $data->filter('osm')->filter('user');

        if ($userData->isEmpty()) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $attributes = $data->filter('@attributes');

        $userProfile->identifier = $attributes->get('id');
        $userProfile->displayName = $attributes->get('display_name');
        $userProfile->photoURL = $userData->filter('img')->filter('@attributes')->get('href');
        $userProfile->description = $userData->get('description');

        return $userProfile;
    }
}
