<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\Contract;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class ComplaintMaidWarrantyDetailsTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_it_returns_elapsed_and_remaining_days_for_an_active_warranty(): void
    {
        Carbon::setTestNow('2026-09-03 12:00:00');
        $contract = $this->contractWithWarranty('2026-08-14', '2026-09-23');

        $details = $this->resolveDetails($contract);

        $this->assertSame(20, $details['days_since_arrival']);
        $this->assertSame('active', $details['status']);
        $this->assertSame(20, $details['remaining_days']);
    }

    public function test_it_marks_a_past_warranty_as_expired(): void
    {
        Carbon::setTestNow('2026-09-03 12:00:00');
        $contract = $this->contractWithWarranty('2026-06-01', '2026-08-30');

        $details = $this->resolveDetails($contract);

        $this->assertSame(94, $details['days_since_arrival']);
        $this->assertSame('expired', $details['status']);
        $this->assertSame(0, $details['remaining_days']);
    }

    private function contractWithWarranty(string $startDate, string $endDate): Contract
    {
        $contract = new Contract([
            'warranty_start_date' => $startDate,
            'warranty_end_date' => $endDate,
        ]);
        $contract->setRelation('travelHistory', null);
        $contract->setRelation('info', null);
        $contract->setRelation('maid', null);

        return $contract;
    }

    private function resolveDetails(Contract $contract): array
    {
        $method = new ReflectionMethod(ComplaintController::class, 'resolveComplaintWarrantyDetails');
        $method->setAccessible(true);

        return $method->invoke(app(ComplaintController::class), $contract);
    }
}
