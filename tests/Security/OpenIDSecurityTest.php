<?php

namespace HybridauthTest\Security;

use Hybridauth\Data\Random;
use Hybridauth\HttpClient\Curl;
use Hybridauth\HttpClient\Url;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\Thirdparty\OpenID\LightOpenID;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/SecurityFixtures.php';

class ScriptedOpenID extends LightOpenID
{
    public $responses = [];
    public $calls = [];
    public function __construct() { }
    public $parameters = [];
    public function fetch($url, $method = 'GET', $params = []) { return $this->request($url, $method, $params); }
    public function raw($transport, $url) {
        return $transport === 'curl' ? parent::request_curl($url) : parent::request_streams($url);
    }
    protected function request_curl($url, $method = 'GET', $params = [], $update = false) {
        $this->calls[] = [$url, $method];
        $this->parameters[] = $params;
        $response = array_shift($this->responses);
        $this->responseCode = $response[0];
        $this->headers = $response[1];
        return $method === 'HEAD' ? $this->headers : $response[2];
    }
}

class MixedDns extends Url
{
    protected static function resolveHost($host) { return ['8.8.8.8', '127.0.0.1']; }
}

class Ipv6OnlyDns extends Url
{
    protected static function lookupIpv4($host) { return []; }
    protected static function lookupIpv6($host) { return ['2606:4700:4700::1111']; }
}

class DualStackDns extends Url
{
    protected static function lookupIpv4($host) { return ['8.8.8.8']; }
    protected static function lookupIpv6($host) { return ['2001:4860:4860::8888']; }
}

class PrivateAaaaDns extends Url
{
    protected static function lookupIpv4($host) { return ['8.8.8.8']; }
    protected static function lookupIpv6($host) { return ['fd00::1']; }
}

class SteamHttp extends Curl
{
    public $url;
    public function request($uri, $method = 'GET', $parameters = [], $headers = [], $multipart = false) {
        $this->url = $uri;
        return '{"response":{"players":[{"personaname":"Demo"}]}}';
    }
}

class OpenIDSecurityTest extends TestCase
{
    private function config()
    {
        return ['keys' => ['id' => 'demo', 'secret' => 'synthetic-secret'],
            'callback' => 'https://demo.invalid/callback?provider=Telegram#fragment'];
    }



    public function testOpenidConstructorReadsGetAndPostSuperglobals(): void
    {
        $server = $_SERVER; $get = $_GET; $post = $_POST;
        try {
            $_SERVER['REQUEST_URI'] = '/callback?openid.mode=id_res';
            foreach (['GET', 'POST'] as $method) {
                $_SERVER['REQUEST_METHOD'] = $method;
                $_GET = ['openid_mode' => 'id_res', 'source' => 'get'];
                $_POST = ['openid_mode' => 'cancel', 'source' => 'post'];
                $client = new LightOpenID('https://demo.invalid');
                $this->assertSame($method === 'GET' ? $_GET : $_POST, $client->data);
                $this->assertSame($method === 'GET' ? 'id_res' : 'cancel', $client->mode);
            }
        } finally { $_SERVER = $server; $_GET = $get; $_POST = $post; }
    }

    public function testSteamApiKeyUsesHttps(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER += ['REQUEST_URI' => '/callback', 'REQUEST_METHOD' => 'GET'];
            $http = new SteamHttp();
            $steam = new \Hybridauth\Provider\Steam($this->config(), $http, new MemoryStorage());
            $profile = $steam->getUserProfileWebAPI('synthetic-key', '76561198000000000');
            $this->assertSame('https', parse_url($http->url, PHP_URL_SCHEME));
            $this->assertStringContainsString('key=synthetic-key', $http->url);
            $this->assertSame('Demo', $profile['displayName']);
        } finally { $_SERVER = $server; }
    }

    public function testPrivateAndNonHttpOpenidDestinationsAreRejected(): void
    {
        $urls = ['http://127.0.0.1/', 'http://127.1/', 'http://2130706433/', 'http://0x7f000001/',
            'http://10.0.0.1/', 'http://172.16.0.1/', 'http://192.168.1.1/', 'http://169.254.169.254/',
            'http://100.64.0.1/', 'http://0.0.0.0/', 'http://224.0.0.1/', 'http://[::1]/',
            'http://[::ffff:127.0.0.1]/', 'http://[fc00::1]/', 'http://[fe80::1]/',
            'http://[2002:7f00:1::]/', 'file:///etc/passwd', 'ftp://8.8.8.8/file',
            'http://user:pass@8.8.8.8/', "http://8.8.8.8/\r\nHost: localhost"];
        foreach ($urls as $url) {
            try { Url::resolve($url); $this->fail('Unsafe URL accepted: ' . $url); }
            catch (InvalidArgumentException $expected) { $this->assertNotEmpty($expected->getMessage()); }
        }
        $this->assertSame('8.8.8.8', Url::resolve('https://8.8.8.8/')['address']);
        $this->assertSame('2606:4700:4700::1111', Url::resolve('https://[2606:4700:4700::1111]/')['address']);
    }

    public function testMixedPublicAndPrivateDnsAnswersAreRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MixedDns::resolve('https://example.invalid/');
    }

    public function testAaaaRecordsAreResolvedAndValidated(): void
    {
        $this->assertSame('2606:4700:4700::1111', Ipv6OnlyDns::resolve('https://ipv6-only.invalid/')['address']);
        $this->assertSame('8.8.8.8', DualStackDns::resolve('https://dual-stack.invalid/')['address']);
        $this->expectException(InvalidArgumentException::class);
        PrivateAaaaDns::resolve('https://rebinding.invalid/');
    }

    public function testCurlIsUsedUnderOpenBasedir(): void
    {
        if (!function_exists('curl_init')) {
            $this->markTestSkipped('cURL is not available.');
        }
        $root = dirname(__DIR__, 2);
        $command = [PHP_BINARY, '-d', 'open_basedir=' . $root, __DIR__ . '/fixtures/open-basedir-transport.php'];
        $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        proc_close($process);
        $this->assertSame('curl', $output, $errors);
    }

    public function testDiscoveryHopsKeepClaimedIdentityButRedirectsUpdateIt(): void
    {
        $xrds = '<xrds><Service><Type>http://specs.openid.net/auth/2.0/signon</Type>'
            . '<URI>https://8.8.8.8/server</URI></Service></xrds>';
        $client = new ScriptedOpenID();
        $client->identity = 'http://8.8.8.8/me#fragment';
        $client->responses = [[200, ['x-xrds-location' => 'http://8.8.8.8/xrds'], ''],
            [200, ['content-type' => 'application/xrds+xml'], ''],
            [200, ['content-type' => 'application/xrds+xml'], $xrds]];
        $this->assertSame('https://8.8.8.8/server', $client->discover('http://8.8.8.8/me#fragment'));
        $this->assertSame('http://8.8.8.8/me#fragment', $client->identity);

        $client = new ScriptedOpenID();
        $client->identity = 'http://8.8.8.8/old';
        $client->responses = [[301, ['location' => '/me'], ''],
            [200, ['content-type' => 'application/xrds+xml'], ''],
            [200, ['content-type' => 'application/xrds+xml'], $xrds]];
        $this->assertSame('https://8.8.8.8/server', $client->discover('http://8.8.8.8/old'));
        $this->assertSame('http://8.8.8.8/me', $client->identity);
    }

    public function testBothRawTransportsRejectPrivateUrlsBeforeConnecting(): void
    {
        foreach (['curl', 'streams'] as $transport) {
            try { (new ScriptedOpenID())->raw($transport, 'http://127.0.0.1:18098/'); $this->fail('Private URL accepted.'); }
            catch (InvalidArgumentException $expected) { $this->assertNotEmpty($expected->getMessage()); }
        }
    }

    public function testRedirectToPrivateAddressIsRejectedBeforeSecondRequest(): void
    {
        $client = new ScriptedOpenID();
        $client->responses = [[302, ['location' => 'http://127.0.0.1/private'], '']];
        try { $client->fetch('http://8.8.8.8/'); $this->fail('Private redirect accepted.'); }
        catch (InvalidArgumentException $expected) { $this->assertCount(1, $client->calls); }
    }

    public function testPublicRedirectAndHeadFallbackStillWork(): void
    {
        $client = new ScriptedOpenID();
        $client->responses = [[302, ['location' => '../identity'], ''], [405, [], ''], [200, ['content-type' => 'text/html'], 'demo']];
        $this->assertSame(['content-type' => 'text/html'], $client->fetch('http://8.8.8.8/a/start', 'HEAD'));
        $this->assertSame([['http://8.8.8.8/a/start', 'HEAD'], ['http://8.8.8.8/identity', 'HEAD'],
            ['http://8.8.8.8/identity', 'GET']], $client->calls);
        $this->assertSame('https://8.8.8.8/next', Url::redirect('https://8.8.8.8/start?secret=demo', '/next'));
        $this->assertSame('https://8.8.8.8/next', Url::redirect('https://8.8.8.8/start', '../../next'));
        $this->assertSame('https://8.8.8.8/a/', Url::redirect('https://8.8.8.8/a/b', '.'));
        $this->assertSame('https://8.8.8.8/start?q=demo', Url::redirect('https://8.8.8.8/start?old=value', '?q=demo'));
    }

    public function testDiscoveredPrivateProviderIsBlocked(): void
    {
        $client = new ScriptedOpenID();
        $client->responses = [[200, ['content-type' => 'text/html'], ''],
            [200, [], '<link rel="openid2.provider" href="http://127.0.0.1/check">']];
        $client->returnUrl = 'https://demo.invalid/callback';
        $client->data = ['openid_mode' => 'id_res', 'openid_ns' => 'http://specs.openid.net/auth/2.0',
            'openid_claimed_id' => 'http://8.8.8.8/identity', 'openid_identity' => 'http://8.8.8.8/identity',
            'openid_return_to' => $client->returnUrl, 'openid_assoc_handle' => 'demo',
            'openid_signed' => 'op_endpoint,claimed_id,identity,return_to,assoc_handle,response_nonce',
            'openid_op_endpoint' => 'http://127.0.0.1/check',
            'openid_response_nonce' => gmdate('Y-m-d\TH:i:s\Z') . 'synthetic', 'openid_sig' => 'invalid'];
        try { $client->validate(); $this->fail('Private provider endpoint accepted.'); }
        catch (InvalidArgumentException $expected) { $this->assertCount(2, $client->calls); }
    }

    public function testDiscoveredPrivateXrdsLocationIsBlocked(): void
    {
        $client = new ScriptedOpenID();
        $client->responses = [[200, ['x-xrds-location' => 'http://127.0.0.1/private'], '']];
        try { $client->discover('http://8.8.8.8/identity'); $this->fail('Private XRDS URL accepted.'); }
        catch (InvalidArgumentException $expected) { $this->assertCount(1, $client->calls); }
    }

    public function testOpenidRejectsHttpsDowngradeAndRedirectLoops(): void
    {
        $client = new ScriptedOpenID();
        $client->responses = [[302, ['location' => 'http://8.8.8.8/'], '']];
        try { $client->fetch('https://8.8.8.8/'); $this->fail('TLS downgrade accepted.'); }
        catch (\Hybridauth\Thirdparty\OpenID\ErrorException $expected) { $this->assertCount(1, $client->calls); }
        $client = new ScriptedOpenID();
        $client->responses = array_fill(0, 6, [302, ['location' => '/loop'], '']);
        try { $client->fetch('https://8.8.8.8/loop'); $this->fail('Redirect loop accepted.'); }
        catch (\Hybridauth\Thirdparty\OpenID\ErrorException $expected) { $this->assertCount(6, $client->calls); }
    }

    public function testPostRedirectPreservesBodyFor307AndDropsItFor303(): void
    {
        foreach ([307, 303] as $status) {
            $client = new ScriptedOpenID();
            $client->responses = [[$status, ['location' => '/next'], ''], [200, [], 'ok']];
            $this->assertSame('ok', $client->fetch('https://8.8.8.8/', 'POST', ['openid.mode' => 'check_authentication']));
            $this->assertSame($status === 307 ? 'POST' : 'GET', $client->calls[1][1]);
            $this->assertSame($status === 307 ? ['openid.mode' => 'check_authentication'] : [], $client->parameters[1]);
        }
    }
}
