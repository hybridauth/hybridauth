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
    use DataStoreTrait;

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

        $this->configure();

        $this->setHttpClient($httpClient);

        $this->setStorage($storage);

        $this->setLogger($logger);

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
    
     */
    public function setHttpClient($httpClient)
    {
        $this->httpClient = $httpClient ?: new HttpClient();

        if ($this->config->exists('curl_options') && method_exists($this->httpClient, 'setCurlOptions')) {
            $this->httpClient->setCurlOptions($this->config->get('curl_options'));
        }
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
    
     */
    public function setStorage($storage)
    {
        $this->storage = $storage ?: new Session();
    }

    /**
     * Return storage instance.
     *
     * @return Storage
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     *
     */
    public function setLogger($logger)
    {
        $this->logger = $logger ?: new Logger(
            $this->config->get('debug_mode') ?: Logger::NONE,
            $this->config->get('debug_file') ?: ''
        );
        
        if (method_exists($this->httpClient, 'setLogger')) {
            $this->httpClient->setLogger($this->logger);
        }
    }

    /**
     * Return logger instance.
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
    * Set Adapter's API callback url
     *
     * @throws InvalidArgumentException
    */
    public function setCallback($callback)
    {
        if (! filter_var($callback, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('A valid callback url is required.');
        }
        
        $this->callback = $callback;
    }

    /**
    * Overwrite Adapter's API endpoints
    */
    public function setApiEndpoints($endpoints)
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
