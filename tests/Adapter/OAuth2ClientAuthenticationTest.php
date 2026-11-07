<?php

namespace HybridauthTest\Adapter;

use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Storage\StorageImpl;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/OAuth2Fixtures.php';

class OAuth2ClientAuthenticationTest extends TestCase
{
    private function config($extra = [])
    {
        return array_replace(['keys' => ['id' => 'demo:id %', 'secret' => 'synthetic:secret %'],
            'callback' => 'https://app.invalid/callback'], $extra);
    }

    private function exchange($adapter)
    {
        $url = new \ReflectionMethod($adapter, 'getAuthorizeUrl');
        $url->invoke($adapter);
        $exchange = new \ReflectionMethod($adapter, 'exchangeCodeForAccessToken');
        $exchange->invoke($adapter, 'synthetic-code');
    }

    public function testDefaultPostCredentialsAreIncludedInBothGrants(): void
    {
        foreach ([OAuth2Adapter::class, \Hybridauth\Provider\Spotify::class, \Hybridauth\Provider\Google::class] as $class) {
            $http = new OAuth2Http();
            $adapter = new $class($this->config(), $http, new StorageImpl());
            $this->exchange($adapter);
            $adapter->setAccessToken(['access_token' => 'A', 'refresh_token' => 'refresh-A']);
            $adapter->refreshAccessToken();
            foreach ($http->requests as $request) {
                $this->assertSame('demo:id %', $request[2]['client_id'], $class);
                $this->assertSame('synthetic:secret %', $request[2]['client_secret'], $class);
                $this->assertArrayNotHasKey('Authorization', $request[3]);
            }
        }
    }

    public function testBasicProvidersUseEncodedCredentialsAndNeverDuplicateBodyAuthentication(): void
    {
        foreach (['Authentiq', 'Paypal', 'Yahoo', 'Pinterest', 'Reddit', 'X'] as $name) {
            $class = '\\Hybridauth\\Provider\\' . $name;
            $http = new OAuth2Http();
            $adapter = new $class($this->config(), $http, new StorageImpl());
            $this->exchange($adapter);
            $adapter->setAccessToken(['access_token' => 'A', 'refresh_token' => 'refresh-A']);
            $adapter->refreshAccessToken();
            foreach ($http->requests as $request) {
                $this->assertSame('Basic ' . base64_encode('demo%3Aid+%25:synthetic%3Asecret+%25'), $request[3]['Authorization'], $name);
                $this->assertArrayNotHasKey('client_secret', $request[2], $name);
                $this->assertArrayNotHasKey('client_id', $request[2], $name);
            }
        }
    }

    public function testConfiguredAuthenticationOverridesProviderDefault(): void
    {
        $http = new OAuth2Http();
        $adapter = new \Hybridauth\Provider\Paypal($this->config(['token_endpoint_auth_method' => 'client_secret_post']),
            $http, new StorageImpl());
        $this->exchange($adapter);
        $this->assertSame('synthetic:secret %', $http->requests[0][2]['client_secret']);
        $this->assertArrayNotHasKey('Authorization', $http->requests[0][3]);
        $http = new OAuth2Http();
        $adapter = new OAuth2Adapter($this->config(['token_endpoint_auth_method' => 'client_secret_basic']), $http, new StorageImpl());
        $this->exchange($adapter);
        $this->assertArrayHasKey('Authorization', $http->requests[0][3]);
        $this->assertArrayNotHasKey('client_secret', $http->requests[0][2]);
    }

    public function testPublicClientNeedsNoSecretAndAlwaysUsesS256WithoutClientAuthentication(): void
    {
        foreach ([OAuth2Adapter::class, \Hybridauth\Provider\X::class] as $class) {
            $http = new OAuth2Http();
            $adapter = new $class($this->config(['keys' => ['id' => 'public-demo'], 'token_endpoint_auth_method' => 'none']),
                $http, new StorageImpl());
            $this->exchange($adapter);
            $this->assertArrayHasKey('code_verifier', $http->requests[0][2]);
            $adapter->setAccessToken(['access_token' => 'A', 'refresh_token' => 'refresh-A']);
            $adapter->refreshAccessToken();
            foreach ($http->requests as $request) {
                $this->assertSame('public-demo', $request[2]['client_id']);
                $this->assertArrayNotHasKey('client_secret', $request[2]);
                $this->assertArrayNotHasKey('Authorization', $request[3]);
            }
        }
    }

    public function testUnsafeOrUnknownAuthenticationConfigurationIsRejected(): void
    {
        foreach ([['token_endpoint_auth_method' => 'unknown'],
            ['keys' => ['id' => 'public-demo']],
            ['token_endpoint_auth_method' => 'none', 'pkce' => false],
            ['token_endpoint_auth_method' => 'none', 'supportRequestState' => false]] as $extra) {
            try { new OAuth2Adapter($this->config($extra), new OAuth2Http(), new StorageImpl()); $this->fail('Invalid client configuration accepted.'); }
            catch (InvalidApplicationCredentialsException $expected) { $this->assertNotEmpty($expected->getMessage()); }
        }
    }

    public function testExplicitRefreshParametersDoNotPersistAndStillAuthenticate(): void
    {
        $http = new OAuth2Http();
        $adapter = new OAuth2Adapter($this->config(), $http, new StorageImpl());
        $adapter->setAccessToken(['access_token' => 'A', 'refresh_token' => 'stored-refresh']);
        $adapter->refreshAccessToken(['grant_type' => 'refresh_token', 'refresh_token' => 'one-off-refresh']);
        $adapter->refreshAccessToken();
        $this->assertSame('one-off-refresh', $http->requests[0][2]['refresh_token']);
        $this->assertSame('stored-refresh', $http->requests[1][2]['refresh_token']);
        $this->assertSame('synthetic:secret %', $http->requests[0][2]['client_secret']);
    }

    public function testWeChatRetainsItsProviderSpecificCredentialNames(): void
    {
        $http = new OAuth2Http();
        $adapter = new \Hybridauth\Provider\WeChat($this->config(), $http, new StorageImpl());
        $this->exchange($adapter);
        $this->assertSame('demo:id %', $http->requests[0][2]['appid']);
        $this->assertSame('synthetic:secret %', $http->requests[0][2]['secret']);
        $this->assertArrayNotHasKey('client_id', $http->requests[0][2]);
        $this->assertArrayNotHasKey('client_secret', $http->requests[0][2]);
        $adapter->setAccessToken(['access_token' => 'A', 'refresh_token' => 'refresh-A']);
        $adapter->refreshAccessToken();
        $this->assertSame('demo:id %', $http->requests[1][2]['appid']);
        $this->assertArrayNotHasKey('client_secret', $http->requests[1][2]);
    }

    public function testCustomBasicHeaderDoesNotProduceTwoAuthenticationMethods(): void
    {
        $http = new OAuth2Http();
        $adapter = new class($this->config(), $http, new StorageImpl()) extends OAuth2Adapter {
            protected function initialize() {
                parent::initialize();
                $this->tokenExchangeHeaders = ['authorization' => 'Basic custom-provider-credential'];
            }
        };
        $this->exchange($adapter);
        $this->assertSame('Basic custom-provider-credential', $http->requests[0][3]['authorization']);
        $this->assertArrayNotHasKey('client_secret', $http->requests[0][2]);
    }
}
