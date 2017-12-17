<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\HttpClient;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TransferException;

/**
 * HybridAuth Guzzle Http client
 *
 * Note: This is just a proof of concept. Feel free to improve it.
 *
 * Example:
 *
 * <code>
 *  $guzzle = new Hybridauth\HttpClient\Guzzle( new GuzzleHttp\Client(), [
 *      'verify'  => '/path/to/your/certificate.crt',
 *      'headers' => [ 'User-Agent' => '..' ]
 *      // 'proxy' => ...
 *  ]);
 *
 *  $adapter = new Hybridauth\Provider\Github( $config, $guzzle );
 *
 *  $adapter->authenticate();
 * </code>
 */
class Guzzle implements HttpClientInterface
{
    /**
    * Method request() arguments
    *
    * This is used for debugging.
    *
    * @var array
    */
    protected $requestArguments = [];

    /**
    * Default request headers
    *
    * @var array
    */
    protected $requestHeader = [];

    /**
    * Raw response returned by server
    *
    * @var string
    */
    protected $responseBody = '';

    /**
    * Headers returned in the response
    *
    * @var array
    */
    protected $responseHeader = [];

    /**
    * Response HTTP status code
    *
    * @var integer
    */
    protected $responseHttpCode = 0;

    /**
    * Last curl error number
    *
    * @var mixed
    */
    protected $responseClientError = null;

    /**
    * Information about the last transfer
    *
    * @var mixed
    */
    protected $responseClientInfo = [];

    /**
    * Hybridauth logger instance
    *
    * @var object
    */
    protected $logger = null;

    /**
    * GuzzleHttp client
    *
    * @var object
    */
    protected $client = null;

    /**
    * ..
    */
    public function __construct($client = null, $config = [])
    {
        $this->client = $client ? $client : new Client($config);
    }

    /**
    * {@inheritdoc}
    */
    public function request($uri, $method = 'GET', $parameters = [], $headers = [])
    {
        $this->requestHeader = array_replace($this->requestHeader, (array) $headers);

        $this->requestArguments = [
            'uri' => $uri,
            'method' => $method,
            'parameters' => $parameters,
            'headers' => $this->requestHeader,
        ];

        $response = null;

        try {
            if ('GET' == $method) {
                $response = $this->client->get($uri, ['query' => $parameters, 'headers' => $this->requestHeader]);
            }

            if ('POST' == $method) {
                $body_content = 'form_params';

                if (isset($this->requestHeader['Content-Type']) && $this->requestHeader['Content-Type'] == 'application/json') {
                    $body_content = 'json';
                }

                $response = $this->client->post($uri, [$body_content => $parameters, 'headers' => $this->requestHeader]);
            }

            if ('PUT' == $method) {
                $body_content = 'form_params';

                if (isset($this->requestHeader['Content-Type']) && $this->requestHeader['Content-Type'] == 'application/json') {
                    $body_content = 'json';
                }

                $response = $this->client->put($uri, [$body_content => $parameters, 'headers' => $this->requestHeader]);
            }
        }

        // guess this will do it for now
        catch (\Exception $e) {
            $response = $e->getResponse();

            $this->responseClientError = $e->getMessage();
        }

        if (! $this->responseClientError) {
            $this->responseBody     = $response->getBody();
            $this->responseHttpCode = $response->getStatusCode();
            $this->responseHeader   = $response->getHeaders();
        }

        if ($this->logger) {
            $this->logger->debug(sprintf('%s::request( %s, %s ), response:', get_class($this), $uri, $method), $this->getResponse());

            if ($this->responseClientError) {
                $this->logger->error(sprintf('%s::request( %s, %s ), error:', get_class($this), $uri, $method), [$this->responseClientError]);
            }
        }

        return $this->responseBody;
    }

    /**
    * {@inheritdoc}
    */
    public function getResponse()
    {
        return [
            'request' => $this->getRequestArguments(),
            'response' => [
                'code'    => $this->getResponseHttpCode(),
                'headers' => $this->getResponseHeader(),
                'body'    => $this->getResponseBody(),
            ],
            'client' => [
                'error' => $this->getResponseClientError(),
                'info'  => $this->getResponseClientInfo(),
                'opts'  => null,
            ],
        ];
    }

    /**
    * Set logger instance
    *
    * @param object $logger
    */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
    * {@inheritdoc}
    */
    public function getResponseBody()
    {
        return $this->responseBody;
    }

    /**
    * {@inheritdoc}
    */
    public function getResponseHeader()
    {
        return $this->responseHeader;
    }

    /**
    * {@inheritdoc}
    */
    public function getResponseHttpCode()
    {
        return $this->responseHttpCode;
    }

    /**
    * {@inheritdoc}
    */
    public function getResponseClientError()
    {
        return $this->responseClientError;
    }

    /**
    * @return array
    */
    protected function getResponseClientInfo()
    {
        return $this->responseClientInfo;
    }

    /**
    * Returns method request() arguments
    *
    * This is used for debugging.
    *
    * @return array
    */
    protected function getRequestArguments()
    {
        return $this->requestArguments;
    }
}
