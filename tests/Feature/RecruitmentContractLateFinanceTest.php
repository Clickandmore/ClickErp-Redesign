<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\NewPermKeys;
use App\PermKeys;
use App\Services\ingaz\RecruitmentTripAccountingService;
use App\Support\AutomaticJournalEntries;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecruitmentContractLateFinanceTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableBranchesModule(false);
        DB::table('fn_confirm_settings')->updateOrInsert(
            ['title' => AutomaticJournalEntries::SETTING_KEY],
            ['value' => 'off', 'updated_at' => now(), 'created_at' => now()]
        );
    }

    private function actor(array $permissions): Admin
    {
        $actor = Admin::factory()->create();
        foreach ($permissions as $name) {
            $actor->givePermissionTo(Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'admin'], ['display_name' => $name, 'is_active' => true]
            ));
        }
        $this->actingAs($actor, 'admin');
        return $actor;
    }

    private function contract(string $status = 'completed'): Contract
    {
        $user = User::withoutEvents(fn () => User::create(['name' => 'عميل العمليات المالية', 'phone' => uniqid('LF-')]));
        $contract = Contract::withoutEvents(fn () => Contract::create(['user_id' => $user->id, 'type' => 'recruitment', 'status' => $status]));
        ContractInfo::withoutEvents(fn () => ContractInfo::create(['contract_id' => $contract->id, 'contract_date' => now()->toDateString()]));
        return $contract;
    }

    private function payload(): array
    {
        return ['invoice_date' => now()->toDateString(), 'external_recruitment_cost' => 1000,
            'external_recruitment_count' => 1, 'send_to_zatca' => false];
    }

    private function prepareDispatch(Contract $contract, ?int $nationalityId = null): \App\Models\RecruitmentOffices
    {
        $nationalityId ??= DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = \App\Models\ingaz\Religion::create(['ar' => ['title' => 'مسلم'], 'en' => ['title' => 'Muslim']]);
        $office = \App\Models\RecruitmentOffices::withoutEvents(fn () => \App\Models\RecruitmentOffices::create(['nationality_id' => $nationalityId]));
        $office->translations()->create(['locale' => 'ar', 'title' => 'مكتب اختبار الإرسالية']);
        $office->translations()->create(['locale' => 'en', 'title' => 'Dispatch office']);
        $maid = \App\Models\ingaz\Maid::withoutEvents(fn () => \App\Models\ingaz\Maid::create([
            'name' => 'عامل اختبار', 'passport_num' => uniqid('DP-'), 'nationality_id' => $nationalityId,
            'religion_id' => $religion->id, 'office_id' => $office->id, 'experience_status' => 'no_experience',
            'age' => 30, 'cv_file' => '', 'height' => '', 'weight' => '',
        ]));
        $contract->update(['maid_id' => $maid->id]);
        $contract->user->update(['national_num' => '1096545619']);
        return $office;
    }

    private function dispatchPayload(): array
    {
        return ['dispatch_date' => now()->toDateString(), 'arrival_station' => 'الرياض', 'office_contract_cost_usd' => 600];
    }

    private function delegationCostPayload(int $accountId = 1): array
    {
        return ['date' => now()->toDateString(), 'reference' => 'COST-TEST', 'confirmed' => '1',
            'payment_source_type' => 'bank', 'payment_account_id' => $accountId, 'amount' => '175.25'];
    }

    public function test_delegation_cost_requires_invoice_permission_and_automatic_accounting(): void
    {
        $actor = $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $contract = $this->contract();
        $url = route('RecruitmentContract.lateFinance.delegationCost', $contract->id);
        $this->postJson($url, $this->delegationCostPayload())->assertStatus(422);
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())->assertOk();
        $this->postJson($url, $this->delegationCostPayload())->assertStatus(422)->assertJsonValidationErrors('cost');
        $this->assertNull($contract->invoice()->firstOrFail()->ingaz_delegation_cost_constraint_id);
        $actor->revokePermissionTo(NewPermKeys::REC_CONTRACTS_LATE_FINANCE);
        $this->actingAs($actor->fresh(), 'admin');
        $this->postJson($url, $this->delegationCostPayload())->assertForbidden();
    }

    public function test_dispatch_is_saved_once_without_changing_the_completed_contract(): void
    {
        $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $contract = $this->contract();
        $this->prepareDispatch($contract);
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())->assertOk();
        $url = route('RecruitmentContract.lateFinance.dispatch', $contract->id);
        $this->get(route('RecruitmentContract.lateFinance.show', $contract->id))->assertOk()->assertSee($url, false);
        $this->post($url, $this->dispatchPayload())->assertSessionHasNoErrors()
            ->assertRedirect(route('RecruitmentContract.lateFinance.show', $contract->id));
        $this->assertSame('completed', $contract->fresh()->status);
        $this->assertEquals(600, $contract->externalOfficeDispatch()->firstOrFail()->office_contract_cost_usd);
        $this->postJson($url, $this->dispatchPayload())->assertStatus(422);
        $this->assertSame(1, $contract->externalOfficeDispatch()->count());
    }

    public function test_dispatch_requires_permission_invoice_and_complete_source_data(): void
    {
        $actor = $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $contract = $this->contract();
        $url = route('RecruitmentContract.lateFinance.dispatch', $contract->id);
        $this->postJson($url, $this->dispatchPayload())->assertStatus(422);
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())->assertOk();
        $this->postJson($url, $this->dispatchPayload())->assertStatus(422)->assertJsonValidationErrors('dispatch_date');
        $actor->revokePermissionTo(NewPermKeys::REC_CONTRACTS_LATE_FINANCE);
        $this->actingAs($actor->fresh(), 'admin');
        $this->postJson($url, $this->dispatchPayload())->assertForbidden();
        $this->assertFalse($contract->externalOfficeDispatch()->exists());
    }

    public function test_dispatch_accounting_failure_rolls_back_the_dispatch(): void
    {
        $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $contract = $this->contract('arrival');
        $this->prepareDispatch($contract);
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())->assertOk();
        $this->mock(RecruitmentTripAccountingService::class, function ($mock) {
            $mock->shouldReceive('recordCompletionEntries')->once()->andThrow(new \RuntimeException('تعذر إثبات تكلفة المكتب'));
        });
        $this->postJson(route('RecruitmentContract.lateFinance.dispatch', $contract->id), $this->dispatchPayload())
            ->assertStatus(422)->assertJsonValidationErrors('office_contract_cost_usd');
        $this->assertFalse($contract->externalOfficeDispatch()->exists());
        $this->assertTrue($contract->invoice()->exists());
    }

    public function test_general_contract_and_invoice_permissions_do_not_grant_the_special_page(): void
    {
        $this->actor([NewPermKeys::REC_CONTRACTS_MANAGE, PermKeys::INVOICES_CREATE]);
        $contract = $this->contract();
        $this->get(route('RecruitmentContract.lateFinance.show', $contract->id))->assertForbidden();
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())->assertForbidden();
        $this->postJson(route('RecruitmentContract.lateFinance.transfer', $contract->id), [])->assertForbidden();
        $this->postJson(route('invoices.store'), $this->payload() + ['contract_id' => $contract->id])->assertStatus(422);
    }

    public function test_page_accepts_arrival_and_completed_and_rejects_other_stages(): void
    {
        $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        foreach (['arrival', 'completed'] as $status) {
            $contract = $this->contract($status);
            $this->get(route('RecruitmentContract.lateFinance.show', $contract->id))->assertOk()
                ->assertSee('استكمال العمليات المالية')->assertSee('invoiceForm')
                ->assertSee(route('RecruitmentContract.lateFinance.invoice', $contract->id), false);
        }
        $contract = $this->contract('new');
        $this->get(route('RecruitmentContract.lateFinance.show', $contract->id))->assertStatus(422);
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())->assertStatus(422);
    }

    public function test_invoice_and_transfer_are_saved_on_the_same_page_without_duplicates_or_stage_changes(): void
    {
        $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $contract = $this->contract();
        $url = route('RecruitmentContract.lateFinance.show', $contract->id);
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())
            ->assertOk()->assertJsonPath('success', true)->assertJsonPath('financial_workspace_url', $url);
        $invoice = $contract->invoice()->firstOrFail();
        $this->assertSame('completed', $contract->fresh()->status);
        $this->get($url)->assertOk()->assertSee('items[0][contract_id]', false)->assertDontSee('id="invoiceForm"', false);
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())->assertStatus(422);
        $payload = ['transfer_date' => now()->toDateString(), 'reference_number' => 'LF-TEST', 'bank_account_id' => null, 'notes' => '',
            'items' => [['contract_id' => $contract->id, 'transferred_amount' => 900]]];
        $this->post(route('RecruitmentContract.lateFinance.transfer', $contract->id), $payload)->assertSessionHasNoErrors()->assertRedirect($url);
        $this->get($url)->assertOk()->assertSee('تم تسجيل التحويل');
        $this->assertEquals(900, $invoice->fresh()->musanedTransferItem->transferred_amount);
        $this->postJson(route('RecruitmentContract.lateFinance.transfer', $contract->id), $payload)->assertStatus(422);
        $this->assertSame(1, $contract->invoice()->count());
    }

    public function test_sources_cannot_be_replaced_and_transfer_requires_the_contract_invoice(): void
    {
        $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $contract = $this->contract();
        $other = $this->contract();
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id),
            $this->payload() + ['contract_id' => $other->id])->assertStatus(422);
        $this->postJson(route('RecruitmentContract.lateFinance.transfer', $contract->id), [
            'items' => [['contract_id' => $other->id, 'transferred_amount' => 900]],
        ])->assertStatus(422);
        $this->assertFalse($contract->invoice()->exists());
        $this->assertFalse($other->invoice()->exists());
    }

    public function test_arrival_accounting_failure_rolls_back_the_invoice(): void
    {
        $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $contract = $this->contract('arrival');
        $this->mock(RecruitmentTripAccountingService::class, function ($mock) {
            $mock->shouldReceive('recordCompletionEntries')->once()->andThrow(new \RuntimeException('تعذر إثبات إيراد الوصول'));
        });
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())
            ->assertStatus(422)->assertJsonPath('message', 'تعذر إثبات إيراد الوصول');
        $this->assertFalse(Invoice::query()->where('contract_id', $contract->id)->exists());
    }

    public function test_the_special_permission_does_not_bypass_branch_access(): void
    {
        $this->enableBranchesModule();
        $ownBranch = \App\Models\Branch::factory()->main()->create();
        $otherBranch = \App\Models\Branch::factory()->create();
        $actor = $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $actor->update(['branch_id' => $ownBranch->id]);
        $this->actingAs($actor->fresh(), 'admin');
        $contract = $this->contract();
        Contract::withoutEvents(fn () => $contract->update(['branch_id' => $otherBranch->id]));
        $this->get(route('RecruitmentContract.lateFinance.show', $contract->id))->assertNotFound();
        $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload())->assertNotFound();
        $this->postJson(route('RecruitmentContract.lateFinance.dispatch', $contract->id), $this->dispatchPayload())->assertNotFound();
        $this->postJson(route('RecruitmentContract.lateFinance.delegationCost', $contract->id), $this->delegationCostPayload())->assertNotFound();
    }

    public function test_late_invoice_posts_balanced_invoice_receipt_and_arrival_entries(): void
    {
        $actor = $this->actor([NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        DB::table('fn_confirm_settings')->where('title', AutomaticJournalEntries::SETTING_KEY)->update(['value' => 'on']);
        $makeAccount = function (string $name, string $status, array $attributes = []) use ($actor) {
            return \Nami\Finance\Models\Account::create($attributes + [
                'name' => $name, 'status' => $status, 'type' => 1, 'parent_id' => null,
                'code' => ((int) DB::table('fn_accounts')->max('code')) + 1,
                'level' => 1, 'operation_type' => 1, 'statement_type' => 'income_statement', 'created_by_id' => $actor->id,
            ]);
        };
        $name = 'جنسية اختبار ' . uniqid();
        $nationality = \App\Models\ingaz\Nationality::create(['ar' => ['title' => $name, 'sub_title' => $name]]);
        $root = $makeAccount('التزامات عقود الاستقدام', 'creditor');
        $liability = $makeAccount('التزامات عقود ' . $name, 'creditor', ['parent_id' => $root->id, 'level' => 2]);
        $sales = $makeAccount('مبيعات ' . $name, 'creditor');
        $musaned = $makeAccount('مساند اختبار', 'debtor', ['type' => \Nami\Finance\Enums\AccountTypesEnum::Bank->value]);
        foreach ([
            'musaned_account' => $musaned->id,
            'nationality_account_' . $nationality->id . '_contract_liability' => $liability->id,
            'nationality_account_' . $nationality->id . '_recruitment_sales' => $sales->id,
            \App\Support\RecruitmentRevenueRecognition::SETTING_KEY => 'on_arrival',
        ] as $key => $value) {
            DB::table('fn_confirm_settings')->updateOrInsert(['title' => $key], ['value' => (string) $value, 'updated_at' => now(), 'created_at' => now()]);
        }
        \Nami\Finance\Models\FnYear::create(['title' => 'سنة اختبار', 'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(), 'status' => 1, 'created_by_id' => $actor->id]);
        DB::table('fn_constraint_code_sequences')->updateOrInsert(['id' => 1], [
            'last_code' => (int) DB::table('fn_constraints')->max('code'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $contract = $this->contract('arrival');
        $contract->update(['required_maid_nationality_id' => $nationality->id]);
        $client = $makeAccount('عميل اختبار', 'debtor');
        $contract->user->update(['account_id' => $client->id]);
        $response = $this->postJson(route('RecruitmentContract.lateFinance.invoice', $contract->id), $this->payload());
        $response->assertOk()->assertJsonPath('success', true);
        $invoice = $contract->invoice()->firstOrFail();
        $this->assertNotNull($invoice->invoice_constraint_id);
        $this->assertNotNull($invoice->arrival_revenue_constraint_id);
        foreach ([$invoice->invoice_constraint_id, $invoice->arrival_revenue_constraint_id] as $constraintId) {
            $lines = DB::table('fn_constraint_details')->where('constraint_id', $constraintId);
            $this->assertEquals($lines->sum('debtor_value'), $lines->sum('creditor_value'));
        }
        $this->assertDatabaseHas('fn_bonds', ['recruitment_contract_id' => $contract->id]);
        $liabilityLines = DB::table('fn_constraint_details')->where('account_id', $liability->id);
        $this->assertEquals($liabilityLines->sum('debtor_value'), $liabilityLines->sum('creditor_value'));
        $this->assertSame('arrival', $contract->fresh()->status);
        $this->prepareDispatch($contract, $nationality->id);
        $costAccount = $makeAccount('تكلفة مبيعات ' . $name, 'debtor');
        $categoryId = DB::table('fn_categories')->insertGetId(['title' => 'خدمات اختبار الإرسالية']);
        $unitId = DB::table('fn_units')->insertGetId(['title' => 'خدمة اختبار الإرسالية']);
        \Nami\Finance\Models\Item::create([
            'name' => 'خدمة اختبار الإرسالية', 'type' => 'service', 'code' => ((int) DB::table('fn_items')->max('code')) + 1,
            'main_category_id' => $categoryId, 'sale_unit_id' => $unitId, 'purchase_unit_id' => $unitId,
            'details' => 'اختبار', 'sale_account_id' => $costAccount->id, 'stock_account_id' => $costAccount->id,
        ]);
        $revenueConstraintId = $invoice->arrival_revenue_constraint_id;
        $this->post(route('RecruitmentContract.lateFinance.dispatch', $contract->id), $this->dispatchPayload())
            ->assertSessionHasNoErrors()->assertRedirect(route('RecruitmentContract.lateFinance.show', $contract->id));
        $invoice->refresh();
        $this->assertSame((int) $revenueConstraintId, (int) $invoice->arrival_revenue_constraint_id);
        $this->assertNotNull($invoice->external_office_cost_constraint_id);
        $this->assertNotNull($invoice->external_office_purchase_id);
        $costLines = DB::table('fn_constraint_details')->where('constraint_id', $invoice->external_office_cost_constraint_id);
        $this->assertEquals(2250, $costLines->sum('debtor_value'));
        $this->assertEquals($costLines->sum('debtor_value'), $costLines->sum('creditor_value'));
        $expense = $makeAccount('تكلفة التفويض اختبار', 'debtor');
        DB::table('fn_confirm_settings')->updateOrInsert(['title' => 'ingaz_delegation_cost_account'],
            ['value' => (string) $expense->id, 'created_at' => now(), 'updated_at' => now()]);
        $url = route('RecruitmentContract.lateFinance.delegationCost', $contract->id);
        $this->get(route('RecruitmentContract.lateFinance.show', $contract->id))->assertOk()->assertSee($url, false);
        $other = $this->contract();
        $costPayload = $this->delegationCostPayload($musaned->id) + ['contract_ids' => [$other->id], 'delegation_ids' => [998811]];
        $this->post($url, $costPayload)->assertSessionHasNoErrors()->assertRedirect(route('RecruitmentContract.lateFinance.show', $contract->id));
        $invoice->refresh();
        $this->assertNotNull($invoice->ingaz_delegation_cost_constraint_id);
        $this->assertDatabaseHas('fn_constraint_details', ['constraint_id' => $invoice->ingaz_delegation_cost_constraint_id,
            'account_id' => $expense->id, 'debtor_value' => 175.25, 'creditor_value' => 0]);
        $this->assertDatabaseHas('fn_constraint_details', ['constraint_id' => $invoice->ingaz_delegation_cost_constraint_id,
            'account_id' => $musaned->id, 'debtor_value' => 0, 'creditor_value' => 175.25]);
        $this->assertFalse($other->invoice()->exists());
        $this->postJson($url, $costPayload)->assertStatus(422)->assertJsonValidationErrors('cost');
        $this->assertSame((int) $invoice->ingaz_delegation_cost_constraint_id, (int) $invoice->fresh()->ingaz_delegation_cost_constraint_id);
        $this->get(route('RecruitmentContract.lateFinance.show', $contract->id))->assertOk()->assertSee('تكلفة التفويض مسجلة بالفعل');
        $this->assertSame('arrival', $contract->fresh()->status);
    }

    public function test_financial_button_is_distinct_and_requires_the_special_permission(): void
    {
        $actor = $this->actor([NewPermKeys::REC_CONTRACTS_VIEW, NewPermKeys::REC_CONTRACTS_LATE_FINANCE]);
        $contract = $this->contract('arrival');
        $url = route('RecruitmentContract.lateFinance.show', $contract->id);
        $this->get(route('contracts.details', $contract->id))->assertOk()->assertSee($url, false);
        $actor->revokePermissionTo(NewPermKeys::REC_CONTRACTS_LATE_FINANCE);
        $this->actingAs($actor->fresh(), 'admin');
        $this->get(route('contracts.details', $contract->id))->assertOk()->assertDontSee($url, false);
    }
}
