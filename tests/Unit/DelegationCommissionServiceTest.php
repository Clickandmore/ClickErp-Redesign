<?php

namespace Tests\Unit;

use App\Models\IngazDelegation;
use App\Models\RecruitmentInvoiceCreditNote;
use App\Models\ingaz\Invoice;
use App\Services\commissions\DelegationCommissionService;
use Illuminate\Support\Collection;
use Tests\TestCase;

class DelegationCommissionServiceTest extends TestCase
{
    public function test_every_non_cancelled_delegation_status_is_eligible(): void
    {
        $service = app(DelegationCommissionService::class);

        $underProcess = $this->delegation(IngazDelegation::STATUS_UNDER_PROCESS);
        $executed = $this->delegation(IngazDelegation::STATUS_EXECUTED);
        $cancelled = $this->delegation(IngazDelegation::STATUS_CANCELLED);

        $this->assertTrue($service->isEligible($underProcess));
        $this->assertTrue($service->isEligible($executed));
        $this->assertFalse($service->isEligible($cancelled));
    }

    public function test_cancelled_delegation_reason_explains_why_commission_is_disabled(): void
    {
        $service = app(DelegationCommissionService::class);

        $this->assertSame(
            'التفويض ملغي — غير مؤهل للعمولة',
            $service->ineligibilityReason($this->delegation(IngazDelegation::STATUS_CANCELLED))
        );
        $this->assertNull(
            $service->ineligibilityReason($this->delegation(IngazDelegation::STATUS_EXECUTED))
        );
    }

    public function test_any_custom_stage_is_eligible_for_commission(): void
    {
        $delegation = $this->delegation('stage_any_custom_status');

        $this->assertTrue(app(DelegationCommissionService::class)->isEligible($delegation));
    }

    public function test_a_zero_value_invoice_does_not_block_a_fixed_commission(): void
    {
        $service = app(DelegationCommissionService::class);

        $this->assertTrue($service->isEligible(
            $this->delegation(IngazDelegation::STATUS_UNDER_PROCESS, 0)
        ));
    }

    public function test_a_fully_credited_delegation_is_not_eligible(): void
    {
        $service = app(DelegationCommissionService::class);
        $delegation = $this->delegation(
            IngazDelegation::STATUS_EXECUTED,
            1500,
            [1000, 500]
        );

        $this->assertFalse($service->isEligible($delegation));
    }

    public function test_a_partially_credited_executed_delegation_remains_eligible(): void
    {
        $service = app(DelegationCommissionService::class);
        $delegation = $this->delegation(
            IngazDelegation::STATUS_EXECUTED,
            1500,
            [500]
        );

        $this->assertTrue($service->isEligible($delegation));
    }

    public function test_duplicate_keys_are_trimmed_and_case_insensitive(): void
    {
        $this->assertSame('ing-100', IngazDelegation::duplicateKey('  ING-100  '));
        $this->assertNull(IngazDelegation::duplicateKey('   '));
        $this->assertNull(IngazDelegation::duplicateKey('0'));
        $this->assertNull(IngazDelegation::duplicateKey('0000'));
        $this->assertSame('000123', IngazDelegation::duplicateKey('000123'));
    }

    private function delegation(string $status, ?float $invoiceTotal = null, array $credits = []): IngazDelegation
    {
        $delegation = new IngazDelegation(['status' => $status]);

        if ($invoiceTotal === null) {
            $delegation->setRelation('invoice', null);

            return $delegation;
        }

        $invoice = new Invoice(['total_due' => $invoiceTotal]);
        $invoice->setRelation('recruitmentCreditNotes', new Collection(array_map(
            fn (float $total) => new RecruitmentInvoiceCreditNote(['total' => $total]),
            $credits
        )));
        $delegation->setRelation('invoice', $invoice);

        return $delegation;
    }
}
