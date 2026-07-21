<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RentalContractStatusBadgeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_active_contract_past_its_end_date_is_labelled_as_awaiting_closure_or_extension(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00'));

        $badge = $this->statusBadgeForEndDate('2026-07-17');

        $this->assertStringContainsString('منتهي - بانتظار الإغلاق أو التمديد', $badge);
        $this->assertStringContainsString('bg-danger', $badge);
    }

    public function test_active_contract_ending_today_is_labelled_as_ending_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 12:00:00'));

        $badge = $this->statusBadgeForEndDate('2026-07-21');

        $this->assertStringContainsString('ينتهي اليوم', $badge);
        $this->assertStringContainsString('bg-warning', $badge);
    }

    private function statusBadgeForEndDate(string $endDate): string
    {
        $contract = new RentalContract(['status' => 'active']);
        $contract->setRelation('info', new RentalContractInfo([
            'ingaz_to' => $endDate,
        ]));

        $reflection = new ReflectionClass(RentContractController::class);
        $controller = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('getStatusBadge');
        $method->setAccessible(true);

        return $method->invoke($controller, 'active', null, $contract);
    }
}
