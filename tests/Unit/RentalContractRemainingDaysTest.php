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
        $this->assertSame(30, $contract->calculateRemainingDays());

        $report = new \App\Services\ingaz\ReportExportService();
        $durationMethod = new \ReflectionMethod($report, 'resolveRentReportDurationDays');
        $durationMethod->setAccessible(true);
        $remainingMethod = new \ReflectionMethod($report, 'resolveRentReportRemainingDays');
        $remainingMethod->setAccessible(true);
        $this->assertSame('30', $durationMethod->invoke($report, $contract, Carbon::parse('2026-09-27'), Carbon::parse('2026-10-27')));
        $this->assertSame('+30', $remainingMethod->invoke($report, $contract, Carbon::parse('2026-10-27')));
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
        $this->assertSame(29, $contract->calculateRemainingDays());
    }

    /** @dataProvider recordedContractPeriods */
    public function test_remaining_days_follow_the_end_date_despite_historical_extensions(string $start, string $end, array $extensions, int $expected): void
    {
        Carbon::setTestNow('2026-09-30 18:00:00');
        $contract = new RentalContract(['status' => 'active']);
        $contract->setRelation('info', new RentalContractInfo([
            'ingaz_from' => $start, 'ingaz_to' => $end,
            'contract_duration_days' => 30, 'package_id' => 8,
        ]));
        $contract->setRelation('extensions', collect($extensions)->map(
            fn ($extension) => new \App\Models\RentalContractExtension($extension)
        ));

        $this->assertSame($expected, $contract->calculateRemainingDays());
    }

    public static function recordedContractPeriods(): array
    {
        return [
            'extension before current start (RC-2026-0108)' => [
                '2026-07-11', '2026-10-09', [
                    ['extension_days' => 30, 'start_date' => '2026-06-12', 'end_date' => '2026-07-11'],
                    ['extension_days' => 30, 'start_date' => '2026-08-11', 'end_date' => '2026-09-09'],
                    ['extension_days' => 30, 'start_date' => '2026-09-10', 'end_date' => '2026-10-09'],
                ], 9,
            ],
            'overlapping extensions (RC-2026-0092)' => [
                '2026-03-23', '2026-10-19', [
                    ['extension_days' => 90, 'start_date' => '2026-04-23', 'end_date' => '2026-07-21'],
                    ['extension_days' => 30, 'start_date' => '2026-06-22', 'end_date' => '2026-07-21'],
                    ['extension_days' => 30, 'start_date' => '2026-07-22', 'end_date' => '2026-08-20'],
                    ['extension_days' => 30, 'start_date' => '2026-08-21', 'end_date' => '2026-09-19'],
                    ['extension_days' => 30, 'start_date' => '2026-09-20', 'end_date' => '2026-10-19'],
                ], 19,
            ],
        ];
    }

    public function test_closed_contract_uses_its_closure_date(): void
    {
        Carbon::setTestNow('2026-10-20');
        $contract = new RentalContract(['status' => 'completed', 'closed_at' => '2026-09-30 18:00:00']);
        $contract->setRelation('info', new RentalContractInfo([
            'ingaz_from' => '2026-07-11', 'ingaz_to' => '2026-10-09',
        ]));

        $this->assertSame(9, $contract->calculateRemainingDays());
    }
}
