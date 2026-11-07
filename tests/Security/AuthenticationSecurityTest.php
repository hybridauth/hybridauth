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

class AuthenticationSecurityTest extends TestCase
{
    private function config()
    {
        return ['keys' => ['id' => 'demo', 'secret' => 'synthetic-secret'],
            'callback' => 'https://demo.invalid/callback?provider=TelegramWidget#fragment'];
    }

    public function testSecureTokensDoNotRepeatWithMtSeed(): void
    {
        mt_srand(12345);
        $first = Random::token();
        mt_srand(12345);
        $second = Random::token();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $first);
        $this->assertNotSame($first, $second);
        $this->assertFalse(Random::equals('0e123', '0e456'));
        $this->assertFalse(Random::equals(null, 'anything'));
    }

    public function testOAuthStatesDifferWithSameMtSeedAndAreConsumed(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        mt_srand(12345);
        $adapter = new Google($this->config(), null, new MemoryStorage());
        parse_str(parse_url($adapter->url(), PHP_URL_QUERY), $first);
        mt_srand(12345);
        $other = new Google($this->config(), null, new MemoryStorage());
        parse_str(parse_url($other->url(), PHP_URL_QUERY), $second);
        $this->assertNotSame($first['state'], $second['state']);
        $adapter->input = ['state' => $first['state'], 'code' => 'demo'];
        $adapter->finish();
        $this->assertSame(1, $adapter->exchanges);
        $this->expectException(InvalidAuthorizationStateException::class);
        $adapter->finish();
    }

    public function testOAuthRejectsLooseStateComparisonBeforeExchangingCode(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $storage = new MemoryStorage();
        $adapter = new Google($this->config(), null, $storage);
        $storage->set('Google.authorization_state', '0e123');
        $adapter->input = ['state' => '0e456', 'code' => 'demo'];
        try { $adapter->finish(); $this->fail('Loose state accepted.'); }
        catch (InvalidAuthorizationStateException $expected) { $this->assertSame(0, $adapter->exchanges); }
    }













    public function testOAuth1NonceUsesSecureRandomness(): void
    {
        $method = new \ReflectionMethod(\Hybridauth\Thirdparty\OAuth\OAuthRequest::class, 'generate_nonce');
        mt_srand(12345); $first = $method->invoke(null);
        mt_srand(12345); $second = $method->invoke(null);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $first);
        $this->assertNotSame($first, $second);
    }

    public function testAppleNameAndXErrorReadRequestSuperglobal(): void
    {
        $request = $_REQUEST;
        try {
            $_REQUEST = ['user' => json_encode(['name' => ['firstName' => 'Demo', 'lastName' => 'User']])];
            $apple = new Apple($this->config(), null, new MemoryStorage());
            $apple->seedToken('demo.' . base64_encode(json_encode(['sub' => 'demo', 'exp' => time() + 60])) . '.demo');
            $this->assertSame('Demo User', $apple->getUserProfile()->displayName);
            $_REQUEST = ['error' => 'access_denied', 'error_description' => 'Demo denied'];
            $x = new X($this->config(), null, new MemoryStorage());
            $this->expectException(\Hybridauth\Exception\UnexpectedApiResponseException::class);
            $this->expectExceptionMessage('Demo denied');
            $x->checkError();
        } finally { $_REQUEST = $request; }
    }
}
