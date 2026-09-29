<?php

namespace Tests\Unit;

use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use Carbon\Carbon;
use Tests\TestCase;

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

    public function test_imported_snapshot_does_not_override_live_remaining_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 15:30:00'));

        $contract = new RentalContract([
            'legacy_source' => 'alrafedin_rental_orders',
        ]);
        $contract->setRelation('info', new RentalContractInfo([
            'ingaz_to' => '2026-07-13',
            'legacy_remaining_days' => 2,
        ]));

        $this->assertSame(-8, $contract->calculateRemainingDays());
    }

    public function test_first_day_is_not_counted_as_both_used_and_remaining(): void
    {
        Carbon::setTestNow('2026-09-27 18:00:00');
        $contract = new RentalContract();
        $contract->setRelation('info', new RentalContractInfo([
            'ingaz_from' => '2026-09-27', 'ingaz_to' => '2026-10-27',
            'contract_duration_days' => 30,
        ]));
        $contract->setRelation('extensions', collect());

        $this->assertSame(1, $contract->calculateDayUsage()['used_days']);
        $this->assertSame(29, $contract->calculateRemainingDays());

        $report = new \App\Services\ingaz\ReportExportService();
        $durationMethod = new \ReflectionMethod($report, 'resolveRentReportDurationDays');
        $durationMethod->setAccessible(true);
        $remainingMethod = new \ReflectionMethod($report, 'resolveRentReportRemainingDays');
        $remainingMethod->setAccessible(true);
        $this->assertSame('30', $durationMethod->invoke($report, $contract, Carbon::parse('2026-09-27'), Carbon::parse('2026-10-27')));
        $this->assertSame('+29', $remainingMethod->invoke($report, $contract, Carbon::parse('2026-10-27')));
    }

    public function test_extension_is_included_once_in_used_and_remaining_days(): void
    {
        Carbon::setTestNow('2026-10-28 18:00:00');
        $contract = new RentalContract();
        $contract->setRelation('info', new RentalContractInfo([
            'ingaz_from' => '2026-09-27', 'ingaz_to' => '2026-11-26',
            'contract_duration_days' => 30,
        ]));
        $contract->setRelation('extensions', collect([
            new \App\Models\RentalContractExtension(['extension_days' => 30]),
        ]));

        $this->assertSame(60, $contract->calculateDayUsage()['duration_days']);
        $this->assertSame(32, $contract->calculateDayUsage()['used_days']);
        $this->assertSame(28, $contract->calculateRemainingDays());
    }
}
