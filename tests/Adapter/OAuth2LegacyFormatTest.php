<?php

namespace HybridauthTest\Adapter;

use Hybridauth\Exception\InvalidAccessTokenException;
use Hybridauth\Storage\StorageImpl;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/OAuth2Fixtures.php';

class OAuth2LegacyFormatTest extends TestCase
{
    public function testLegacyFormatsRemainAutomaticWithoutWeakeningTokenValidation(): void
    {
        $http = new OAuth2Http();
        $http->headers = ['Content-Type' => 'text/html'];
        $adapter = new OAuth2Adapter(['keys' => ['id' => 'demo', 'secret' => 'synthetic'],
            'callback' => 'https://app.invalid/callback'], $http, new StorageImpl());
        $adapter->accept('access_token=legacy-form');
        $this->assertSame('legacy-form', $adapter->getAccessToken()['access_token']);
        $adapter->accept('<token><access_token>legacy-xml</access_token></token>');
        $this->assertSame('legacy-xml', $adapter->getAccessToken()['access_token']);
        $before = $adapter->getAccessToken();
        try {
            $adapter->accept('<!DOCTYPE token [<!ENTITY demo "synthetic">]><token><access_token>&demo;</access_token></token>');
            $this->fail('XML entity accepted.');
        } catch (InvalidAccessTokenException $expected) {
            $this->assertSame($before, $adapter->getAccessToken());
        }
    }
}
