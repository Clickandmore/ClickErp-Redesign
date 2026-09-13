<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SaudiPhoneFormattingTest extends TestCase
{
    /** @dataProvider phoneNumbers */
    public function test_it_formats_common_saudi_phone_variants(string $input, string $expected): void
    {
        $this->assertSame($expected, formatSaudiPhone($input));
    }

    public static function phoneNumbers(): array
    {
        return [
            'local mobile' => ['0501234567', '966501234567'],
            'mobile without zero' => ['501234567', '966501234567'],
            'international mobile' => ['+966501234567', '966501234567'],
            'international prefix' => ['00966501234567', '966501234567'],
            'country code plus local number' => ['+9660501234567', '966501234567'],
            'duplicated country code' => ['+966966501234567', '966501234567'],
        ];
    }
}
