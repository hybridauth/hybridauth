<?php

namespace HybridauthTest\Adapter;

use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Storage\StorageImpl;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/OAuth2Fixtures.php';

class OAuth2TokenFormatTest extends TestCase
{
    private function adapter($http = null, $extra = [])
    {
        return new OAuth2Adapter(array_replace([
            'keys' => ['id' => 'demo', 'secret' => 'synthetic-secret'],
            'callback' => 'https://app.invalid/callback',
        ], $extra), $http ?: new OAuth2Http(), new StorageImpl());
    }

    public function testLegacyFormResponseAndExplicitFormatOverride(): void
    {
        $http = new OAuth2Http();
        $http->headers = ['content_type' => 'application/x-www-form-urlencoded'];
        $adapter = $this->adapter($http);
        $adapter->accept('access_token=synthetic&expires_in=3600&scope=read+only');
        $this->assertSame('synthetic', $adapter->getAccessToken()['access_token']);
        $this->assertSame('read only', $adapter->getAccessToken()['scope']);
        $http->headers = ['Content-Type' => 'text/plain'];
        $adapter = $this->adapter($http, ['token_response_format' => 'form']);
        $adapter->accept('access_token=legacy');
        $this->assertSame('legacy', $adapter->getAccessToken()['access_token']);
    }

    public function testAdapterDefaultResponseFormatAppliesAndConfigurationOverridesIt(): void
    {
        $http = new OAuth2Http();
        $http->headers = ['content_type' => 'text/html; charset=utf-8'];
        $adapter = new FormTokenAdapter(['keys' => ['id' => 'demo', 'secret' => 'synthetic-secret'],
            'callback' => 'https://app.invalid/callback'], $http, new StorageImpl());
        $adapter->accept('access_token=qq-style&expires_in=7776000&refresh_token=refresh-Q');
        $this->assertSame('qq-style', $adapter->getAccessToken()['access_token']);
        $this->assertSame('refresh-Q', $adapter->getAccessToken()['refresh_token']);
        $adapter = new FormTokenAdapter(['keys' => ['id' => 'demo', 'secret' => 'synthetic-secret'],
            'callback' => 'https://app.invalid/callback', 'token_response_format' => 'auto'], $http, new StorageImpl());
        $this->expectException(InvalidAccessTokenException::class);
        $adapter->accept('access_token=qq-style');
    }

    public function testQqDeclaresFormTokenResponses(): void
    {
        $property = new \ReflectionProperty(\Hybridauth\Provider\QQ::class, 'tokenResponseFormat');
        $this->assertSame('form', $property->getDefaultValue());
    }

    public function testLegacyXmlIsExplicitAndCannotDeclareEntities(): void
    {
        $http = new OAuth2Http();
        $http->headers = ['Content-Type' => 'application/xml'];
        $xml = '<token><access_token>legacy-token</access_token><expires_in>60</expires_in></token>';
        try { $this->adapter($http)->accept($xml); $this->fail('Implicit XML token response accepted.'); }
        catch (InvalidAccessTokenException $expected) { $this->assertNotEmpty($expected->getMessage()); }
        $adapter = $this->adapter($http, ['token_response_format' => 'xml']);
        $adapter->accept($xml);
        $this->assertSame('legacy-token', $adapter->getAccessToken()['access_token']);
        $before = $adapter->getAccessToken();
        try {
            $adapter->accept('<!DOCTYPE token [<!ENTITY demo "synthetic">]><token><access_token>&demo;</access_token></token>');
            $this->fail('XML entity declaration accepted.');
        } catch (InvalidAccessTokenException $expected) { $this->assertSame($before, $adapter->getAccessToken()); }
    }

    public function testUnknownResponseFormatIsRejectedEvenForValidJson(): void
    {
        $adapter = $this->adapter(null, ['token_response_format' => 'unknown']);
        $this->expectException(InvalidAccessTokenException::class);
        $adapter->accept('{"access_token":"synthetic"}');
    }

}
