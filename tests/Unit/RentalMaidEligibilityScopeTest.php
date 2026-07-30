<?php

namespace Tests\Unit;

use App\Models\ingaz\Maid;
use Tests\TestCase;

class RentalMaidEligibilityScopeTest extends TestCase
{
    public function test_rental_contract_candidates_must_be_inside_shelter_and_willing_to_work(): void
    {
        $query = Maid::query()->willingToWorkInsideShelter();
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $this->assertStringContainsString('contracts', $sql);
        $this->assertStringContainsString('contract_infos', $sql);
        $this->assertStringContainsString('wants_to_work', $sql);
        $this->assertContains('shelter', $bindings);
        $this->assertContains('yes', $bindings);
    }
}
