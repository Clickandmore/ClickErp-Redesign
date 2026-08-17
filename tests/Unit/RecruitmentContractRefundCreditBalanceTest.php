<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\RecruitmentInvoiceCreditNote;
use App\Models\ingaz\Invoice;
use App\Services\ingaz\RecruitmentContractRefundService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RecruitmentContractRefundCreditBalanceTest extends TestCase
{
    public function test_refund_creates_only_the_uncredited_invoice_balance(): void
    {
        $invoice = new Invoice(['total_due' => 1000]);
        $invoice->setRelation('musanedTransferItem', null);
        $invoice->setRelation('recruitmentCreditNotes', new Collection([
            new RecruitmentInvoiceCreditNote(['total' => 300]),
        ]));

        $contract = new Contract(['created_at' => now()->subDay()]);
        $contract->setRelation('invoice', $invoice);
        $contract->setRelation('info', null);

        $summary = app(RecruitmentContractRefundService::class)->preview(
            $contract,
            now()->toDateString()
        );

        $this->assertSame(300.0, $summary['previous_credit_amount']);
        $this->assertSame(700.0, $summary['remaining_credit_note_amount']);
        $this->assertSame(1000.0, $summary['refund_amount']);
    }

    public function test_fully_credited_invoice_has_no_second_credit_note_balance(): void
    {
        $invoice = new Invoice(['total_due' => 1000]);
        $invoice->setRelation('musanedTransferItem', null);
        $invoice->setRelation('recruitmentCreditNotes', new Collection([
            new RecruitmentInvoiceCreditNote(['total' => 1000]),
        ]));

        $contract = new Contract(['created_at' => now()->subDay()]);
        $contract->setRelation('invoice', $invoice);
        $contract->setRelation('info', null);

        $summary = app(RecruitmentContractRefundService::class)->preview(
            $contract,
            now()->toDateString()
        );

        $this->assertSame(0.0, $summary['remaining_credit_note_amount']);
    }
}
