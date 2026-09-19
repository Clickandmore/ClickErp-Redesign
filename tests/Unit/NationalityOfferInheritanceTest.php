<?php

namespace Tests\Unit;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use Tests\TestCase;

class NationalityOfferInheritanceTest extends TestCase
{
    public function test_available_maid_inherits_nationality_offer(): void
    {
        $nationality = new Nationality([
            'offer_price' => 750,
            'offer_duration' => '10',
            'offer_description' => 'عرض موسمي',
            'offer_starts_at' => '2026-09-01',
            'offer_expires_at' => '2026-09-10',
        ]);

        $maid = new Maid(['available' => true, 'status' => 'available']);
        $maid->setRelation('nationality', $nationality);

        $this->assertSame('nationality', $maid->effective_offer_source);
        $this->assertSame(750.0, (float) $maid->effective_offer_price);
        $this->assertSame('عرض موسمي', $maid->effective_offer_description);
        $this->assertSame('2026-09-01', $maid->effective_offer_starts_at->toDateString());
    }

    public function test_individual_offer_has_priority_over_nationality_offer(): void
    {
        $nationality = new Nationality(['offer_price' => 750]);
        $maid = new Maid([
            'available' => true,
            'status' => 'available',
            'offer_price' => 500,
            'offer_description' => 'عرض خاص',
        ]);
        $maid->setRelation('nationality', $nationality);

        $this->assertSame('maid', $maid->effective_offer_source);
        $this->assertSame(500.0, (float) $maid->effective_offer_price);
        $this->assertSame('عرض خاص', $maid->effective_offer_description);
    }

    public function test_unavailable_maid_does_not_inherit_nationality_offer(): void
    {
        $nationality = new Nationality(['offer_price' => 750]);
        $maid = new Maid(['available' => false, 'status' => 'reserved']);
        $maid->setRelation('nationality', $nationality);

        $this->assertNull($maid->effective_offer_source);
        $this->assertNull($maid->effective_offer_price);
    }
}
