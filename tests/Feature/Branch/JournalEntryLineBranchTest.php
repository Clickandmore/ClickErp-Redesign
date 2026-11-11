<?php

namespace Tests\Feature\Branch;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\Support\BranchReportFilter;
use App\Support\BranchSharingSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\CashMovementReportService;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * عمود "الفرع" لكل سطر في شاشة القيد اليدوي:
 *   - القيمة الافتراضية فرع رأس القيد.
 *   - المستخدم يختار فقط من الفروع المسموح له بها.
 *   - التقارير تقرأ فرع السطر، فيظهر كل سطر في تقرير فرعه.
 */
class JournalEntryLineBranchTest extends TestCase
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

    /** @test */
    public function lines_default_to_the_header_branch(): void
    {
        $this->actingAsAdmin($this->accountant());
        [$debit, $credit] = [$this->account('مدين افتراضي'), $this->account('دائن افتراضي')];

        $constraint = $this->postEntry([
            'branch_id' => $this->main->id,
            'account_id' => [$debit, $credit],
        ]);

        $this->assertSame([$this->main->id, $this->main->id], $this->lineBranches($constraint));
        $this->assertSame($this->main->id, (int) $constraint->branch_id);
        $this->assertFalse((bool) $constraint->is_multi_branch);
    }

    /** @test */
    public function a_line_can_be_posted_to_another_allowed_branch(): void
    {
        $this->actingAsAdmin($this->accountant());
        [$debit, $credit] = [$this->account('مدين رئيسي'), $this->account('دائن رياض')];

        $constraint = $this->postEntry([
            'branch_id' => $this->main->id,
            'account_id' => [$debit, $credit],
            'detail_branch_id' => ['', $this->riyadh->id],
        ]);

        $this->assertSame([$this->main->id, $this->riyadh->id], $this->lineBranches($constraint));
        $this->assertSame($this->main->id, (int) $constraint->branch_id);
        $this->assertTrue((bool) $constraint->is_multi_branch);
    }

    /** @test */
    public function a_branch_employee_cannot_post_a_line_to_another_branch(): void
    {
        $this->actingAsAdmin($this->financeStaff($this->riyadh));

        $this->postJson(route('finance.add-constraint.store'), $this->payload([
            'account_id' => [$this->account('مدين'), $this->account('دائن')],
            'detail_branch_id' => [$this->riyadh->id, $this->main->id],
        ]))->assertStatus(422)->assertJsonValidationErrors('detail_branch_id');
    }

    /** @test */
    public function each_line_account_is_checked_against_its_own_branch(): void
    {
        BranchSharingSettings::persist(['accounts_per_branch' => true]);
        $this->actingAsAdmin($this->accountant());
        $mainDebit = $this->account('مدين للرئيسي فقط');
        $riyadhCredit = $this->account('دائن للرياض فقط');
        $this->assignAccountToBranch($mainDebit, $this->main->id);
        $this->assignAccountToBranch($riyadhCredit, $this->riyadh->id);

        // حساب الرياض على سطر الرياض: مقبول رغم أن رأس القيد للرئيسي.
        $this->postEntry([
            'branch_id' => $this->main->id,
            'account_id' => [$mainDebit, $riyadhCredit],
            'detail_branch_id' => [$this->main->id, $this->riyadh->id],
        ]);

        // حساب الرياض على سطر يتبع الرئيسي: مرفوض.
        $this->postJson(route('finance.add-constraint.store'), $this->payload([
            'branch_id' => $this->main->id,
            'account_id' => [$mainDebit, $riyadhCredit],
        ]))->assertStatus(422)->assertJsonValidationErrors('account_id');
    }

    /** @test */
    public function reports_split_a_multi_branch_entry_by_line_branch(): void
    {
        $this->actingAsAdmin($this->accountant());
        [$debit, $credit] = [$this->account('مدين تقرير'), $this->account('دائن تقرير')];
        $constraint = $this->postEntry([
            'branch_id' => $this->main->id,
            'account_id' => [$debit, $credit],
            'detail_branch_id' => [$this->main->id, $this->riyadh->id],
        ]);

        // موظف الرياض: تقاريره (كلها مبنية على BranchReportFilter) ترى سطر الرياض فقط.
        $this->actingAsAdmin($this->financeStaff($this->riyadh));
        $riyadhLines = BranchReportFilter::apply(DB::table('fn_constraint_details'), 'fn_constraint_details')
            ->where('constraint_id', $constraint->id)
            ->whereNull('deleted_at')
            ->pluck('account_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->assertSame([$credit], $riyadhLines);

        // نتحقق من استعلام تقرير فعلي، لا من الفلتر المساعد وحده.
        $cashMovementLines = app(CashMovementReportService::class)->query()
            ->where('d.constraint_id', $constraint->id)
            ->pluck('d.account_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $this->assertSame([$credit], $cashMovementLines);

        // والقيد نفسه ظاهر له، وصفحة القيد تعرض السطرين حتى لا يبدو غير متوازن.
        $this->assertNotNull(Constraint::query()->find($constraint->id));
        $this->get(route('finance.add-constraint.show', $constraint->id))
            ->assertOk()
            ->assertSee('مدين تقرير')
            ->assertSee('دائن تقرير');
    }

    /** @test */
    public function editing_keeps_line_branches_and_accepts_a_change(): void
    {
        $this->actingAsAdmin($this->accountant());
        [$debit, $credit] = [$this->account('مدين تعديل'), $this->account('دائن تعديل')];
        $constraint = $this->postEntry([
            'branch_id' => $this->main->id,
            'account_id' => [$debit, $credit],
            'detail_branch_id' => [$this->main->id, $this->riyadh->id],
        ]);
        $detailIds = $constraint->constraintDetails()->withoutBranchScope()->orderBy('id')->pluck('id')->all();

        // بلا detail_branch_id: كل سطر يبقى على فرعه.
        $this->putJson(route('finance.add-constraint.update', $constraint->id), [
            'date' => now()->toDateString(),
            'account_id' => [$debit, $credit],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [50, 0],
            'creditor_value' => [0, 50],
            'old_id' => $detailIds,
        ])->assertOk();
        $this->assertSame([$this->main->id, $this->riyadh->id], $this->lineBranches($constraint));

        // نقل السطر الدائن إلى الرئيسي.
        $this->putJson(route('finance.add-constraint.update', $constraint->id), [
            'date' => now()->toDateString(),
            'account_id' => [$debit, $credit],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [50, 0],
            'creditor_value' => [0, 50],
            'old_id' => $detailIds,
            'detail_branch_id' => [$this->main->id, $this->main->id],
        ])->assertOk();
        $this->assertSame([$this->main->id, $this->main->id], $this->lineBranches($constraint));
    }

    /** @test */
    public function editing_cannot_move_a_line_to_an_existing_but_unauthorized_branch(): void
    {
        $this->actingAsAdmin($this->accountant());
        [$debit, $credit] = [$this->account('مدين صلاحية تعديل'), $this->account('دائن صلاحية تعديل')];
        $constraint = $this->postEntry([
            'branch_id' => $this->main->id,
            'account_id' => [$debit, $credit],
            'detail_branch_id' => [$this->main->id, $this->riyadh->id],
        ]);
        $detailIds = $constraint->constraintDetails()->withoutBranchScope()->orderBy('id')->pluck('id')->all();

        $this->actingAsAdmin($this->financeStaff($this->riyadh));

        $this->putJson(route('finance.add-constraint.update', $constraint->id), [
            'date' => now()->toDateString(),
            'account_id' => [$debit, $credit],
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [50, 0],
            'creditor_value' => [0, 50],
            'old_id' => $detailIds,
            // الرئيسي موجود بالفعل في القيد، لكنه ليس ضمن فروع هذا الموظف.
            'detail_branch_id' => [$this->main->id, $this->main->id],
        ])->assertStatus(422)->assertJsonValidationErrors('detail_branch_id');

        $this->assertSame([$this->main->id, $this->riyadh->id], $this->lineBranches($constraint));
    }

    /** @test */
    public function the_entry_screens_show_a_branch_column_defaulting_to_the_header_branch(): void
    {
        $this->actingAsAdmin($this->accountant());
        $this->ensureActiveYear();

        $this->get(route('finance.add-constraint.create', ['branch_id' => $this->riyadh->id]))
            ->assertOk()
            ->assertSee('name="detail_branch_id[]"', false)
            ->assertSee('<option value="' . $this->riyadh->id . '" selected', false);

        [$debit, $credit] = [$this->account('مدين شاشة'), $this->account('دائن شاشة')];
        $constraint = $this->postEntry([
            'branch_id' => $this->main->id,
            'account_id' => [$debit, $credit],
            'detail_branch_id' => [$this->main->id, $this->riyadh->id],
        ]);

        $this->get(route('finance.add-constraint.edit', $constraint->id))
            ->assertOk()
            ->assertSee('name="detail_branch_id[]"', false)
            ->assertSee('<option value="' . $this->riyadh->id . '" selected', false)
            ->assertSee('<option value="' . $this->main->id . '" selected', false);
    }

    /** @test */
    public function line_branches_are_ignored_when_the_branches_module_is_off(): void
    {
        $this->enableBranchesModule(false);
        $this->actingAsAdmin($this->accountant());
        [$debit, $credit] = [$this->account('مدين بلا فروع'), $this->account('دائن بلا فروع')];

        $constraint = $this->postEntry([
            'account_id' => [$debit, $credit],
            'detail_branch_id' => [$this->riyadh->id, $this->main->id],
        ]);

        $this->assertFalse((bool) $constraint->is_multi_branch);
        $this->assertNotContains($this->riyadh->id, $this->lineBranches($constraint));
    }

    private function postEntry(array $overrides): Constraint
    {
        $id = $this->postJson(route('finance.add-constraint.store'), $this->payload($overrides))
            ->assertOk()
            ->json('data.id');

        return Constraint::query()->withoutBranchScope()->findOrFail($id);
    }

    private function payload(array $overrides): array
    {
        $this->ensureActiveYear();

        return array_merge([
            'code' => 999100,
            'value' => 100,
            'date' => now()->toDateString(),
            'account_type' => ['debtor', 'creditor'],
            'debtor_value' => [100, 0],
            'creditor_value' => [0, 100],
        ], $overrides);
    }

    /** @return array<int, int|null> */
    private function lineBranches(Constraint $constraint): array
    {
        return DB::table('fn_constraint_details')
            ->where('constraint_id', $constraint->id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->pluck('branch_id')
            ->map(fn ($id) => $id === null ? null : (int) $id)
            ->all();
    }

    private function ensureActiveYear(): void
    {
        if (! DB::table('fn_constraint_code_sequences')->where('id', 1)->exists()) {
            DB::table('fn_constraint_code_sequences')->insert([
                'id' => 1,
                'last_code' => (int) DB::table('fn_constraints')->max('code'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (DB::table('fn_years')->where('status', 1)
            ->where('from_date', '<=', now()->toDateString())
            ->where('to_date', '>=', now()->toDateString())
            ->exists()) {
            return;
        }

        DB::table('fn_years')->update(['status' => 0]);
        DB::table('fn_years')->insert([
            'title' => 'سنة اختبار فرع السطر',
            'from_date' => now()->startOfYear()->toDateString(),
            'to_date' => now()->endOfYear()->toDateString(),
            'status' => 1,
            'created_by_id' => auth('admin')->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function accountant(): Admin
    {
        $admin = Admin::factory()->global()->create();
        $admin->assignRole('Administrator');

        return $admin;
    }

    private function financeStaff(Branch $branch): Admin
    {
        // موظف مقيّد بفرعه: الدور الإداري المُجهّز يمنح branches_view_all فننزعها.
        $admin = $this->branchAdmin($branch);
        $admin->assignRole('Administrator');

        return $this->lockToOwnBranch($admin);
    }

    private function account(string $name): int
    {
        return (int) DB::table('fn_accounts')->insertGetId([
            'name' => $name,
            'code' => (string) random_int(81000000, 89999999),
            'level' => 1,
            'status' => 'debtor',
            'type' => 1,
            'operation_type' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
}
