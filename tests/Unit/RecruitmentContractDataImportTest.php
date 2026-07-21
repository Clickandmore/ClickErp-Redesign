<?php

namespace Tests\Unit;

use App\Enums\ingaz\ContractStatusEnum;
use App\Services\ingaz\DataImportExecutionService;
use ReflectionMethod;
use Tests\TestCase;

class RecruitmentContractDataImportTest extends TestCase
{
    /** @dataProvider importedStatusProvider */
    public function test_it_normalizes_recruitment_statuses_used_by_the_tawsst_sheet(
        string $sourceStatus,
        string $expectedStatus
    ): void {
        $service = app(DataImportExecutionService::class);
        $method = new ReflectionMethod($service, 'normalizeRecruitmentContractStatus');
        $method->setAccessible(true);

        $this->assertSame($expectedStatus, $method->invoke($service, $sourceStatus));
    }

    public static function importedStatusProvider(): array
    {
        return [
            'completed' => ['مكتمل', ContractStatusEnum::Completed->value],
            'cancelled' => ['ملغى', 'cancelled'],
            'active legacy wording' => ['سار', ContractStatusEnum::Sari->value],
            'pending singular' => ['بانتظار اختيار العاملة', ContractStatusEnum::PendingMaidSelection->value],
            'pending plural' => ['بانتظار اختيار العمالة', ContractStatusEnum::PendingMaidSelection->value],
        ];
    }

    public function test_pending_contract_can_be_imported_without_maid_data(): void
    {
        $service = app(DataImportExecutionService::class);
        $method = new ReflectionMethod($service, 'recruitmentBundleHasMaidData');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($service, [
            'passport_num' => '',
            'name_en' => '',
            'nationality' => '',
        ]));

        $this->assertTrue($method->invoke($service, [
            'passport_num' => 'P1234567',
            'name_en' => '',
            'nationality' => '',
        ]));
    }
}
