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

    private function signedTelegramData($state, $date = null)
    {
        $data = ['auth_date' => $date === null ? (string) time() : $date, 'first_name' => 'Demo', 'id' => '424242'];
        $parts = [];
        foreach ($data as $key => $value) { $parts[] = $key . '=' . $value; }
        $data['hash'] = hash_hmac('sha256', implode("\n", $parts), hash('sha256', 'synthetic-secret', true));
        $data['ha_state'] = $state;
        return $data;
    }

    private function telegramAttempt()
    {
        $adapter = new TelegramWidget($this->config(), null, new MemoryStorage());
        $html = $adapter->widget();
        preg_match('/ha_state=([a-f0-9]{64})/', $html, $match);
        $this->assertCount(2, $match);
        $this->assertStringContainsString('provider=TelegramWidget&amp;ha_state=', $html);
        $this->assertStringContainsString('#fragment', $html);
        return [$adapter, $match[1]];
    }

    public function testTelegramAcceptsMatchingSessionOnce(): void
    {
        list($adapter, $state) = $this->telegramAttempt();
        $adapter->input = $this->signedTelegramData($state);
        $adapter->authenticate();
        $this->assertSame('424242', $adapter->getUserProfile()->identifier);
        $this->expectException(InvalidAuthorizationCodeException::class);
        $adapter->authenticate();
    }

    public function testTelegramRejectsCrossSessionAndUnsolicitedCallbacks(): void
    {
        list($attacker, $state) = $this->telegramAttempt();
        list($victim) = $this->telegramAttempt();
        foreach ([$victim, new TelegramWidget($this->config(), null, new MemoryStorage())] as $adapter) {
            $adapter->input = $this->signedTelegramData($state);
            try { $adapter->authenticate(); $this->fail('Cross-session login accepted.'); }
            catch (InvalidAuthorizationCodeException $expected) { $this->assertFalse($adapter->isConnected()); }
        }
    }

    public function testTelegramRejectsBadSignaturesAndExpiredOrFutureData(): void
    {
        foreach (['bad-signature', (string) (time() - 90000), (string) (time() + 3600)] as $case) {
            list($adapter, $state) = $this->telegramAttempt();
            $adapter->input = $this->signedTelegramData($state, $case === 'bad-signature' ? null : $case);
            if ($case === 'bad-signature') { $adapter->input['hash'] = str_repeat('0', 64); }
            try { $adapter->authenticate(); $this->fail('Invalid Telegram data accepted.'); }
            catch (InvalidAuthorizationCodeException $expected) { $this->assertFalse($adapter->isConnected()); }
        }
    }

    public function testAdapterCallbacksSelectFinishFromSuperglobals(): void
    {
        $server = $_SERVER; $get = $_GET; $request = $_REQUEST;
        try {
            $_SERVER += ['REQUEST_URI' => '/callback', 'REQUEST_METHOD' => 'GET'];
            $_GET = ['oauth_token' => 'demo-request-token'];
            $storage = new MemoryStorage();
            $storage->set('Twitter.request_token', 'demo-request-token');
            $twitter = new Twitter($this->config(), null, $storage);
            $twitter->authenticate();
            $this->assertSame('finish', $twitter->flow);
            $_GET = [];
            $twitter->authenticate();
            $this->assertSame('begin', $twitter->flow);
            $_GET = ['denied' => 'demo-request-token'];
            $twitter->authenticate();
            $this->assertSame('finish', $twitter->flow);

            $_GET = ['openid_mode' => 'id_res'];
            $_REQUEST = [];
            $config = $this->config();
            $config['openid_identifier'] = 'https://8.8.8.8/identity';
            $openid = new OpenIDAdapter($config, null, new MemoryStorage());
            $openid->authenticate();
            $this->assertSame('finish', $openid->flow);
            $_GET = [];
            $openid->authenticate();
            $this->assertSame('begin', $openid->flow);
        } finally { $_SERVER = $server; $_GET = $get; $_REQUEST = $request; }
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
