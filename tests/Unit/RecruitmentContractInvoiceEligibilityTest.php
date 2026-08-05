<?php

namespace Tests\Unit;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use PHPUnit\Framework\TestCase;

class RecruitmentContractInvoiceEligibilityTest extends TestCase
{
    public function test_invoice_eligible_statuses_are_the_supported_pre_arrival_stages(): void
    {
        $this->assertSame([
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
        ], Contract::invoiceEligibleStatuses());
    }

    /** @dataProvider invoiceEligibilityProvider */
    public function test_contract_invoice_eligibility_depends_on_its_stage(string $status, bool $eligible): void
    {
        $contract = new Contract(['status' => $status]);

        $this->assertSame($eligible, $contract->isInvoiceEligible());
    }

    public static function invoiceEligibilityProvider(): array
    {
        return [
            'pending maid selection' => [ContractStatusEnum::PendingMaidSelection->value, true],
            'new' => [ContractStatusEnum::New->value, true],
            'musaned' => [ContractStatusEnum::Msaned->value, true],
            'delegation' => [ContractStatusEnum::Tafwed->value, true],
            'visa processing' => [ContractStatusEnum::Taffez->value, true],
            'ticketing' => [ContractStatusEnum::Tazker->value, true],
            'arrival' => [ContractStatusEnum::Arrival->value, false],
            'completed' => [ContractStatusEnum::Completed->value, false],
            'refunded' => [ContractStatusEnum::Refunded->value, false],
            'returned' => [ContractStatusEnum::Back->value, false],
            'shelter' => [ContractStatusEnum::Shelter->value, false],
            'legacy current' => [ContractStatusEnum::Sari->value, false],
        ];
    }
}
