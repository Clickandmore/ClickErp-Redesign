<?php

namespace Tests\Unit;

use App\Support\RentalContractSettlement;
use PHPUnit\Framework\TestCase;

class RentalContractSettlementTest extends TestCase
{
    public function test_early_closure_can_use_a_higher_daily_price(): void
    {
        $result = RentalContractSettlement::calculate(1500, 30, 1500, 4, earlyClosureDailyRate: 100);
        $this->assertSame(400.0, $result['used_cost']);
        $this->assertSame(1100.0, $result['refund_value']);
        $this->assertSame(100.0, $result['used_daily_rate']);
        $this->assertSame(50.0, $result['daily_rate']);
    }

    public function test_an_early_custom_charge_can_exceed_the_original_invoice(): void
    {
        $result = RentalContractSettlement::calculate(1500, 30, 1500, 4, earlyClosureDailyRate: 500);
        $this->assertSame(2000.0, $result['earned_amount']);
        $this->assertSame(500.0, $result['company_due_value']);
        $this->assertSame(0.0, $result['refund_value']);
    }

    public function test_overdue_price_does_not_reprice_the_completed_month(): void
    {
        $result = RentalContractSettlement::calculate(1500, 30, 1500, 30, 4, overdueDailyRate: 100);
        $this->assertSame(1500.0, $result['used_cost']);
        $this->assertSame(400.0, $result['overdue_cost']);
        $this->assertSame(1900.0, $result['earned_amount']);
        $this->assertSame(400.0, $result['company_due_value']);
    }

    public function test_early_price_also_applies_to_an_incomplete_multi_month_contract(): void
    {
        $result = RentalContractSettlement::calculate(3000, 60, 3000, 34, earlyClosureDailyRate: 70);
        $this->assertSame(2380.0, $result['earned_amount']);
        $this->assertSame(620.0, $result['refund_value']);
    }

    public function test_a_completed_contract_ignores_an_early_price(): void
    {
        $result = RentalContractSettlement::calculate(1500, 30, 1500, 30, earlyClosureDailyRate: 100);
        $this->assertSame(1500.0, $result['used_cost']);
        $this->assertNull($result['early_closure_daily_rate_override']);
    }

    public function test_zero_is_an_explicit_overdue_waiver(): void
    {
        $result = RentalContractSettlement::calculate(1500, 30, 1500, 30, 4, overdueDailyRate: 0);
        $this->assertSame(0.0, $result['overdue_cost']);
        $this->assertSame(0.0, $result['company_due_value']);
    }

    public function test_custom_price_works_with_partial_payment_without_refunding_unpaid_money(): void
    {
        $result = RentalContractSettlement::calculate(1500, 30, 100, 4, earlyClosureDailyRate: 100);
        $this->assertSame(0.0, $result['refund_value']);
        $this->assertSame(300.0, $result['company_due_value']);
    }

    public function test_custom_daily_price_rounds_the_total_in_currency(): void
    {
        $result = RentalContractSettlement::calculate(2000, 30, 2000, 7, earlyClosureDailyRate: 80.25);
        $this->assertSame(561.75, $result['used_cost']);
        $this->assertSame(1438.25, $result['refund_value']);
    }

    public function test_custom_prices_cannot_be_combined_with_discounts(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RentalContractSettlement::calculate(1500, 30, 1500, 4, dailyDiscountType: 'fixed', dailyDiscountValue: 10, earlyClosureDailyRate: 100);
    }

    public function test_a_negative_custom_price_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RentalContractSettlement::calculate(1500, 30, 1500, 4, overdueDailyRate: -1);
    }

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

    public function test_a_fixed_daily_discount_changes_only_the_earned_amount(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 2000.00,
            durationDays: 30,
            paidAmount: 2000.00,
            usedDays: 2,
            dailyDiscountType: 'fixed',
            dailyDiscountValue: 16.67
        );

        $this->assertSame(66.67, $settlement['daily_rate']);
        $this->assertSame(50.00, $settlement['adjusted_daily_rate']);
        $this->assertSame(100.00, $settlement['used_cost']);
        $this->assertSame(1900.00, $settlement['refund_value']);
    }

    public function test_a_percentage_daily_discount_is_capped_at_one_hundred_percent(): void
    {
        $settlement = RentalContractSettlement::calculate(
            contractValue: 2000.00,
            durationDays: 30,
            paidAmount: 2000.00,
            usedDays: 2,
            dailyDiscountType: 'percentage',
            dailyDiscountValue: 150
        );

        $this->assertSame(0.0, $settlement['adjusted_daily_rate']);
        $this->assertSame(0.0, $settlement['used_cost']);
        $this->assertSame(2000.00, $settlement['refund_value']);
    }
}
