<?php

namespace Tests\Unit;

use App\Models\IngazDelegation;
use App\Models\ingaz\Invoice;
use App\Models\MusanedTransfer;
use App\Models\MusanedTransferItem;
use App\Models\RecruitmentInvoiceCreditNote;
use App\Support\IngazDelegationFinancialDocuments;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IngazDelegationFinancialDocumentsTest extends TestCase
{
    public function test_delegation_without_invoice_has_no_financial_documents(): void
    {
        $delegation = new IngazDelegation();
        $delegation->setRelation('invoice', null);
        $this->assertSame([], IngazDelegationFinancialDocuments::for($delegation));
    }

    public function test_linked_entries_and_exact_legacy_references_are_collected_without_writes(): void
    {
        $delegation = (new IngazDelegation())->forceFill(['id' => 987654321]);
        $invoice = (new Invoice())->forceFill([
            'id' => 987654322, 'invoice_constraint_id' => 101,
            'ingaz_delegation_cost_constraint_id' => 102,
        ]);
        $invoice->setRelation('recruitmentCreditNotes', collect([
            (new RecruitmentInvoiceCreditNote())->forceFill(['id' => 20, 'constraint_id' => 103]),
        ]));
        $transfer = (new MusanedTransfer())->forceFill([
            'settlement_constraint_id' => 104, 'bank_transfer_constraint_id' => 105,
        ]);
        $item = new MusanedTransferItem();
        $item->setRelation('transfer', $transfer);
        $invoice->setRelation('musanedTransferItem', $item);
        $delegation->setRelation('invoice', $invoice);
        $queries = [];
        DB::listen(function ($query) use (&$queries) { $queries[] = $query; });

        $documents = collect(IngazDelegationFinancialDocuments::for($delegation));
        foreach ([101, 102, 103, 104, 105] as $id) {
            $this->assertContains(route('finance.add-constraint.show', $id), $documents->pluck('url'));
        }
        $bondQuery = collect($queries)->first(fn ($query) => str_contains($query->sql, 'fn_bonds'));
        $this->assertNotNull($bondQuery);
        $this->assertContains('qabd', $bondQuery->bindings);
        $this->assertContains('سند قبض سداد فاتورة تفويض إنجاز #987654321 / الفاتورة #987654322 /%', $bondQuery->bindings);
        foreach ($queries as $query) {
            $this->assertStringStartsWith('select', strtolower($query->sql));
        }
    }
}
