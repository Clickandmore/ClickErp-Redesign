<?php

namespace Tests\Unit;

use App\Console\Commands\ResetAlrafedainDelegationCosts as Reset;
use PHPUnit\Framework\TestCase;

class ResetAlrafedainDelegationCostsTest extends TestCase
{
    public function test_local_copy_requires_explicit_flag_and_loopback_host(): void
    {
        $this->assertTrue(Reset::targetAllowed('rafdain_test', '127.0.0.1', 'rafdain_test', true));
        $this->assertFalse(Reset::targetAllowed('rafdain_test', 'remote.example', 'rafdain_test', true));
        $this->assertFalse(Reset::targetAllowed('rafdain_test', '127.0.0.1', 'rafdain_test', false));
        $this->assertFalse(Reset::targetAllowed('another_company', '127.0.0.1', 'another_company', true));
        $this->assertFalse(Reset::targetAllowed('alrafedain_dashboard', '127.0.0.1', 'alrafedain_dashboard', true));
    }

    private function journal(): array
    {
        return ['id' => 104663, 'value' => 137, 'statement' => 'قيد تكلفة تفويض إنجاز #77 / الفاتورة #90 / إنجاز 123'];
    }

    private function invoice(): array
    {
        return ['id' => 90, 'ingaz_delegation_id' => 77, 'ingaz_delegation_cost_constraint_id' => 104663];
    }

    private function lines(): array
    {
        return [['account_id' => 1, 'debtor_value' => 137, 'creditor_value' => 0],
            ['account_id' => 2, 'debtor_value' => 0, 'creditor_value' => 137]];
    }

    public function test_verified_old_cost_can_be_removed_and_unlinked(): void
    {
        $this->assertNull(Reset::skipReason($this->journal(), $this->invoice(), $this->lines(), [
            ['table' => 'invoices', 'column' => 'ingaz_delegation_cost_constraint_id', 'id' => 90],
        ]));
    }

    public function test_old_unlinked_cost_is_found_by_exact_statement(): void
    {
        $invoice = array_replace($this->invoice(), ['ingaz_delegation_cost_constraint_id' => null]);
        $this->assertNull(Reset::skipReason($this->journal(), $invoice, $this->lines(), []));
    }

    public function test_new_confirmed_payment_cost_is_protected(): void
    {
        $journal = array_replace($this->journal(), ['statement' => 'قيد تكلفة تفويض إنجاز #77 / الفاتورة #90 / تحويل بنكي / مرجع ABC']);
        $this->assertNotNull(Reset::skipReason($journal, $this->invoice(), $this->lines(), []));
    }

    public function test_invoice_revenue_journal_is_never_removed(): void
    {
        $journal = array_replace($this->journal(), ['statement' => 'قيد فاتورة تفويض إنجاز #77 / الفاتورة #90 / إنجاز 123']);
        $this->assertNotNull(Reset::skipReason($journal, $this->invoice(), $this->lines(), []));
    }

    public function test_other_document_reference_blocks_deletion(): void
    {
        $this->assertNotNull(Reset::skipReason($this->journal(), $this->invoice(), $this->lines(), [
            ['table' => 'invoices', 'column' => 'invoice_constraint_id', 'id' => 90],
        ]));
    }

    public function test_mismatched_invoice_link_blocks_deletion(): void
    {
        $invoice = array_replace($this->invoice(), ['ingaz_delegation_cost_constraint_id' => 88]);
        $this->assertNotNull(Reset::skipReason($this->journal(), $invoice, $this->lines(), []));
    }

    public function test_unbalanced_or_modified_cost_is_protected(): void
    {
        $lines = $this->lines();
        $lines[1]['creditor_value'] = 100;
        $this->assertNotNull(Reset::skipReason($this->journal(), $this->invoice(), $lines, []));
        $this->assertNotNull(Reset::skipReason($this->journal(), null, $this->lines(), []));
    }
}
