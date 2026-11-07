<?php

namespace HybridauthTest\HttpClient;

use Hybridauth\HttpClient\Curl;
use Hybridauth\Thirdparty\OpenID\ErrorException;
use Hybridauth\Thirdparty\OpenID\LightOpenID;
use PHPUnit\Framework\TestCase;

class TlsOpenID extends LightOpenID
{
    public function __construct($origin)
    {
        if (property_exists($this, 'trustedEndpoints')) {
            $this->trustedEndpoints = [['origin' => $origin, 'addresses' => ['127.0.0.1/32']]];
        }
    }
    public function fetch($url, $streams)
    {
        $response = $streams ? $this->request_streams($url) : $this->request_curl($url);
        if (is_string($response) && strpos($response, 'HTTP/') === 0) {
            $response = substr($response, strpos($response, "\r\n\r\n") + 4);
        }
        return $response;
    }
}

class TlsVerificationTest extends TestCase
{
    private static $server;
    private static $port;
    private static $cert;
    private static $key;
    private static $log;

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0');
        self::$port = (int) substr(strrchr(stream_socket_get_name($socket, false), ':'), 1);
        fclose($socket);
        $options = ['config' => __DIR__ . '/fixtures/tls.cnf', 'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA, 'digest_alg' => 'sha256'];
        $key = openssl_pkey_new($options);
        $csr = openssl_csr_new(['commonName' => '127.0.0.1'], $key, $options);
        $cert = openssl_csr_sign($csr, null, $key, 1, $options + ['x509_extensions' => 'server']);
        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($key, $keyPem, null, $options);
        self::$cert = tempnam(sys_get_temp_dir(), 'ha-tls-cert-');
        self::$key = tempnam(sys_get_temp_dir(), 'ha-tls-key-');
        self::$log = tempnam(sys_get_temp_dir(), 'ha-tls-log-');
        file_put_contents(self::$cert, $certPem);
        file_put_contents(self::$key, $keyPem);
        self::$server = proc_open([PHP_BINARY, __DIR__ . '/fixtures/tls-server.php'],
            [0 => ['pipe', 'r'], 1 => ['file', self::$log, 'a'], 2 => ['file', self::$log, 'a']],
            $pipes, __DIR__, array_merge(getenv(), ['HYBRIDAUTH_TLS_PORT' => (string) self::$port,
                'HYBRIDAUTH_TLS_CERT' => self::$cert, 'HYBRIDAUTH_TLS_KEY' => self::$key]));
        fclose($pipes[0]);
        for ($i = 0; $i < 50; $i++) {
            $ready = @fsockopen('127.0.0.1', self::$port, $errno, $error, 0.1);
            if ($ready) { fclose($ready); return; }
            usleep(40000);
        }
        throw new \RuntimeException('TLS fixture did not start.');
    }

    public static function tearDownAfterClass(): void
    {
        if (is_resource(self::$server)) { proc_terminate(self::$server); proc_close(self::$server); }
        foreach ([self::$cert, self::$key, self::$log] as $file) {
            if ($file) { unlink($file); }
        }
    }

    private function url() { return 'https://127.0.0.1:' . self::$port . '/'; }

    public function testTlsVerificationDefaultsAreExplicit(): void
    {
        $options = (new \ReflectionProperty(Curl::class, 'curlOptions'))->getValue(new Curl());
        $this->assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        $this->assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
        $this->assertTrue((new \ReflectionClass(LightOpenID::class))->getDefaultProperties()['verify_peer']);
    }

    public function testCurlRejectsUntrustedCertificateAndAcceptsConfiguredCa(): void
    {
        $client = new Curl();
        $client->setCurlOptions([CURLOPT_PROXY => '']);
        $this->assertFalse($client->request($this->url()));
        $this->assertNotEmpty($client->getResponseClientError());
        $client->setCurlOptions([CURLOPT_CAINFO => self::$cert]);
        $this->assertSame(['fixture' => 'trusted-local-authority'], json_decode($client->request($this->url()), true));
    }

    public function testTrustedCaDoesNotDisableHostnameVerification(): void
    {
        $client = new Curl();
        $client->setCurlOptions([CURLOPT_PROXY => '', CURLOPT_CAINFO => self::$cert,
            CURLOPT_RESOLVE => ['localhost:' . self::$port . ':127.0.0.1']]);
        $this->assertFalse($client->request('https://localhost:' . self::$port . '/'));
        $this->assertNotEmpty($client->getResponseClientError());
    }

    public function testOpenidTransportsAcceptConfiguredCaAndRejectUntrustedCertificate(): void
    {
        foreach ([false, true] as $streams) {
            $client = new TlsOpenID(rtrim($this->url(), '/'));
            $client->cainfo = self::$cert;
            $response = $client->fetch($this->url(), $streams);
            $this->assertSame(['fixture' => 'trusted-local-authority'], json_decode($response, true),
                ($streams ? 'streams: ' : 'curl: ') . $response);
            $client->cainfo = null;
            try {
                $response = @$client->fetch($this->url(), $streams);
                $this->assertNotSame(['fixture' => 'trusted-local-authority'], json_decode((string) $response, true));
            } catch (ErrorException $expected) {
                $this->assertNotEmpty($expected->getMessage());
            }
        }
    }
}
