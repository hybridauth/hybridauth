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
 * StackExchange OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'     => [ 'id' => '', 'secret' => '' ],
 *       'site'     => 'stackoverflow'
 *       'api_key'  => '...' // that thing to receive a higher request quota.
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\StackExchange( $config );
 *
 *   $adapter->authenticate();
 *
 *   $userProfile = $adapter->getUserProfile();
 */
class StackExchange extends OAuth2
{
    /**
    * {@inheritdoc}
    */
    protected $scope = null;

    /**
    * {@inheritdoc}
    */
    protected $apiBaseUrl = 'https://api.stackexchange.com/2.2/';

    /**
    * {@inheritdoc}
    */
    protected $authorizeUrl = 'https://stackexchange.com/oauth';

    /**
    * {@inheritdoc}
    */
    protected $accessTokenUrl = 'https://stackexchange.com/oauth/access_token';

    /**
    * {@inheritdoc}
    */
    protected $apiDocumentation = 'https://api.stackexchange.com/docs/authentication';

    /**
    * {@inheritdoc}
    */
    protected function initialize()
    {
        parent::initialize();

        $apiKey = $this->config->get('api_key');

        $this->apiRequestParameters = [ 'key' => $apiKey];
    }

    /**
    * {@inheritdoc}
    */
    public function getUserProfile()
    {
        $site = $this->config->get('site');

        $response = $this->apiRequest('me?site=' . $site);

        if (! $response || !isset($response->items) || !isset($response->items[0])) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $data = new Data\Collection($response->items[0]);

        $userProfile = new User\Profile();

        $userProfile->identifier  = $data->get('id');
        $userProfile->displayName = $data->get('display_name');
        $userProfile->photoURL    = $data->get('profile_image');
        $userProfile->profileURL  = $data->get('link');
        $userProfile->region      = $data->get('location');
        $userProfile->age         = $data->get('age');

        return $userProfile;
    }
}
