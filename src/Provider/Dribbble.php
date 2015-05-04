<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 *
 */
final class Dribbble  extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.dribbble.com/v1/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://dribbble.com/oauth/authorize';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://dribbble.com/oauth/token';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('user');

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->profileURL  = $data->get('html_url');
        $userProfile->photoURL    = $data->get('avatar_url');
        $userProfile->description = $data->get('bio');
        $userProfile->region      = $data->get('location');
        $userProfile->displayName = $data->get('name');
        
        $userProfile->displayName = $userProfile->displayName ? $userProfile->displayName : $data->get('username');

        $userProfile->webSiteURL  = $data->filter('links')->get('web');

        return $userProfile;
    }
}
