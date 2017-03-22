<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth;

use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Storage\Session;
use Hybridauth\Logger\LoggerInterface;
use Hybridauth\Logger\Logger;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Curl as HttpClient;
use Hybridauth\Deprecated\DeprecatedHybridauthTrait;

/**
 * The sole purpose for this class is to provide an unified entry point for the various providers
 * and to ensure a MINIMAL backward compatibility with Hybridauth 2.x.
 */
class Hybridauth
{
    /**
    * Hybridauth version.
    *
    * @var string
    */
    public static $version = '3.0.0-Remake';

    /**
    * Hybridauth config.
    *
    * @var array
    */
    protected $config;

    /**
    * Storage.
    *
    * @var StorageInterface
    */
    protected $storage;

    /**
    * HttpClient.
    *
    * @var HttpClientInterface
    */
    protected $httpClient;

    /**
    * Logger.
    *
    * @var LoggerInterface
    */
    protected $logger;

    /**
    * @param array|string        $config     Array with configuration or path to PHP file that will return array
    * @param HttpClientInterface $httpClient
    * @param StorageInterface    $storage
    * @param LoggerInterface     $logger
    *
    * @throws InvalidArgumentException
    */
    public function __construct(
        $config = [],
        HttpClientInterface $httpClient = null,
        StorageInterface    $storage = null,
        LoggerInterface     $logger = null
    ) {
        if (is_string($config) && file_exists($config)) {
            $config = include $config;
        } elseif (! is_array($config)) {
            throw new InvalidArgumentException('Hybriauth config does not exist on the given path.');
        }

        $this->config = $config + [
            'debug_mode' => Logger::NONE,
            'debug_file' => '',
            'curl_options' => null,
            'providers' => []
        ];

        $this->storage = $storage ?: new Session();

        $this->logger = $logger ?: new Logger($this->config['debug_mode'], $this->config['debug_file']);

        $this->httpClient = $httpClient ?: new HttpClient();

        if ($this->config['curl_options'] && method_exists($this->httpClient, 'setCurlOptions')) {
            $this->httpClient->setCurlOptions($this->config['curl_options']);
        }

        if (method_exists($this->httpClient, 'setLogger')) {
            $this->httpClient->setLogger($this->logger);
        }
    }

    /**
    * Instantiate the given provider and authentication or authorization protocol.
    *
    * If user not authenticated yet, the user will be redirected to the authorization Service
    * to authorize the application.
    *
    * @param string $provider Provider (case insensitive)
    *
    * @throws Exception\Exception
    * @throws Exception\RuntimeException
    * @throws Exception\UnexpectedValueException
    * @throws Exception\InvalidArgumentException
    * @throws Exception\AuthorizationDeniedException
    * @throws Exception\HttpClientFailureException
    * @throws Exception\HttpRequestFailedException
    * @throws Exception\InvalidAccessTokenException
    * @throws Exception\InvalidApplicationCredentialsException
    * @throws Exception\InvalidAuthorizationCodeException
    * @throws Exception\InvalidAuthorizationStateException
    * @throws Exception\InvalidOauthTokenException
    * @throws Exception\InvalidOpenidIdentifierException
    *
    * @return Adapter\AdapterInterface
    */
    public function authenticate($provider)
    {
        $this->logger->info("Enter Hybridauth::authenticate( $provider )");

        $adapter = $this->getAdapter($provider);

        $adapter->authenticate();

        return $adapter;
    }

    /**
    * Instantiate and returns the given provider adapter.
    *
    * @param string $provider Provider (case insensitive)
    *
    * @throws Exception\UnexpectedValueException
    * @throws Exception\InvalidArgumentException
    *
    * @return Adapter\AdapterInterface
    */
    public function getAdapter($provider)
    {
        $config = $this->getProviderConfig($provider);

        $adapter = "Hybridauth\\Provider\\$provider";

        return new $adapter($config, $this->httpClient, $this->storage, $this->logger);
    }

    /**
    * Get provider config by name.
    *
    * @param string $provider Provider (case insensitive)
    *
    * @throws Exception\UnexpectedValueException
    * @throws Exception\InvalidArgumentException
    *
    * @return array
    */
    protected function getProviderConfig($provider)
    {
        $provider = strtolower($provider);

        $providersConfig = array_change_key_case($this->config['providers'], CASE_LOWER);

        if (! isset($providersConfig[$provider])) {
            throw new InvalidArgumentException('Unknown Provider.');
        }

        if (! $providersConfig[$provider]['enabled']) {
            throw new UnexpectedValueException('Disabled Provider.');
        }

        $config = $providersConfig[$provider];

        if (! isset($config['callback']) && isset($this->config['callback'])) {
            $config['callback'] = $this->config['callback'];
        }

        return $config;
    }
}
