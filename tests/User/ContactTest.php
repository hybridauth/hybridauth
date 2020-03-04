<?php

namespace HybridauthTest\Hybridauth\User;

use Hybridauth\User\Contact;

class ContactTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance_of()
    {
        $contact = new Contact;

        $this->assertInstanceOf('\\Hybridauth\\User\\Contact', $contact);
    }

    public function test_has_attributes()
    {
        $this->assertClassHasAttribute('identifier', Contact::class);
        $this->assertClassHasAttribute('webSiteURL', Contact::class);
        $this->assertClassHasAttribute('profileURL', Contact::class);
        $this->assertClassHasAttribute('photoURL', Contact::class);
        $this->assertClassHasAttribute('displayName', Contact::class);
        $this->assertClassHasAttribute('description', Contact::class);
        $this->assertClassHasAttribute('email', Contact::class);
    }

    public function test_set_attributes()
    {
        $contact = new Contact;

        $contact->identifier = true;
        $contact->webSiteURL = true;
        $contact->profileURL = true;
        $contact->photoURL = true;
        $contact->displayName = true;
        $contact->description = true;
        $contact->email = true;
    }

    /**
     * @expectedException \Hybridauth\Exception\UnexpectedValueException
     */
    public function test_property_overloading()
    {
        $contact = new Contact;
        $contact->slug = true;
    }
}
