<?php

namespace HybridauthTest\Security;

use Hybridauth\HttpClient\Url;
use Hybridauth\Exception\InvalidArgumentException;
use Hybridauth\Thirdparty\OpenID\LightOpenID;
use PHPUnit\Framework\TestCase;

class CorporateDns extends Url
{
    protected static function resolveHost($host) { return ['10.50.0.10']; }
}

class CorporateMixedDns extends Url
{
    protected static function resolveHost($host) { return ['10.50.0.10', '10.50.1.10']; }
}

class CorporateLoopbackDns extends Url
{
    protected static function resolveHost($host) { return ['127.0.0.1']; }
}

class CorporateClient extends LightOpenID
{
    public $streams = false;
    public function fetch($url, $method = 'GET', $params = []) {
        return $this->streams ? $this->request_streams($url, $method, $params)
            : $this->request($url, $method, $params);
    }
}

class CorporateAdapter extends \Hybridauth\Provider\OpenID
{
    public function client() { return $this->openIdClient; }
}

class CorporateHostnameClient extends CorporateClient
{
    protected function resolveUrl($url) { return CorporateLoopbackDns::resolve($url, $this->trustedEndpoints); }
}

class CorporateStorage implements \Hybridauth\Storage\StorageInterface
{
    private $items = [];
    public function get($key) { return isset($this->items[$key]) ? $this->items[$key] : null; }
    public function set($key, $value) { $this->items[$key] = $value; }
    public function delete($key) { unset($this->items[$key]); }
    public function clear() { $this->items = []; }
    public function deleteMatch($key) {
        foreach (array_keys($this->items) as $item) {
            if (strpos($item, $key) !== false) { unset($this->items[$item]); }
        }
    }
}

class CorporateOpenIDTest extends TestCase
{
    private static $authority;
    private static $proxy;
    private static $tls;
    private static $tlsPort;
    private static $cert;
    private static $key;
    private static $port;
    private static $proxyPort;
    private static $log;
    private static $proxyLog;
    private static $serverLog;
    private $server;

    private static function freePort()
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        return $port;
    }

    private static function start($arguments, $port, $env)
    {
        $process = proc_open($arguments, [0 => ['pipe', 'r'], 1 => ['file', self::$serverLog, 'a'],
            2 => ['file', self::$serverLog, 'a']], $pipes, __DIR__, array_merge(getenv(), $env));
        fclose($pipes[0]);
        for ($i = 0; $i < 50; $i++) {
            $ready = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
            if ($ready) { fclose($ready); return $process; }
            usleep(40000);
        }
        throw new \RuntimeException('Corporate OpenID fixture failed to start.');
    }

    public static function setUpBeforeClass(): void
    {
        self::$port = self::freePort();
        self::$proxyPort = self::freePort();
        self::$log = tempnam(sys_get_temp_dir(), 'ha-corp-');
        self::$proxyLog = tempnam(sys_get_temp_dir(), 'ha-proxy-');
        self::$serverLog = tempnam(sys_get_temp_dir(), 'ha-servers-');
        self::$authority = self::start([PHP_BINARY, '-S', '127.0.0.1:' . self::$port,
            __DIR__ . '/fixtures/openid-router.php'], self::$port, ['HYBRIDAUTH_TEST_LOG' => self::$log]);
        if (function_exists('openssl_pkey_new')) {
            $options = ['config' => __DIR__ . '/fixtures/openssl.cnf', 'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA, 'digest_alg' => 'sha256'];
            $key = openssl_pkey_new($options);
            $csr = openssl_csr_new(['commonName' => 'sso.corp.invalid'], $key, $options);
            $cert = openssl_csr_sign($csr, null, $key, 1, $options + ['x509_extensions' => 'server']);
            openssl_x509_export($cert, $certPem);
            openssl_pkey_export($key, $keyPem, null, $options);
            self::$cert = tempnam(sys_get_temp_dir(), 'ha-cert-');
            self::$key = tempnam(sys_get_temp_dir(), 'ha-key-');
            file_put_contents(self::$cert, $certPem);
            file_put_contents(self::$key, $keyPem);
            self::$tlsPort = self::freePort();
            self::$tls = self::start([PHP_BINARY, __DIR__ . '/fixtures/tls-authority.php'], self::$tlsPort,
                ['HYBRIDAUTH_TLS_PORT' => (string) self::$tlsPort,
                    'HYBRIDAUTH_TLS_CERT' => self::$cert, 'HYBRIDAUTH_TLS_KEY' => self::$key]);
        }
        self::$proxy = self::start([PHP_BINARY, __DIR__ . '/fixtures/connect-proxy.php'], self::$proxyPort,
            ['HYBRIDAUTH_PROXY_PORT' => (string) self::$proxyPort,
                'HYBRIDAUTH_TARGET_PORT' => self::$port . (self::$tlsPort ? ',' . self::$tlsPort : ''),
                'HYBRIDAUTH_PROXY_LOG' => self::$proxyLog]);
    }

    public static function tearDownAfterClass(): void
    {
        foreach ([self::$proxy, self::$tls, self::$authority] as $process) {
            if (is_resource($process)) { proc_terminate($process); proc_close($process); }
        }
        foreach ([self::$log, self::$proxyLog, self::$serverLog, self::$cert, self::$key] as $file) {
            if ($file) { unlink($file); }
        }
    }

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $_SERVER += ['REQUEST_URI' => '/callback', 'REQUEST_METHOD' => 'GET'];
        file_put_contents(self::$log, '');
        file_put_contents(self::$proxyLog, '');
    }

    protected function tearDown(): void { $_SERVER = $this->server; }

    private function origin() { return 'http://127.0.0.1:' . self::$port; }
    private function rules() { return [['origin' => $this->origin(), 'addresses' => ['127.0.0.1/32']]]; }
    private function dnsRules() { return [['origin' => 'https://sso.corp.invalid', 'addresses' => ['10.50.0.10/32']]]; }

    public function testPrivateDnsAuthorityRequiresExactOriginAndApprovedAddress(): void
    {
        $this->assertSame('10.50.0.10', CorporateDns::resolve('https://sso.corp.invalid/login', $this->dnsRules())['address']);
        foreach (['http://sso.corp.invalid/', 'https://sso.corp.invalid:444/',
            'https://other.corp.invalid/', 'https://sso.corp.invalid.attacker.invalid/'] as $url) {
            try { CorporateDns::resolve($url, $this->dnsRules()); $this->fail('Unapproved origin accepted.'); }
            catch (InvalidArgumentException $expected) { $this->assertNotEmpty($expected->getMessage()); }
        }
        $rules = $this->dnsRules(); $rules[0]['addresses'] = ['10.50.0.11'];
        $this->expectException(InvalidArgumentException::class);
        CorporateDns::resolve('https://sso.corp.invalid/', $rules);
    }

    public function testDnsRebindingOutsideApprovedCidrIsRejected(): void
    {
        $rules = $this->dnsRules(); $rules[0]['addresses'] = ['10.50.0.0/24'];
        $this->assertSame('10.50.0.10', CorporateDns::resolve('https://sso.corp.invalid/', $rules)['address']);
        $this->expectException(InvalidArgumentException::class);
        CorporateMixedDns::resolve('https://sso.corp.invalid/', $rules);
    }

    public function testIpv6PrivateAuthorityCanBeExplicitlyTrusted(): void
    {
        $rules = [['origin' => 'https://[fd12::1234]', 'addresses' => ['fd12::/64']]];
        $this->assertSame('fd12::1234', Url::resolve('https://[fd12::1234]/openid', $rules)['address']);
        $this->expectException(InvalidArgumentException::class);
        Url::resolve('https://[fd12::1234]/openid', [['origin' => 'https://[fd12::1234]', 'addresses' => ['10.0.0.0/8']]]);
    }

    public function testMalformedOrBroadHostnameRulesFailClosed(): void
    {
        $rules = [false, ['https://sso.corp.invalid'],
            [['origin' => 'https://*.corp.invalid', 'addresses' => ['10.0.0.0/8']]],
            [['origin' => 'https://sso.corp.invalid/path', 'addresses' => ['10.0.0.0/8']]],
            [['origin' => 'https://sso.corp.invalid', 'addresses' => []]],
            [['origin' => 'https://sso.corp.invalid', 'addresses' => ['10.0.0.0/33']]],
            [['origin' => 'https://sso.corp.invalid', 'addresses' => ['invalid']]]];
        foreach ($rules as $rule) {
            try { CorporateDns::resolve('https://sso.corp.invalid/', $rule); $this->fail('Invalid rule accepted.'); }
            catch (InvalidArgumentException $expected) { $this->assertNotEmpty($expected->getMessage()); }
        }
    }

    public function testBothTransportsReachAllowedInternalAuthorityAndBlockItByDefault(): void
    {
        foreach ([false, true] as $streams) {
            $client = new CorporateClient('https://demo.invalid', null, $this->rules()); $client->streams = $streams;
            $response = json_decode($client->fetch($this->origin() . '/echo'), true);
            $this->assertSame('127.0.0.1:' . self::$port, $response['host']);
            $client->trustedEndpoints = [];
            file_put_contents(self::$log, '');
            try { $client->fetch($this->origin() . '/echo'); $this->fail('Default private policy bypassed.'); }
            catch (InvalidArgumentException $expected) { $this->assertSame('', file_get_contents(self::$log)); }
        }
    }

    public function testAllowedPrivateDiscoveryAndCallbackVerificationWork(): void
    {
        $client = new CorporateClient('https://demo.invalid', null, $this->rules());
        $client->returnUrl = 'https://demo.invalid/callback';
        $identity = $this->origin() . '/corporate-identity';
        $client->data = ['openid_mode' => 'id_res', 'openid_ns' => 'http://specs.openid.net/auth/2.0',
            'openid_claimed_id' => $identity, 'openid_identity' => $identity,
            'openid_return_to' => $client->returnUrl, 'openid_assoc_handle' => 'synthetic',
            'openid_signed' => 'op_endpoint,claimed_id,identity,return_to,assoc_handle,response_nonce',
            'openid_op_endpoint' => $this->origin() . '/verify',
            'openid_response_nonce' => gmdate('Y-m-d\TH:i:s\Z') . 'synthetic', 'openid_sig' => 'synthetic'];
        $this->assertSame(1, $client->validate());
        $this->assertStringContainsString('POST /verify', file_get_contents(self::$log));
    }

    public function testProxyConnectsToCheckedIpAndPreservesHostAndAuthenticationBoundary(): void
    {
        foreach ([false, true] as $streams) {
            file_put_contents(self::$proxyLog, '');
            $proxy = 'http://demo:proxy%3Asecret@127.0.0.1:' . self::$proxyPort;
            $origin = 'http://sso.corp.invalid:' . self::$port;
            $rules = [['origin' => $origin, 'addresses' => ['127.0.0.1/32']]];
            $client = new CorporateHostnameClient('https://demo.invalid', $proxy, $rules); $client->streams = $streams;
            $response = json_decode($client->fetch($origin . '/echo', 'POST', ['demo' => 'value']), true);
            $this->assertSame('sso.corp.invalid:' . self::$port, $response['host']);
            $this->assertSame('demo=value', $response['body']);
            $this->assertFalse($response['proxy_auth']);
            $connect = json_decode(trim(file_get_contents(self::$proxyLog)), true);
            $this->assertSame('CONNECT 127.0.0.1:' . self::$port . ' HTTP/1.1', $connect['request']);
            $this->assertTrue($connect['authenticated']);
        }
    }

    public function testUnapprovedPrivateRequestNeverReachesConfiguredProxy(): void
    {
        $client = new CorporateClient('https://demo.invalid', 'http://127.0.0.1:' . self::$proxyPort);
        try { $client->fetch($this->origin() . '/echo'); $this->fail('Unapproved proxied request accepted.'); }
        catch (InvalidArgumentException $expected) { $this->assertSame('', file_get_contents(self::$proxyLog)); }
    }

    public function testPrivateRedirectCannotEscapeAllowedOriginThroughProxy(): void
    {
        $client = new CorporateClient('https://demo.invalid', 'http://127.0.0.1:' . self::$proxyPort, $this->rules());
        try { $client->fetch($this->origin() . '/private-redirect'); $this->fail('Unapproved redirect accepted.'); }
        catch (InvalidArgumentException $expected) {
            $this->assertSame("GET /private-redirect\n", file_get_contents(self::$log));
            $this->assertCount(1, array_filter(explode("\n", file_get_contents(self::$proxyLog))));
        }
    }

    public function testAdapterPassesTrustedEndpointsProxyAndCorporateCaBundle(): void
    {
        $config = ['callback' => 'https://demo.invalid/callback', 'openid_identifier' => $this->origin() . '/corporate-identity',
            'openid_trusted_endpoints' => $this->rules(), 'openid_proxy' => 'http://127.0.0.1:' . self::$proxyPort,
            'openid_cainfo' => '/configured/corporate-ca.pem'];
        $adapter = new CorporateAdapter($config, null, new CorporateStorage());
        $this->assertSame($this->rules(), $adapter->client()->trustedEndpoints);
        $this->assertSame('/configured/corporate-ca.pem', $adapter->client()->cainfo);
        $adapter->client()->cainfo = null;
        $adapter->client()->identity = $config['openid_identifier'];
        $url = $adapter->client()->authUrl();
        $this->assertStringStartsWith($this->origin() . '/verify?', $url);
        $this->assertNotEmpty(file_get_contents(self::$proxyLog));
    }

    public function testCorporateCaAndHostnameValidationWorkDirectlyAndThroughProxy(): void
    {
        if (!self::$tlsPort) { $this->markTestSkipped('OpenSSL is needed to generate the local TLS fixture.'); }
        $origin = 'https://sso.corp.invalid:' . self::$tlsPort;
        $rules = [['origin' => $origin, 'addresses' => ['127.0.0.1/32']]];
        foreach ([false, true] as $streams) {
            foreach ([null, 'http://127.0.0.1:' . self::$proxyPort] as $proxy) {
                $client = new CorporateHostnameClient('https://demo.invalid', $proxy, $rules);
                $client->streams = $streams;
                $client->cainfo = self::$cert;
                $data = json_decode($client->fetch($origin . '/echo'), true);
                $this->assertSame('sso.corp.invalid:' . self::$tlsPort, $data['host']);
            }
        }
    }

    public function testInternalTrustNeverDisablesCertificateOrHostnameChecks(): void
    {
        if (!self::$tlsPort) { $this->markTestSkipped('OpenSSL is needed to generate the local TLS fixture.'); }
        foreach ([false, true] as $streams) {
            foreach ([null, 'http://127.0.0.1:' . self::$proxyPort] as $proxy) {
                foreach (['untrusted-cert', 'wrong-host'] as $case) {
                    $host = $case === 'wrong-host' ? 'other.corp.invalid' : 'sso.corp.invalid';
                    $origin = 'https://' . $host . ':' . self::$tlsPort;
                    $client = new CorporateHostnameClient('https://demo.invalid', $proxy,
                        [['origin' => $origin, 'addresses' => ['127.0.0.1/32']]]);
                    $client->streams = $streams;
                    if ($case === 'wrong-host') { $client->cainfo = self::$cert; }
                    try { $client->fetch($origin . '/echo'); $this->fail('Invalid TLS identity accepted.'); }
                    catch (\Hybridauth\Thirdparty\OpenID\ErrorException $expected) {
                        $this->assertNotEmpty($expected->getMessage());
                    }
                }
            }
        }
    }
}
