<?php

namespace Tests\Unit;

use App\Models\SponsorshipTransfer;
use PHPUnit\Framework\TestCase;

class SponsorshipTransferInvoiceEligibilityTest extends TestCase
{
    /** @dataProvider ineligibleTransfers */
    public function test_failed_cancelled_and_rejected_transfers_cannot_be_invoiced(array $attributes): void
    {
        $transfer = (new SponsorshipTransfer())->forceFill($attributes);
        $transfer->setRelation('invoice', null);

        $this->assertFalse($transfer->canIssueInvoice());
    }

    public static function ineligibleTransfers(): array
    {
        return [
            'failed' => [['status' => 'failed', 'workflow_source' => null]],
            'cancelled' => [['status' => 'cancelled', 'workflow_source' => null]],
            'rejected' => [['status' => 'rejected', 'workflow_source' => null]],
        ];
    }

    public function test_active_non_rental_transfer_without_invoice_can_be_invoiced(): void
    {
        $transfer = (new SponsorshipTransfer())->forceFill([
            'status' => 'trial_started',
            'workflow_source' => null,
        ]);
        $transfer->setRelation('invoice', null);

        $this->assertTrue($transfer->canIssueInvoice());
    }

    public function test_completed_rental_transfer_can_document_its_recorded_transfer_amount(): void
    {
        $transfer = new SponsorshipTransfer(['status' => 'transferred', 'workflow_source' => 'rental']);
        $transfer->setRelation('invoice', null);
        $this->assertTrue($transfer->canIssueInvoice());
        $transfer->setRelation('invoice', new \App\Models\ingaz\Invoice());
        $this->assertFalse($transfer->canIssueInvoice());
    }

    public function test_rental_request_can_be_invoiced_immediately_and_during_trial(): void
    {
        foreach (['pending', 'approved', 'contract_sent', 'contract_signed', 'maid_delivered', 'trial_started'] as $status) {
            $transfer = new SponsorshipTransfer(['status' => $status, 'workflow_source' => 'rental']);
            $transfer->setRelation('invoice', null);
            $this->assertTrue($transfer->canIssueInvoice(), $status);
        }
    }
}
