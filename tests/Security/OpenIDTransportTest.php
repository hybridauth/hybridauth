<?php

namespace HybridauthTest\Security;

use Hybridauth\HttpClient\Url;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Thirdparty\OpenID\LightOpenID;
use PHPUnit\Framework\TestCase;

class FixtureOpenID extends LightOpenID
{
    public $streams = false;
    public $address = '127.0.0.1';
    public function __construct() { }
    protected function resolveUrl($url)
    {
        if (parse_url($url, PHP_URL_HOST) !== 'fixture.invalid') {
            return parent::resolveUrl($url);
        }
        // Only this test fixture bypasses public-address policy to exercise real pinned transports.
        $target = Url::resolve(str_replace('fixture.invalid', '8.8.8.8', $url));
        $target['host'] = 'fixture.invalid';
        $target['address'] = $this->address;
        return $target;
    }
    protected function request_curl($url, $method = 'GET', $params = [], $update = false)
    {
        return $this->streams ? parent::request_streams($url, $method, $params, $update)
            : parent::request_curl($url, $method, $params, $update);
    }
    public function fetch($url, $method = 'GET', $params = [])
    {
        return $this->request($url, $method, $params, true);
    }
}

class OpenIDTransportTest extends TestCase
{
    private static $server;
    private static $log;
    private static $serverLog;
    private static $port;

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::$port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        self::$log = tempnam(sys_get_temp_dir(), 'ha-http-');
        self::$serverLog = tempnam(sys_get_temp_dir(), 'ha-server-');
        self::$server = proc_open([PHP_BINARY, '-S', '127.0.0.1:' . self::$port, __DIR__ . '/fixtures/openid-router.php'],
            [0 => ['pipe', 'r'], 1 => ['file', self::$serverLog, 'a'], 2 => ['file', self::$serverLog, 'a']],
            $pipes, __DIR__, array_merge(getenv(), ['HYBRIDAUTH_TEST_LOG' => self::$log]));
        fclose($pipes[0]);
        for ($i = 0; $i < 50; $i++) {
            $ready = @fsockopen('127.0.0.1', self::$port, $errno, $error, 0.1);
            if ($ready) { fclose($ready); return; }
            usleep(40000);
        }
        throw new \RuntimeException('Local OpenID fixture did not start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) {
            proc_terminate(self::$server);
            proc_close(self::$server);
        }
        unlink(self::$log);
        unlink(self::$serverLog);
    }

    private function url($path)
    {
        return 'http://fixture.invalid:' . self::$port . $path;
    }

    public function testPinnedTransportsPreserveHostAndGetParameters(): void
    {
        foreach ([false, true] as $streams) {
            $client = new FixtureOpenID(); $client->streams = $streams;
            $response = json_decode($client->fetch($this->url('/echo?existing=1'), 'GET', ['demo' => 'a b']), true);
            $this->assertSame('fixture.invalid:' . self::$port, $response['host']);
            $this->assertSame(['existing' => '1', 'demo' => 'a b'], $response['query']);
            $this->assertSame('GET', $response['method']);
        }
    }

    public function testCurlPinsIpv6WithoutResolvingTheOriginalHostname(): void
    {
        $socket = @stream_socket_server('tcp://[::1]:0');
        if (!$socket) { $this->markTestSkipped('IPv6 loopback is unavailable.'); }
        $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        $server = proc_open([PHP_BINARY, '-S', '[::1]:' . $port, __DIR__ . '/fixtures/openid-router.php'],
            [0 => ['pipe', 'r'], 1 => ['file', self::$serverLog, 'a'], 2 => ['file', self::$serverLog, 'a']],
            $pipes, __DIR__, array_merge(getenv(), ['HYBRIDAUTH_TEST_LOG' => self::$log]));
        fclose($pipes[0]);
        try {
            $ready = false;
            for ($i = 0; $i < 50; $i++) {
                $ready = @fsockopen('[::1]', $port, $errno, $error, 0.1);
                if ($ready) { fclose($ready); break; }
                usleep(40000);
            }
            $this->assertNotFalse($ready, 'IPv6 fixture did not start.');
            $client = new FixtureOpenID();
            $client->address = '::1';
            $response = json_decode($client->fetch('http://fixture.invalid:' . $port . '/echo'), true);
            $this->assertSame('fixture.invalid:' . $port, $response['host']);
            $this->assertSame('GET', $response['method']);
        } finally {
            proc_terminate($server);
            proc_close($server);
        }
    }

    public function testPinnedTransportsFollowPublicRedirectsAndUpdateClaimedIdentity(): void
    {
        foreach ([false, true] as $streams) {
            $client = new FixtureOpenID(); $client->streams = $streams;
            $response = $client->fetch($this->url('/redirect'));
            $this->assertStringContainsString('openid2.provider', $response);
            $this->assertSame($this->url('/identity'), $client->identity);
        }
    }

    public function testPinnedTransportsPreservePostBodyOn307(): void
    {
        foreach ([false, true] as $streams) {
            $client = new FixtureOpenID(); $client->streams = $streams;
            $response = json_decode($client->fetch($this->url('/post307'), 'POST', ['openid.mode' => 'check_authentication']), true);
            $this->assertSame('POST', $response['method']);
            $this->assertSame('openid.mode=check_authentication', $response['body']);
        }
    }

    public function testPinnedTransportsHandleHead405AndNeverFetchPrivateRedirect(): void
    {
        foreach ([false, true] as $streams) {
            $client = new FixtureOpenID(); $client->streams = $streams;
            $headers = $client->fetch($this->url('/head405'), 'HEAD');
            $this->assertStringContainsString('text/html', $headers['content-type']);
            file_put_contents(self::$log, '');
            try { $client->fetch($this->url('/private-redirect')); $this->fail('Private redirect followed.'); }
            catch (InvalidArgumentException $expected) {
                $this->assertSame("GET /private-redirect\n", file_get_contents(self::$log));
            }
        }
    }
}
