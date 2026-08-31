<?php

namespace Tests\Feature;

use App\Models\IngazDelegation;
use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\CommissionCalculationService;
use App\Services\commissions\DelegationCommissionService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class DelegationCommissionReversalTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @dataProvider activeDuplicateFieldProvider
     */
    public function test_database_rejects_each_active_duplicate_identifier(string $field): void
    {
        $admin = Admin::factory()->create();
        $first = $this->createDelegation($admin);

        $this->expectException(QueryException::class);
        $this->createDelegation($admin, [
            $field => $first->{$field},
        ]);
    }

    public function test_cancelled_identifiers_can_be_used_by_a_replacement_delegation(): void
    {
        $admin = Admin::factory()->create();
        $first = $this->createDelegation($admin);
        $identifiers = [
            'ingaz_number' => $first->ingaz_number,
            'msaned_contract_number' => $first->msaned_contract_number,
            'visa_number' => $first->visa_number,
        ];

        $first->update(['status' => IngazDelegation::STATUS_CANCELLED]);
        $replacement = $this->createDelegation($admin, $identifiers);

        $this->assertNotSame($first->id, $replacement->id);
        $this->assertNull($first->fresh()->active_ingaz_number_key);
        $this->assertSame(IngazDelegation::duplicateKey($replacement->ingaz_number), $replacement->active_ingaz_number_key);
    }

    public function test_cancelling_after_payment_keeps_history_and_creates_one_negative_balance_reversal(): void
    {
        $admin = Admin::factory()->create();
        $delegation = $this->createDelegation($admin);
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $admin->id,
            'total_requests' => 1,
            'total_base_amount' => 1500,
            'total_commission' => 50,
            'status' => CommissionBatch::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'created_by' => $admin->id,
        ]);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id,
            'ingaz_delegation_id' => $delegation->id,
            'source_type' => CommissionOperationService::SOURCE_DELEGATION,
            'source_id' => $delegation->id,
            'service_type' => 'electronic_ingaz_delegation',
            'base_amount' => 1500,
            'method' => 'fixed',
            'rate_or_amount' => 50,
            'commission_amount' => 50,
        ]);

        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $admin->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 50,
            'reference_type' => 'commission_batch',
            'reference_id' => $batch->id,
            'balance_after' => 50,
            'created_by' => $admin->id,
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $admin->id,
            'type' => CommissionBalanceTransaction::TYPE_DEBIT,
            'amount' => 50,
            'reference_type' => 'commission_payment',
            'reference_id' => 991001,
            'balance_after' => 0,
            'created_by' => $admin->id,
        ]);

        $service = app(DelegationCommissionService::class);
        $first = $service->reverseApprovedCommission($delegation, $admin->id, 'اختبار إلغاء التفويض');
        $second = $service->reverseApprovedCommission($delegation, $admin->id, 'محاولة مكررة');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(-50.0, (float) $first->balance_after);
        $this->assertDatabaseHas('commission_batch_items', [
            'id' => $item->id,
            'batch_id' => $batch->id,
            'commission_reversal_transaction_id' => $first->id,
        ]);
        $this->assertDatabaseHas('commission_balance_transactions', [
            'reference_type' => 'commission_payment',
            'reference_id' => 991001,
            'amount' => 50,
        ]);
        $this->assertSame(1, CommissionBalanceTransaction::query()
            ->where('reference_type', 'commission_reversal')
            ->where('reference_id', $item->id)
            ->count());
    }

    public function test_approval_rechecks_delegation_eligibility_after_preview(): void
    {
        $admin = Admin::factory()->create();
        $delegation = $this->createDelegation($admin);
        $key = CommissionOperationService::SOURCE_DELEGATION . ':' . $delegation->id;
        $operation = [
            'key' => $key,
            'source_type' => CommissionOperationService::SOURCE_DELEGATION,
            'source_id' => $delegation->id,
            'source_label' => 'تفويض إنجاز',
            'reference' => $delegation->ingaz_number,
            'order_reference' => '-',
            'service_type' => 'electronic_ingaz_delegation',
            'client_name' => 'عميل اختبار',
            'status' => 'تم التنفيذ',
            'base_amount' => 1500.0,
            'is_paid' => true,
            'operation_date' => now()->startOfDay(),
            'order_id' => null,
            'contract_id' => null,
            'commissioned' => false,
        ];

        $operationService = Mockery::mock(CommissionOperationService::class);
        $operationService->shouldReceive('findSelected')->once()->andReturn(collect([$operation]));
        $eligibility = Mockery::mock(DelegationCommissionService::class);
        $eligibility->shouldReceive('assertEligible')
            ->once()
            ->andThrow(ValidationException::withMessages(['operations' => 'تم إلغاء التفويض أثناء المراجعة.']));
        $calculation = new CommissionCalculationService(
            $operationService,
            $eligibility,
            Mockery::mock(RecruitmentCommissionService::class)
        );

        try {
            $calculation->approveBatch(
                $admin->id,
                [$key],
                [$key => ['method' => 'fixed', 'value' => 50]],
                null,
                $admin->id
            );
            $this->fail('كان يجب رفض اعتماد التفويض بعد فشل إعادة فحص الأهلية.');
        } catch (ValidationException $e) {
            $this->assertSame('تم إلغاء التفويض أثناء المراجعة.', $e->errors()['operations'][0]);
        }

        $this->assertDatabaseMissing('commission_batch_items', [
            'source_type' => CommissionOperationService::SOURCE_DELEGATION,
            'source_id' => $delegation->id,
        ]);
    }

    public function test_non_executed_delegation_is_listed_but_cannot_be_selected_for_commission(): void
    {
        $admin = Admin::factory()->create();
        $delegation = $this->createDelegation($admin, [
            'status' => IngazDelegation::STATUS_UNDER_PROCESS,
        ]);
        $key = CommissionOperationService::SOURCE_DELEGATION . ':' . $delegation->id;
        $service = app(CommissionOperationService::class);

        $operation = $service->getOperations($admin->id, [
            'service_type' => 'electronic_ingaz_delegation',
        ])->firstWhere('key', $key);

        $this->assertNotNull($operation);
        $this->assertFalse($operation['commission_eligible']);
        $this->assertSame(
            'لم يتم تنفيذ التفويض بعد — غير مؤهل للعمولة',
            $operation['commission_ineligible_reason']
        );
        $this->assertTrue($service->findSelected($admin->id, [$key])->isEmpty());
    }

    public static function activeDuplicateFieldProvider(): array
    {
        return [
            'رقم إنجاز' => ['ingaz_number'],
            'رقم عقد مساند' => ['msaned_contract_number'],
            'رقم التأشيرة' => ['visa_number'],
        ];
    }

    private function createDelegation(Admin $admin, array $overrides = []): IngazDelegation
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            return IngazDelegation::query()->create(array_merge([
                'delegation_date_gregorian' => now()->toDateString(),
                'delegation_date_hijri' => '1448-01-01',
                'nationality_id' => 991001,
                'country_id' => 991001,
                'profession_id' => 991001,
                'marketer_id' => $admin->id,
                'visa_type' => 'electronic_ingaz_delegation',
                'worker_name' => 'عاملة اختبار',
                'worker_passport_number' => 'TEST-P-' . uniqid(),
                'visa_number' => 'TEST-V-' . uniqid(),
                'worker_religion' => 'unspecified',
                'worker_experience' => 'unspecified',
                'external_office_name' => 'مكتب اختبار',
                'status' => IngazDelegation::STATUS_EXECUTED,
                'ingaz_number' => 'TEST-I-' . uniqid(),
                'msaned_contract_number' => 'TEST-M-' . uniqid(),
            ], $overrides));
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
}
