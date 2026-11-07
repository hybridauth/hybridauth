<?php

namespace HybridauthTest\HttpClient;

use Hybridauth\HttpClient\Curl;
use Hybridauth\HttpClient\Guzzle;
use PHPUnit\Framework\TestCase;

class RequestIsolationTest extends TestCase
{
    private static $server;
    private static $log;
    private static $port;

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::$port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        self::$log = tempnam(sys_get_temp_dir(), 'ha-request-');
        self::$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . self::$port, __DIR__ . '/fixtures/echo.php'],
            [0 => ['pipe', 'r'], 1 => ['file', self::$log, 'a'], 2 => ['file', self::$log, 'a']], $pipes);
        fclose($pipes[0]);
        for ($i = 0; $i < 50; $i++) {
            $ready = @fsockopen('127.0.0.1', self::$port, $errno, $error, 0.1);
            if ($ready) { fclose($ready); return; }
            usleep(40000);
        }
        throw new \RuntimeException('HTTP fixture did not start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        unlink(self::$log);
    }

    private function url($path = '', $host = '127.0.0.1')
    {
        return 'http://' . $host . ':' . self::$port . '/' . $path;
    }

    public function testCredentialsAndJsonEncodingDoNotReachAnotherHost(): void
    {
        $client = new Curl();
        $first = json_decode($client->request($this->url(), 'POST', ['first' => true],
            ['Authorization' => 'Bearer synthetic-A', 'content-type' => 'application/json; charset=UTF-8']), true);
        $this->assertSame('Bearer synthetic-A', $first['authorization']);
        $this->assertSame(['first' => true], json_decode($first['body'], true));
        $second = json_decode($client->request($this->url('', 'localhost'), 'POST', ['grant_type' => 'refresh_token']), true);
        $this->assertNull($second['authorization']);
        $this->assertSame('application/x-www-form-urlencoded', $second['content_type']);
        $this->assertSame('grant_type=refresh_token', $second['body']);
    }

    public function testCustomMethodsAndBodiesAreScopedToOneRequest(): void
    {
        $client = new Curl();
        foreach (['PATCH', 'DELETE', 'PUT', 'POST', 'HEAD'] as $method) {
            $client->request($this->url(), $method, ['old' => 'body']);
            $response = json_decode($client->request($this->url('echo?existing=1#fragment'), 'GET', ['next' => 'query']), true);
            $this->assertSame('GET', $response['method'], $method);
            $this->assertSame('', $response['body'], $method);
            $this->assertSame(['existing' => '1', 'next' => 'query'], $response['query']);
        }
    }

    public function testResponseHeadersDoNotSurviveRequestsOrRedirects(): void
    {
        $client = new Curl();
        $client->request($this->url('?extra=1'));
        $this->assertSame('present', $client->getResponseHeader()['x_previous_response']);
        $client->request($this->url('?redirect=1'));
        $this->assertArrayNotHasKey('x_previous_response', $client->getResponseHeader());
        $this->assertArrayNotHasKey('x_redirect_only', $client->getResponseHeader());
    }

    public function testConfiguredCurlDefaultsRemainAvailable(): void
    {
        $client = new Curl();
        $client->setCurlOptions([CURLOPT_TIMEOUT => 7]);
        $client->request($this->url());
        $client->request($this->url());
        $this->assertSame(7, $client->getResponse()['client']['opts'][CURLOPT_TIMEOUT]);
    }

    public function testCustomHeaderPreparationStillReceivesOnlyCurrentRequestHeaders(): void
    {
        $client = new class extends Curl {
            protected function prepareRequestHeaders() {
                return array_merge(parent::prepareRequestHeaders(), ['X-Custom-Header: configured']);
            }
        };
        $first = json_decode($client->request($this->url(), 'GET', [], ['Authorization' => 'Bearer synthetic-A']), true);
        $second = json_decode($client->request($this->url()), true);
        $this->assertSame('configured', $first['custom_header']);
        $this->assertSame('configured', $second['custom_header']);
        $this->assertNull($second['authorization']);
    }

    public function testGuzzleDoesNotRetainHeadersOrErrors(): void
    {
        $transport = new class {
            public $requests = [];
            public $fail = false;
            public function request($method, $uri, $options) {
                $this->requests[] = [$method, $uri, $options];
                if ($this->fail) { throw new \RuntimeException('Synthetic transport failure'); }
                return new class {
                    public function getBody() { return 'demo'; }
                    public function getStatusCode() { return 200; }
                    public function getHeaders() { return ['content-type' => ['text/plain']]; }
                };
            }
        };
        $client = new Guzzle($transport);
        $client->request('https://first.invalid', 'POST', ['demo' => true],
            ['Authorization' => 'Bearer synthetic-A', 'Content-Type' => 'application/json']);
        $transport->fail = true;
        $this->assertSame('', $client->request('https://second.invalid'));
        $this->assertSame(0, $client->getResponseHttpCode());
        $this->assertNotEmpty($client->getResponseClientError());
        $this->assertSame([], $client->getResponseHeader());
        $transport->fail = false;
        $this->assertSame('demo', $client->request('https://second.invalid', 'POST', ['grant_type' => 'refresh_token']));
        $this->assertNull($client->getResponseClientError());
        $this->assertSame(200, $client->getResponseHttpCode());
        $this->assertSame([], $transport->requests[2][2]['headers']);
        $this->assertArrayHasKey('form_params', $transport->requests[2][2]);
    }
}
