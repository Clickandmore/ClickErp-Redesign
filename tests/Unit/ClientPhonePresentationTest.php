<?php

namespace Tests\Unit;

use App\Models\ingaz\User;
use PHPUnit\Framework\TestCase;

class ClientPhonePresentationTest extends TestCase
{
    public function test_internal_duplicate_marker_is_not_presented_as_part_of_phone(): void
    {
        $client = new User([
            'phone_code' => '+966',
            'phone' => '540330006-dup-2',
            'phone2' => '540330006-dup-',
        ]);

        $this->assertSame('+966540330006', $client->primaryPhoneValue());
        $this->assertSame('540330006', $client->secondaryPhoneValue());
        $this->assertSame('540330006', $client->canonicalPhoneValue());
        $this->assertTrue($client->hasInternalDuplicatePhoneMarker());
    }

    public function test_country_prefixes_and_arabic_digits_share_the_same_canonical_phone(): void
    {
        $international = new User(['source_phone' => '+966 55 228 1700']);
        $arabicDigits = new User(['phone' => '٠٥٥٢٢٨١٧٠٠']);

        $this->assertSame('552281700', $international->canonicalPhoneValue());
        $this->assertSame('552281700', $arabicDigits->canonicalPhoneValue());
    }

    public function test_internal_import_placeholder_is_not_presented_as_a_phone(): void
    {
        $client = new User([
            'phone_code' => '+966',
            'phone' => 'arabia-client-123',
        ]);

        $this->assertSame('', $client->primaryPhoneValue());
        $this->assertNull($client->canonicalPhoneValue());
    }
}
