<?php

namespace HybridauthTest\Hybridauth\User;

use Hybridauth\User\Activity;
use ReflectionClass;

class ActivityTest extends \PHPUnit\Framework\TestCase
{
    public function test_instance_of()
    {
        $activity = new Activity();

        $this->assertInstanceOf('\\Hybridauth\\User\\Activity', $activity);
    }

    public function test_has_attributes()
    {
        $reflection = new ReflectionClass('\\Hybridauth\\User\\Activity');

        $this->assertTrue($reflection->hasProperty('id'));
        $this->assertTrue($reflection->hasProperty('date'));
        $this->assertTrue($reflection->hasProperty('text'));
        $this->assertTrue($reflection->hasProperty('user'));
    }

    public function test_set_attributes()
    {
        $activity = new Activity();

        $activity->id = 'activity-id';
        $activity->date = '2023-01-01';
        $activity->text = 'Example activity';
        $activity->user = 'user-info';

        $this->assertSame('activity-id', $activity->id);
        $this->assertSame('2023-01-01', $activity->date);
        $this->assertSame('Example activity', $activity->text);
        $this->assertSame('user-info', $activity->user);
    }

    public function test_property_overloading()
    {
        $this->expectException(\Hybridauth\Exception\UnexpectedValueException::class);

        $activity = new Activity();
        $activity->slug = true;
    }
}
