<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Services\commissions\CommissionAccrualService;
use App\Services\commissions\CommissionPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\FnYear;
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
        [$admin, $batch] = $this->financeFixture();
        $parent = Account::query()->create([
            'name' => 'المسوقين',
            'status' => 'creditor',
            'level' => 1,
            'code' => 144,
            'type' => AccountTypesEnum::Marketers->value,
        ]);
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

    private function financeFixture(): array
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
        $payable = Account::query()->create([
            'name' => 'عمولات الموظف المستحقة',
            'status' => 'creditor',
            'level' => 1,
            'code' => 223,
            'type' => AccountTypesEnum::Marketer->value,
        ]);
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
