<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/
namespace Hybridauth\Provider;

require_once __DIR__ . '/../../vendor/autoload.php';

use \Firebase\JWT\JWT;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * Line OAuth2 provider adapter.
 */
class Line extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    public $scope = 'openid email profile';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://access.line.me/oauth2/v2.1';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://access.line.me/oauth2/v2.1/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.line.me/oauth2/v2.1/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developers.line.me/en/services/line-login';
    
    /**
     * {@inheritdoc}
     */
    protected function validateAccessTokenExchange($response)
    {
        $collection = parent::validateAccessTokenExchange($response);

        $this->storeData('id_token', $collection->get('id_token'));

        return $collection;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $jwtDecoded = JWT::decode($this->getStoredData('id_token'), $this->clientSecret, array('HS256'));

        $data = new Data\Collection($jwtDecoded);

        if (! $data->get('sub')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $data->get('sub');
        $userProfile->displayName = $data->get('name');
        $userProfile->photoURL = $data->get('picture');
        $userProfile->email = $data->get('email');

        return $userProfile;
    }
}
