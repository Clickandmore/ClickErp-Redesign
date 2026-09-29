<?php

namespace Tests\Unit;

use App\Support\RentalContractDays;
use App\Support\RentalContractSettlement;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class RentalContractDaysTest extends TestCase
{
    /** @dataProvider serviceDates */
    public function test_service_days_and_financial_settlement_agree(string $date, int $used, int $remaining, int $overdue): void
    {
        $days = RentalContractDays::calculate(30, new DateTimeImmutable('2026-09-27'), new DateTimeImmutable('2026-10-27'), new DateTimeImmutable($date));
        $this->assertSame($used, $days['used_days']);
        $this->assertSame($remaining, $days['remaining_days']);
        $this->assertSame($overdue, $days['overdue_days']);
        $this->assertSame(30, $days['used_days'] + $days['remaining_days']);
        $settlement = RentalContractSettlement::calculate(3000, 30, 3000, $days['used_days'], $days['overdue_days']);
        $this->assertSame((float) ($used * 100), $settlement['used_cost']);
        $this->assertSame((float) ($overdue * 100), $settlement['overdue_cost']);
        $this->assertSame((float) max(0, ($remaining - $overdue) * 100), $settlement['refund_value']);
    }

    public static function serviceDates(): array
    {
        return [
            ['2026-09-26', 0, 30, 0],
            ['2026-09-27 00:00:00', 1, 29, 0],
            ['2026-09-27 23:59:59', 1, 29, 0],
            ['2026-09-28', 2, 28, 0],
            ['2026-09-29', 3, 27, 0],
            ['2026-10-26', 30, 0, 0],
            ['2026-10-27', 30, 0, 0],
            ['2026-10-28', 30, 0, 1],
        ];
    }
}
