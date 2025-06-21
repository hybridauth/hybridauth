<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2025 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Provider;

use Hybridauth\Adapter\OAuth2;
use Hybridauth\Exception\UnexpectedApiResponseException;
use Hybridauth\Data;
use Hybridauth\User;

/**
 * X (Twitter) OAuth2 provider adapter.
 *
 * Example:
 *
 *   $config = [
 *       'callback' => Hybridauth\HttpClient\Util::getCurrentUrl(),
 *       'keys' => ['id' => '', 'secret' => ''],
 *       'scope' => 'tweet.read users.read users.email offline.access',
 *   ];
 *
 *   $adapter = new Hybridauth\Provider\X($config);
 *
 *   try {
 *       $adapter->authenticate();
 *
 *       $userProfile = $adapter->getUserProfile();
 *       $tokens = $adapter->getAccessToken();
 *   } catch (\Exception $e) {
 *       echo $e->getMessage();
 *   }
 */
class X extends OAuth2
{
    /**
     * {@inheritdoc}
     */
    protected $scope = 'tweet.read users.read users.email offline.access';

    /**
     * {@inheritdoc}
     */
    protected $apiBaseUrl = 'https://api.x.com/2/';

    /**
     * {@inheritdoc}
     */
    protected $authorizeUrl = 'https://x.com/i/oauth2/authorize';

    /**
     * {@inheritdoc}
     */
    protected $accessTokenUrl = 'https://api.x.com/2/oauth2/token';

    /**
     * {@inheritdoc}
     */
    protected $apiDocumentation = 'https://docs.x.com/resources/fundamentals/authentication/oauth-2-0/authorization-code';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();

        $this->tokenExchangeHeaders = [
            'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];

        $codeVerifier = $this->getStoredData('codeVerifier');
        if (!$codeVerifier) {
            $codeVerifier = $this->generateCodeVerifier();
            $this->storeData('codeVerifier', $codeVerifier);
        }

        $codeChallenge = $this->getStoredData('codeChallenge');
        if (!$codeChallenge) {
            $codeChallenge = $this->generateCodeChallenge($codeVerifier);
            $this->storeData('codeChallenge', $codeChallenge);
        }

        // Set additional authorize URL parameters required by Twitter
        $this->AuthorizeUrlParameters += [
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'response_type' => 'code',
        ];

        if ($this->isRefreshTokenAvailable()) {
            $this->tokenRefreshParameters += [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function exchangeCodeForAccessToken($code)
    {
        $parameters = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->callback,
            'code_verifier' => $this->getStoredData('codeVerifier'),
        ];

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenExchangeMethod,
            $parameters,
            $this->tokenExchangeHeaders,
        );

        $this->validateApiResponse('Unable to exchange code for API access token');

        return $response;
    }

    /**
     * Generate a random code verifier for PKCE
     *
     * @return string
     */
    protected function generateCodeVerifier()
    {
        $random = random_bytes(64);
        return rtrim(strtr(base64_encode($random), '+/', '-_'), '=');
    }

    /**
     * Generate a code challenge for PKCE
     *
     * @param string $codeVerifier
     * @return string
     */
    protected function generateCodeChallenge($codeVerifier)
    {
        $hash = hash('sha256', $codeVerifier, true);
        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        $response = $this->apiRequest('users/me?user.fields=id,name,username,profile_image_url,description,verified,location,url,entities,confirmed_email');

        $data = new Data\Collection($response);

        if (!$data->exists('data') || !$data->get('data')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userData = $data->get('data');
        $userData = new Data\Collection($userData);

        if (!$userData->exists('id')) {
            throw new UnexpectedApiResponseException('Provider API returned an unexpected response.');
        }

        $userProfile = new User\Profile();

        $userProfile->identifier = $userData->get('id');
        $userProfile->displayName = $userData->get('name');
        $userProfile->description = $userData->get('description');
        $userProfile->photoURL = $userData->get('profile_image_url');
        $userProfile->webSiteURL = $userData->get('url');
        $userProfile->email = $userData->get('confirmed_email');
        $userProfile->region = $userData->get('location');
        $userProfile->emailVerified = $userData->get('confirmed_email') ? $userData->get('confirmed_email') : '';
        $userProfile->profileURL = 'https://x.com/' . $userData->get('username');

        $userProfile->data['username'] = $userData->get('username');
        $userProfile->data['profile_verified'] = $userData->get('verified');

        if ($userData->exists('entities') && $userData->get('entities')) {
            $entities = $userData->get('entities');
            if (isset($entities->url) && isset($entities->url->urls[0]->expanded_url)) {
                $userProfile->webSiteURL = $entities->url->urls[0]->expanded_url;
            }
        }

        return $userProfile;
    }

    /**
     * {@inheritdoc}
     */
    protected function authenticateCheckError()
    {
        $error = isset($_REQUEST['error']) ? $_REQUEST['error'] : null;

        if ($error) {
            $error_description = isset($_REQUEST['error_description']) ? $_REQUEST['error_description'] : '';

            throw new UnexpectedApiResponseException(
                "Authentication failed! {$this->providerId} returned an error: {$error_description}",
                10
            );
        }
    }
}
