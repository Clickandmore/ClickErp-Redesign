<?php

namespace Tests\Unit;

use App\Models\SponsorshipTransfer;
use App\Services\ingaz\SponsorshipTransferCompletionService;
use App\Services\ingaz\SponsorshipTransferFailureService;
use App\Services\ingaz\SponsorshipTransferPaymentService;
use App\Services\ingaz\SponsorshipTransferTrialStartService;
use Mockery;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;
use PHPUnit\Framework\TestCase;

class RentalSponsorshipTransferAccountingTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_rental_workflow_is_identified_explicitly(): void
    {
        $rental = new SponsorshipTransfer(['workflow_source' => 'rental']);
        $standard = new SponsorshipTransfer(['workflow_source' => 'standard']);

        $this->assertTrue($rental->isRentalWorkflow());
        $this->assertFalse($standard->isRentalWorkflow());
    }

    public function test_rental_workflow_skips_receipt_and_refund_bonds(): void
    {
        $service = new SponsorshipTransferPaymentService(
            Mockery::mock(BondService::class),
            Mockery::mock(YearService::class)
        );
        $transfer = new SponsorshipTransfer(['workflow_source' => 'rental']);

        $this->assertSame(
            ['bond' => null, 'constraint' => null],
            $service->createReceipt($transfer, 1)
        );
        $this->assertSame(
            ['bond' => null, 'constraint' => null],
            $service->createRefund($transfer, 100, 1)
        );
    }

    public function test_rental_workflow_skips_all_transfer_constraints(): void
    {
        $constraintService = Mockery::mock(ConstraintService::class);
        $yearService = Mockery::mock(YearService::class);
        $transfer = new SponsorshipTransfer(['workflow_source' => 'rental']);

        $ledger = Mockery::mock(\App\Services\ingaz\ShelterCostLedgerService::class);

        $this->assertNull((new SponsorshipTransferTrialStartService($constraintService, $yearService, $ledger))->record($transfer));
        $this->assertNull((new SponsorshipTransferCompletionService($constraintService, $yearService, $ledger))->record($transfer));
        $this->assertNull((new SponsorshipTransferFailureService($constraintService, $yearService, $ledger))->record($transfer));
    }
}
