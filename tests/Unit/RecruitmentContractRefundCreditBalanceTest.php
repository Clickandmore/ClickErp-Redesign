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
    /** @dataProvider editablePenaltyCases */
    public function test_manual_penalty_updates_refund(array $overrides, string $party, bool $transferred, float $percent, float $amount, float $refund): void
    {
        $invoice = new Invoice(['total_due' => 1000]);
        $item = new \App\Models\MusanedTransferItem();
        $item->setRelation('transfer', new \App\Models\MusanedTransfer());
        $invoice->setRelation('musanedTransferItem', $transferred ? $item : null);
        $invoice->setRelation('recruitmentCreditNotes', new Collection());
        $contract = new Contract(['created_at' => now()->subDay()]);
        $contract->setRelation('invoice', $invoice);
        $contract->setRelation('info', null);

        $summary = app(RecruitmentContractRefundService::class)->preview($contract, now()->toDateString(), $party, $overrides);

        $this->assertSame($percent, $summary['penalty_percent']);
        $this->assertSame($amount, $summary['penalty_amount']);
        $this->assertSame($refund, $summary['refund_amount']);
    }

    public static function editablePenaltyCases(): array
    {
        return [
            'percentage' => [['penalty_percent' => 12.5], 'client', true, 12.5, 125.0, 875.0],
            'amount' => [['penalty_amount' => 123.45], 'client', true, 12.345, 123.45, 876.55],
            'zero' => [['penalty_amount' => 0], 'client', true, 0.0, 0.0, 1000.0],
            'full' => [['penalty_percent' => 100], 'client', true, 100.0, 1000.0, 0.0],
            'office' => [['penalty_amount' => 200], 'office', true, 20.0, 200.0, 1000.0],
            'external office' => [['penalty_amount' => 200], 'external_office', true, 20.0, 200.0, 1000.0],
            'before transfer' => [['penalty_amount' => 200], 'client', false, 0.0, 0.0, 1000.0],
        ];
    }

    /** @dataProvider invalidPenaltyCases */
    public function test_invalid_manual_penalty_is_rejected(array $overrides): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->test_manual_penalty_updates_refund($overrides, 'client', true, 0.0, 0.0, 0.0);
    }

    public static function invalidPenaltyCases(): array
    {
        return [
            [['penalty_amount' => -1]],
            [['penalty_amount' => 1001]],
            [['penalty_percent' => -1]],
            [['penalty_percent' => 101]],
        ];
    }

}
