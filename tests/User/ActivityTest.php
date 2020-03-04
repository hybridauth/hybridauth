<?php

namespace HybridauthTest\Hybridauth\User;

use Hybridauth\Exception\UnexpectedValueException;
use Hybridauth\User\Activity;

class ActivityTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance_of()
    {
        $activity = new Activity;

        $this->assertInstanceOf('\\Hybridauth\\User\\Activity', $activity);
    }

    public function test_has_attributes()
    {
        $this->assertClassHasAttribute('id', Activity::class);
        $this->assertClassHasAttribute('date', Activity::class);
        $this->assertClassHasAttribute('text', Activity::class);
        $this->assertClassHasAttribute('user', Activity::class);
    }

    public function test_set_attributes()
    {
        $activity = new Activity;

        $activity->id = true;
        $activity->date = true;
        $activity->text = true;
        $activity->user = true;
    }

    public function test_property_overloading()
    {
        $this->expectException(UnexpectedValueException::class);

        $activity = new Activity;
        $activity->slug = true;
    }
}
