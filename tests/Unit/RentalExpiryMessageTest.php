<?php

namespace Tests\Unit;

use App\Support\RentalExpiryMessage;
use PHPUnit\Framework\TestCase;

class RentalExpiryMessageTest extends TestCase
{
    public function test_customer_is_asked_to_contact_the_company_without_a_link(): void
    {
        $message = RentalExpiryMessage::customer('محمد', '123', '2026-10-20');

        $this->assertStringContainsString('محمد', $message);
        $this->assertStringContainsString('عقد الإيجار رقم 123 ينتهي بتاريخ 2026-10-20', $message);
        $this->assertStringContainsString('يرجى التواصل معنا', $message);
        $this->assertStringNotContainsString('رابط', $message);
        $this->assertDoesNotMatchRegularExpression('~https?://|www\.~i', $message);
    }

    public function test_expired_contract_uses_past_tense_and_handles_missing_name(): void
    {
        $message = RentalExpiryMessage::customer('', '123', '2026-10-12', true);

        $this->assertStringStartsWith('عميلنا العزيز،', $message);
        $this->assertStringContainsString('انتهى بتاريخ 2026-10-12', $message);
    }

    public function test_staff_is_asked_to_follow_up_with_the_named_customer(): void
    {
        $message = RentalExpiryMessage::staff('محمد', '123', '2026-10-20');

        $this->assertStringContainsString('عقد الإيجار رقم 123 للعميل محمد', $message);
        $this->assertStringContainsString('يرجى التواصل مع العميل', $message);
        $this->assertStringNotContainsString('يرجى التواصل معنا', $message);
    }
}
