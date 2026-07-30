<?php

namespace Tests\Unit;

use App\Support\RentalContractSettlement;
use PHPUnit\Framework\TestCase;

class RentalContractSettlementTest extends TestCase
{
    public function test_an_unpaid_contract_is_never_refunded(): void
    {
        // The reported bug: a 3-month contract (1 month + 2 months extension)
        // cancelled on day 11 with nothing paid proposed refunding 1,565.00,
        // which is the whole contract value the client never handed over.
        $settlement = RentalContractSettlement::calculate(
            contractValue: 4695.00,
            durationDays: 90,
            paidAmount: 0.0,
            usedDays: 11
        );

        $this->assertSame(0.0, $settlement['refund_value']);
        $this->assertSame(573.83, $settlement['company_due_value']);
        $this->assertSame('company_due', $settlement['settlement_type']);
        $this->assertSame(52.17, $settlement['daily_rate']);
    }

    public function test_a_fully_paid_contract_refunds_only_the_unused_days(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 4695.00,
            durationDays: 90,
            paidAmount: 4695.00,
            usedDays: 11
        );

        $this->assertSame(573.83, $settlement['used_cost']);
        $this->assertSame(4121.17, $settlement['refund_value']);
        $this->assertSame(0.0, $settlement['company_due_value']);
        $this->assertSame('refund', $settlement['settlement_type']);
    }

    public function test_a_partially_paid_contract_settles_against_what_was_earned(): void
    {
        // Paid less than the used days are worth, so the balance is owed.
        $settlement = RentalContractSettlement::calculate(
            contractValue: 4695.00,
            durationDays: 90,
            paidAmount: 300.00,
            usedDays: 11
        );

        $this->assertSame(0.0, $settlement['refund_value']);
        $this->assertSame(273.83, $settlement['company_due_value']);
    }

    public function test_a_partial_payment_above_the_used_cost_is_refunded(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 4695.00,
            durationDays: 90,
            paidAmount: 1000.00,
            usedDays: 11
        );

        $this->assertSame(426.17, $settlement['refund_value']);
        $this->assertSame(0.0, $settlement['company_due_value']);
    }

    public function test_a_contract_used_to_its_end_settles_to_zero(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 4695.00,
            durationDays: 90,
            paidAmount: 4695.00,
            usedDays: 90
        );

        $this->assertSame(4695.00, $settlement['used_cost']);
        $this->assertSame(0.0, $settlement['refund_value']);
        $this->assertSame(0.0, $settlement['company_due_value']);
    }

    public function test_days_kept_past_the_end_date_are_charged_on_top(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 4695.00,
            durationDays: 90,
            paidAmount: 4695.00,
            usedDays: 90,
            overdueDays: 5
        );

        $this->assertSame(260.83, $settlement['overdue_cost']);
        $this->assertSame(260.83, $settlement['company_due_value']);
        $this->assertSame(0.0, $settlement['refund_value']);
    }

    public function test_used_days_cannot_exceed_the_contract_duration(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 4695.00,
            durationDays: 90,
            paidAmount: 4695.00,
            usedDays: 200
        );

        $this->assertSame(90, $settlement['used_days']);
        $this->assertSame(4695.00, $settlement['used_cost']);
        $this->assertSame(0.0, $settlement['refund_value']);
    }

    public function test_closing_before_any_day_is_used_refunds_everything_paid(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 4695.00,
            durationDays: 90,
            paidAmount: 4695.00,
            usedDays: 0
        );

        $this->assertSame(0.0, $settlement['used_cost']);
        $this->assertSame(4695.00, $settlement['refund_value']);
    }

    public function test_a_contract_without_a_duration_earns_nothing(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 1565.00,
            durationDays: 0,
            paidAmount: 1565.00,
            usedDays: 11
        );

        $this->assertSame(0.0, $settlement['daily_rate']);
        $this->assertSame(0.0, $settlement['used_cost']);
        $this->assertSame(1565.00, $settlement['refund_value']);
    }

    public function test_an_overpayment_is_returned_in_full(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 1565.00,
            durationDays: 30,
            paidAmount: 2000.00,
            usedDays: 30
        );

        $this->assertSame(435.00, $settlement['refund_value']);
    }
}
