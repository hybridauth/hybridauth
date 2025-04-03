<?php

namespace HybridauthTest\Hybridauth\Provider;

use Hybridauth\Provider\Keycloak;
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
}
