<?php

namespace Tests\Unit;

use App\Console\Commands\RepairAlrafedainInvoiceBreakdowns as Repair;
use PHPUnit\Framework\TestCase;

class AlrafedainInvoiceBreakdownPlanTest extends TestCase
{
    private function invoice(): array
    {
        return ['subtotal' => 11378, 'vat_amount' => 622.8, 'total_due' => 12000.8,
            'direct_recruitment_cost' => 11378, 'direct_recruitment_count' => 1];
    }

    private function credits(): array
    {
        return [
            ['account_id' => 423, 'account_name' => 'مبيعات باكستان', 'creditor_value' => 4152],
            ['account_id' => 423, 'account_name' => 'مبيعات باكستان', 'creditor_value' => 6786],
            ['account_id' => 428, 'account_name' => 'تكاليف حكومية', 'creditor_value' => 440],
            ['account_id' => 114, 'account_name' => 'حساب ضريبة القيمة المضافة', 'creditor_value' => 622.8],
        ];
    }

    public function test_recovers_actual_components_and_is_idempotent(): void
    {
        $plan = Repair::plan($this->invoice(), $this->credits());
        $this->assertSame('ready', $plan['status']);
        $this->assertEquals(4152, $plan['after']['direct_recruitment_cost']);
        $this->assertEquals(6786, $plan['after']['external_recruitment_cost']);
        $this->assertEquals(440, $plan['after']['government_cost']);
        $this->assertArrayNotHasKey('vat_amount', $plan['after']);
        $this->assertArrayNotHasKey('total_due', $plan['after']);
        $this->assertSame('already_correct', Repair::plan(array_merge($this->invoice(), $plan['after']), $this->credits())['status']);
    }

    public function test_does_not_guess_external_and_government_from_a_collapsed_posting(): void
    {
        $credits = $this->credits();
        $credits[0]['creditor_value'] = 11378;
        unset($credits[1], $credits[2]);
        $this->assertSame('tax_base_mismatch', Repair::plan($this->invoice(), $credits)['status']);
    }

    public function test_does_not_rewrite_a_manual_breakdown(): void
    {
        $invoice = $this->invoice();
        $invoice['direct_recruitment_cost'] = 4000;
        $this->assertSame('existing_breakdown_differs', Repair::plan($invoice, $this->credits())['status']);
    }

    public function test_rejects_wrong_tax_or_an_unknown_credit_account(): void
    {
        $credits = $this->credits();
        $credits[3]['creditor_value'] = 600;
        $this->assertSame('unsupported_posting', Repair::plan($this->invoice(), $credits)['status']);
        $credits = $this->credits();
        $credits[1]['account_name'] = 'حساب آخر';
        $this->assertSame('unsupported_account', Repair::plan($this->invoice(), $credits)['status']);
    }

    public function test_zero_vat_and_discounted_invoices_need_review(): void
    {
        $invoice = $this->invoice();
        $invoice['vat_amount'] = 0;
        $this->assertSame('unsupported_invoice', Repair::plan($invoice, $this->credits())['status']);
        $invoice = $this->invoice();
        $invoice['discount'] = 5;
        $this->assertSame('unsupported_invoice', Repair::plan($invoice, $this->credits())['status']);
    }
}
