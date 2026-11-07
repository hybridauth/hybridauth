<?php

namespace HybridauthTest\HttpClient;

use Hybridauth\HttpClient\Guzzle;
use Hybridauth\HttpClient\Util;
use PHPUnit\Framework\TestCase;

class FakeGuzzleResponse
{
    public function getBody() { return 'ok'; }
    public function getStatusCode() { return 200; }
    public function getHeaders() { return []; }
}

class FakeGuzzleClient
{
    public $calls = [];
    public function request($method, $uri, $options)
    {
        $this->calls[] = [$method, $uri, $options];
        return new FakeGuzzleResponse();
    }
}

class GuzzleWithDefaults extends Guzzle
{
    protected $requestHeader = ['content-type' => 'text/plain', 'User-Agent' => 'demo-agent'];
}

class HeaderMergeTest extends TestCase
{
    public function testHeaderNamesMergeCaseInsensitively(): void
    {
        $merged = Util::mergeHeaders(['content-type' => 'text/plain', 'Accept' => '*/*'], ['Content-Type' => 'application/json']);
        $this->assertSame(['Accept' => '*/*', 'Content-Type' => 'application/json'], $merged);
        $this->assertSame(['A' => '1'], Util::mergeHeaders([], ['A' => '1']));
    }

    public function testJsonContentTypeIgnoresCaseAndParameters(): void
    {
        $this->assertTrue(Util::isJsonContentType(['content-type' => 'Application/JSON; charset=UTF-8']));
        $this->assertFalse(Util::isJsonContentType(['Content-Type' => 'application/jsonp']));
        $this->assertFalse(Util::isJsonContentType(['Accept' => 'application/json']));
    }

    public function testGuzzleMatchesCurlHeaderSemanticsWithoutLeakingToNextRequest(): void
    {
        $client = new FakeGuzzleClient();
        $guzzle = new GuzzleWithDefaults($client);
        $guzzle->request('https://api.invalid/a', 'POST', ['demo' => true],
            ['Content-Type' => 'application/json; charset=UTF-8', 'Authorization' => 'Bearer synthetic']);
        $guzzle->request('https://api.invalid/b', 'POST', ['demo' => true]);

        list(, , $first) = $client->calls[0];
        $this->assertSame(['demo' => true], $first['json']);
        $this->assertSame(['User-Agent' => 'demo-agent', 'Content-Type' => 'application/json; charset=UTF-8',
            'Authorization' => 'Bearer synthetic'], $first['headers']);

        list(, , $second) = $client->calls[1];
        $this->assertArrayHasKey('form_params', $second);
        $this->assertSame(['content-type' => 'text/plain', 'User-Agent' => 'demo-agent'], $second['headers']);
    }
}
