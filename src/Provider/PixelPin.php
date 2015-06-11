<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2015 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 *
 */
class PixelPin extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://ws3.pixelpin.co.uk/index.php/api/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://login.pixelpin.co.uk/OAuth2/FLogin.aspx';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://ws3.pixelpin.co.uk/index.php/api/token';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenName = 'oauth_token';

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('userdata');

        $data = new Data\Collection($response);

        if (! $data->exists('id')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier    = $data->get('id');
        $userProfile->firstName     = $data->get('firstName');
        $userProfile->displayName   = $data->get('firstName');
        $userProfile->email         = $data->get('email');
        $userProfile->emailVerified = $data->get('email');

        return $userProfile;
    }
}
