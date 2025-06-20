<?php

namespace HybridauthTest\Hybridauth\User;

use Hybridauth\User\Contact;
use ReflectionClass;

class ContactTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance_of()
    {
        $contact = new Contact();

        $this->assertInstanceOf('\\Hybridauth\\User\\Contact', $contact);
    }

    public function test_has_attributes()
    {
        $reflection = new ReflectionClass('\\Hybridauth\\User\\Contact');

        $this->assertTrue($reflection->hasProperty('identifier'));
        $this->assertTrue($reflection->hasProperty('webSiteURL'));
        $this->assertTrue($reflection->hasProperty('profileURL'));
        $this->assertTrue($reflection->hasProperty('photoURL'));
        $this->assertTrue($reflection->hasProperty('displayName'));
        $this->assertTrue($reflection->hasProperty('description'));
        $this->assertTrue($reflection->hasProperty('email'));
    }

    public function test_set_attributes()
    {
        $contact = new Contact();

        $contact->identifier = 'contact-id';
        $contact->webSiteURL = 'https://example.com';
        $contact->profileURL = 'https://profile.example.com';
        $contact->photoURL = 'https://photos.example.com/pic.jpg';
        $contact->displayName = 'John Doe';
        $contact->description = 'Contact description';
        $contact->email = 'contact@example.com';

        $this->assertSame('contact-id', $contact->identifier);
        $this->assertSame('https://example.com', $contact->webSiteURL);
        $this->assertSame('https://profile.example.com', $contact->profileURL);
        $this->assertSame('https://photos.example.com/pic.jpg', $contact->photoURL);
        $this->assertSame('John Doe', $contact->displayName);
        $this->assertSame('Contact description', $contact->description);
        $this->assertSame('contact@example.com', $contact->email);
    }

    public function test_property_overloading()
    {
        $this->expectException(\Hybridauth\Exception\UnexpectedValueException::class);

        $contact = new Contact();
        $contact->slug = true;
    }
}
