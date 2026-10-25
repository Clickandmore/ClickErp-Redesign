<?php

namespace Tests\Feature\Finance;

use App\Models\auth\Admin;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\OperationTypeEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\AccountRestructureLog;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Models\FnYear;
use Nami\Finance\Services\AccountRestructureService;
use Nami\Finance\Services\ConstraintService;
use Nami\Finance\Services\YearService;
use Tests\TestCase;

/**
 * إعادة هيكلة الدليل المحاسبي: حذف حساب نظيف فقط، نقل حساب بفروعه مع إعادة الترقيم،
 * ودمج حساب في آخر بنقل كل القيود والارتباطات ثم حذفه — وكلها بصلاحيات حصرية.
 */
class AccountRestructureTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;
    private Account $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        $this->ensureActiveFiscalYear();
        $this->ensureConstraintNumberSequence();

        // المستويان 1 و2 هيكل محمي؛ حسابات الاختبار تبدأ من المستوى 3.
        $root = $this->createAccount('جذر اختبار', null, true);
        $this->section = $this->createAccount('قسم اختبار', $root);
    }

    public function test_merge_moves_journal_lines_and_account_settings_then_deletes_source(): void
    {
        $parent = $this->createAccount('الموردين اختبار');
        $source = $this->createAccount('AFKAN مكرر', $parent);
        $target = $this->createAccount('AFKAN', $parent);
        $other = $this->createAccount('حساب مقابل');
        $constraint = $this->storeConstraint([[$source->id, 'debtor', 100, 0], [$other->id, 'creditor', 0, 100]]);
        DB::table('fn_confirm_settings')->insert([
            ['title' => 'test_supplier_account', 'value' => (string) $source->id, 'created_by_id' => $this->admin->id],
            ['title' => 'test_rate_value', 'value' => (string) $source->id, 'created_by_id' => $this->admin->id],
        ]);

        $result = app(AccountRestructureService::class)->merge($source->id, $target->id, 'حساب مكرر لنفس المورد');

        $this->assertNull(Account::query()->find($source->id));
        $this->assertSame(1, DB::table('fn_constraint_details')->where('constraint_id', $constraint->id)->where('account_id', $target->id)->count());
        $this->assertSame(0, DB::table('fn_constraint_details')->where('account_id', $source->id)->count());
        $this->assertSame((string) $target->id, DB::table('fn_confirm_settings')->where('title', 'test_supplier_account')->value('value'));
        // قيمة رقمية ليست حساباً بالاسم لا تُمس.
        $this->assertSame((string) $source->id, DB::table('fn_confirm_settings')->where('title', 'test_rate_value')->value('value'));
        $this->assertSame(1, $result['moved']['سطور القيود']);

        $log = AccountRestructureLog::query()->where('account_id', $source->id)->sole();
        $this->assertSame(AccountRestructureLog::ACTION_MERGE, $log->action);
        $this->assertSame((int) $target->id, (int) $log->target_account_id);
        $this->assertSame('حساب مكرر لنفس المورد', $log->reason);
    }

    public function test_merge_is_refused_for_parents_the_chart_skeleton_and_closed_periods(): void
    {
        $service = app(AccountRestructureService::class);
        $parent = $this->createAccount('رئيسي');
        $child = $this->createAccount('فرعي', $parent);
        $leaf = $this->createAccount('فرعي آخر');

        $this->assertRefused(fn () => $service->merge($parent->id, $leaf->id, 'x'), 'حسابات فرعية');
        $this->assertRefused(fn () => $service->merge($leaf->id, $parent->id, 'x'), 'الحساب الهدف حساب رئيسي');
        $this->assertRefused(fn () => $service->merge($this->section->id, $leaf->id, 'x'), 'هيكل الدليل');

        $this->storeConstraint([[$child->id, 'debtor', 50, 0], [$leaf->id, 'creditor', 0, 50]]);
        DB::table('fn_fiscal_year_closures')->insert(['title' => 'إقفال اختبار', 'closing_date' => now()->toDateString(), 'created_at' => now()]);
        $this->assertRefused(fn () => $service->merge($child->id, $leaf->id, 'x'), 'فترة مالية مقفلة');
        $this->assertNotNull(Account::query()->find($child->id));
    }

    public function test_move_recodes_the_subtree_and_keeps_its_journal_lines(): void
    {
        $oldParent = $this->createAccount('الوكلاء الخارجيين');
        $newParent = $this->createAccount('الموردين');
        $account = $this->createAccount('مكتب', $oldParent);
        $grandChild = $this->createAccount('فرع المكتب', $account);
        $other = $this->createAccount('مقابل');
        $this->storeConstraint([[$grandChild->id, 'debtor', 10, 0], [$other->id, 'creditor', 0, 10]]);

        app(AccountRestructureService::class)->move($account->id, $newParent->id, 'تصحيح التصنيف');

        $account->refresh();
        $grandChild->refresh();
        $this->assertSame((int) $newParent->id, (int) $account->parent_id);
        $this->assertSame($newParent->level + 1, (int) $account->level);
        $this->assertSame((int) $newParent->code * 100, (int) $account->code);
        $this->assertSame((int) $account->code * 100, (int) $grandChild->code);
        $this->assertSame($account->level + 1, (int) $grandChild->level);
        $this->assertSame(1, DB::table('fn_constraint_details')->where('account_id', $grandChild->id)->count());
        $this->assertSame(AccountRestructureLog::ACTION_MOVE, AccountRestructureLog::query()->where('account_id', $account->id)->sole()->action);
    }

    public function test_move_is_refused_under_its_own_descendant_or_a_posting_account(): void
    {
        $service = app(AccountRestructureService::class);
        $account = $this->createAccount('أب');
        $child = $this->createAccount('ابن', $account);
        $posting = $this->createAccount('حساب عليه قيود');
        $this->storeConstraint([[$posting->id, 'debtor', 5, 0], [$child->id, 'creditor', 0, 5]]);

        $this->assertRefused(fn () => $service->move($account->id, $child->id), 'تحت أحد فروعه');
        $this->assertRefused(fn () => $service->move($account->id, $posting->id), 'عليه قيود');
    }

    public function test_accounts_looked_up_by_a_fixed_code_cannot_be_moved_merged_or_deleted(): void
    {
        $service = app(AccountRestructureService::class);
        $code = collect(AccountRestructureService::protectedCodes())
            ->first(fn ($code) => !Account::query()->where('code', $code)->exists());
        $this->assertNotNull($code, 'No free protected code in the test database.');

        $ancestor = $this->createAccount('أب لحساب ثابت');
        $fixed = $this->createAccount('حساب بكود ثابت', $ancestor);
        $fixed->update(['code' => $code]);
        $other = $this->createAccount('آخر');

        $this->assertTrue(AccountRestructureService::isProtected($fixed->fresh()));
        $this->assertRefused(fn () => $service->delete($fixed->id), $code);
        $this->assertRefused(fn () => $service->merge($fixed->id, $other->id, 'x'), $code);
        // نقل الأب يغيّر كود الفرع الثابت أيضاً.
        $this->assertRefused(fn () => $service->move($ancestor->id, $other->id), 'أحد فروعه');
        $this->assertSame($code, (string) $fixed->fresh()->code);
    }

    public function test_delete_requires_its_own_permission_and_a_clean_account(): void
    {
        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_module', 'guard_name' => 'admin']));
        $clean = $this->createAccount('حساب نظيف');
        $used = $this->createAccount('حساب مستخدم');
        $other = $this->createAccount('مقابل');
        $this->storeConstraint([[$used->id, 'debtor', 5, 0], [$other->id, 'creditor', 0, 5]]);

        // صلاحية النظام المالي العامة لا تكفي للحذف.
        $this->deleteJson(route('finance.accounting-guide-management.destroy', $clean->id))->assertForbidden();
        $this->deleteJson(route('finance.accounts.destroy', $clean->id))->assertForbidden();
        $this->postJson(route('finance.accounting-guide-management.merge', $used->id), [])->assertForbidden();

        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_accounting_guide_management_delete', 'guard_name' => 'admin']));
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->deleteJson(route('finance.accounting-guide-management.destroy', $used->id))->assertStatus(422);
        $this->assertNotNull(Account::query()->find($used->id));

        $this->deleteJson(route('finance.accounting-guide-management.destroy', $clean->id))->assertSuccessful();
        $this->assertNull(Account::query()->find($clean->id));
        $this->assertSame(AccountRestructureLog::ACTION_DELETE, AccountRestructureLog::query()->where('account_id', $clean->id)->sole()->action);
    }

    public function test_merge_endpoint_requires_the_source_code_as_confirmation(): void
    {
        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_accounting_guide_management_merge', 'guard_name' => 'admin']));
        $source = $this->createAccount('مصدر');
        $target = $this->createAccount('هدف');
        $url = route('finance.accounting-guide-management.merge', $source->id);
        $payload = ['target_account_id' => $target->id, 'reason' => 'مكرر'];

        $this->postJson($url, $payload + ['confirm_code' => '0'])->assertStatus(422);
        $this->assertNotNull(Account::query()->find($source->id));

        $this->postJson($url, $payload + ['confirm_code' => (string) $source->code])->assertOk();
        $this->assertNull(Account::query()->find($source->id));
    }

    public function test_undo_merge_restores_the_account_and_only_the_rows_that_were_moved(): void
    {
        $service = app(AccountRestructureService::class);
        $parent = $this->createAccount('الموردين اختبار');
        $source = $this->createAccount('مورد أ', $parent);
        $target = $this->createAccount('مورد ب', $parent);
        $other = $this->createAccount('مقابل');
        $branch = \App\Models\Branch::factory()->create();
        DB::table('fn_account_branch')->insert(['account_id' => $source->id, 'branch_id' => $branch->id, 'created_at' => now(), 'updated_at' => now()]);
        $movedConstraint = $this->storeConstraint([[$source->id, 'debtor', 100, 0], [$other->id, 'creditor', 0, 100]]);
        DB::table('fn_confirm_settings')->insert(['title' => 'test_supplier_account', 'value' => (string) $source->id, 'created_by_id' => $this->admin->id]);
        $sourceSnapshot = Account::query()->find($source->id)->only(['id', 'code', 'name', 'parent_id', 'level', 'status', 'type']);

        $service->merge($source->id, $target->id, 'مكرر');
        // قيد جديد على الهدف بعد الدمج يجب أن يبقى عليه بعد التراجع.
        $laterConstraint = $this->storeConstraint([[$target->id, 'debtor', 40, 0], [$other->id, 'creditor', 0, 40]]);
        $mergeLog = AccountRestructureLog::query()->where('action', AccountRestructureLog::ACTION_MERGE)->where('account_id', $source->id)->sole();

        $result = $service->undoMerge($mergeLog->id, 'دمج خاطئ');

        $restored = Account::query()->find($source->id);
        $this->assertNotNull($restored);
        $this->assertEquals($sourceSnapshot, $restored->only(['id', 'code', 'name', 'parent_id', 'level', 'status', 'type']));
        $this->assertSame(1, DB::table('fn_constraint_details')->where('constraint_id', $movedConstraint->id)->where('account_id', $source->id)->count());
        $this->assertSame(1, DB::table('fn_constraint_details')->where('constraint_id', $laterConstraint->id)->where('account_id', $target->id)->count());
        $this->assertSame((string) $source->id, DB::table('fn_confirm_settings')->where('title', 'test_supplier_account')->value('value'));
        $this->assertTrue(DB::table('fn_account_branch')->where('account_id', $source->id)->where('branch_id', $branch->id)->exists());
        $this->assertSame(1, $result['restored']['سطور القيود']);
        $this->assertNotNull($mergeLog->fresh()->reversed_at);
        $this->assertSame(1, AccountRestructureLog::query()->where('action', AccountRestructureLog::ACTION_MERGE_UNDO)->where('account_id', $source->id)->count());

        $this->assertRefused(fn () => $service->undoMerge($mergeLog->id, 'مرة ثانية'), 'سابقاً');
    }

    public function test_undo_merge_is_refused_when_the_code_was_reused_or_the_target_is_gone(): void
    {
        $service = app(AccountRestructureService::class);
        $source = $this->createAccount('مصدر');
        $target = $this->createAccount('هدف');
        $sourceCode = $source->code;
        $service->merge($source->id, $target->id, 'مكرر');
        $mergeLog = AccountRestructureLog::query()->where('action', AccountRestructureLog::ACTION_MERGE)->where('account_id', $source->id)->sole();

        $reuser = $this->createAccount('أخذ الكود');
        $reuser->update(['code' => $sourceCode]);
        $this->assertRefused(fn () => $service->undoMerge($mergeLog->id, 'x'), 'أصبح مستخدماً');

        $reuser->delete();
        $service->merge($target->id, $this->createAccount('هدف لاحق')->id, 'دمج لاحق');
        $this->assertRefused(fn () => $service->undoMerge($mergeLog->id, 'x'), 'لم يعد موجوداً');
        $this->assertNull(Account::query()->find($source->id));
    }

    public function test_undo_merge_requires_the_merge_permission(): void
    {
        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_module', 'guard_name' => 'admin']));
        $source = $this->createAccount('مصدر');
        app(AccountRestructureService::class)->merge($source->id, $this->createAccount('هدف')->id, 'مكرر');
        $mergeLog = AccountRestructureLog::query()->where('account_id', $source->id)->sole();

        $this->getJson(route('finance.accounting-guide-management.logs'))->assertOk();
        $this->postJson(route('finance.accounting-guide-management.merge-undo', $mergeLog->id), ['reason' => 'x'])->assertForbidden();

        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_accounting_guide_management_merge', 'guard_name' => 'admin']));
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->getJson(route('finance.accounting-guide-management.merge-undo-form', $mergeLog->id))->assertOk();
        $this->postJson(route('finance.accounting-guide-management.merge-undo', $mergeLog->id), ['reason' => 'خطأ'])->assertOk();
        $this->assertNotNull(Account::query()->find($source->id));
    }

    private function assertRefused(callable $action, string $expected): void
    {
        try {
            $action();
            $this->fail('Expected the operation to be refused: ' . $expected);
        } catch (ValidationException $e) {
            $this->assertStringContainsString($expected, implode(' ', $e->validator->errors()->all()));
        }
    }

    private function createAccount(string $name, ?Account $parent = null, bool $root = false): Account
    {
        $parent = $root ? null : ($parent ?? $this->section);
        $code = $parent
            ? max(((int) $parent->code) * 100, (int) Account::query()->where('parent_id', $parent->id)->max('code') + 1)
            : ((int) DB::table('fn_accounts')->whereNull('parent_id')->max('code')) + random_int(100, 999);

        return Account::query()->create([
            'name' => $name . ' ' . uniqid(),
            'code' => $code,
            'parent_id' => $parent?->id,
            'level' => $parent ? $parent->level + 1 : 1,
            'operation_type' => 1,
            'status' => 'debtor',
            'statement_type' => 'income_statement',
            'type' => 1,
            'created_by_id' => $this->admin->id,
        ]);
    }

    private function storeConstraint(array $rows): Constraint
    {
        $service = app(ConstraintService::class);
        $year = app(YearService::class)->checkDateRange(now()->toDateString());
        $data = [
            'code' => $service->lastCode(),
            'date' => now()->toDateString(),
            'statement' => 'قيد اختبار',
            'account_id' => array_column($rows, 0),
            'account_type' => array_column($rows, 1),
            'debtor_value' => array_column($rows, 2),
            'creditor_value' => array_column($rows, 3),
            'statement_account' => array_fill(0, count($rows), 'قيد اختبار'),
            'cost_center_required' => 'off',
            'cost_center_id' => array_fill(0, count($rows), null),
            'value' => array_sum(array_column($rows, 2)),
        ];

        $constraint = $service->storeConstraint($data, $year);
        $service->storeConstraintDetails($data['account_id'], $constraint->id, $data);

        return $constraint;
    }

    private function ensureActiveFiscalYear(): void
    {
        $exists = FnYear::query()->whereDate('from_date', '<=', now())->whereDate('to_date', '>=', now())->where('status', 1)->exists();
        if (!$exists) {
            FnYear::query()->create([
                'title' => 'اختبار ' . now()->year, 'from_date' => now()->startOfYear()->toDateString(),
                'to_date' => now()->endOfYear()->toDateString(), 'status' => 1, 'created_by_id' => $this->admin->id,
            ]);
        }
    }

    private function ensureConstraintNumberSequence(): void
    {
        if (DB::table('fn_constraint_code_sequences')->where('id', 1)->exists()) {
            return;
        }
        DB::table('fn_constraint_code_sequences')->insert([
            'id' => 1, 'last_code' => (int) DB::table('fn_constraints')->max('code'), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
