<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception\Exception;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\AuthorizationDeniedException;
use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Data;
use Hybridauth\HttpClient;

/**
 * This class  can be used to simplify the authorization flow of OAuth 2 based service providers.
 *
 * Subclasses (i.e., providers adapters) can either use the already provided methods or override
 * them when necessary.
 */
abstract class OAuth2 extends AbstractAdapter implements AdapterInterface
{
    /**
     * Client Identifier
     *
     * RFC6749: client_id REQUIRED. The client identifier issued to the client during
     * the registration process described by Section 2.2.
     *
     * http://tools.ietf.org/html/rfc6749#section-2.2
     *
     * @var string
     */
    protected $clientId = '';

    /**
     * Client Secret
     *
     * RFC6749: client_secret REQUIRED. The client secret. The client MAY omit the
     * parameter if the client secret is an empty string.
     *
     * http://tools.ietf.org/html/rfc6749#section-2.2
     *
     * @var string
     */
    protected $clientSecret = '';

    /**
     * Access Token Scope
     *
     * RFC6749: The authorization and token endpoints allow the client to specify the
     * scope of the access request using the "scope" request parameter.
     *
     * http://tools.ietf.org/html/rfc6749#section-3.3
     *
     * @var string
     */
    protected $scope = '';

    /**
     * Base URL to provider API
     *
     * This var will be used to build urls when sending signed requests
     *
     * @var string
     */
    protected $apiBaseUrl = '';

    /**
     * Authorization Endpoint
     *
     * RFC6749: The authorization endpoint is used to interact with the resource
     * owner and obtain an authorization grant.
     *
     * http://tools.ietf.org/html/rfc6749#section-3.1
     *
     * @var string
     */
    protected $authorizeUrl = '';

    /**
     * Access Token Endpoint
     *
     * RFC6749: The token endpoint is used by the client to obtain an access token by
     * presenting its authorization grant or refresh token.
     *
     * http://tools.ietf.org/html/rfc6749#section-3.2
     *
     * @var string
     */
    protected $accessTokenUrl = '';

    /**
     * TokenInfo endpoint
     *
     * Access token validation. OPTIONAL.
     *
     * @var string
     */
    protected $accessTokenInfoUrl = '';

    /**
     * IPD API Documentation
     *
     * OPTIONAL.
     *
     * @var string
     */
    protected $apiDocumentation = '';

    /**
     * Redirection Endpoint or Callback
     *
     * RFC6749: After completing its interaction with the resource owner, the
     * authorization server directs the resource owner's user-agent back to
     * the client.
     *
     * http://tools.ietf.org/html/rfc6749#section-3.1.2
     *
     * @var string
     */
    protected $callback = '';

    /**
     * Authorization Url Parameters
     *
     * @var array
     */
    protected $AuthorizeUrlParameters = [];


    /**
     * Authorization Url Parameter encoding type
     * @see https://www.php.net/manual/de/function.http-build-query.php
     *
     * @var string
     */
    protected $AuthorizeUrlParametersEncType = PHP_QUERY_RFC1738;

    /**
     * Authorization Request State
     *
     * @var bool
     */
    protected $supportRequestState = true;

    /**
     * Access Token name
     *
     * While most providers will use 'access_token' as name for the Access Token attribute, other do not.
     * On the latter case, this should be set by sub classes.
     *
     * @var string
     */
    protected $accessTokenName = 'access_token';

    /**
     * Authorization Request HTTP method.
     *
     * @see exchangeCodeForAccessToken()
     *
     * @var string
     */
    protected $tokenExchangeMethod = 'POST';

    /**
     * Authorization Request URL parameters.
     *
     * Sub classes may change add any additional parameter when necessary.
     *
     * @see exchangeCodeForAccessToken()
     *
     * @var array
     */
    protected $tokenExchangeParameters = [];

    /**
     * Authorization Request HTTP headers.
     *
     * Sub classes may add any additional header when necessary.
     *
     * @see exchangeCodeForAccessToken()
     *
     * @var array
     */
    protected $tokenExchangeHeaders = [];

    /**
     * Refresh Token Request HTTP method.
     *
     * @see refreshAccessToken()
     *
     * @var string
     */
    protected $tokenRefreshMethod = 'POST';

    /**
     * Refresh Token Request URL parameters.
     *
     * Sub classes may change add any additional parameter when necessary.
     *
     * @see refreshAccessToken()
     *
     * @var array|null
     */
    protected $tokenRefreshParameters = null;

    /**
     * Refresh Token Request HTTP headers.
     *
     * Sub classes may add any additional header when necessary.
     *
     * @see refreshAccessToken()
     *
     * @var array
     */
    protected $tokenRefreshHeaders = [];

    /**
     * Authorization Request URL parameters.
     *
     * Sub classes may change add any additional parameter when necessary.
     *
     * @see apiRequest()
     *
     * @var array
     */
    protected $apiRequestParameters = [];

    /**
     * Authorization Request HTTP headers.
     *
     * Sub classes may add any additional header when necessary.
     *
     * @see apiRequest()
     *
     * @var array
     */
    protected $apiRequestHeaders = [];

    protected $refreshingAccessToken = false;

    /** @var string|null */
    protected $pkceMethod = null;

    protected $ignoreLegacyPkceDisable = false;

    /** @var string */
    protected $tokenEndpointAuthMethod = 'client_secret_post';

    /** @var string auto|json|form|xml; the token_response_format option overrides it. */
    protected $tokenResponseFormat = 'auto';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->clientId = $this->config->filter('keys')->get('id') ?: $this->config->filter('keys')->get('key');
        $this->clientSecret = $this->config->filter('keys')->get('secret');

        if ($this->config->exists('token_endpoint_auth_method')) {
            $this->tokenEndpointAuthMethod = $this->config->get('token_endpoint_auth_method');
        }
        if (!in_array($this->tokenEndpointAuthMethod, ['client_secret_post', 'client_secret_basic', 'none'], true)) {
            throw new InvalidApplicationCredentialsException('Unsupported token_endpoint_auth_method.');
        }
        if (!$this->clientId || ($this->tokenEndpointAuthMethod !== 'none' && !$this->clientSecret)) {
            throw new InvalidApplicationCredentialsException(
                'Your application id and the credentials required by the token authentication method are missing for '
                . $this->providerId
            );
        }
        if ($this->tokenEndpointAuthMethod === 'none') {
            $this->pkceMethod = 'S256';
        }

        $this->scope = $this->config->exists('scope') ? $this->config->get('scope') : $this->scope;

        if ($this->config->exists('supportRequestState')) {
            $this->supportRequestState = $this->config->get('supportRequestState');
        }

        if ($this->config->exists('pkce')) {
            $pkce = $this->config->get('pkce');
            if ($pkce === true || $pkce === 'S256') {
                $this->pkceMethod = 'S256';
            } elseif ($pkce !== false) {
                throw new InvalidApplicationCredentialsException('Only S256 PKCE is supported.');
            } elseif ($this->pkceMethod !== null
                && (!$this->ignoreLegacyPkceDisable || $this->tokenEndpointAuthMethod === 'none')
            ) {
                throw new InvalidApplicationCredentialsException(
                    'PKCE cannot be disabled for ' . $this->providerId
                    . ' because the provider or public client authentication requires it.'
                );
            }
        }
        if ($this->pkceMethod && !$this->supportRequestState) {
            throw new InvalidApplicationCredentialsException('PKCE requires authorization state validation.');
        }

        if ($this->config->exists('tokens')) {
            $this->setAccessToken($this->config->get('tokens'));
        }

        $this->setCallback($this->config->get('callback'));
        $this->setApiEndpoints($this->config->get('endpoints'));
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->AuthorizeUrlParameters = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'redirect_uri' => $this->callback,
            'scope' => $this->scope,
        ];

        $this->tokenExchangeParameters = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->callback
        ];

        $this->tokenRefreshParameters = null;
        $refreshToken = $this->getStoredData('refresh_token');
        if (!empty($refreshToken)) {
            $this->tokenRefreshParameters = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];
        }

        $this->apiRequestHeaders = [
            'Authorization' => 'Bearer ' . $this->getStoredData('access_token')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate()
    {
        $this->logger->info(sprintf('%s::authenticate()', get_class($this)));

        if ($this->isConnected()) {
            return true;
        }

        try {
            $this->authenticateCheckError();

            $code = $this->filterInput($_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET, 'code');

            if (empty($code)) {
                $this->authenticateBegin();
            } else {
                $this->authenticateFinish();
            }
        } catch (Exception $e) {
            $this->disconnect();

            throw $e;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isConnected()
    {
        if ((bool)$this->getStoredData('access_token')) {
            return (!$this->hasAccessTokenExpired() || $this->isRefreshTokenAvailable());
        }
        return false;
    }

    /**
     * If we can use a refresh token, then an expired token does not stop us being connected.
     *
     * @return bool
     */
    public function isRefreshTokenAvailable()
    {
        return is_array($this->tokenRefreshParameters);
    }

    public function disconnect()
    {
        parent::disconnect();
        $this->tokenRefreshParameters = null;
        $this->tokenRefreshHeaders = [];
        $this->apiRequestHeaders = [];
        $this->apiRequestParameters = [];
    }

    public function getAccessToken()
    {
        $tokens = parent::getAccessToken();
        foreach (['scope', 'token_metadata', 'expires_in', 'expires_at'] as $name) {
            $value = $this->getStoredData($name);
            if ($value !== null) {
                $tokens[$name] = $value;
            }
        }
        return $tokens;
    }

    /**
     * Authorization Request Error Response
     *
     * RFC6749: If the request fails due to a missing, invalid, or mismatching
     * redirection URI, or if the client identifier is missing or invalid,
     * the authorization server SHOULD inform the resource owner of the error.
     *
     * http://tools.ietf.org/html/rfc6749#section-4.1.2.1
     *
     * @throws \Hybridauth\Exception\InvalidAuthorizationCodeException
     * @throws \Hybridauth\Exception\AuthorizationDeniedException
     */
    protected function authenticateCheckError()
    {
        $input = isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET;
        $error = $this->filterInput($input, 'error', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($error)) {
            $error_description = $this->filterInput($input, 'error_description', FILTER_SANITIZE_SPECIAL_CHARS);
            $error_uri = $this->filterInput($input, 'error_uri', FILTER_SANITIZE_SPECIAL_CHARS);

            $collated_error = sprintf('Provider returned an error: %s %s %s', $error, $error_description, $error_uri);

            if ($error == 'access_denied') {
                throw new AuthorizationDeniedException($collated_error);
            }

            throw new InvalidAuthorizationCodeException($collated_error);
        }
    }

    /**
     * Initiate the authorization protocol
     *
     * Build Authorization URL for Authorization Request and redirect the user-agent to the
     * Authorization Server.
     */
    protected function authenticateBegin()
    {
        $authUrl = $this->getAuthorizeUrl();

        $this->logger->debug(sprintf('%s::authenticateBegin(), redirecting user to:', get_class($this)), [$authUrl]);

        HttpClient\Util::redirect($authUrl);
    }

    /**
     * Finalize the authorization process
     *
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws InvalidAccessTokenException
     * @throws InvalidAuthorizationStateException
     */
    protected function authenticateFinish()
    {
        $this->logger->debug(
            sprintf('%s::authenticateFinish(), callback url:', get_class($this)),
            [HttpClient\Util::getCurrentUrl(true)]
        );

        $state = $this->filterInput($_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET, 'state');
        $code = $this->filterInput($_SERVER['REQUEST_METHOD'] === 'POST' ? INPUT_POST : INPUT_GET, 'code');

        /**
         * Authorization Request State
         *
         * RFC6749: state : RECOMMENDED. An opaque value used by the client to maintain
         * state between the request and callback. The authorization server includes
         * this value when redirecting the user-agent back to the client.
         *
         * http://tools.ietf.org/html/rfc6749#section-4.1.1
         */
        if ($this->supportRequestState
            && !Data\Random::equals($this->getStoredData('authorization_state'), $state)
        ) {
            $this->deleteStoredData('authorization_state');
            $this->deleteStoredData('pkce_code_verifier');
            throw new InvalidAuthorizationStateException(
                'The authorization state [state='
                . substr(htmlentities((string) $state, ENT_QUOTES, 'UTF-8'), 0, 100) . '] '
                . 'of this page is either invalid or has already been consumed.'
            );
        }

        $this->deleteStoredData('authorization_state');

        /**
         * Authorization Request Code
         *
         * RFC6749: If the resource owner grants the access request, the authorization
         * server issues an authorization code and delivers it to the client:
         *
         * http://tools.ietf.org/html/rfc6749#section-4.1.2
         */
        $response = $this->exchangeCodeForAccessToken($code);

        $this->validateAccessTokenExchange($response);

        $this->initialize();
    }

    /**
     * Build Authorization URL for Authorization Request
     *
     * RFC6749: The client constructs the request URI by adding the following
     * $parameters to the query component of the authorization endpoint URI:
     *
     *    - response_type  REQUIRED. Value MUST be set to "code".
     *    - client_id      REQUIRED.
     *    - redirect_uri   OPTIONAL.
     *    - scope          OPTIONAL.
     *    - state          RECOMMENDED.
     *
     * http://tools.ietf.org/html/rfc6749#section-4.1.1
     *
     * Sub classes may redefine this method when necessary.
     *
     * @param array $parameters
     *
     * @return string Authorization URL
     */
    protected function getAuthorizeUrl($parameters = [])
    {
        $parameters = !empty($parameters)
            ? $parameters
            : array_replace(
                (array)$this->AuthorizeUrlParameters,
                (array)$this->config->get('authorize_url_parameters')
            );

        if ($this->supportRequestState) {
            if (!isset($parameters['state'])) {
                $parameters['state'] = 'HA-' . Data\Random::token();
            }

            if (!is_string($parameters['state']) || $parameters['state'] === '') {
                throw new InvalidAuthorizationStateException('Authorization state must be a nonempty string.');
            }
            $this->storeData('authorization_state', $parameters['state']);
        }

        if ($this->pkceMethod === 'S256') {
            $verifier = Data\Random::token();
            $this->storeData('pkce_code_verifier', $verifier);
            $challenge = base64_encode(hash('sha256', $verifier, true));
            $parameters['code_challenge'] = rtrim(strtr($challenge, '+/', '-_'), '=');
            $parameters['code_challenge_method'] = 'S256';
        }
        if (isset($parameters['scope']) && is_array($parameters['scope'])) {
            $parameters['scope'] = implode(' ', $parameters['scope']);
        }
        $queryParams = http_build_query($parameters, '', '&', $this->AuthorizeUrlParametersEncType);
        $parts = explode('#', $this->authorizeUrl, 2);
        $separator = strpos($parts[0], '?') === false ? '?' : (preg_match('/[?&]$/', $parts[0]) ? '' : '&');
        return $parts[0] . $separator . $queryParams . (isset($parts[1]) ? '#' . $parts[1] : '');
    }

    /**
     * Access Token Request
     *
     * This method will exchange the received $code in loginFinish() with an Access Token.
     *
     * RFC6749: The client makes a request to the token endpoint by sending the
     * following parameters using the "application/x-www-form-urlencoded"
     * with a character encoding of UTF-8 in the HTTP request entity-body:
     *
     *    - grant_type    REQUIRED. Value MUST be set to "authorization_code".
     *    - code          REQUIRED. The authorization code received from the authorization server.
     *    - redirect_uri  REQUIRED.
     *    - client_id     REQUIRED.
     *
     * http://tools.ietf.org/html/rfc6749#section-4.1.3
     *
     * @param string $code
     *
     * @return string Raw Provider API response
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     */
    protected function exchangeCodeForAccessToken($code)
    {
        $parameters = $this->tokenExchangeParameters;
        $parameters['code'] = $code;
        if ($this->pkceMethod === 'S256') {
            $verifier = $this->consumePkceVerifier();
            if (!is_string($verifier) || !preg_match('/^[A-Za-z0-9._~-]{43,128}$/D', $verifier)) {
                throw new InvalidAuthorizationCodeException(
                    'Missing or consumed PKCE verifier; restart authorization.'
                );
            }
            $parameters['code_verifier'] = $verifier;
        }

        list($parameters, $headers) = $this->prepareTokenRequest($parameters, $this->tokenExchangeHeaders);
        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenExchangeMethod,
            $parameters,
            $headers
        );

        $this->validateApiResponse('Unable to exchange code for API access token');

        return $response;
    }

    protected function prepareTokenRequest($parameters, $headers)
    {
        $explicit = $this->config->exists('token_endpoint_auth_method');
        $basicHeader = false;
        foreach ($headers as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $basicHeader = stripos($value, 'Basic ') === 0;
                if ($explicit || $this->tokenEndpointAuthMethod !== 'client_secret_post') {
                    unset($headers[$name]);
                }
            }
        }
        if ($this->tokenEndpointAuthMethod === 'none') {
            unset($parameters['client_secret']);
            $parameters['client_id'] = $this->clientId;
        } elseif ($this->tokenEndpointAuthMethod === 'client_secret_basic') {
            unset($parameters['client_id'], $parameters['client_secret']);
            $headers['Authorization'] = 'Basic '
                . base64_encode(urlencode($this->clientId) . ':' . urlencode($this->clientSecret));
        } elseif ($basicHeader && !$explicit) {
            // Preserve custom provider authentication without duplicating credentials in the body.
            unset($parameters['client_id'], $parameters['client_secret']);
        } else {
            $parameters['client_id'] = $this->clientId;
            $parameters['client_secret'] = $this->clientSecret;
        }
        return [$parameters, $headers];
    }

    protected function consumePkceVerifier()
    {
        $verifier = $this->getStoredData('pkce_code_verifier');
        $this->deleteStoredData('pkce_code_verifier');
        return $verifier;
    }

    /**
     * Validate Access Token Response
     *
     * RFC6749: If the access token request is valid and authorized, the
     * authorization server issues an access token and optional refresh token.
     * If the request client authentication failed or is invalid, the authorization
     * server returns an error response as described in Section 5.2.
     *
     * Example of a successful response:
     *
     *  HTTP/1.1 200 OK
     *  Content-Type: application/json;charset=UTF-8
     *  Cache-Control: no-store
     *  Pragma: no-cache
     *
     *  {
     *      "access_token":"2YotnFZFEjr1zCsicMWpAA",
     *      "token_type":"example",
     *      "expires_in":3600,
     *      "refresh_token":"tGzv3JOkF0XG5Qx2TlKWIA",
     *      "example_parameter":"example_value"
     *  }
     *
     * http://tools.ietf.org/html/rfc6749#section-4.1.4
     *
     * This method uses Data_Parser to attempt to decodes the raw $response (usually JSON)
     * into a data collection.
     *
     * @param string $response
     *
     * @return \Hybridauth\Data\Collection
     * @throws InvalidAccessTokenException
     */
    protected function validateAccessTokenExchange($response)
    {
        $data = $this->parseTokenResponse($response);
        $collection = new Data\Collection($data);

        if ($collection->exists('error') && $collection->get('error') !== null && $collection->get('error') !== '') {
            throw new InvalidAccessTokenException('Provider returned an OAuth token error.');
        }
        $accessToken = $collection->get('access_token');
        if (!is_string($accessToken) || trim($accessToken) === '') {
            throw new InvalidAccessTokenException('Provider returned no usable access_token.');
        }
        // Null fields are treated as omitted.
        foreach (['refresh_token', 'token_type'] as $name) {
            $value = $collection->get($name);
            if ($value !== null && !is_string($value)) {
                throw new InvalidAccessTokenException('Provider returned an invalid ' . $name . '.');
            }
        }
        // Some providers (e.g. Twitch) return the granted scopes as an array.
        $scope = $collection->get('scope');
        if (is_array($scope)) {
            foreach ($scope as $item) {
                if (!is_string($item)) {
                    throw new InvalidAccessTokenException('Provider returned an invalid scope.');
                }
            }
            $scope = implode(' ', $scope);
        } elseif ($scope !== null && !is_string($scope)) {
            throw new InvalidAccessTokenException('Provider returned an invalid scope.');
        }

        $lifetimes = [];
        foreach (['expires_in', 'expires'] as $name) {
            $value = $collection->get($name);
            if ($value === null) {
                continue;
            }
            if ((!is_int($value) && !is_float($value) && !is_string($value))
                || !preg_match('/^[0-9]+$/D', (string) $value)
                || (float) $value > PHP_INT_MAX - time()
            ) {
                throw new InvalidAccessTokenException('Provider returned an invalid ' . $name . '.');
            }
            $lifetimes[$name] = (int) $value;
        }
        // Legacy "expires" (Facebook, Deezer) is a relative lifetime in seconds where 0 means non-expiring.
        $expiresAt = null;
        if (isset($lifetimes['expires_in'])) {
            $expiresAt = time() + $lifetimes['expires_in'];
        } elseif (!empty($lifetimes['expires'])) {
            $expiresAt = time() + $lifetimes['expires'];
        }

        $this->storeData('access_token', $accessToken);
        $this->storeData('token_type', $collection->get('token_type'));

        if ($collection->get('refresh_token')) {
            $this->storeData('refresh_token', $collection->get('refresh_token'));
        } elseif (!$this->refreshingAccessToken) {
            $this->deleteStoredData('refresh_token');
        }

        $this->deleteStoredData('expires_in');
        $this->deleteStoredData('expires_at');
        if (isset($lifetimes['expires_in'])) {
            $this->storeData('expires_in', $collection->get('expires_in'));
        }
        if ($expiresAt !== null) {
            $this->storeData('expires_at', $expiresAt);
        }

        if ($scope !== null) {
            $this->storeData('scope', $scope);
        } elseif (!$this->refreshingAccessToken) {
            $this->deleteStoredData('scope');
        }
        $metadata = $collection->toArray();
        foreach (['access_token', 'refresh_token', 'token_type', 'expires_in', 'expires', 'scope'] as $name) {
            unset($metadata[$name]);
        }
        $this->storeData('token_metadata', $metadata);

        $this->deleteStoredData('authorization_state');

        $this->initialize();

        return $collection;
    }

    protected function parseTokenResponse($response)
    {
        $format = $this->config->get('token_response_format') ?: $this->tokenResponseFormat;
        if (!in_array($format, ['auto', 'json', 'form', 'xml'], true)) {
            throw new InvalidAccessTokenException('Unsupported token_response_format.');
        }
        $contentType = '';
        foreach ((array) $this->httpClient->getResponseHeader() as $name => $value) {
            if (strtolower(str_replace('_', '-', $name)) === 'content-type') {
                $contentType = strtolower(is_array($value) ? implode(';', $value) : $value);
            }
        }
        $json = strpos($contentType, '/json') !== false || strpos($contentType, '+json') !== false;
        $prefix = substr(ltrim((string) $response), 0, 1);
        if ($json || $format === 'json' || ($format === 'auto' && ($prefix === '{' || $prefix === '['))) {
            $data = json_decode($response);
            if (json_last_error() !== JSON_ERROR_NONE || !is_object($data)) {
                throw new InvalidAccessTokenException('Provider returned an invalid JSON token response.');
            }
            return $data;
        }
        if ($format === 'xml') {
            if (stripos($response, '<!DOCTYPE') !== false || stripos($response, '<!ENTITY') !== false) {
                throw new InvalidAccessTokenException('XML token responses must not declare entities.');
            }
            $data = (new Data\Parser())->parseXml($response);
            return is_array($data) && count($data) === 1 ? reset($data) : $data;
        }
        if ($format === 'auto' && $contentType !== ''
            && strpos($contentType, 'application/x-www-form-urlencoded') === false
        ) {
            throw new InvalidAccessTokenException('Provider returned an unsupported token response content type.');
        }
        return (new Data\Parser())->parseQueryString($response);
    }

    /**
     * Refreshing an Access Token
     *
     * RFC6749: If the authorization server issued a refresh token to the
     * client, the client makes a refresh request to the token endpoint by
     * adding the following parameters ... in the HTTP request entity-body:
     *
     *    - grant_type     REQUIRED. Value MUST be set to "refresh_token".
     *    - refresh_token  REQUIRED. The refresh token issued to the client.
     *    - scope          OPTIONAL.
     *
     * http://tools.ietf.org/html/rfc6749#section-6
     *
     * This method is similar to exchangeCodeForAccessToken(). The only
     * difference is here we exchange refresh_token for a new access_token.
     *
     * @param array $parameters
     *
     * @return string|null Raw Provider API response, or null if we cannot refresh
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws InvalidAccessTokenException
     */
    public function refreshAccessToken($parameters = [])
    {
        if (empty($parameters) && !$this->isRefreshTokenAvailable()) {
            return null;
        }

        $parameters = !empty($parameters) ? $parameters : $this->tokenRefreshParameters;
        list($parameters, $headers) = $this->prepareTokenRequest($parameters, $this->tokenRefreshHeaders);
        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenRefreshMethod,
            $parameters,
            $headers
        );

        $this->validateApiResponse('Unable to refresh the access token');

        $this->validateRefreshAccessToken($response);

        return $response;
    }

    /**
     * Check whether access token has expired
     *
     * @param int|null $time
     * @return bool|null
     */
    public function hasAccessTokenExpired($time = null)
    {
        if ($time === null) {
            $time = time();
        }

        $expires_at = $this->getStoredData('expires_at');
        if ($expires_at === null || $expires_at === false || $expires_at === '') {
            return null;
        }

        return $expires_at <= $time;
    }

    /**
     * Validate Refresh Access Token Request
     *
     * RFC6749: If valid and authorized, the authorization server issues an
     * access token as described in Section 5.1.  If the request failed
     * verification or is invalid, the authorization server returns an error
     * response as described in Section 5.2.
     *
     * http://tools.ietf.org/html/rfc6749#section-6
     * http://tools.ietf.org/html/rfc6749#section-5.1
     * http://tools.ietf.org/html/rfc6749#section-5.2
     *
     * This method simply use validateAccessTokenExchange(), however sub
     * classes may redefine it when necessary.
     *
     * @param $response
     *
     * @return \Hybridauth\Data\Collection
     * @throws InvalidAccessTokenException
     */
    protected function validateRefreshAccessToken($response)
    {
        $this->refreshingAccessToken = true;
        try {
            return $this->validateAccessTokenExchange($response);
        } finally {
            $this->refreshingAccessToken = false;
        }
    }

    /**
     * Send a signed request to provider API
     *
     * RFC6749: Accessing Protected Resources: The client accesses protected
     * resources by presenting the access token to the resource server. The
     * resource server MUST validate the access token and ensure that it has
     * not expired and that its scope covers the requested resource.
     *
     * Note: Since the specifics of error responses is beyond the scope of
     * RFC6749 and OAuth specifications, Hybridauth will consider any HTTP
     * status code that is different than '200 OK' as an ERROR.
     *
     * http://tools.ietf.org/html/rfc6749#section-7
     *
     * @param string $url
     * @param string $method
     * @param array $parameters
     * @param array $headers
     * @param bool $multipart
     *
     * @return mixed
     * @throws \Hybridauth\Exception\HttpClientFailureException
     * @throws \Hybridauth\Exception\HttpRequestFailedException
     * @throws InvalidAccessTokenException
     */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        // refresh tokens if needed
        $this->maintainToken();
        if ($this->hasAccessTokenExpired() === true) {
            $this->refreshAccessToken();
        }

        if (strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0) {
            $url = rtrim($this->apiBaseUrl, '/') . '/' . ltrim($url, '/');
        }

        $parameters = array_replace($this->apiRequestParameters, (array)$parameters);
        $headers = array_replace($this->apiRequestHeaders, (array)$headers);

        $response = $this->httpClient->request(
            $url,
            $method,     // HTTP Request Method. Defaults to GET.
            $parameters, // Request Parameters
            $headers,    // Request Headers
            $multipart   // Is request multipart
        );

        $this->validateApiResponse('Signed API request to ' . $url . ' has returned an error');

        $response = (new Data\Parser())->parse($response);

        return $response;
    }
}
