<?php

namespace Tests\Unit;

use Nami\Finance\Services\FinancialPositionService;
use PHPUnit\Framework\TestCase;

class FinancialPositionServiceTest extends TestCase
{
    private function account(int $id, ?int $parent, string $code, int $type = 1): object
    {
        return (object) ['id' => $id, 'parent_id' => $parent, 'code' => $code, 'name' => $code, 'type' => $type];
    }

    private function balance(int $id, string $debit, string $credit = '0'): object
    {
        return (object) ['account_id' => $id, 'debit' => $debit, 'credit' => $credit];
    }

    private function accounts(): array
    {
        return [$this->account(1, null, '1'), $this->account(2, 1, '12'), $this->account(3, 2, '121'),
            $this->account(4, null, '2'), $this->account(5, 4, '14'), $this->account(6, null, '3'),
            $this->account(7, null, '4'), $this->account(8, null, '5')];
    }

    public function test_parent_postings_and_descendants_count_once_and_root_profit_is_included(): void
    {
        $r = (new FinancialPositionService)->build($this->accounts(), [
            $this->balance(2, '10.10'), $this->balance(3, '100.20'),
            $this->balance(5, '0', '20.10'), $this->balance(6, '0', '50.20'),
            $this->balance(7, '0', '45.00'), $this->balance(8, '5.00'),
        ]);
        $this->assertSame(11030, $r['assets']);
        $this->assertSame(4000, $r['result']);
        $this->assertSame(9020, $r['equity']);
        $this->assertTrue($r['balanced']);
        $this->assertSame(11030, $r['sections']['current_assets']['rows'][0]['amount']);
    }

    public function test_closing_profit_to_equity_does_not_duplicate_it(): void
    {
        $service = new FinancialPositionService;
        $before = $service->build($this->accounts(), [$this->balance(3, '100'), $this->balance(7, '0', '100')]);
        $after = $service->build($this->accounts(), [$this->balance(3, '100'), $this->balance(7, '100', '100'), $this->balance(6, '0', '100')]);
        $this->assertSame($before['equity'], $after['equity']);
        $this->assertSame(0, $after['result']);
        $this->assertTrue($after['balanced']);
    }

    public function test_contra_balances_are_subtracted_and_losses_reduce_equity(): void
    {
        $r = (new FinancialPositionService)->build($this->accounts(), [
            $this->balance(2, '100'), $this->balance(3, '0', '30'),
            $this->balance(6, '0', '100'), $this->balance(8, '30'),
        ]);
        $this->assertSame(7000, $r['assets']);
        $this->assertSame(-3000, $r['result']);
        $this->assertTrue($r['balanced']);
    }

    public function test_offsetting_unclassified_accounts_are_not_hidden_by_a_zero_total(): void
    {
        $accounts = array_merge($this->accounts(), [$this->account(9, null, '9'), $this->account(10, 9, '90')]);
        $r = (new FinancialPositionService)->build($accounts, [$this->balance(9, '100'), $this->balance(10, '0', '100')]);
        $this->assertSame(0, $r['sections']['unclassified']['total']);
        $this->assertTrue($r['incomplete']);
        $this->assertNull($r['current_ratio']);
    }

    public function test_missing_accounts_cycles_and_orphans_remain_visible(): void
    {
        $accounts = [$this->account(1, 2, '1'), $this->account(2, 1, '2'), $this->account(3, 99, '3')];
        $r = (new FinancialPositionService)->build($accounts, [$this->balance(1, '10'), $this->balance(3, '20'), $this->balance(99, '0', '30')]);
        $this->assertCount(4, $r['issues']);
        $this->assertTrue($r['incomplete']);
        $this->assertCount(4, $r['sections']['unclassified']['rows']);
    }

    public function test_unbalanced_ledger_is_reported_without_a_balancing_plug(): void
    {
        $r = (new FinancialPositionService)->build($this->accounts(), [$this->balance(3, '100')]);
        $this->assertFalse($r['balanced']);
        $this->assertSame(10000, $r['difference']);
        $this->assertSame(10000, $r['ledger']);
        $this->assertSame(0, $r['result']);
    }

    public function test_unlimited_depth_and_legacy_non_prefix_liability_codes(): void
    {
        $accounts = $this->accounts();
        $parent = 5;
        for ($i = 20; $i <= 35; $i++) {
            $accounts[] = $this->account($i, $parent, (string) (1000 + $i));
            $parent = $i;
        }
        $r = (new FinancialPositionService)->build($accounts, [$this->balance(35, '0', '0.29'), $this->balance(3, '0.29')]);
        $this->assertSame(29, $r['sections']['current_liabilities']['total']);
        $this->assertTrue($r['balanced']);
    }
}
