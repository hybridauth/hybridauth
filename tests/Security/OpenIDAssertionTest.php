<?php

namespace HybridauthTest\Security;

use Hybridauth\Exception\InvalidAuthorizationStateException;
use Hybridauth\HttpClient\Util;
use Hybridauth\Storage\StorageImpl;
use Hybridauth\Thirdparty\OpenID\LightOpenID;
use PHPUnit\Framework\TestCase;

class AssertionClient extends LightOpenID
{
    public $checks = 0;
    public $reply = "is_valid:true\n";
    public $localIdentity = 'https://identity.invalid/user';
    public function __construct() { }
    public function discover($url)
    {
        $this->identity = $this->localIdentity;
        $this->version = 2;
        $this->server = 'https://provider.invalid/check';
        return $this->server;
    }
    protected function request($url, $method = 'GET', $params = [], $update_claimed_id = false)
    {
        $this->checks++;
        return $this->reply;
    }
}

class BoundOpenID extends \Hybridauth\Provider\OpenID
{
    protected function initialize() { $this->openIdClient = new AssertionClient(); }
    public function client() { return $this->openIdClient; }
    public function begin() { return $this->authenticateBegin(); }
    public function finish() { return $this->authenticateFinish(); }
}

class OpenIDAssertionTest extends TestCase
{
    private function client()
    {
        $client = new AssertionClient();
        $client->returnUrl = 'https://app.invalid/callback';
        $client->data = [
            'openid_mode' => 'id_res', 'openid_ns' => 'http://specs.openid.net/auth/2.0',
            'openid_claimed_id' => 'https://identity.invalid/user',
            'openid_identity' => 'https://identity.invalid/user',
            'openid_op_endpoint' => 'https://provider.invalid/check',
            'openid_return_to' => $client->returnUrl, 'openid_assoc_handle' => 'synthetic',
            'openid_response_nonce' => gmdate('Y-m-d\TH:i:s\Z') . 'synthetic',
            'openid_signed' => 'op_endpoint,claimed_id,identity,return_to,assoc_handle,response_nonce',
            'openid_sig' => 'synthetic',
        ];
        return $client;
    }

    public function testValidAssertionReachesDiscoveredProvider(): void
    {
        $client = $this->client();
        $this->assertSame(1, $client->validate());
        $this->assertSame(1, $client->checks);
    }

    public function testEachRequiredFieldMustBeSigned(): void
    {
        $signed = explode(',', $this->client()->data['openid_signed']);
        foreach ($signed as $field) {
            $client = $this->client();
            $client->data['openid_signed'] = implode(',', array_diff($signed, [$field]));
            $this->assertFalse($client->validate(), $field);
            $this->assertSame(0, $client->checks);
        }
    }

    public function testForgedLocalIdentityOrEndpointIsRejectedBeforeVerification(): void
    {
        foreach (['openid_identity', 'openid_op_endpoint'] as $field) {
            $client = $this->client();
            $client->data[$field] = 'https://attacker.invalid/';
            $this->assertFalse($client->validate());
            $this->assertSame(0, $client->checks);
        }
    }

    public function testOnlyExactPositiveVerificationResponseIsAccepted(): void
    {
        foreach (["not_is_valid:true\n", "is_valid:trueevil\n", "is_valid:false\n"] as $reply) {
            $client = $this->client();
            $client->reply = $reply;
            $this->assertSame(0, $client->validate());
        }
    }

    public function testUnsignedAxValuesCannotSupplyProfileData(): void
    {
        $client = $this->client();
        $client->data += ['openid_ns_ax' => 'http://openid.net/srv/ax/1.0',
            'openid_ax_type_email' => 'http://axschema.org/contact/email',
            'openid_ax_value_email' => 'forged@example.invalid'];
        $client->data['openid_signed'] .= ',ax.type.email';
        $this->assertSame([], $client->getAttributes());
        $client->data['openid_signed'] .= ',ax.value.email';
        $this->assertSame(['contact/email' => 'forged@example.invalid'], $client->getAttributes());
    }

    public function testOpenidCallbackIsBoundToSessionAndConsumedBeforeVerification(): void
    {
        $get = $_GET;
        $storage = new StorageImpl();
        Util::setRedirectHandler(function ($url) { });
        try {
            $adapter = new BoundOpenID(['callback' => 'https://app.invalid/callback?provider=OpenID',
                'openid_identifier' => 'https://identity.invalid/user'], null, $storage);
            $adapter->begin();
            $returnUrl = $adapter->client()->returnUrl;
            parse_str(parse_url($returnUrl, PHP_URL_QUERY), $query);
            $this->assertNotEmpty($query['ha_state']);
            $this->assertSame('OpenID', $query['provider']);
            $_GET = ['ha_state' => $query['ha_state']];
            $adapter = new BoundOpenID(['callback' => 'https://app.invalid/callback?provider=OpenID',
                'openid_identifier' => 'https://identity.invalid/user'], null, $storage);
            $adapter->client()->data = $this->client()->data;
            $adapter->client()->data['openid_return_to'] = $returnUrl;
            $adapter->finish();
            $this->assertTrue($adapter->isConnected());
            $this->assertNull($storage->get('BoundOpenID.authorization_state'));
            $this->expectException(InvalidAuthorizationStateException::class);
            $adapter->finish();
        } finally {
            $_GET = $get;
            Util::setRedirectHandler(null);
        }
    }

    public function testUnsolicitedCallbackNeverReachesVerification(): void
    {
        $get = $_GET;
        try {
            $_GET = ['ha_state' => 'forged'];
            $adapter = new BoundOpenID(['callback' => 'https://app.invalid/callback',
                'openid_identifier' => 'https://identity.invalid/user'], null, new StorageImpl());
            try {
                $adapter->finish();
                $this->fail('Unsolicited callback accepted.');
            } catch (InvalidAuthorizationStateException $expected) {
                $this->assertSame(0, $adapter->client()->checks);
            }
        } finally { $_GET = $get; }
    }
}
