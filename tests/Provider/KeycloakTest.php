<?php

namespace HybridauthTest\Hybridauth\Provider;

use Hybridauth\HttpClient\HttpClientInterface;
use Hybridauth\Provider\Keycloak;
use Hybridauth\Storage\StorageImpl;
use Hybridauth\Storage\StorageInterface;
use Hybridauth\User\Profile;

class KeycloakTest extends \PHPUnit\Framework\TestCase
{
    public function test_getUserProfile()
    {
        //Mock OAuth2 Api request
        $keycloak = $this->getMockBuilder(Keycloak::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['apiRequest'])
            ->getMock();
        $keycloak->expects($this->once())
            ->method('apiRequest')
            ->willReturn([
                'sub' => 1,
                'preferred_username' => 'alice@example.com',
                'email' => 'alice@example.com',
                'given_name' => 'Alice',
                'family_name' => 'Jenkins',
                'email_verified' => true
            ]);

        $profile = new Profile();
        $profile->identifier = 1;
        $profile->displayName = 'alice@example.com';
        $profile->firstName = 'Alice';
        $profile->lastName = 'Jenkins';
        $profile->email = 'alice@example.com';
        $profile->emailVerified = true;

        $this->assertEquals($profile, $keycloak->getUserProfile());
    }

    /* Test parsing keycloak user profile with organization feature enabled. The issued ID token is similar but contains the organization scope with this format:
        "name": "Alice Jenkins",
        "preferred_username": "alice@acme.org",
        "given_name": "Alice",
        "family_name": "Jenkins",
        "email": "alice@acme.org" 
        "email_verified": true,
        "organization":    {
            "my_org": {}
        },
    */
    public function test_getUserProfileWithOrganization()
    {
        //Mock OAuth2 Api request
        $keycloak = $this->getMockBuilder(Keycloak::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['apiRequest'])
            ->getMock();
        $keycloak->expects($this->once())
            ->method('apiRequest')
            ->willReturn([
                'sub' => 2,
                'preferred_username' => 'alice@example.com',
                'email' => 'alice@example.com',
                'given_name' => 'Alice',
                'family_name' => 'Jenkins',
                'email_verified' => true,
                'organization' => json_decode('{ "my_org": {} }'),
            ]);

        $profile = new Profile();
        $profile->identifier = 2;
        $profile->displayName = 'alice@example.com';
        $profile->firstName = 'Alice';
        $profile->lastName = 'Jenkins';
        $profile->email = 'alice@example.com';
        $profile->emailVerified = true;
        $profile->data = ['organization' => 'my_org'];

        $this->assertEquals($profile, $keycloak->getUserProfile());
    }

    public function test_refreshAccessToken_ProviderClientIdAndSecret()
    {
        $config = [
            'url' => 'sha.dok',
            'keys' => [
                'id' => 'ga',
                'secret' => 'bu',
            ],
            'tokens' => [
                'access_token' => 'old_access_token',
                'token_type' => 'Bearer',
                'expires_at' => 1731454668,
                'refresh_token' => 'zo',
            ],
            'realm' => 'meu',
            'callback' => 'http://callbacksha.dok'
        ];

        $httpClient = $this->createMock(HttpClientInterface::class);
        $keycloak = new Keycloak($config, $httpClient, new StorageImpl());

        $httpClient->expects($this->once())
            ->method('request')
            ->with(
                'sha.dok/realms/meu/protocol/openid-connect/token',
                'POST',
                [
                    'grant_type' => 'refresh_token',
                    'client_id' => 'ga',
                    'client_secret' => 'bu',
                    'refresh_token' => 'zo',
                ],
                []
            )
            ->willReturn(json_encode(['access_token' => 'new_access_token']));

        $httpClient->expects($this->once())
            ->method('getResponseClientError')
            ->willReturn(false);

        $httpClient->expects($this->once())
            ->method('getResponseHttpCode')
            ->willReturn(200);

        $keycloak->refreshAccessToken();
    }
}
