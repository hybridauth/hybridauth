<?php

namespace HybridauthTest\Hybridauth\User;

use Hybridauth\User\Profile;

class ProfileTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance_of()
    {
        $profile = new Profile;

        $this->assertInstanceOf('\\Hybridauth\\User\\Profile', $profile);
    }

    public function test_has_attributes()
    {
        $this->assertClassHasAttribute('identifier', Profile::class);
        $this->assertClassHasAttribute('webSiteURL', Profile::class);
        $this->assertClassHasAttribute('profileURL', Profile::class);
        $this->assertClassHasAttribute('photoURL', Profile::class);
        $this->assertClassHasAttribute('displayName', Profile::class);
        $this->assertClassHasAttribute('firstName', Profile::class);
        $this->assertClassHasAttribute('lastName', Profile::class);
        $this->assertClassHasAttribute('description', Profile::class);
        $this->assertClassHasAttribute('gender', Profile::class);
        $this->assertClassHasAttribute('language', Profile::class);
        $this->assertClassHasAttribute('age', Profile::class);
        $this->assertClassHasAttribute('birthDay', Profile::class);
        $this->assertClassHasAttribute('birthMonth', Profile::class);
        $this->assertClassHasAttribute('birthYear', Profile::class);
        $this->assertClassHasAttribute('email', Profile::class);
        $this->assertClassHasAttribute('emailVerified', Profile::class);
        $this->assertClassHasAttribute('phone', Profile::class);
        $this->assertClassHasAttribute('address', Profile::class);
        $this->assertClassHasAttribute('country', Profile::class);
        $this->assertClassHasAttribute('region', Profile::class);
        $this->assertClassHasAttribute('city', Profile::class);
        $this->assertClassHasAttribute('zip', Profile::class);
    }

    public function test_set_attributes()
    {
        $profile = new Profile;

        $profile->identifier = true;
        $profile->webSiteURL = true;
        $profile->profileURL = true;
        $profile->photoURL = true;
        $profile->displayName = true;
        $profile->firstName = true;
        $profile->lastName = true;
        $profile->description = true;
        $profile->gender = true;
        $profile->language = true;
        $profile->age = true;
        $profile->birthDay = true;
        $profile->birthMonth = true;
        $profile->birthYear = true;
        $profile->email = true;
        $profile->emailVerified = true;
        $profile->phone = true;
        $profile->address = true;
        $profile->country = true;
        $profile->region = true;
        $profile->city = true;
        $profile->zip = true;
    }

    /**
     * @expectedException \Hybridauth\Exception\UnexpectedValueException
     */
    public function test_property_overloading()
    {
        $profile = new Profile;
        $profile->slug = true;
    }
}
