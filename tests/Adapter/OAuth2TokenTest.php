<?php

namespace HybridauthTest\Adapter;

use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Storage\StorageImpl;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/OAuth2Fixtures.php';

class OAuth2TokenTest extends TestCase
{
    private function adapter($http = null, $extra = [])
    {
        return new OAuth2Adapter(array_replace([
            'keys' => ['id' => 'demo', 'secret' => 'synthetic-secret'],
            'callback' => 'https://app.invalid/callback',
        ], $extra), $http ?: new OAuth2Http(), new StorageImpl());
    }

    public function testReplacingAccountDiscardsPreviousRefreshCapability(): void
    {
        $http = new OAuth2Http();
        $adapter = $this->adapter($http);
        $adapter->setAccessToken(['access_token' => 'account-A', 'refresh_token' => 'refresh-A']);
        $this->assertTrue($adapter->isRefreshTokenAvailable());
        $adapter->setAccessToken(['access_token' => 'account-B', 'expires_at' => time() - 1]);
        $this->assertFalse($adapter->isRefreshTokenAvailable());
        $this->assertNull($adapter->refreshAccessToken());
        $this->assertSame([], $http->requests);
        $this->assertSame('account-B', $adapter->getAccessToken()['access_token']);
    }

    public function testDisconnectClearsRefreshAndResourceCredentials(): void
    {
        $http = new OAuth2Http();
        $adapter = $this->adapter($http);
        $adapter->setAccessToken(['access_token' => 'account-A', 'refresh_token' => 'refresh-A']);
        $adapter->disconnect();
        $this->assertFalse($adapter->isRefreshTokenAvailable());
        $this->assertSame([], $adapter->getAccessToken());
        $adapter->apiRequest('me');
        $this->assertArrayNotHasKey('Authorization', $http->requests[0][3]);
    }

    public function testInvalidResponsesDoNotChangeTokenState(): void
    {
        $http = new OAuth2Http();
        $adapter = $this->adapter($http);
        $adapter->setAccessToken(['access_token' => 'original', 'refresh_token' => 'refresh-A']);
        $before = $adapter->getAccessToken();
        $cases = [
            '{}', '{"access_token":""}', '{"access_token":"   "}', '{"access_token":false}',
            '{"access_token":123}', '{"access_token":["nested"]}',
            '{"access_token":"new","error":"invalid_grant"}',
            '{"access_token":"new","expires_in":"soon"}',
            '{"access_token":"new","expires_in":-1}',
            '{"access_token":"new","expires_in":0.5}',
            '{"access_token":"new","expires_in":true}',
            '{"access_token":"new","expires_in":"999999999999999999999"}',
            '{"access_token":"new","expires":"soon"}',
            '{"access_token":"new","refresh_token":[]}',
            '{"access_token":"new","token_type":[]}',
            '{"access_token":"new","scope":[1]}',
            '{"access_token":"new","scope":{"read":true}}',
        ];
        foreach ($cases as $response) {
            try { $adapter->accept($response); $this->fail('Invalid token response accepted: ' . $response); }
            catch (InvalidAccessTokenException $expected) { $this->assertSame($before, $adapter->getAccessToken()); }
        }
    }

    public function testDeclaredJsonNeverFallsBackToFormParsing(): void
    {
        $http = new OAuth2Http();
        $http->headers = ['Content-Type' => ['application/problem+json; charset=utf-8']];
        $adapter = $this->adapter($http);
        foreach (['access_token=synthetic', '{broken', '[]', 'null'] as $body) {
            try { $adapter->accept($body); $this->fail('Malformed JSON accepted.'); }
            catch (InvalidAccessTokenException $expected) { $this->assertSame([], $adapter->getAccessToken()); }
        }
    }



    public function testLifetimeIsReplacedAndZeroLifetimeIsExposed(): void
    {
        $adapter = $this->adapter();
        $adapter->accept('{"access_token":"old","expires_in":3600}');
        $this->assertFalse($adapter->hasAccessTokenExpired());
        $adapter->accept('{"access_token":"new"}');
        $this->assertNull($adapter->hasAccessTokenExpired());
        $this->assertArrayNotHasKey('expires_at', $adapter->getAccessToken());
        $adapter->accept('{"access_token":"immediate","expires_in":0}');
        $this->assertSame(0, $adapter->getAccessToken()['expires_in']);
        $this->assertTrue($adapter->hasAccessTokenExpired());
    }

    public function testLegacyExpiresIsRelativeAndZeroMeansNonExpiring(): void
    {
        $adapter = $this->adapter();
        $before = time();
        $adapter->accept('access_token=legacy&expires=5183999');
        $expiresAt = $adapter->getAccessToken()['expires_at'];
        $this->assertGreaterThanOrEqual($before + 5183999, $expiresAt);
        $this->assertLessThanOrEqual(time() + 5183999, $expiresAt);
        $this->assertFalse($adapter->hasAccessTokenExpired());
        $adapter->accept('access_token=offline&expires=0');
        $this->assertArrayNotHasKey('expires_at', $adapter->getAccessToken());
        $this->assertNull($adapter->hasAccessTokenExpired());
        $adapter->accept('{"access_token":"standard","expires_in":60,"expires":0}');
        $this->assertFalse($adapter->hasAccessTokenExpired());
    }

    public function testArrayScopesAndNullFieldsAreAccepted(): void
    {
        $adapter = $this->adapter();
        $adapter->accept('{"access_token":"twitch","refresh_token":"refresh-T","scope":["openid","user:read:email"],'
            . '"token_type":"bearer","expires_in":14124}');
        $this->assertSame('openid user:read:email', $adapter->getAccessToken()['scope']);
        $adapter->accept('{"access_token":"nulls","refresh_token":null,"token_type":null,"scope":null,'
            . '"expires_in":null,"expires":null}');
        $tokens = $adapter->getAccessToken();
        $this->assertSame('nulls', $tokens['access_token']);
        $this->assertArrayNotHasKey('refresh_token', $tokens);
        $this->assertArrayNotHasKey('scope', $tokens);
        $this->assertNull($adapter->hasAccessTokenExpired());
        $adapter->accept('{"access_token":"empty-grant","scope":[]}');
        $this->assertSame('', $adapter->getAccessToken()['scope']);
    }

    public function testRefreshFlagIsResetWhenRefreshValidationFails(): void
    {
        $adapter = $this->adapter();
        $adapter->accept('{"access_token":"A","refresh_token":"refresh-A"}');
        try { $adapter->acceptRefresh('{"error":"invalid_grant"}'); $this->fail('Refresh error accepted.'); }
        catch (InvalidAccessTokenException $expected) { $this->assertNotEmpty($expected->getMessage()); }
        $adapter->accept('{"access_token":"other-account"}');
        $this->assertFalse($adapter->isRefreshTokenAvailable());
    }









    public function testRefreshRotationAndOmittedFieldsHaveExplicitSemantics(): void
    {
        $adapter = $this->adapter();
        $adapter->accept('{"access_token":"A","refresh_token":"refresh-A","scope":"read","expires_in":60}');
        $adapter->acceptRefresh('{"access_token":"B"}');
        $this->assertSame('refresh-A', $adapter->getAccessToken()['refresh_token']);
        $this->assertSame('read', $adapter->getAccessToken()['scope']);
        $this->assertArrayNotHasKey('expires_at', $adapter->getAccessToken());
        $adapter->acceptRefresh('{"access_token":"C","refresh_token":"refresh-C","scope":"limited"}');
        $this->assertSame('refresh-C', $adapter->getAccessToken()['refresh_token']);
        $adapter->accept('{"access_token":"another-account"}');
        $this->assertFalse($adapter->isRefreshTokenAvailable());
        $this->assertArrayNotHasKey('scope', $adapter->getAccessToken());
    }

    public function testTokenMetadataRoundTripsWithoutOverwritingCanonicalFields(): void
    {
        $adapter = $this->adapter();
        $adapter->accept('{"access_token":"A","scope":"read-only","instance_url":"https://instance.invalid","expires_at":1}');
        $tokens = $adapter->getAccessToken();
        $this->assertSame('read-only', $tokens['scope']);
        $this->assertSame('https://instance.invalid', $tokens['token_metadata']['instance_url']);
        $this->assertArrayNotHasKey('expires_at', $tokens);
        $adapter->setAccessToken($tokens);
        $this->assertSame($tokens, $adapter->getAccessToken());
    }
}
