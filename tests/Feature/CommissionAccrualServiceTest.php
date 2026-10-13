<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Services\commissions\CommissionAccrualService;
use App\Services\commissions\CommissionCalculationService;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\CommissionPaymentService;
use App\Services\commissions\DelegationCommissionService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\FnYear;
use Mockery;
use Tests\TestCase;

class CommissionAccrualServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_approval_accrual_debits_expense_and_credits_employee_payable(): void
    {
        [$admin, $batch, $expense, $payable] = $this->financeFixture();

        $constraint = app(CommissionAccrualService::class)->createForBatch($batch, $admin->id);

        $batch->refresh();
        $this->assertSame($constraint->id, (int) $batch->accrual_constraint_id);
        $this->assertSame(ConstraintTypiesEnum::MarketerEntry, $constraint->type);
        $this->assertDatabaseHas('fn_constraint_details', [
            'constraint_id' => $constraint->id,
            'account_id' => $expense->id,
            'type' => 'debtor',
            'debtor_value' => 500,
            'creditor_value' => 0,
        ]);
        $this->assertDatabaseHas('fn_constraint_details', [
            'constraint_id' => $constraint->id,
            'account_id' => $payable->id,
            'type' => 'creditor',
            'debtor_value' => 0,
            'creditor_value' => 500,
        ]);
    }

    public function test_approval_uses_the_marketers_own_payable_account_when_available(): void
    {
        [$admin, $batch] = $this->financeFixture(false);
        $parent = Account::query()->where('code', 144)->firstOrFail();
        $marketerAccount = Account::query()->create([
            'name' => $admin->name,
            'status' => 'creditor',
            'level' => 2,
            'code' => 14400,
            'type' => AccountTypesEnum::Marketer->value,
            'parent_id' => $parent->id,
        ]);

        app(CommissionAccrualService::class)->createForBatch($batch, $admin->id);

        $this->assertSame($marketerAccount->id, (int) $batch->fresh()->payable_account_id);
        $this->assertDatabaseHas('fn_constraint_details', [
            'constraint_id' => $batch->fresh()->accrual_constraint_id,
            'account_id' => $marketerAccount->id,
            'type' => 'creditor',
        ]);
    }

    public function test_approval_uses_the_explicitly_linked_account_instead_of_matching_names(): void
    {
        [$admin, $batch] = $this->financeFixture(false);
        $admin->update(['name' => 'LAYLA SHAMRUOKH']);
        $parent = Account::query()->where('code', 144)->firstOrFail();
        $marketerAccount = Account::query()->create([
            'name' => 'ليلى شمروخ',
            'status' => 'creditor',
            'level' => 2,
            'code' => 1445,
            'type' => AccountTypesEnum::Marketer->value,
            'parent_id' => $parent->id,
        ]);
        $admin->update(['commission_payable_account_id' => $marketerAccount->id]);

        app(CommissionAccrualService::class)->createForBatch($batch, $admin->id);

        $this->assertSame($marketerAccount->id, (int) $batch->fresh()->payable_account_id);
    }

    public function test_a_second_approval_accepts_the_same_automatic_marketer_payable_account(): void
    {
        [$admin, $firstBatch] = $this->financeFixture(false);
        $parent = Account::query()->where('code', 144)->firstOrFail();
        $marketerAccount = Account::query()->create([
            'name' => $admin->name,
            'status' => 'creditor',
            'level' => 2,
            'code' => 14400,
            'type' => AccountTypesEnum::Marketer->value,
            'parent_id' => $parent->id,
        ]);
        $accrualService = app(CommissionAccrualService::class);
        $accrualService->createForBatch($firstBatch, $admin->id);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $admin->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 500,
            'reference_type' => 'commission_batch',
            'reference_id' => $firstBatch->id,
            'balance_after' => 500,
            'created_by' => $admin->id,
        ]);

        $rental = \App\Models\RentalContract::query()->create(['marketer_id' => $admin->id, 'status' => 'active']);
        $key = CommissionOperationService::SOURCE_RENTAL_CONTRACT . ':' . $rental->id;
        $operationService = Mockery::mock(CommissionOperationService::class);
        $operationService->shouldReceive('findSelected')->once()->andReturn(collect([[
            'key' => $key,
            'source_type' => CommissionOperationService::SOURCE_RENTAL_CONTRACT,
            'source_id' => $rental->id,
            'reference' => 'RC-TEST-2',
            'service_type' => 'rental',
            'client_name' => 'عميل اختبار',
            'operation_date' => now()->startOfDay(),
            'base_amount' => 2000,
            'order_id' => null,
            'contract_id' => $rental->id,
        ]]));
        $calculation = new CommissionCalculationService(
            $operationService,
            Mockery::mock(DelegationCommissionService::class),
            app(RecruitmentCommissionService::class),
            $accrualService
        );

        $secondBatch = $calculation->approveBatch(
            $admin->id,
            [$key],
            [$key => ['method' => 'percentage', 'value' => 1.75]],
            null,
            $admin->id
        );

        $this->assertSame($marketerAccount->id, (int) $firstBatch->fresh()->payable_account_id);
        $this->assertSame($marketerAccount->id, (int) $secondBatch->payable_account_id);
        $this->assertSame('full', $secondBatch->items->first()->rental_commission_terms['policy']);
        $this->assertSame(535.0, (float) $secondBatch->balanceTransactions()->first()->balance_after);
    }

    public function test_rolling_back_unpaid_approval_soft_deletes_its_accrual_constraint(): void
    {
        [$admin, $batch, $expense, $payable] = $this->financeFixture();
        $constraint = app(CommissionAccrualService::class)->createForBatch($batch, $admin->id);

        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $admin->id,
            'type' => CommissionBalanceTransaction::TYPE_CREDIT,
            'amount' => 500,
            'reference_type' => 'commission_batch',
            'reference_id' => $batch->id,
            'balance_after' => 500,
            'created_by' => $admin->id,
        ]);

        app(CommissionPaymentService::class)->rollbackUnpaidBatch($admin->id, $batch->id);

        $this->assertDatabaseMissing('commission_batches', ['id' => $batch->id]);
        $this->assertSoftDeleted('fn_constraints', ['id' => $constraint->id]);
        $this->assertSoftDeleted('fn_constraint_details', ['constraint_id' => $constraint->id]);
    }

    public function test_missing_account_is_created_and_linked_for_an_administrator_and_reused(): void
    {
        [$admin, $batch] = $this->financeFixture(false);
        $admin->update(['name' => 'مشرف', 'admin_type' => 'super_admin']);
        $wrongFallback = Account::query()->create([
            'name' => 'دفعات مقدمة نقل خدمات', 'status' => 'creditor', 'level' => 1,
            'code' => 223, 'type' => AccountTypesEnum::ShortLiabilities->value,
        ]);
        $service = app(CommissionAccrualService::class);
        $service->createForBatch($batch, $admin->id);
        $account = $service->payableAccountForMarketer($admin->id);

        $this->assertSame($account->id, (int) $admin->fresh()->commission_payable_account_id);
        $this->assertSame($account->id, (int) $batch->fresh()->payable_account_id);
        $this->assertNotSame($wrongFallback->id, $account->id);
        $this->assertSame('مشرف', $account->name);
        $this->assertSame(AccountTypesEnum::Marketer, $account->type);
        $this->assertSame('144', (string) $account->parent->code);
        $this->assertSame(1, Account::where('parent_id', $account->parent_id)->where('name', 'مشرف')->count());
    }

    public function test_known_legacy_name_is_linked_without_creating_a_duplicate(): void
    {
        [$admin] = $this->financeFixture(false);
        $admin->update(['name' => 'LAYLA SHAMRUOKH', 'email' => 'laylashamruk@gmail.com']);
        $parent = Account::where('code', 144)->firstOrFail();
        $account = Account::create([
            'name' => 'ليلى شمروخ', 'status' => 'creditor', 'level' => 2,
            'code' => 1445, 'parent_id' => $parent->id, 'type' => AccountTypesEnum::Marketer->value,
        ]);
        $this->assertSame($account->id, app(CommissionAccrualService::class)->payableAccountForMarketer($admin->id)->id);
        $this->assertSame($account->id, (int) $admin->fresh()->commission_payable_account_id);
    }

    public function test_failed_accrual_rolls_back_automatic_account_creation_and_link(): void
    {
        [$admin, $batch] = $this->financeFixture(false);
        $batch->update(['approved_at' => '1900-01-01']);
        $count = Account::count();
        try {
            app(CommissionAccrualService::class)->createForBatch($batch, $admin->id);
            $this->fail('An inactive financial year must reject accrual.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('accrual', $exception->errors());
        }
        $this->assertSame($count, Account::count());
        $this->assertNull($admin->fresh()->commission_payable_account_id);
        $this->assertNull($batch->fresh()->accrual_constraint_id);
    }

    public function test_an_account_owned_by_another_marketer_is_not_reused_by_name(): void
    {
        [$owner, , , $account] = $this->financeFixture();
        $marketer = Admin::factory()->create(['name' => $account->name]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(CommissionAccrualService::class)->payableAccountForMarketer($marketer->id);
    }

    public function test_ambiguous_name_matches_require_an_explicit_link(): void
    {
        [$admin] = $this->financeFixture(false);
        $parent = Account::where('code', 144)->firstOrFail();
        foreach ([1441, 1442] as $code) {
            Account::create([
                'name' => $admin->name, 'status' => 'creditor', 'level' => 2,
                'code' => $code, 'parent_id' => $parent->id, 'type' => AccountTypesEnum::Marketer->value,
            ]);
        }
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(CommissionAccrualService::class)->payableAccountForMarketer($admin->id);
    }

    private function financeFixture(bool $linked = true): array
    {
        DB::table('fn_constraint_code_sequences')->updateOrInsert(
            ['id' => 1],
            ['last_code' => 0, 'created_at' => now(), 'updated_at' => now()]
        );
        $admin = Admin::factory()->create();
        FnYear::query()->create([
            'title' => '2026',
            'from_date' => '2026-01-01',
            'to_date' => '2026-12-31',
            'status' => 1,
        ]);
        $expense = Account::query()->create([
            'name' => 'مصروف عمولات الموظفين',
            'status' => 'debtor',
            'level' => 1,
            'code' => 5417,
            'type' => AccountTypesEnum::MarketerExpenses->value,
        ]);
        $parent = Account::query()->create([
            'name' => 'المسوقين',
            'status' => 'creditor',
            'level' => 1,
            'code' => 144,
            'type' => AccountTypesEnum::Marketers->value,
        ]);
        $payable = Account::query()->create([
            'name' => 'عمولات الموظف المستحقة',
            'status' => 'creditor',
            'level' => 1,
            'code' => 14499,
            'parent_id' => $parent->id,
            'type' => AccountTypesEnum::Marketer->value,
        ]);
        if ($linked) {
            $admin->update(['commission_payable_account_id' => $payable->id]);
        }
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $admin->id,
            'total_requests' => 1,
            'total_base_amount' => 5000,
            'total_commission' => 500,
            'status' => CommissionBatch::STATUS_APPROVED,
            'approved_by' => $admin->id,
            'approved_at' => '2026-09-01 12:00:00',
            'created_by' => $admin->id,
        ]);

        return [$admin, $batch, $expense, $payable];
    }
}
