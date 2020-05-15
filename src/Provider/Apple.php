<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

use Hybridauth\Adapter\OAuth2;

/**
 * Apple OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'     => [ 'private_key' => '', 'id' => '', 'team_id' => '', 'key_id' => '', 'secret' => '' ],
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Apple( $config );
 *
 *   $adapter->authenticate();
 *
 *   $accessToken = $adapter->getAccessToken();
 */
class Apple extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    public $scope = 'name email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://appleid.apple.com/auth/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://appleid.apple.com/auth/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://appleid.apple.com/auth/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://developer.apple.com/documentation/sign_in_with_apple';

    /**
    * {@inheritdoc}
    */
    protected function initialize(){

        parent::initialize();
        $this->AuthorizeUrlParameters['response_mode'] = 'form_post';
    }


    /**
     * {@inheritdoc}
     */
    public function getAccessToken()
    {
        $tokenNames = [
            'access_token',
            'id_token',
            'access_token_secret',
            'token_type',
            'refresh_token',
            'expires_in',
            'expires_at',
        ];

        $tokens = [];

        foreach ($tokenNames as $name) {
            if ($this->getStoredData($name)) {
                $tokens[ $name ] = $this->getStoredData($name);
            }
        }

        return $tokens;
    }

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

    }

}
