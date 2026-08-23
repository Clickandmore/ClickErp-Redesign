<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ingaz\Maid;
use Tests\TestCase;

class ExternalOfficeDispatchSourceValidationTest extends TestCase
{
    public function test_dispatch_source_allows_a_missing_visa_number(): void
    {
        $contract = new class extends Contract
        {
            public function buildExternalOfficeDispatchSourceData($officeContractCostUsd = null): array
            {
                return [
                    'contract_number' => '7663',
                    'visa_number' => '',
                    'sponsor_name' => 'Test Sponsor',
                    'sponsor_identity_number' => '1096545619',
                    'worker_name' => 'Test Worker',
                    'passport_number' => 'A21238127',
                    'worker_age' => '25',
                    'worker_experience' => 'لم يسبق لها العمل',
                    'worker_religion' => 'غير مسلمة',
                    'office_name' => 'Test Office',
                    'recruitment_office_id' => 50,
                    'office_contract_cost_usd' => '1000.00',
                ];
            }
        };
        $contract->setRelation('maid', new Maid());

        $this->assertSame([], $contract->externalOfficeDispatchSourceValidationErrors());
    }

    public function test_dispatch_source_allows_a_missing_worker_age(): void
    {
        $contract = new class extends Contract
        {
            public function buildExternalOfficeDispatchSourceData($officeContractCostUsd = null): array
            {
                return [
                    'contract_number' => '605',
                    'visa_number' => '123456789',
                    'sponsor_name' => 'Test Sponsor',
                    'sponsor_identity_number' => '1096545619',
                    'worker_name' => 'Test Worker',
                    'passport_number' => 'A21238127',
                    'worker_age' => '',
                    'worker_experience' => 'لم يسبق لها العمل',
                    'worker_religion' => 'غير مسلمة',
                    'office_name' => 'Test Office',
                    'recruitment_office_id' => 50,
                    'office_contract_cost_usd' => '1000.00',
                ];
            }
        };
        $contract->setRelation('maid', new Maid());

        $this->assertSame([], $contract->externalOfficeDispatchSourceValidationErrors());
    }
}
