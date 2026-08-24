<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\User;
use App\Models\RecruitmentOffices;
use App\Support\RecruitmentContractStageLabels;
use Mockery;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

class RecruitmentContractExportTest extends TestCase
{
    public function test_export_contains_contract_identifiers_and_external_office_data(): void
    {
        $labelsCache = new ReflectionProperty(RecruitmentContractStageLabels::class, 'cachedLabels');
        $labelsCache->setAccessible(true);
        $labelsCache->setValue(null, RecruitmentContractStageLabels::defaults());

        $office = Mockery::mock(RecruitmentOffices::class)->makePartial();
        $office->shouldReceive('getAttribute')->with('title')->andReturn('وكيل نيروبي');
        $office->shouldReceive('getAttribute')->with('phone_office')->andReturn('+254700000000');
        $office->shouldReceive('getAttribute')->with('license_number')->andReturn('LIC-90');
        $office->shouldReceive('getAttribute')->with('manager_name')->andReturn('مدير الوكيل');
        $office->shouldReceive('getAttribute')->with('manager_phone')->andReturn('0700000000');
        $office->shouldReceive('getAttribute')->with('manager_email')->andReturn('agent@example.test');
        $office->shouldReceive('getAttribute')->with('street')->andReturn('Nairobi Road');
        $office->setRelation('nationality', null);

        $info = new ContractInfo([
            'manual_maid_name' => 'عاملة تجريبية',
            'manual_maid_passport_num' => 'P001122',
            'visa_number' => 'VISA-100',
            'msaned_number' => 'MSN-200',
            'tafwed_number' => 'TFW-300',
            'contract_date' => '2026-08-20',
            'contract_duration' => 24,
            'contract_value' => 15000,
        ]);
        $info->setRelation('manualMaidOffice', $office);
        $info->setRelation('manualMaidNationality', null);

        $client = new User([
            'name' => 'عميل تجريبي',
            'phone_code' => '+966',
            'phone' => '500000000',
            'national_num' => '1010101010',
        ]);

        $contract = new Contract([
            'id' => 321,
            'status' => 'new',
        ]);
        $contract->setRelation('info', $info);
        $contract->setRelation('user', $client);
        $contract->setRelation('maid', null);
        $contract->setRelation('marketer', null);
        $contract->setRelation('order', null);

        $row = $this->invokeControllerMethod('recruitmentContractExportRow', [$contract]);
        $columns = $this->invokeControllerMethod('recruitmentContractExportColumns');

        $this->assertSame(array_keys($columns), array_keys($row));
        $this->assertSame('وكيل نيروبي', $row['external_office']);
        $this->assertSame('+254700000000', $row['external_office_phone']);
        $this->assertSame('LIC-90', $row['external_office_license']);
        $this->assertSame('VISA-100', $row['visa_number']);
        $this->assertSame('MSN-200', $row['msaned_number']);
        $this->assertSame('TFW-300', $row['tafwed_number']);
        $this->assertSame('عاملة تجريبية', $row['maid_name']);
        $this->assertSame('15000', $row['contract_value']);
    }

    private function invokeControllerMethod(string $name, array $arguments = []): array
    {
        $method = new ReflectionMethod(RecruitmentContractController::class, $name);
        $method->setAccessible(true);

        return $method->invokeArgs(new RecruitmentContractController(), $arguments);
    }
}
