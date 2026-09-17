<?php

namespace Tests\Unit;

use App\Models\SponsorshipTransfer;
use PHPUnit\Framework\TestCase;

class SponsorshipTransferInvoiceEligibilityTest extends TestCase
{
    /** @dataProvider ineligibleTransfers */
    public function test_failed_cancelled_rejected_and_rental_transfers_cannot_be_invoiced(array $attributes): void
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
            'rental' => [['status' => 'trial_started', 'workflow_source' => 'rental']],
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
}
