<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Adapter\OAuth2;
use Hybridauth\Data;
use Hybridauth\User;

use CoderCat\JWKToPEM\JWKConverter;
use \Firebase\JWT\JWT;


/**
 * Apple OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys'     => [ 'id' => '', 'secret' => '' ],
 *       'scope'    => 'name email',
 *
 *        // Apple's custom auth url params
 *       'authorize_url_parameters' => [
 *              'response_mode' => 'form_post', // query, fragment, form_post. form_post is always used if scope is defined.
 *              // etc.
 *       ]
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\Apple( $config );
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $tokens = $adapter->getAccessToken();
 *       $response = $adapter->setUserStatus("Hybridauth test message..");
 *   }
 *   catch( Exception $e ){
 *       echo $e->getMessage() ;
 *   }
 *
 * requires require firebase/php-jwt: composer require firebase/php-jwt
 *
 * @see https://github.com/sputnik73/hybridauth-sign-in-with-apple
 * @see https://developer.apple.com/documentation/sign_in_with_apple/sign_in_with_apple_rest_api
 */
class Apple extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'name email';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = null; // No API available

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
    protected $apiDocumentation = 'https://developer.apple.com/documentation/sign_in_with_apple/sign_in_with_apple_rest_api';

    /**
     * {@inheritdoc}
     * The Sign in with Apple servers require percent encoding (or URL encoding)
     * for its query parameters. If you are using the Sign in with Apple REST API,
     * you must provide values with encoded spaces (`%20`) instead of plus (`+`) signs.
     */
    protected $AuthorizeUrlParametersEncType = PHP_QUERY_RFC3986;

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->AuthorizeUrlParameters += [
            'response_mode' => 'form_post'
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function exchangeCodeForAccessToken($code)
    {
        $this->tokenExchangeParameters['client_secret'] = $this->getSecret();
        return parent::exchangeCodeForAccessToken($code);
    }

    protected function validateAccessTokenExchange($response)
    {
        $data = (new Data\Parser())->parse($response);

        $collection = new Data\Collection($data);

        if (!$collection->exists('id_token')) {
            throw new InvalidAccessTokenException(
                'Provider returned an invalid access_token: ' . htmlentities($response)
            );
        }

        $json = file_get_contents('https://appleid.apple.com/auth/keys');
        $public_keys = json_decode($json, true);

        $jwkConverter = new JWKConverter();
        $pem = $jwkConverter->toPEM($public_keys['keys'][0]);

        $token = JWT::decode($collection->get('id_token'), $pem, ['RS256']);
        $this->storeData('token_payload', $token);

        parent::validateAccessTokenExchange($response);
    }

    public function getUserProfile()
    {
        if (empty($tokenPayload = $this->getStoredData('token_payload'))) {
            return false;
        }

        $token = new Data\Collection($tokenPayload);

        if (!$token->exists('sub')) {
            throw new UnexpectedValueException('Missing token payload.');
        }

        $userProfile = new User\Profile();
        $userProfile->identifier = $token->get('sub');
        $userProfile->email = $token->get('email');

        if (!empty($_REQUEST['user'])) {
            $objUser = json_decode($_REQUEST['user']);
            $user = new Data\Collection($objUser);

            $name = $user->get('name');
            $userProfile->firstName = $name->firstName;
            $userProfile->lastName = $name->lastName;
            $userProfile->displayName = join(' ', array($userProfile->firstName,
                $userProfile->lastName));
        }

        return $userProfile;
    }

    /**
     * @return string secret token
     */
    private function getSecret()
    {
        // Your 10-character Team ID
        if (!$team_id = $this->config->filter('keys')->get('team_id')) {
            throw new InvalidApplicationCredentialsException(
                'Your team id is required generate the JWS token.'
            );
        }

        // Your Services ID, e.g. com.aaronparecki.services
        if (!$client_id = $this->clientId) {
            throw new InvalidApplicationCredentialsException(
                'Your client id is required generate the JWS token.'
            );
        }

        // Find the 10-char Key ID value from the portal
        if (!$key_id = $this->config->filter('keys')->get('key_id')) {
            throw new InvalidApplicationCredentialsException(
                'Your key id is required generate the JWS token.'
            );
        }

        // Save your private key from Apple in a file called `key.txt`
        if (!$key_file = $this->config->filter('keys')->get('key_file')) {
            throw new InvalidApplicationCredentialsException(
                'Your key file is required generate the JWS token.'
            );
        }

        if (!file_exists($key_file)) {
            throw new InvalidApplicationCredentialsException(
                "Your key file $key_file does not exist."
            );
        }

        $key = file_get_contents($key_file);

        $data = [
            'iat' => time(),
            'exp' => time() + 86400 * 180,
            'iss' => $team_id,
            'aud' => 'https://appleid.apple.com',
            'sub' => $client_id
        ];

        $secret = JWT::encode($data, $key, 'ES256', $key_id);

        return $secret;
    }
}
