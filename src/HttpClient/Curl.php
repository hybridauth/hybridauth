<?php
/*!
* Hybridauth
* https://hybridauth.github.io | https://github.com/hybridauth/hybridauth
*  (c) 2017 Hybridauth authors | https://hybridauth.github.io/license.html
*/

namespace Hybridauth\HttpClient;

/**
 * Hybridauth default Http client
 */
class Curl implements HttpClientInterface
{
    /**
     * Default curl options
     *
     * These defaults options can be overwritten when sending requests.
     *
     * See setCurlOptions()
     *
     * @var array
     */
    protected $curlOptions = [
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_ENCODING => 'identity',
        // phpcs:ignore
        CURLOPT_USERAGENT => 'Hybridauth, PHP Social Authentication Library (https://github.com/hybridauth/hybridauth)',
    ];

    /**
     * Method request() arguments
     *
     * This is used for debugging.
     *
     * @var array
     */
    protected $requestArguments = [];

    protected $requestOptions = [];

    /**
     * Default request headers
     *
     * @var array
     */
    protected $requestHeader = [
        'Accept' => '*/*',
        'Cache-Control' => 'max-age=0',
        'Connection' => 'keep-alive',
        'Expect' => '',
        'Pragma' => '',
    ];

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
     * @var int
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
     * {@inheritdoc}
     */
    public function request($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false)
    {
        $requestHeaders = Util::mergeHeaders($this->requestHeader, $headers);
        $curlOptions = $this->curlOptions;
        $this->responseHeader = [];
        $this->responseBody = '';
        $this->responseHttpCode = 0;
        $this->responseClientError = null;
        $this->responseClientInfo = [];
        $method = strtoupper($method);

        $this->requestArguments = [
            'uri' => $uri,
            'method' => $method,
            'parameters' => $parameters,
            'headers' => $requestHeaders,
        ];

        $curl = curl_init();

        switch ($method) {
            case 'GET':
            case 'DELETE':
                unset($curlOptions[CURLOPT_POST]);
                unset($curlOptions[CURLOPT_POSTFIELDS]);

                $uri = explode('#', $uri, 2)[0];
                if ($parameters) {
                    $uri .= (strpos($uri, '?') !== false ? '&' : '?') . http_build_query($parameters);
                }
                if ($method === 'DELETE') {
                    $curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
                }
                break;
            case 'PUT':
            case 'POST':
            case 'PATCH':
                $body_content = $multipart ? $parameters : http_build_query($parameters);
                if (Util::isJsonContentType($requestHeaders)) {
                    $body_content = json_encode($parameters);
                }

                if ($method === 'POST') {
                    $curlOptions[CURLOPT_POST] = true;
                } else {
                    $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
                }
                $curlOptions[CURLOPT_POSTFIELDS] = $body_content;
                break;
            case 'HEAD':
                $curlOptions[CURLOPT_NOBODY] = true;
                break;
            default:
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $method;
        }

        $curlOptions[CURLOPT_URL] = $uri;
        $curlOptions[CURLOPT_HTTPHEADER] = $this->prepareRequestHeaders();
        $curlOptions[CURLOPT_HEADERFUNCTION] = [$this, 'fetchResponseHeader'];
        $this->requestOptions = $curlOptions;

        foreach ($curlOptions as $opt => $value) {
            curl_setopt($curl, $opt, $value);
        }

        $response = curl_exec($curl);

        $this->responseBody = $response;
        $this->responseHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $this->responseClientError = curl_error($curl);
        $this->responseClientInfo = curl_getinfo($curl);

        if ($this->logger) {
            // phpcs:ignore
            $this->logger->debug(sprintf('%s::request( %s, %s ), response:', get_class($this), $uri, $method), $this->getResponse());

            if (false === $response) {
                // phpcs:ignore
                $this->logger->error(sprintf('%s::request( %s, %s ), error:', get_class($this), $uri, $method), [$this->responseClientError]);
            }
        }

        if (version_compare(PHP_VERSION, '8.5.0', '<')) {
            curl_close($curl);
        }

        return $this->responseBody;
    }

    /**
     * Get response details
     *
     * @return array Map structure of details
     */
    public function getResponse()
    {
        $curlOptions = $this->requestOptions ?: $this->curlOptions;

        $curlOptions[CURLOPT_HEADERFUNCTION] = '*omitted';

        return [
            'request' => $this->getRequestArguments(),
            'response' => [
                'code' => $this->getResponseHttpCode(),
                'headers' => $this->getResponseHeader(),
                'body' => $this->getResponseBody(),
            ],
            'client' => [
                'error' => $this->getResponseClientError(),
                'info' => $this->getResponseClientInfo(),
                'opts' => $curlOptions,
            ],
        ];
    }

    /**
     * Reset curl options
     *
     * @param array $curlOptions
     */
    public function setCurlOptions($curlOptions)
    {
        foreach ($curlOptions as $opt => $value) {
            $this->curlOptions[$opt] = $value;
        }
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

    /**
     * Fetch server response headers
     *
     * @param mixed $curl
     * @param string $header
     *
     * @return int
     */
    protected function fetchResponseHeader($curl, $header)
    {
        if (strpos($header, 'HTTP/') === 0) {
            $this->responseHeader = [];
        }
        $pos = strpos($header, ':');

        if (!empty($pos)) {
            $key = str_replace('-', '_', strtolower(substr($header, 0, $pos)));

            $value = trim(substr($header, $pos + 2));

            $this->responseHeader[$key] = $value;
        }

        return strlen($header);
    }

    /**
     * Convert request headers to the expect curl format
     *
     * @return array
     */
    protected function prepareRequestHeaders()
    {
        $headers = [];

        $requestHeaders = isset($this->requestArguments['headers'])
            ? $this->requestArguments['headers'] : $this->requestHeader;
        foreach ($requestHeaders as $header => $value) {
            $headers[] = trim($header) . ': ' . trim($value);
        }

        return $headers;
    }
}
