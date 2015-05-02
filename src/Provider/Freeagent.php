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
final class Freeagent extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.freeagent.com/v2/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://api.freeagent.com/v2/approve_app';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://api.freeagent.com/v2/token_endpoint';

    /**
    * {@inheritdoc}
    */
    protected function initialize()
    {
        parent::initialize();

        $this->apiRequestParameters = [
            'Authorization' => 'Bearer ' . $this->token("access_token")
        ];
    }

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/me');

        $data = new Data\Collection($response);

        if (! $data->exists('user')) {
            throw new UnexpectedValueException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $data = $data->get('user');

        $userProfile->identifier  = str_ireplace($this->apiBaseUrl .'users/', '', $data->get('url'));
        $userProfile->description = $data->get('role');
        $userProfile->email       = $data->get('email');
        $userProfile->firstName   = $data->get('first_name');
        $userProfile->lastName    = $data->get('last_name');
        $userProfile->displayName = trim($$data->get('first_name') . ' ' . $data->get('last_name'));

        return $userProfile;
    }
}
