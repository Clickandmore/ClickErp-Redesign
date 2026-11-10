<?php

namespace Tests\Unit;

use App\Enums\ingaz\ContractStatusEnum;
use App\Services\ingaz\DataImportExecutionService;
use ReflectionMethod;
use Tests\TestCase;

class RecruitmentContractDataImportTest extends TestCase
{
    /** @dataProvider maidReservationProvider */
    public function test_import_reserves_workers_only_for_current_contracts(string $contractStatus, string $maidStatus, bool $shouldReserve): void
    {
        $maid = \Mockery::mock(\App\Models\ingaz\Maid::class)->makePartial();
        $maid->status = $maidStatus;
        if ($shouldReserve) {
            $maid->shouldReceive('update')->once()->with(['status' => 'reserved', 'available' => false])->andReturn(true);
        } else {
            $maid->shouldNotReceive('update');
        }
        $contract = new \App\Models\Contract(['status' => $contractStatus]);
        $service = app(DataImportExecutionService::class);
        $method = new ReflectionMethod($service, 'reserveImportedRecruitmentMaid');
        $method->setAccessible(true);
        $method->invoke($service, $contract, $maid);
        $this->addToAssertionCount(1);
    }

    public static function maidReservationProvider(): array
    {
        return [
            'new worker linked to active contract' => ['new', 'available', true],
            'legacy active contract' => ['sari', 'available', true],
            'arrival' => ['arrival', 'available', true],
            'inconsistent availability on reserved worker' => ['new', 'reserved', true],
            'cancelled contract' => ['cancelled', 'available', false],
            'completed contract' => ['completed', 'available', false],
            'returned contract' => ['back', 'available', false],
            'shelter contract' => ['shelter', 'available', false],
            'preserve other worker status' => ['new', 'contracted', false],
        ];
    }

    public function test_import_without_a_worker_does_not_attempt_reservation(): void
    {
        $service = app(DataImportExecutionService::class);
        $method = new ReflectionMethod($service, 'reserveImportedRecruitmentMaid');
        $method->setAccessible(true);
        $method->invoke($service, new \App\Models\Contract(['status' => 'pending_maid_selection']), null);
        $this->addToAssertionCount(1);
    }
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
