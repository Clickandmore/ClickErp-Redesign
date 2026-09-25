<?php

namespace Tests\Feature\Branch;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Support\BranchSharingSettings;
use App\Support\BranchQueryContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\ConstraintService;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class FinanceAccountBranchSelectionTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    private Branch $main;
    private Branch $riyadh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
        BranchSharingSettings::flush();
        $this->main = $this->makeBranch(['is_main' => true]);
        $this->riyadh = $this->makeBranch();
    }

    private function account(string $name, ?int $branchId = null, ?int $parentId = null): int
    {
        return (int) DB::table('fn_accounts')->insertGetId([
            'name' => $name,
            'code' => (string) random_int(81000000, 89999999),
            'level' => $parentId ? 2 : 1,
            'status' => 'debtor',
            'type' => 1,
            'operation_type' => 1,
            'parent_id' => $parentId,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function costCenter(string $title, ?int $branchId = null): int
    {
        return (int) DB::table('fn_cost_centers')->insertGetId([
            'title' => $title,
            'code' => (string) random_int(910000, 999999),
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function financeStaff(Branch $branch): Admin
    {
        $admin = $this->branchAdmin($branch);
        $admin->assignRole('المالية');

        return $admin;
    }

    private function assignAccountToBranch(int $accountId, int $branchId): void
    {
        DB::table('fn_account_branch')->insert([
            'account_id' => $accountId,
            'branch_id' => $branchId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function the_account_tree_hides_accounts_owned_by_other_branches(): void
    {
        $shared = $this->account('أصل مشترك');
        $mainAccount = $this->account('حساب الفرع الرئيسي', $this->main->id, $shared);
        $riyadhAccount = $this->account('حساب فرع الرياض', $this->riyadh->id, $shared);

        $this->actingAsAdmin($this->financeStaff($this->riyadh));
        BranchQueryContext::flush();

        $treeIds = app(AccountService::class)->getAccountTree()['accounts']
            ->flatMap(function ($root) {
                return collect([$root->id])->merge($root->all_children_ids);
            });

        $this->assertContains($shared, $treeIds);
        $this->assertContains($riyadhAccount, $treeIds);
        $this->assertNotContains($mainAccount, $treeIds);
    }

    /** @test */
    public function the_riyadh_account_tree_page_does_not_render_main_branch_accounts(): void
    {
        $mainAccount = $this->account('حساب رئيسي سري للاختبار', $this->main->id);
        $riyadhAccount = $this->account('حساب رياض ظاهر للاختبار', $this->riyadh->id);
        $this->actingAsAdmin($this->financeStaff($this->riyadh));
        BranchQueryContext::flush();

        $this->get(route('finance.accounts.index'))
            ->assertOk()
            ->assertSee('حساب رياض ظاهر للاختبار')
            ->assertDontSee('حساب رئيسي سري للاختبار');

        unset($mainAccount, $riyadhAccount);
    }

    /** @test */
    public function account_lists_are_shared_for_entries_until_account_branch_assignments_are_enabled(): void
    {
        $shared = $this->account('حساب مشترك');
        $mainAccount = $this->account('نهائي رئيسي', $this->main->id);
        $riyadhAccount = $this->account('نهائي الرياض', $this->riyadh->id);

        $ids = app(ConstraintService::class)->getAccounts(null, $this->riyadh->id)->pluck('id');

        $this->assertContains($shared, $ids);
        $this->assertContains($riyadhAccount, $ids);
        $this->assertContains($mainAccount, $ids);
    }

    /** @test */
    public function a_restricted_accountant_cannot_post_an_entry_to_another_branch(): void
    {
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $ownDebit = $this->account('مدين الرياض', $this->riyadh->id);
        $foreignCredit = $this->account('دائن الرئيسي', $this->main->id);
        $this->assignAccountToBranch($ownDebit, $this->riyadh->id);
        $this->assignAccountToBranch($foreignCredit, $this->main->id);
        $this->actingAsAdmin($this->financeStaff($this->riyadh));

        $this->postJson(route('finance.add-constraint.store'), [
            'branch_id' => $this->main->id,
            'code' => 999001,
            'value' => 100,
            'date' => now()->toDateString(),
            'account_id' => [$ownDebit, $foreignCredit],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [100, 0],
            'creditor_value' => [0, 100],
        ])->assertStatus(422)->assertJsonValidationErrors('account_id');
    }

    /** @test */
    public function a_global_accountant_must_choose_a_branch_before_posting_an_entry(): void
    {
        $debit = $this->account('مدين الإدارة');
        $credit = $this->account('دائن الإدارة');
        $admin = Admin::factory()->global()->create();
        $admin->assignRole('Administrator');
        $this->actingAsAdmin($admin);

        $this->postJson(route('finance.add-constraint.store'), [
            'code' => 999002,
            'value' => 100,
            'date' => now()->toDateString(),
            'account_id' => [$debit, $credit],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [100, 0],
            'creditor_value' => [0, 100],
        ])->assertStatus(422)->assertJsonValidationErrors('branch_id');
    }

    /** @test */
    public function the_entry_form_for_management_loads_only_the_selected_branch_accounts(): void
    {
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $sharedAccount = $this->account('حساب مشترك بنموذج القيد');
        $mainAccount = $this->account('حساب رئيسي بنموذج القيد');
        $riyadhAccount = $this->account('حساب رياض بنموذج القيد');
        $this->assignAccountToBranch($mainAccount, $this->main->id);
        $this->assignAccountToBranch($riyadhAccount, $this->riyadh->id);
        $admin = Admin::factory()->global()->create();
        $admin->assignRole('Administrator');
        $this->actingAsAdmin($admin);
        DB::table('fn_years')->update(['status' => 0]);
        DB::table('fn_years')->insert([
            'title' => 'سنة اختبار اختيار الفرع',
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'status' => 1,
            'created_by_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('finance.add-constraint.create', ['branch_id' => $this->riyadh->id]))
            ->assertOk()
            ->assertSee('حساب مشترك بنموذج القيد')
            ->assertSee('حساب رياض بنموذج القيد')
            ->assertDontSee('حساب رئيسي بنموذج القيد')
            ->assertSee('action="' . route('finance.add-constraint.store', ['branch_id' => $this->riyadh->id]) . '"', false)
            ->assertSee('name="branch_id" value="' . $this->riyadh->id . '"', false)
            ->assertSee('constraint_branch_id', false)
            ->assertSee('branchOptionsRoute', false)
            ->assertDontSee('window.location.assign', false);

        unset($sharedAccount);
    }

    /** @test */
    public function branch_options_are_loaded_without_reloading_the_create_page(): void
    {
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $mainAccount = $this->account('حساب خيارات رئيسي');
        $riyadhAccount = $this->account('حساب خيارات الرياض');
        $this->assignAccountToBranch($mainAccount, $this->main->id);
        $this->assignAccountToBranch($riyadhAccount, $this->riyadh->id);
        $admin = Admin::factory()->global()->create();
        $admin->assignRole('Administrator');
        $this->actingAsAdmin($admin);

        $this->getJson(route('finance.add-constraint.branch-options', ['branch_id' => $this->riyadh->id]))
            ->assertOk()
            ->assertJsonPath('selected_branch_id', $this->riyadh->id)
            ->assertJsonFragment([
                'id' => $riyadhAccount,
                'name' => 'حساب خيارات الرياض',
            ])
            ->assertJsonMissing([
                'name' => 'حساب خيارات رئيسي',
            ]);
    }

    /** @test */
    public function accounts_per_branch_uses_the_pivot_table_without_hiding_shared_accounts(): void
    {
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $shared = $this->account('حساب مشترك بلا تخصيص');
        $mainOnly = $this->account('حساب مخصص للرئيسي فقط');
        $riyadhOnly = $this->account('حساب مخصص للرياض فقط');
        $this->assignAccountToBranch($mainOnly, $this->main->id);
        $this->assignAccountToBranch($riyadhOnly, $this->riyadh->id);

        $ids = app(ConstraintService::class)->getAccounts(null, $this->riyadh->id)->pluck('id');

        $this->assertContains($shared, $ids);
        $this->assertContains($riyadhOnly, $ids);
        $this->assertNotContains($mainOnly, $ids);
    }

    /** @test */
    public function accounts_per_branch_rejects_accounts_assigned_to_another_branch(): void
    {
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $ownDebit = $this->account('مدين رياض بتخصيص');
        $foreignCredit = $this->account('دائن رئيسي بتخصيص');
        $this->assignAccountToBranch($ownDebit, $this->riyadh->id);
        $this->assignAccountToBranch($foreignCredit, $this->main->id);
        $this->actingAsAdmin($this->financeStaff($this->riyadh));

        $this->postJson(route('finance.add-constraint.store'), [
            'code' => 999004,
            'value' => 100,
            'date' => now()->toDateString(),
            'account_id' => [$ownDebit, $foreignCredit],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [100, 0],
            'creditor_value' => [0, 100],
        ])->assertStatus(422)->assertJsonValidationErrors('account_id');
    }

    /** @test */
    public function accounts_per_branch_rejects_foreign_accounts_when_updating_an_existing_entry(): void
    {
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $ownDebit = $this->account('مدين رياض لتعديل قيد');
        $ownCredit = $this->account('دائن رياض لتعديل قيد');
        $foreignDebit = $this->account('مدين رئيسي ممنوع في تعديل قيد');
        $this->assignAccountToBranch($ownDebit, $this->riyadh->id);
        $this->assignAccountToBranch($ownCredit, $this->riyadh->id);
        $this->assignAccountToBranch($foreignDebit, $this->main->id);
        $riyadhAdmin = $this->financeStaff($this->riyadh);
        $this->actingAsAdmin($riyadhAdmin);

        DB::table('fn_years')->update(['status' => 0]);
        $yearId = DB::table('fn_years')->insertGetId([
            'title' => 'سنة اختبار منع تعديل قيد لفرع آخر',
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'status' => 1,
            'created_by_id' => $riyadhAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $constraintId = DB::table('fn_constraints')->insertGetId([
            'code' => 999006,
            'value' => 100,
            'date' => now()->toDateString(),
            'statement' => 'قيد رياض سليم قبل التعديل',
            'type' => 1,
            'year_id' => $yearId,
            'branch_id' => $this->riyadh->id,
            'created_by_id' => $riyadhAdmin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $detailIds = [];
        foreach ([
            [$ownDebit, 'debtor', 100, 0],
            [$ownCredit, 'creditor', 0, 100],
        ] as [$accountId, $type, $debtor, $creditor]) {
            $detailIds[] = DB::table('fn_constraint_details')->insertGetId([
                'constraint_id' => $constraintId,
                'account_id' => $accountId,
                'date' => now()->toDateString(),
                'type' => $type,
                'debtor_value' => $debtor,
                'creditor_value' => $creditor,
                'statement' => 'سطر قبل التعديل',
                'year_id' => $yearId,
                'branch_id' => $this->riyadh->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->putJson(route('finance.add-constraint.update', $constraintId), [
            'value' => 77,
            'date' => now()->toDateString(),
            'account_id' => [$foreignDebit, $ownCredit],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [77, 0],
            'creditor_value' => [0, 77],
            'old_id' => $detailIds,
        ])->assertStatus(422)->assertJsonValidationErrors('account_id');

        $this->assertDatabaseMissing('fn_constraint_details', [
            'constraint_id' => $constraintId,
            'account_id' => $foreignDebit,
            'branch_id' => $this->riyadh->id,
        ]);
    }

    /** @test */
    public function account_service_can_assign_and_unassign_accounts_to_multiple_branches(): void
    {
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $admin = Admin::factory()->global()->create();
        $this->actingAsAdmin($admin);

        $account = app(AccountService::class)->storeAccount([
            'name' => 'حساب متعدد الفروع للاختبار ' . random_int(1000, 9999),
            'parent_id' => 'without',
            'status' => 'debtor',
            'type' => 1,
            'operation_type' => 1,
            'created_by_id' => $admin->id,
            'account_branch_scope_present' => 1,
            'branch_ids' => [$this->main->id, $this->riyadh->id],
        ]);

        $this->assertDatabaseHas('fn_account_branch', [
            'account_id' => $account->id,
            'branch_id' => $this->main->id,
        ]);
        $this->assertDatabaseHas('fn_account_branch', [
            'account_id' => $account->id,
            'branch_id' => $this->riyadh->id,
        ]);

        app(AccountService::class)->updateAccount($account->id, [
            'name' => $account->name,
            'parent_id' => 'without',
            'status' => 'debtor',
            'type' => 1,
            'operation_type' => 1,
            'account_branch_scope_present' => 1,
            'branch_ids' => [],
        ], 0, null);

        $this->assertDatabaseMissing('fn_account_branch', [
            'account_id' => $account->id,
            'branch_id' => $this->main->id,
        ]);
        $this->assertDatabaseMissing('fn_account_branch', [
            'account_id' => $account->id,
            'branch_id' => $this->riyadh->id,
        ]);
    }

    /** @test */
    public function an_account_statement_for_parent_account_includes_child_account_movements(): void
    {
        $parent = $this->account('أب كشف الحساب');
        $child = $this->account('ابن كشف الحساب', null, $parent);
        $counter = $this->account('حساب مقابل لكشف الأب');

        DB::table('fn_years')->update(['status' => 0]);
        $yearId = DB::table('fn_years')->insertGetId([
            'title' => 'سنة كشف حساب الأب',
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'status' => 1,
            'created_by_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $constraintId = DB::table('fn_constraints')->insertGetId([
            'code' => 999005,
            'value' => 100,
            'date' => now()->toDateString(),
            'statement' => 'قيد ابن كشف الحساب',
            'type' => 1,
            'year_id' => $yearId,
            'branch_id' => $this->riyadh->id,
            'created_by_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('fn_constraint_details')->insert([
            [
                'constraint_id' => $constraintId,
                'account_id' => $child,
                'date' => now()->toDateString(),
                'type' => 'debtor',
                'debtor_value' => 100,
                'creditor_value' => 0,
                'statement' => 'حركة الابن',
                'year_id' => $yearId,
                'branch_id' => $this->riyadh->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'constraint_id' => $constraintId,
                'account_id' => $counter,
                'date' => now()->toDateString(),
                'type' => 'creditor',
                'debtor_value' => 0,
                'creditor_value' => 100,
                'statement' => 'حركة مقابلة',
                'year_id' => $yearId,
                'branch_id' => $this->riyadh->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAsAdmin($this->financeStaff($this->riyadh));
        BranchQueryContext::flush();

        $this->get(route('finance.account-statement.index', [
            'account_id' => $parent,
            'to_date' => now()->toDateString(),
            'type' => 'both',
            'other_account' => 'yes',
        ]))
            ->assertOk()
            ->assertSee('حركة الابن')
            ->assertSee('حساب مقابل لكشف الأب');
    }

    /** @test */
    public function an_account_statement_only_shows_accounts_on_the_opposite_side_of_a_compound_entry(): void
    {
        $selectedDebit = $this->account('الحساب المحدد في القيد المركب');
        $unrelatedDebit = $this->account('مدين آخر غير مقابل للحساب المحدد');
        $counterCredit = $this->account('الدائن المقابل للحساب المحدد');
        $admin = $this->branchAdmin($this->riyadh);

        DB::table('fn_years')->update(['status' => 0]);
        $yearId = DB::table('fn_years')->insertGetId([
            'title' => 'سنة اختبار الحساب المقابل',
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'status' => 1,
            'created_by_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $constraintId = DB::table('fn_constraints')->insertGetId([
            'code' => 999007,
            'value' => 150,
            'date' => now()->toDateString(),
            'statement' => 'قيد مركب لاختبار الحساب المقابل',
            'type' => 1,
            'year_id' => $yearId,
            'branch_id' => $this->riyadh->id,
            'created_by_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $selectedDetailId = null;
        foreach ([
            [$selectedDebit, 'debtor', 100, 0, 'حركة الحساب المحدد'],
            [$unrelatedDebit, 'debtor', 50, 0, 'حركة مدينة مستقلة'],
            [$counterCredit, 'creditor', 0, 150, 'الحركة الدائنة المقابلة'],
        ] as [$accountId, $type, $debtor, $creditor, $statement]) {
            $detailId = DB::table('fn_constraint_details')->insertGetId([
                'constraint_id' => $constraintId,
                'account_id' => $accountId,
                'date' => now()->toDateString(),
                'type' => $type,
                'debtor_value' => $debtor,
                'creditor_value' => $creditor,
                'statement' => $statement,
                'year_id' => $yearId,
                'branch_id' => $this->riyadh->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($accountId === $selectedDebit) {
                $selectedDetailId = (int) $detailId;
            }
        }

        $this->actingAsAdmin($admin);
        BranchQueryContext::flush();

        $filters = [
            'account_id' => $selectedDebit,
            'from_date' => '',
            'to_date' => now()->toDateString(),
            'type' => 'both',
            'other_account' => 'yes',
        ];
        $service = app(ConstraintService::class);
        $statementRows = $service->filterAccount($filters);
        $counterAccounts = collect(data_get(
            $service->filterCounterAccountsForStatement($statementRows),
            'details.' . $selectedDetailId . '.accounts',
            []
        ));

        $this->assertSame(['الدائن المقابل للحساب المحدد'], $counterAccounts->pluck('name')->all());
        $this->assertSame(150.0, (float) $counterAccounts->first()->amount);
    }

    /** @test */
    public function the_selected_branch_in_the_create_url_is_enough_when_posting_the_entry(): void
    {
        $debit = $this->account('مدين رياض من الرابط', $this->riyadh->id);
        $credit = $this->account('دائن رياض من الرابط', $this->riyadh->id);
        $admin = Admin::factory()->global()->create();
        $admin->assignRole('Administrator');
        $this->actingAsAdmin($admin);
        DB::table('fn_years')->update(['status' => 0]);
        DB::table('fn_years')->insert([
            'title' => 'سنة اختبار حفظ فرع من الرابط',
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'status' => 1,
            'created_by_id' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson(route('finance.add-constraint.store', ['branch_id' => $this->riyadh->id]), [
            'code' => 999003,
            'value' => 100,
            'date' => now()->toDateString(),
            'account_id' => [$debit, $credit],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [100, 0],
            'creditor_value' => [0, 100],
        ]);

        $response->assertOk();
        $constraintId = $response->json('data.id');
        $this->assertDatabaseHas('fn_constraints', [
            'id' => $constraintId,
            'branch_id' => $this->riyadh->id,
        ]);
    }

    /** @test */
    public function shared_cost_center_setting_exposes_unassigned_cost_centers_to_branch_entries(): void
    {
        BranchSharingSettings::persist(['shared_cost_center_root' => true]);
        $shared = $this->costCenter('مركز تكلفة مشترك للرياض');
        $mainOnly = $this->costCenter('مركز تكلفة رئيسي فقط', $this->main->id);
        $riyadhOnly = $this->costCenter('مركز تكلفة الرياض', $this->riyadh->id);

        $ids = app(ConstraintService::class)->getCostCenters($this->riyadh->id)->pluck('id');

        $this->assertContains($shared, $ids);
        $this->assertContains($riyadhOnly, $ids);
        $this->assertNotContains($mainOnly, $ids);
    }

    /** @test */
    public function disabling_shared_cost_center_setting_hides_unassigned_cost_centers_from_new_branches(): void
    {
        BranchSharingSettings::persist(['shared_cost_center_root' => false]);
        $shared = $this->costCenter('مركز تكلفة غير مخصص مخفي');
        $riyadhOnly = $this->costCenter('مركز تكلفة الرياض فقط', $this->riyadh->id);

        $ids = app(ConstraintService::class)->getCostCenters($this->riyadh->id)->pluck('id');

        $this->assertNotContains($shared, $ids);
        $this->assertContains($riyadhOnly, $ids);
    }

    /** @test */
    public function a_non_main_branch_tree_keeps_parent_paths_without_showing_unassigned_leaf_accounts(): void
    {
        $parent = $this->account('أب مشترك لشجرة الرياض');
        $legacyLeaf = $this->account('حساب قديم نهائي لا يظهر للرياض', null, $parent);
        $riyadhLeaf = $this->account('حساب رياض تحت أب مشترك', $this->riyadh->id, $parent);

        $this->actingAsAdmin($this->financeStaff($this->riyadh));
        BranchQueryContext::flush();

        $treeIds = app(AccountService::class)->getAccountTree()['accounts']
            ->flatMap(function ($root) {
                return collect([$root->id])->merge($root->all_children_ids);
            });

        $this->assertContains($parent, $treeIds);
        $this->assertContains($riyadhLeaf, $treeIds);
        $this->assertNotContains($legacyLeaf, $treeIds);
    }
}
