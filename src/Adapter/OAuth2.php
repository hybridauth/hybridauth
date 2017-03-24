<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
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
    protected $clientId = '' ;

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
    protected $clientSecret = '' ;

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
    * ...
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
    * Redirection Endpoint
    *
    * The redirection endpoint URI is generated and used internally by Hybridauth Endpoint class.
    *
    * RFC6749: After completing its interaction with the resource owner, the
    * authorization server directs the resource owner's user-agent back to
    * the client.
    *
    * http://tools.ietf.org/html/rfc6749#section-3.1.2
    *
    * @var string
    */
    protected $endpoint = '';

    /**
    * Authorization Request State
    *
    * @var boolean
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
    * See exchangeCodeForAccessToken()
    *
    * @var string
    */
    protected $tokenExchangeMethod = 'POST';

    /**
    * Authorization Request URL parameters.
    *
    * Sub classes may change add any additional parameter when necessary.
    *
    * See exchangeCodeForAccessToken()
    *
    * @var array
    */
    protected $tokenExchangeParameters = [];

    /**
    * Authorization Request HTTP headers.
    *
    * Sub classes may add any additional header when necessary.
    *
    * See exchangeCodeForAccessToken()
    *
    * @var array
    */
    protected $tokenExchangeHeaders = [];

    /**
    * Authorization Request URL parameters.
    *
    * Sub classes may change add any additional parameter when necessary.
    *
    * See apiRequest()
    *
    * @var array
    */
    protected $apiRequestParameters = [];

    /**
    * Authorization Request HTTP headers.
    *
    * Sub classes may add any additional header when necessary.
    *
    * See apiRequest()
    *
    * @var array
    */
    protected $apiRequestHeaders = [];

    /**
    * Adapter initializer
    *
    * @throws InvalidApplicationCredentialsException
    */
    protected function initialize()
    {
        if (! $this->config->filter('keys')->get('id')) {
            throw new InvalidApplicationCredentialsException(
                'Your application id is required in order to connect to ' . $this->providerId
            );
        }

        $this->clientId     = $this->config->filter('keys')->get('id');
        $this->clientSecret = $this->config->filter('keys')->get('secret');

        $this->scope = $this->config->exists('scope')
                            ? $this->config->get('scope')
                            : $this->scope;

        if ($this->config->exists('tokens')) {
            $this->setAccessToken($this->config->get('tokens'));
        }

        /**
        * Set the default Access Token Request parameters
        *
        * Sub classes may reset this when necessary
        *
        * See exchangeCodeForAccessToken()
        */
        $this->tokenExchangeParameters = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $this->endpoint
        ];

        $this->overrideEndpoints();
    }

    /**
    * {@inheritdoc}
    */
    public function authenticate()
    {
        if ($this->isAuthorized()) {
            return true;
        }

        try {
            if (! isset($_GET['code'])) {
                $this->authenticateBegin();
            }
            else {
                $this->authenticateFinish();
            }
        }
        catch (\Exception $e) {
            $this->clearTokens();

            throw $e;
        }
    }

    /**
    * Initiate the authorization protocol
    *
    * Build Authorization URL for Authorization Request and redirect the user-agent to the
    * Authorization Server.
    */
    public function authenticateBegin()
    {
        $authUrl = $this->getAuthorizeUrl();

        HttpClient\Util::redirect($authUrl);
    }

    /**
    * Finalize the authorization process
    *
    * @throws InvalidAuthorizationStateException
    * @throws InvalidAuthorizationCodeException
    */
    public function authenticateFinish()
    {
        $collection = new Data\Collection($_GET);

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
            &&  $this->token('authorization_state') != $collection->get('state')
        ) {
            throw new InvalidAuthorizationStateException(
                'The authorization state [state=' . substr(htmlentities($collection->get('state')), 0, 100). '] ' 
                    . 'of this page is either invalid or has already been consumed.'
            );
        }

        /**
        * Authorization Request Error Response
        *
        * RFC6749: If the request fails due to a missing, invalid, or mismatching
        * redirection URI, or if the client identifier is missing or invalid,
        * the authorization server SHOULD inform the resource owner of the error.
        *
        * http://tools.ietf.org/html/rfc6749#section-4.1.2.1
        */
        if ($collection->get('error')) {
            throw new InvalidAuthorizationCodeException(
                'Provider returned an error: ' . htmlentities($collection->get('error'))
            );
        }

        /**
        * Authorization Request Code
        *
        * RFC6749: If the resource owner grants the access request, the authorization
        * server issues an authorization code and delivers it to the client:
        *
        * http://tools.ietf.org/html/rfc6749#section-4.1.2
        */
        $response = $this->exchangeCodeForAccessToken($collection->get('code'));

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
        $defaults = [
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->endpoint,
            'scope'         => $this->scope,
        ];

        $parameters = array_merge($defaults, (array) $parameters);

        if ($this->supportRequestState) {
            $state = 'HA-' . str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

            $this->token('authorization_state', $state);

            $parameters['state'] = $state;
        }

        return $this->authorizeUrl . '?' . http_build_query($parameters, '', '&');
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
    */
    protected function exchangeCodeForAccessToken($code)
    {
        $this->tokenExchangeParameters['code'] = $code;

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenExchangeMethod,
            $this->tokenExchangeParameters,
            $this->tokenExchangeHeaders
        );

        $this->validateApiResponse('Unable to exchange code for API access token');

        return $response;
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
        $data = (new Data\Parser())->parse($response);

        $collection = new Data\Collection($data);

        if (! $collection->exists('access_token')) {
            throw new InvalidAccessTokenException(
                'Provider returned an invalid access_token: ' . htmlentities($response)
            );
        }

        $this->token('access_token', $collection->get('access_token'));
        $this->token('token_type', $collection->get('token_type'));
        $this->token('refresh_token', $collection->get('refresh_token'));
        $this->token('expires_in', $collection->get('expires_in'));

        // calculate when the access token expire
        if ($collection->exists('expires_in')) {
            $expires_at = time() + (int) $collection->get('expires_in');

            $this->token('access_token_expires_at', $expires_at);
        }

        $this->deleteToken('authorization_state');

        return $collection;
    }

    /**
    * Refreshing an Access Token
    *
    * RFC6749: If the authorization server issued a refresh token to the client, the
    * client makes a refresh request to the token endpoint by adding the
    * following parameters ... in the HTTP request entity-body:
    *
    *    - grant_type     REQUIRED. Value MUST be set to "refresh_token".
    *    - refresh_token  REQUIRED. The refresh token issued to the client.
    *    - scope          OPTIONAL.
    *
    * http://tools.ietf.org/html/rfc6749#section-6
    *
    * This method is similar to exchangeCodeForAccessToken(). The only difference is here
    * we exchange refresh_token for a new access_token.
    *
    * @return string Raw Provider API response
    */
    public function refreshAccessToken($forceRefresh = false)
    {
        $proceed = $forceRefresh;

        // have an access token?
        if ($this->token('access_token')) {

            // have to refresh?
            if ($this->token('refresh_token') && $this->token('access_token_expires_at')) {
                
                // expired?
                if ($this->token('access_token_expires_at') <= time()) {
                    $proceed = true;
                }
            }
        }

        if(! $proceed )
            return;

        $defaults = [
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->token('refresh_token'),
        ];

        $this->tokenExchangeParameters = array_merge($defaults, (array) $this->tokenExchangeParameters);

        $response = $this->httpClient->request(
            $this->accessTokenUrl,
            $this->tokenExchangeMethod,
            $this->tokenExchangeParameters,
            $this->tokenExchangeHeaders
        );

        $this->validateApiResponse('Unable to refresh the access token');

        $this->validateRefreshAccessToken($response);

        return $response;
    }

    /**
    * Validate Refresh Access Token Request
    *
    * RFC6749: If valid and authorized, the authorization server issues an access
    * token as described in Section 5.1.  If the request failed verification or is
    * invalid, the authorization server returns an error response as described in
    * Section 5.2.
    *
    * http://tools.ietf.org/html/rfc6749#section-6
    * http://tools.ietf.org/html/rfc6749#section-5.1
    * http://tools.ietf.org/html/rfc6749#section-5.2
    *
    * This method simply use validateAccessTokenExchange(), however sub classes may
    * redefine it when necessary.
    *
    * @param $response
    *
    * @return \Hybridauth\Data\Collection
    */
    protected function validateRefreshAccessToken($response)
    {
        return $this->validateAccessTokenExchange($response);
    }

    /**
    * Send a signed request to provider API
    *
    * RFC6749: Accessing Protected Resources: The client accesses protected resources
    * by presenting the access token to the resource server. The resource server MUST
    * validate the access token and ensure that it has not expired and that its scope
    * covers the requested resource.
    *
    * Note: Since the specifics of error responses is beyond the scope of RFC6749 and
    * OAuth specifications, Hybridauth will consider any HTTP status code that is different
    * than '200 OK' as an ERROR.
    *
    * http://tools.ietf.org/html/rfc6749#section-7
    *
    * @param string $url
    * @param string $method
    * @param array  $parameters
    * @param array  $headers
    *
    * @return object
    */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [])
    {
        if (strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0) {
            $url = $this->apiBaseUrl . $url;
        }

        $this->apiRequestParameters[ $this->accessTokenName ] = $this->token('access_token');

        $parameters = array_merge($this->apiRequestParameters, (array) $parameters);

        $headers = array_merge($this->apiRequestHeaders, (array) $headers);

        $response = $this->httpClient->request(
            $url, //
            $method, // HTTP Request Method. Defaults to GET.
            $parameters, // Request Parameters
            $headers     // Request Headers
        );

        $this->validateApiResponse('Signed API request has returned an error');

        $response = (new Data\Parser())->parse($response);

        return $response;
    }
}
