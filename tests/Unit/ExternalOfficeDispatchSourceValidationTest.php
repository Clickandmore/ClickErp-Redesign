<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ingaz\Maid;
use Tests\TestCase;

class ExternalOfficeDispatchSourceValidationTest extends TestCase
{
    public function test_company_rental_maid_dispatch_uses_company_sponsor_details_without_a_customer(): void
    {
        $contract = new Contract(['id' => 605, 'type' => 'recruitment', 'status' => 'new', 'company_profile_id' => 1]);
        $contract->setRelation('companyProfile', new \App\Models\CompanyProfile([
            'id' => 1, 'name' => 'Rental Company', 'tax_number' => '300000000000003', 'registration_number' => '7001234567',
        ]));
        $contract->setRelation('user', null);
        $contract->setRelation('info', new \App\Models\ContractInfo(['visa_number' => '']));
        $maid = new Maid(['name' => 'Rental Worker', 'passport_num' => 'P123456', 'experience_status' => 'no_experience']);
        $maid->setRelation('religion', new class(['title' => 'Test Religion']) extends \Illuminate\Database\Eloquent\Model {
            protected $guarded = [];
        });
        $office = new \App\Models\RecruitmentOffices(['id' => 50, 'cost_of_dollar' => '1000']);
        $office->setRelation('translations', collect([new \App\Models\RecruitmentOfficeTranslation([
            'locale' => app()->getLocale(), 'title' => 'External Office',
        ])]));
        $maid->setRelation('office', $office);
        $maid->setRelation('recruitmentOffice', null);
        $contract->info->setRelation('manualMaidOffice', null);
        $contract->setRelation('maid', $maid);

        $source = $contract->buildExternalOfficeDispatchSourceData();

        $this->assertTrue($contract->canManageExternalOfficeDispatch());
        $this->assertSame('Rental Company', $source['sponsor_name']);
        $this->assertSame('7001234567', $source['sponsor_identity_number']);
        $this->assertSame([], $contract->externalOfficeDispatchSourceValidationErrors());

        $originalHash = $contract->buildExternalOfficeDispatchSourceHash();
        $contract->companyProfile->name = 'Updated Company';
        $this->assertSame('Updated Company', $contract->buildExternalOfficeDispatchSourceData()['sponsor_name']);
        $this->assertNotSame($originalHash, $contract->buildExternalOfficeDispatchSourceHash());
        $originalHash = $contract->buildExternalOfficeDispatchSourceHash();
        $contract->companyProfile->registration_number = '7007654321';
        $this->assertNotSame($originalHash, $contract->buildExternalOfficeDispatchSourceHash());
        $contract->companyProfile->registration_number = null;
        $this->assertSame('', $contract->buildExternalOfficeDispatchSourceData()['sponsor_identity_number']);
        $this->assertContains('رقم السجل التجاري غير مكتمل في إعدادات الشركة.', $contract->externalOfficeDispatchSourceValidationErrors());

        // A different customer keeps their own document, even when company settings are loaded.
        $contract->company_profile_id = null;
        $customer = new \App\Models\ingaz\User(['name' => 'Customer', 'national_num' => '1096545619', 'client_type' => 'individual']);
        $contract->setRelation('user', $customer);
        $this->assertSame('Customer', $contract->buildExternalOfficeDispatchSourceData()['sponsor_name']);
        $this->assertSame($customer->documentNumber(), $contract->buildExternalOfficeDispatchSourceData()['sponsor_identity_number']);
        $this->assertSame([], $contract->externalOfficeDispatchSourceValidationErrors());
        $customer->client_type = 'company';
        $customer->tax_number = '300123456789003';
        $this->assertSame($customer->documentNumber(), $contract->buildExternalOfficeDispatchSourceData()['sponsor_identity_number']);
        $this->assertSame('Customer', $contract->buildExternalOfficeDispatchSourceData()['sponsor_name']);
    }

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
