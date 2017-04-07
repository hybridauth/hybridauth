<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\Adapter;

use Hybridauth\Exception\NotImplementedException;
use Hybridauth\Exception\InvalidArgumentException;
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
     * Callback url
     *
     * @var string
     */
    protected $callback = '';

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
     * Wheteher to validate API status codes of http responses
     *
     * @var validateApiResponseHttpCode
     */
    protected $validateApiResponseHttpCode = true;

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

        $this->config = new Data\Collection($config);

        $this->storage = $storage ?: new Session();

        $this->logger = $logger ?: new Logger(
            $this->config->exists('debug_mode') ? $this->config->get('debug_mode') : Logger::NONE,
            $this->config->exists('debug_file') ? $this->config->get('debug_file') : ''
        );

        $this->httpClient = $httpClient ?: new HttpClient();

        if ($this->config->exists('curl_options') && method_exists($this->httpClient, 'setCurlOptions')) {
            $this->httpClient->setCurlOptions($this->config->get('curl_options'));
        }

        if (method_exists($this->httpClient, 'setLogger')) {
            $this->httpClient->setLogger($this->logger);
        }

        $this->configure();

        $this->logger->debug(sprintf('Initialize %s, config: ', get_class($this)), $config);

        $this->initialize();
    }

    /**
    * Load adapter's configuration
    *
    * @throws InvalidArgumentException
    * @throws InvalidApplicationCredentialsException
    * @throws InvalidOpenidIdentifierException
    */
    abstract protected function configure();

    /**
    * Adapter initializer
    */
    abstract protected function initialize();

    /**
     * {@inheritdoc}
     */
    public function getUserProfile()
    {
        throw new NotImplementedException('Provider does not support this feature.');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContacts()
    {
        throw new NotImplementedException('Provider does not support this feature.');
    }

    /**
     * {@inheritdoc}
     */
    public function setUserStatus($status)
    {
        throw new NotImplementedException('Provider does not support this feature.');
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivity($stream)
    {
        throw new NotImplementedException('Provider does not support this feature.');
    }

    /**
     * {@inheritdoc}
     */
    public function apiRequest($url, $method = 'GET', $parameters = [], $headers = [])
    {
        throw new NotImplementedException('Provider does not support this feature.');
    }

    /**
     * {@inheritdoc}
     *
     * Checking access_token only works for oauth1 and oauth2, openid will overwrite this method.
     */
    public function isConnected()
    {
        return (bool) $this->getStoredData('access_token');
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->clearStoredData();

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
            if ($this->getStoredData($name)) {
                $tokens[ $name ] = $this->getStoredData($name);
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
        $this->clearStoredData();

        foreach ($tokens as $token => $value) {
            $this->storeData($token, $value);
        }
    }

    /**
     * Store a piece of data in storage.
     *
     * These method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it  
     * can be also used by providers to store any other useful data (i.g., user_id, auth_nonce, etc.)
     *
     * @param string $token
     * @param mixed  $value
     *
     * @return mixed
     */
    public function storeData($name, $value = null)
    {
        // if empty, we simply delete the thing as we'd want to only store necessary data
        if (empty($value)) {
            return $this->deleteStoredData($name);
        }

        $this->storage->set($this->providerId.'.'.$name, $value);
    }

    /**
     * Retrieve a piece of data from storage.
     *
     * These method is mainly used for OAuth tokens (access, secret, refresh, and whatnot), but it  
     * can be also used by providers to retrieve from store any other useful data (i.g., user_id, auth_nonce, etc.)
     *
     * @param string $token
     *
     * @return mixed
     */
    public function getStoredData($name)
    {
        return $this->storage->get($this->providerId.'.'.$name);
    }

    /**
     * Delete a stored piece of data.
     *
     * @param string $name
     */
    protected function deleteStoredData($name)
    {
        $this->storage->delete($this->providerId.'.'.$name);
    }

    /**
     * Delete all stored data of the instantiated adapter
     */
    public function clearStoredData()
    {
        $this->storage->deleteMatch($this->providerId.'.');
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
    * Set Adapter's API callback url
     *
     * @throws InvalidArgumentException
    */
    protected function setCallback($callback)
    {
        if (! filter_var($callback, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('A valid callback url is required.');
        }
        
        $this->callback = $callback;
    }

    /**
    * Overwrite Adapter's API endpoints
    */
    protected function setApiEndpoints($endpoints)
    {
        if(empty($endpoints)){
            return;
        }

        $this->apiBaseUrl = $endpoints->get('api_base_url') ?: $this->apiBaseUrl;
        $this->authorizeUrl = $endpoints->get('authorize_url') ?: $this->authorizeUrl;
        $this->accessTokenUrl = $endpoints->get('access_token_url') ?: $this->accessTokenUrl;
    }

    /**
     * Validate signed API responses Http status code.
     *
     * Since the specifics of error responses is beyond the scope of RFC6749 and OAuth Core specifications,
     * Hybridauth will consider any HTTP status code that is different than '200 OK' as an ERROR.
     *
     * @param string $error String to pre append to message thrown in exception
     *
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     */
    protected function validateApiResponse($error = '')
    {
        $error .= !empty($error) ? '. ' : '';

        if ($this->httpClient->getResponseClientError()) {
            throw new HttpClientFailureException(
                $error.'HTTP client error: '.$this->httpClient->getResponseClientError().'.'
            );
        }

        // if validateApiResponseHttpCode is set to false, we by pass verification of http status code
        if (! $this->validateApiResponseHttpCode){
            return;
        }

        if (200 != $this->httpClient->getResponseHttpCode()) {
            throw new HttpRequestFailedException(
                $error . 'HTTP error '.$this->httpClient->getResponseHttpCode().
                '. Raw Provider API response: '.$this->httpClient->getResponseBody().'.'
            );
        }
    }
}
