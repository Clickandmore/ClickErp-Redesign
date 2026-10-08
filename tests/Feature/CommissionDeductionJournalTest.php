<?php

namespace Tests\Feature;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\commissions\CommissionBalanceTransaction;
use App\Models\commissions\CommissionBatch;
use App\Models\commissions\CommissionBatchItem;
use App\Services\commissions\CommissionAccrualService;
use App\Services\commissions\CommissionDeductionJournalService;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\CommissionProfileService;
use App\Services\commissions\RecruitmentCommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Models\FnYear;
use Tests\TestCase;

class CommissionDeductionJournalTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithCommissionFinance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommissionFinance();
    }

    private function fixture(): array
    {
        $admin = Admin::factory()->create();
        $contract = Contract::withoutEvents(fn () => Contract::query()->create([
            'status' => ContractStatusEnum::Refunded->value, 'marketer_id' => $admin->id, 'contract_date' => '2026-07-01',
        ]));
        $batch = CommissionBatch::query()->create([
            'marketer_id' => $admin->id, 'status' => CommissionBatch::STATUS_APPROVED,
            'total_commission' => 1200, 'approved_at' => now(), 'approved_by' => $admin->id,
        ]);
        app(CommissionAccrualService::class)->createForBatch($batch, $admin->id);
        $item = CommissionBatchItem::query()->create([
            'batch_id' => $batch->id, 'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id, 'contract_id' => $contract->id, 'service_type' => 'recruitment',
            'commission_amount' => 200, 'operation_date' => '2026-07-01',
        ]);
        CommissionBalanceTransaction::query()->create([
            'marketer_id' => $admin->id, 'type' => 'credit', 'amount' => 1200,
            'reference_type' => 'commission_batch', 'reference_id' => $batch->id, 'balance_after' => 1200,
        ]);
        return [$admin, $batch->fresh(), $item];
    }

    private function ledgerBalance(int $accountId): float
    {
        return (float) DB::table('fn_constraint_details as d')
            ->join('fn_constraints as c', 'c.id', '=', 'd.constraint_id')
            ->where('d.account_id', $accountId)->whereNull('d.deleted_at')->whereNull('c.deleted_at')
            ->where('c.approval_status', 'approved')->selectRaw('COALESCE(SUM(d.creditor_value - d.debtor_value), 0) AS balance')
            ->value('balance');
    }

    /** @dataProvider decisions */
    public function test_settlement_keeps_ledger_and_commission_balance_equal(string $decision, ?float $partial, float $deduction): void
    {
        [$admin, $batch, $item] = $this->fixture();
        $this->assertSame(1200.0, $this->ledgerBalance($batch->payable_account_id));
        app(RecruitmentCommissionService::class)->resolveSettlement($item, $decision, $admin->id, null, $partial);
        $this->assertSame(1200.0 - $deduction, $this->ledgerBalance($batch->payable_account_id));
        $this->assertSame(1200.0 - $deduction, app(CommissionProfileService::class)->getMarketerBalance($admin->id));
        $item->refresh();
        if ($deduction > 0) {
            $tx = $item->reversalTransaction;
            $this->assertNotNull($tx->constraint_id);
            $this->assertDatabaseHas('fn_constraint_details', [
                'constraint_id' => $tx->constraint_id, 'account_id' => $batch->payable_account_id,
                'debtor_value' => $deduction, 'creditor_value' => 0,
            ]);
            $this->assertDatabaseHas('fn_constraint_details', [
                'constraint_id' => $tx->constraint_id, 'account_id' => $batch->expense_account_id,
                'debtor_value' => 0, 'creditor_value' => $deduction,
            ]);
            $constraintId = $tx->constraint_id;
            app(CommissionDeductionJournalService::class)->post($tx, $admin->id);
            $this->assertSame($constraintId, $tx->fresh()->constraint_id);
            $this->assertSame(1, DB::table('fn_constraint_details')->where('constraint_id', $constraintId)->where('account_id', $batch->payable_account_id)->count());
        } else {
            $this->assertNull($item->commission_reversal_transaction_id);
        }
        $this->expectException(ValidationException::class);
        app(RecruitmentCommissionService::class)->resolveSettlement($item, $decision, $admin->id, null, $partial);
    }

    public static function decisions(): array
    {
        return [['deducted', null, 200.0], ['partial', 75.0, 75.0], ['zeroed', null, 0.0]];
    }

    public function test_repair_posts_only_missing_journal_without_debiting_balance_again(): void
    {
        [$admin, $batch, $item] = $this->fixture();
        $tx = CommissionBalanceTransaction::query()->create([
            'marketer_id' => $admin->id, 'type' => 'debit', 'amount' => 200,
            'reference_type' => 'commission_reversal', 'reference_id' => $item->id, 'balance_after' => 1000,
            'created_by' => $admin->id,
        ]);
        $item->update(['commission_reversal_transaction_id' => $tx->id, 'commission_reversed_at' => now()]);
        $count = CommissionBalanceTransaction::query()->count();
        $this->assertSame(1200.0, $this->ledgerBalance($batch->payable_account_id));
        $admin->givePermissionTo(\App\Models\Permission::firstOrCreate([
            'name' => \App\PermKeys::COMMISSIONS_CALCULATE, 'guard_name' => 'admin',
        ]));
        $this->actingAs($admin, 'admin');
        $this->get(route('commissions.marketers.show', $admin->id))->assertOk()->assertSee('إنشاء قيود الخصومات السابقة');
        $this->post(route('commissions.marketers.deduction-journals.repair', $admin->id))->assertRedirect();
        $service = app(CommissionDeductionJournalService::class);
        $this->assertSame(0, $service->repairForMarketer($admin->id, $admin->id));
        $this->assertSame($count, CommissionBalanceTransaction::query()->count());
        $this->assertSame(1000.0, $this->ledgerBalance($batch->payable_account_id));
        $this->assertSame(1000.0, app(CommissionProfileService::class)->getMarketerBalance($admin->id));
    }

    public function test_failed_journal_rolls_back_deduction_and_keeps_it_pending(): void
    {
        [$admin, , $item] = $this->fixture();
        FnYear::query()->update(['status' => 0]);
        try {
            app(RecruitmentCommissionService::class)->resolveSettlement($item, 'deducted', $admin->id);
            $this->fail('A deduction without its financial journal was accepted.');
        } catch (ValidationException $exception) {
            $this->assertNull($item->fresh()->commission_reversal_transaction_id);
            $this->assertSame(1200.0, app(CommissionProfileService::class)->getMarketerBalance($admin->id));
            $this->assertCount(1, app(RecruitmentCommissionService::class)->pendingDeductions($admin->id));
        }
    }

    public function test_financial_approval_requirement_is_respected(): void
    {
        [$admin, , $item] = $this->fixture();
        DB::table('fn_confirm_settings')->insert(['title' => 'daily_constraints', 'value' => 'on', 'created_at' => now(), 'updated_at' => now()]);
        app(RecruitmentCommissionService::class)->resolveSettlement($item, 'deducted', $admin->id);
        $this->assertSame('pending', $item->fresh()->reversalTransaction->constraint->approval_status);
    }

    public function test_contract_date_filters_do_not_hide_recruitment_deductions(): void
    {
        [$admin, , $item] = $this->fixture();
        $service = app(RecruitmentCommissionService::class);
        foreach ([
            [],
            ['date_filter' => 'month_year', 'year' => 2026, 'month' => 1],
            ['date_filter' => 'range', 'from_date' => '2025-01-01', 'to_date' => '2025-01-02'],
        ] as $dates) {
            $this->assertSame([$item->id], $service->pendingDeductions($admin->id, $dates)->pluck('id')->all());
        }
        $this->assertCount(0, $service->pendingDeductions($admin->id, ['service_type' => 'rental']));
    }

    public function test_repair_route_requires_calculation_permission(): void
    {
        [$admin] = $this->fixture();
        $actor = Admin::factory()->create(['admin_type' => 'customer_service', 'status' => 'active']);
        $actor->syncPermissions([]);
        $this->actingAs($actor, 'admin');
        $url = route('commissions.marketers.deduction-journals.repair', $admin->id);
        $this->postJson($url)->assertForbidden();
        $actor->givePermissionTo(\App\Models\Permission::firstOrCreate([
            'name' => \App\PermKeys::COMMISSIONS_CALCULATE, 'guard_name' => 'admin',
        ]));
        $this->actingAs($actor->fresh(), 'admin');
        $this->get(route('commissions.marketers.show', $admin->id))->assertOk()->assertSee('القيد المالي');
        $this->post($url)->assertRedirect();
    }
}
