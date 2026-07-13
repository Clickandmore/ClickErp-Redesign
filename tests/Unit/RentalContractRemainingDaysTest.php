<?php

namespace Tests\Unit;

use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class RentalContractRemainingDaysTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_counts_tomorrows_end_date_as_one_remaining_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-13 15:30:00'));

        $contract = new RentalContract();
        $contract->setRelation('info', new RentalContractInfo([
            'ingaz_to' => '2026-07-14',
        ]));

        $this->assertSame(1, $contract->calculateRemainingDays());
    }

    public function test_it_counts_contract_as_expiring_today_not_overdue(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-14 15:30:00'));

        $contract = new RentalContract();
        $contract->setRelation('info', new RentalContractInfo([
            'ingaz_to' => '2026-07-14',
        ]));

        $this->assertSame(0, $contract->calculateRemainingDays());
    }
}
