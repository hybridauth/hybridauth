<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

namespace Hybridauth;

use Hybridauth\Exception\RuntimeException;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Storage\Session;
use Hybridauth\Logger\LoggerInterface;
use Hybridauth\Logger\Logger;
use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\HttpClient\Curl as HttpClient;
use Hybridauth\Provider\ProviderAdapter;
use Hybridauth\Deprecated\DeprecatedHybridauthTrait;

/**
 * The sole purpose for this class is to provide an unified entry point for the various providers
 * and to ensure a MINIMAL backward compatibility with Hybridauth 2.x
 */
class Hybridauth
{
    use DeprecatedHybridauthTrait;

    /**
     * Hybridauth version
     *
     * @var string
     */
    protected $version = '3.0.0-Remake';

    /**
     * Hybridauth config
     *
     * @var array
     */
    protected $config = [];

    /**
     * Storage
     *
     * @var object
     */
    protected $storage = null;

    /**
     * HttpClient
     *
     * @var object
     */
    protected $httpClient = null;

    /**
     * Logger
     *
     * @var object
     */
    protected $logger = null;

    /**
     * @param array $config
     * @param HttpClientInterface $httpClient
     * @param StorageInterface $storage
     * @param LoggerInterface $logger
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        $config = [],
        HttpClientInterface $httpClient = null,
        StorageInterface $storage = null,
        LoggerInterface $logger = null
    ) {
        if (is_string($config) && file_exists($config)) {
            $config = include $config;
        } elseif (!is_array($config)) {
            throw new InvalidArgumentException('Hybriauth config does not exist on the given path.');
        }

        $this->config = $config;

        $this->storage = $storage ? $storage : new Session();

        $this->logger = $logger ? $logger : new Logger(
            (isset($config['debug_mode']) ? $config['debug_mode'] : false),
            (isset($config['debug_file']) ? $config['debug_file'] : '')
        );

        $this->httpClient = $httpClient ? $httpClient : new HttpClient();

        if (isset($config['curl_options']) && method_exists($this->httpClient, 'setCurlOptions')) {
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
     * @param string $providerId Provider ID (canse insensitive)
     *
     * @throws Exception
     * @throws RuntimeException
     * @throws UnexpectedValueException
     * @throws InvalidArgumentException
     * @throws AuthorizationDeniedException
     * @throws HttpClientFailureException
     * @throws HttpRequestFailedException
     * @throws InvalidAccessTokenException
     * @throws InvalidApplicationCredentialsException
     * @throws InvalidAuthorizationCodeException
     * @throws InvalidAuthorizationStateException
     * @throws InvalidOauthTokenException
     * @throws InvalidOpenidIdentifierException
     *
     * @return object|null
     */
    public function authenticate($providerId)
    {
        $this->logger->info("Enter Hybridauth::authenticate( $providerId )");

        $adapter = $this->getAdapter($providerId);

        $adapter->authenticate();

        return $adapter;
    }

    /**
     * Instantiate and returns the given provider adapter.
     *
     * @return object
     */
    public function getAdapter($providerId)
    {
        $config = $this->getProviderConfigById($providerId);

        $adapter = 'Hybridauth\\Provider\\'.$providerId;

        $instance = new $adapter($config, $this->httpClient, $this->storage, $this->logger);

        return $instance;
    }

    /**
     * Get provider config by ID
     *
     * @param string $id Provider ID (canse insensitive)
     *
     * @return array
     */
    protected function getProviderConfigById($id)
    {
        $id = $this->validateProviderID($id);

        $config = $this->config['providers'][$id];

        if (isset($this->config['callback'])) {
            $config['callback'] = $this->config['callback'];
        }

        return $config;
    }

    /**
     * Get provider real provider ID. (case sensitive)
     *
     * @param string $providerId Provider ID (canse insensitive)
     *
     * @throws InvalidArgumentException
     * @throws UnexpectedValueException
     * @return string $id
     */
    protected function validateProviderID($providerId)
    {
        foreach ($this->config["providers"] as $idpId => $config) {
            if (strtolower($idpId) == strtolower($providerId)) {
                $providerId = $idpId;
            }
        }

        if (!isset($this->config['providers'][$providerId])) {
            throw new InvalidArgumentException('Unknown Provider.');
        }

        if (!$this->config['providers'][$providerId]['enabled']) {
            throw new UnexpectedValueException('Disabled Provider.');
        }

        return $providerId;
    }
}
