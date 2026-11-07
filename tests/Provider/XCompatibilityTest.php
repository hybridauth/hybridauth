<?php

namespace HybridauthTest\Provider;

use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\Provider\X;
use Hybridauth\Storage\StorageImpl;
use HybridauthTest\Adapter\OAuth2Http;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__) . '/Adapter/OAuth2Fixtures.php';

class CompatibleX extends X
{
    public $input = [];
    public function url() { return $this->getAuthorizeUrl(); }
    public function finish() { return $this->authenticateFinish(); }
    public function filterInput($type, $name, $filter = FILTER_DEFAULT, $options = 0)
    {
        return isset($this->input[$name]) ? $this->input[$name] : null;
    }
}

class XCompatibilityTest extends TestCase
{
    private function config()
    {
        return ['keys' => ['id' => 'demo', 'secret' => 'synthetic'],
            'callback' => 'https://app.invalid/callback', 'pkce' => false];
    }

    public function testPreviouslyIgnoredFalseSettingStillUsesPkce(): void
    {
        $adapter = new CompatibleX($this->config(), new OAuth2Http(), new StorageImpl());
        parse_str(parse_url($adapter->url(), PHP_URL_QUERY), $query);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertNotEmpty($query['code_challenge']);
    }

    public function testPreUpgradeAttemptCompletesOnceWithOriginalVerifier(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $storage = new StorageImpl();
            $storage->set('CompatibleX.authorization_state', 'existing-state');
            $storage->set('CompatibleX.codeVerifier', str_repeat('a', 64));
            $storage->set('CompatibleX.codeChallenge', 'old-challenge');
            $http = new OAuth2Http();
            $adapter = new CompatibleX($this->config(), $http, $storage);
            $adapter->input = ['state' => 'existing-state', 'code' => 'demo'];
            $adapter->finish();
            $this->assertSame(str_repeat('a', 64), $http->requests[0][2]['code_verifier']);
            $this->assertNull($storage->get('CompatibleX.codeVerifier'));
            $this->assertNull($storage->get('CompatibleX.codeChallenge'));
            $this->assertNull($storage->get('CompatibleX.authorization_state'));
            $this->expectException(InvalidAuthorizationStateException::class);
            $adapter->finish();
        } finally { $_SERVER = $server; }
    }

    public function testNewAttemptRemovesLegacyVerifierBeforeGeneratingChallenge(): void
    {
        $storage = new StorageImpl();
        $storage->set('CompatibleX.codeVerifier', str_repeat('a', 64));
        $storage->set('CompatibleX.codeChallenge', 'old-challenge');
        $adapter = new CompatibleX($this->config(), new OAuth2Http(), $storage);
        $adapter->url();
        $this->assertNull($storage->get('CompatibleX.codeVerifier'));
        $this->assertNull($storage->get('CompatibleX.codeChallenge'));
        $this->assertNotNull($storage->get('CompatibleX.pkce_code_verifier'));
    }

    public function testLegacyVerifierDoesNotBypassStateValidation(): void
    {
        $server = $_SERVER;
        try {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $storage = new StorageImpl();
            $storage->set('CompatibleX.codeVerifier', str_repeat('a', 64));
            $storage->set('CompatibleX.authorization_state', 'existing-state');
            $http = new OAuth2Http();
            $adapter = new CompatibleX($this->config(), $http, $storage);
            $adapter->input = ['state' => 'forged', 'code' => 'demo'];
            try { $adapter->finish(); $this->fail('Forged state accepted.'); }
            catch (InvalidAuthorizationStateException $expected) { $this->assertSame([], $http->requests); }
        } finally { $_SERVER = $server; }
    }
}
