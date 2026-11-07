<?php

namespace HybridauthTest\Adapter;

use Hybridauth\Exception\AuthorizationDeniedException;
use Hybridauth\Exception\InvalidApplicationCredentialsException;
use Hybridauth\Exception\InvalidAuthorizationCodeException;
use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Storage\StorageImpl;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/OAuth2Fixtures.php';

class OAuth2AuthorizationTest extends TestCase
{
    private function config($extra = [])
    {
        return array_replace(['keys' => ['id' => 'demo', 'secret' => 'synthetic-secret'],
            'callback' => 'https://app.invalid/callback', 'pkce' => 'S256'], $extra);
    }

    private function query($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        return $query;
    }

    public function testAuthorizationPreservesQueriesAndFragmentsAndNormalizesScopes(): void
    {
        foreach (['?audience=demo#fragment', '?', '?audience=demo&'] as $suffix) {
            $adapter = new OAuth2Adapter($this->config(['scope' => ['read', 'write'],
                'endpoints' => ['authorize_url' => 'https://provider.invalid/authorize' . $suffix]]),
                new OAuth2Http(), new StorageImpl());
            $url = $adapter->url();
            $query = $this->query($url);
            $this->assertSame('code', $query['response_type']);
            $this->assertSame('read write', $query['scope']);
            if (strpos($suffix, 'audience=') !== false) { $this->assertSame('demo', $query['audience']); }
            if (strpos($suffix, '#') !== false) { $this->assertSame('fragment', parse_url($url, PHP_URL_FRAGMENT)); }
        }
    }

    public function testEachAttemptHasFreshStateAndVerifierWithS256Challenge(): void
    {
        $storage = new StorageImpl();
        $adapter = new OAuth2Adapter($this->config(), new OAuth2Http(), $storage);
        $first = $this->query($adapter->url());
        $verifier = $storage->get('OAuth2Adapter.pkce_code_verifier');
        $second = $this->query($adapter->url());
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9._~-]{43,128}$/D', $verifier);
        $this->assertSame('S256', $first['code_challenge_method']);
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='), $first['code_challenge']);
        $this->assertNotSame($first['state'], $second['state']);
        $this->assertNotSame($first['code_challenge'], $second['code_challenge']);
        $this->assertSame($second['state'], $storage->get('OAuth2Adapter.authorization_state'));
    }

    public function testExplicitStateIsPreservedWhilePkceRemainsFresh(): void
    {
        $adapter = new OAuth2Adapter($this->config(['authorize_url_parameters' => ['state' => 'caller-managed']]),
            new OAuth2Http(), new StorageImpl());
        $this->assertSame('caller-managed', $this->query($adapter->url())['state']);
        $this->assertSame('caller-managed', $this->query($adapter->url())['state']);
    }

    public function testVerifierSurvivesReconstructionAndIsConsumedBeforeExchange(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $storage = new StorageImpl();
            $http = new OAuth2Http();
            $adapter = new OAuth2Adapter($this->config(), $http, $storage);
            $query = $this->query($adapter->url());
            $verifier = $storage->get('OAuth2Adapter.pkce_code_verifier');
            $callback = new OAuth2Adapter($this->config(), $http, $storage);
            $callback->input = ['state' => $query['state'], 'code' => 'synthetic-code'];
            $callback->finish();
            $this->assertSame($verifier, $http->requests[0][2]['code_verifier']);
            $this->assertNull($storage->get('OAuth2Adapter.pkce_code_verifier'));
            $this->assertNull($storage->get('OAuth2Adapter.authorization_state'));
            $this->expectException(InvalidAuthorizationStateException::class);
            $callback->finish();
        } finally { $_SERVER = $server; }
    }

    public function testMissingVerifierCannotSilentlyDisablePkce(): void
    {
        $http = new OAuth2Http();
        $adapter = new OAuth2Adapter($this->config(), $http, new StorageImpl());
        try { $adapter->exchange('code'); $this->fail('Missing verifier accepted.'); }
        catch (InvalidAuthorizationCodeException $expected) { $this->assertSame([], $http->requests); }
    }

    public function testVerifierIsConsumedEvenIfTransportFails(): void
    {
        $storage = new StorageImpl();
        $http = new OAuth2Http();
        $http->fail = true;
        $adapter = new OAuth2Adapter($this->config(), $http, $storage);
        $adapter->url();
        try { $adapter->exchange('code'); $this->fail('Transport failure ignored.'); }
        catch (\RuntimeException $expected) { $this->assertNull($storage->get('OAuth2Adapter.pkce_code_verifier')); }
    }

    public function testInvalidStateDiscardsVerifierWithoutExchangingCode(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $storage = new StorageImpl();
            $http = new OAuth2Http();
            $adapter = new OAuth2Adapter($this->config(), $http, $storage);
            $adapter->url();
            $adapter->input = ['state' => 'wrong-state', 'code' => 'code'];
            try { $adapter->finish(); $this->fail('Invalid state accepted.'); }
            catch (InvalidAuthorizationStateException $expected) {
                $this->assertNull($storage->get('OAuth2Adapter.pkce_code_verifier'));
                $this->assertSame([], $http->requests);
            }
        } finally { $_SERVER = $server; }
    }

    public function testPkceRejectsPlainOrDisabledState(): void
    {
        foreach ([['pkce' => 'plain'], ['supportRequestState' => false]] as $extra) {
            try { new OAuth2Adapter($this->config($extra), new OAuth2Http(), new StorageImpl()); $this->fail('Unsafe PKCE configuration accepted.'); }
            catch (InvalidApplicationCredentialsException $expected) { $this->assertNotEmpty($expected->getMessage()); }
        }
    }

    public function testDisablingRequiredPkceExplainsWhy(): void
    {
        $cases = [
            [OAuth2Adapter::class, $this->config(['pkce' => false, 'token_endpoint_auth_method' => 'none'])],
            [\Hybridauth\Provider\X::class, $this->config(['pkce' => false, 'token_endpoint_auth_method' => 'none'])],
        ];
        foreach ($cases as $case) {
            list($class, $config) = $case;
            try { new $class($config, new OAuth2Http(), new StorageImpl()); $this->fail('Required PKCE was disabled.'); }
            catch (InvalidApplicationCredentialsException $expected) {
                $this->assertStringContainsString('PKCE cannot be disabled', $expected->getMessage());
            }
        }
        $this->expectExceptionMessage('Only S256 PKCE is supported.');
        new OAuth2Adapter($this->config(['pkce' => 'plain']), new OAuth2Http(), new StorageImpl());
    }

    public function testXUsesSharedPkceAndRotatesEveryAttempt(): void
    {
        $storage = new StorageImpl();
        $adapter = new class($this->config(), new OAuth2Http(), $storage) extends \Hybridauth\Provider\X {
            public function url() { return $this->getAuthorizeUrl(); }
        };
        $first = $this->query($adapter->url());
        $second = $this->query($adapter->url());
        $this->assertSame('S256', $first['code_challenge_method']);
        $this->assertNotSame($first['code_challenge'], $second['code_challenge']);
    }

    public function testPostErrorCallbacksReadPostFields(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $adapter = new class($this->config(), new OAuth2Http(), new StorageImpl()) extends OAuth2Adapter {
                public function filterInput($type, $name, $filter = FILTER_DEFAULT, $options = 0) {
                    return $type === INPUT_POST && $name === 'error' ? 'access_denied' : null;
                }
            };
            $this->expectException(AuthorizationDeniedException::class);
            $adapter->checkError();
        } finally { $_SERVER = $server; }
    }
}
