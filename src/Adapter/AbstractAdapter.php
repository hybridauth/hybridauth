<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception\NotImplementedException;
use Hybridauth\Exception\HttpClientFailureException;
use Hybridauth\Exception\HttpRequestFailedException;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Storage\Session;
use Hybridauth\Logger\LoggerInterface;
use Hybridauth\Logger\Logger;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Curl as HttpClient;
use Hybridauth\Data;
use Hybridauth\Deprecated\DeprecatedAdapterTrait;

/**
 *
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Provider ID (unique name).
     *
     * @var string
     */
    protected $providerId = '';

    /**
     * Specific Provider config.
     *
     * @var mixed
     */
    protected $config = [];

    /**
     * Extra Provider parameters.
     *
     * @var array
     */
    protected $params;

    /**
     * Redirection Endpoint (i.e., redirect_uri, callback_url).
     *
     * @var string
     */
    protected $endpoint = '';

    /**
     * Storage.
     *
     * @var StorageInterface
     */
    public $storage;

    /**
     * HttpClient.
     *
     * @var HttpClientInterface
     */
    public $httpClient;

    /**
     * Logger.
     *
     * @var LoggerInterface
     */
    public $logger;

    /**
     * Common adapters constructor.
     *
     * @param array               $config
     * @param HttpClientInterface $httpClient
     * @param StorageInterface    $storage
     * @param LoggerInterface     $logger
     */
    public function __construct(
        $config = [],
        HttpClientInterface $httpClient = null,
        StorageInterface    $storage = null,
        LoggerInterface     $logger = null
    ) {
        $this->providerId = str_replace('Hybridauth\\Provider\\', '', get_class($this));

        $this->storage = $storage ?: new Session();

        $this->logger = $logger ?: new Logger(
            isset($config['debug_mode']) ? $config['debug_mode'] : Logger::NONE,
            isset($config['debug_file']) ? $config['debug_file'] : ''
        );

        $this->httpClient = $httpClient ?: new HttpClient();

        if (isset($config['curl_options']) && method_exists($this->httpClient, 'setCurlOptions')) {
            $this->httpClient->setCurlOptions($this->config['curl_options']);
        }

        if (method_exists($this->httpClient, 'setLogger')) {
            $this->httpClient->setLogger($this->logger);
        }

        $this->logger->debug('Initialize '.get_class($this).'. Provider config: ', $config);

        $this->config = new Data\Collection($config);

        $this->endpoint = $this->config->get('callback');

        $this->initialize();
    }

    /**
    * Adapter initializer
    *
    * @throws InvalidArgumentException
    * @throws InvalidApplicationCredentialsException
    * @throws InvalidOpenidIdentifierException
    */
    abstract protected function initialize();

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        throw new NotImplementedException('Provider does not support this feature.', 8);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        throw new NotImplementedException('Provider does not support this feature.', 8);
    }

    /**
     * {@inheritdoc}
     */
    public function setUserStatus($status)
    {
        throw new NotImplementedException('Provider does not support this feature.', 8);
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream)
    {
        throw new NotImplementedException('Provider does not support this feature.', 8);
    }

    /**
     * {@inheritdoc}
     */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [])
    {
        throw new NotImplementedException('Provider does not support this feature.', 8);
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthorized()
    {
        return (bool) $this->token('access_token');
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->clearTokens();

        return true;
    }

    /**
     * Return oauth access tokens.
     *
     * @param array $tokenNames
     *
     * @return array
     */
    public function getAccessToken($tokenNames = [])
    {
        if (empty($tokenNames)) {
            $tokenNames = [
                'access_token',
                'access_token_secret',
                'token_type',
                'refresh_token',
                'expires_in',
                'expires_at',
            ];
        }

        $tokens = [];

        foreach ($tokenNames as $name) {
            if ($this->token($name)) {
                $tokens[ $name ] = $this->token($name);
            }
        }

        return $tokens;
    }

    /**
     * Reset adapter access tokens.
     *
     * @param array $tokens
     */
    public function setAccessToken($tokens = [])
    {
        $this->clearTokens();

        foreach ($tokens as $token => $value) {
            $this->token($token, $value);
        }
    }

    /**
     * Get or Set a token.
     *
     * This method provide a common way for providers adapter to store data internally.
     * These tokens can be either OAuth tokens or any useful data (i.e., user_id, auth_nonce, etc.)
     *
     * @param string $token
     * @param mixed  $value
     *
     * @return mixed
     */
    public function token($token, $value = null)
    {
        if ($value === null) {
            return $this->storage->get($this->providerId.'.token.'.$token);
        }

        // we only store necessary data
        if (empty($value)) {
            $this->deleteToken($token);
        } else {
            $this->storage->set($this->providerId.'.token.'.$token, $value);
        }
    }

    /**
     * Delete all tokens of the instantiated adapter.
     */
    public function clearTokens()
    {
        $this->storage->deleteMatch($this->providerId.'.');
    }

    /**
     * Delete a stored token.
     *
     * @param string $token
     */
    protected function deleteToken($token)
    {
        $this->storage->delete($this->providerId.'.token.'.$token);
    }

    /**
     * Return http client instance.
     *
     * @return HttpClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

    /**
     * Validate Signed API Requests responses.
     *
     * Since the specifics of error responses is beyond the scope of RFC6749 and OAuth Core specifications,
     * Hybridauth will consider any HTTP status code that is different than '200 OK' as an ERROR.
     *
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     */
    protected function validateApiResponse($error = '')
    {
        $error .= !empty($error) ? '. ' : '';

        if ($this->httpClient->getResponseClientError()) {
            throw new HttpClientFailureException(
                $error . 'HTTP client error: '.
                $this->httpClient->getResponseClientError().
                '.'
            );
        }

        if (200 != $this->httpClient->getResponseHttpCode()) {
            throw new HttpRequestFailedException(
                $error . 'HTTP error '.
                $this->httpClient->getResponseHttpCode().
                '. Raw Provider API response: '.
                $this->httpClient->getResponseBody().
                '.'
            );
        }
    }

    /**
     * Override defaults endpoints.
     */
    protected function overrideEndpoints()
    {
        $endpoints = $this->config->filter('endpoints');

        $this->apiBaseUrl = $endpoints->get('api_base_url')     ?: $this->apiBaseUrl;
        $this->authorizeUrl = $endpoints->get('authorize_url')    ?: $this->authorizeUrl;
        $this->accessTokenUrl = $endpoints->get('access_token_url') ?: $this->accessTokenUrl;
    }
}
