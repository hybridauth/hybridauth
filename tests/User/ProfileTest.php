<?php

namespace HybridauthTest\Hybridauth\User;

use Hybridauth\User\Profile;
use ReflectionClass;

class ProfileTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance_of()
    {
        $profile = new Profile();

        $this->assertInstanceOf('\\Hybridauth\\User\\Profile', $profile);
    }

    public function test_has_attributes()
    {
        $reflection = new ReflectionClass('\\Hybridauth\\User\\Profile');

        $this->assertTrue($reflection->hasProperty('identifier'));
        $this->assertTrue($reflection->hasProperty('webSiteURL'));
        $this->assertTrue($reflection->hasProperty('profileURL'));
        $this->assertTrue($reflection->hasProperty('photoURL'));
        $this->assertTrue($reflection->hasProperty('displayName'));
        $this->assertTrue($reflection->hasProperty('firstName'));
        $this->assertTrue($reflection->hasProperty('lastName'));
        $this->assertTrue($reflection->hasProperty('description'));
        $this->assertTrue($reflection->hasProperty('gender'));
        $this->assertTrue($reflection->hasProperty('language'));
        $this->assertTrue($reflection->hasProperty('age'));
        $this->assertTrue($reflection->hasProperty('birthDay'));
        $this->assertTrue($reflection->hasProperty('birthMonth'));
        $this->assertTrue($reflection->hasProperty('birthYear'));
        $this->assertTrue($reflection->hasProperty('email'));
        $this->assertTrue($reflection->hasProperty('emailVerified'));
        $this->assertTrue($reflection->hasProperty('phone'));
        $this->assertTrue($reflection->hasProperty('address'));
        $this->assertTrue($reflection->hasProperty('country'));
        $this->assertTrue($reflection->hasProperty('region'));
        $this->assertTrue($reflection->hasProperty('city'));
        $this->assertTrue($reflection->hasProperty('zip'));
    }

    public function test_set_attributes()
    {
        $profile = new Profile();

        $profile->identifier = 'profile-id';
        $profile->webSiteURL = 'https://example.com';
        $profile->profileURL = 'https://profile.example.com';
        $profile->photoURL = 'https://photos.example.com/pic.jpg';
        $profile->displayName = 'John Doe';
        $profile->firstName = 'John';
        $profile->lastName = 'Doe';
        $profile->description = 'Profile description';
        $profile->gender = 'male';
        $profile->language = 'en_US';
        $profile->age = 30;
        $profile->birthDay = 1;
        $profile->birthMonth = 1;
        $profile->birthYear = 1990;
        $profile->email = 'profile@example.com';
        $profile->emailVerified = true;
        $profile->phone = '+1234567890';
        $profile->address = '123 Main St';
        $profile->country = 'USA';
        $profile->region = 'CA';
        $profile->city = 'San Francisco';
        $profile->zip = '94105';

        $this->assertSame('profile-id', $profile->identifier);
        $this->assertSame('https://example.com', $profile->webSiteURL);
        $this->assertSame('https://profile.example.com', $profile->profileURL);
        $this->assertSame('https://photos.example.com/pic.jpg', $profile->photoURL);
        $this->assertSame('John Doe', $profile->displayName);
        $this->assertSame('John', $profile->firstName);
        $this->assertSame('Doe', $profile->lastName);
        $this->assertSame('Profile description', $profile->description);
        $this->assertSame('male', $profile->gender);
        $this->assertSame('en_US', $profile->language);
        $this->assertSame(30, $profile->age);
        $this->assertSame(1, $profile->birthDay);
        $this->assertSame(1, $profile->birthMonth);
        $this->assertSame(1990, $profile->birthYear);
        $this->assertSame('profile@example.com', $profile->email);
        $this->assertTrue($profile->emailVerified);
        $this->assertSame('+1234567890', $profile->phone);
        $this->assertSame('123 Main St', $profile->address);
        $this->assertSame('USA', $profile->country);
        $this->assertSame('CA', $profile->region);
        $this->assertSame('San Francisco', $profile->city);
        $this->assertSame('94105', $profile->zip);
    }

    public function test_property_overloading()
    {
        $this->expectException(\Hybridauth\Exception\UnexpectedValueException::class);

        $profile = new Profile();
        $profile->slug = true;
    }
}
