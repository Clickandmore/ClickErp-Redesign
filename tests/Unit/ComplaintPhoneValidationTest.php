<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\ComplaintController;
use Illuminate\Support\Facades\Validator;
use ReflectionMethod;
use Tests\TestCase;

class ComplaintPhoneValidationTest extends TestCase
{
    public function test_complaint_phone_rule_accepts_stored_and_display_formats(): void
    {
        $method = new ReflectionMethod(ComplaintController::class, 'complainantPhoneRules');
        $method->setAccessible(true);
        $rules = $method->invoke(app(ComplaintController::class));

        $this->assertTrue(Validator::make(['phone' => '503577774'], ['phone' => $rules])->passes());
        $this->assertTrue(Validator::make(['phone' => '0503577774'], ['phone' => $rules])->passes());
        $this->assertFalse(Validator::make(['phone' => '123'], ['phone' => $rules])->passes());
    }
}
