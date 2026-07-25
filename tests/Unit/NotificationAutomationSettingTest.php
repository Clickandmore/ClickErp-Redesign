<?php

namespace Tests\Unit;

use App\Models\ingaz\Setting;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class NotificationAutomationSettingTest extends TestCase
{
    public function test_automation_storage_fields_are_mass_assignable(): void
    {
        $setting = (new ReflectionClass(Setting::class))->newInstanceWithoutConstructor();
        $fillable = $setting->getFillable();

        $this->assertContains('key', $fillable);
        $this->assertContains('value', $fillable);
        $this->assertContains('group', $fillable);
    }
}
