<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\auth\Admin;
use App\Support\BranchQueryContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\AccountStatementService;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\TrailBalanceService;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * التقارير المالية تُبنى على SQL خام لا يراه الـ global scope، فهي الطبقة التي
 * يظهر فيها اختلاط مالية الفروع فعلياً — لا شاشة القيود.
 *
 * كل حالة هنا تُقاس بمبلغ محدّد وليس بوجود/غياب صف، لأن الخطأ المُحتمل في هذه
 * الاستعلامات ليس "سطر ظاهر خطأً" بل "مجموع خاطئ بصمت". والأهم أن كل اختبار
 * له توأم مع تعطيل الموديول يثبت أن الرقم لم يتغيّر عمّا كان قبل الفروع.
 *
 * توزيع البيانات في كل اختبار:
 *   الفرع الرئيسي  → 100
 *   الفرع الآخر    → 250
 *   بلا فرع (قديم) →  40
 *   المجموع        → 390
 */
class FinanceReportBranchScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    private Branch $main;
    private Branch $other;
    private int $accountId;
    private int $authorId;
    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        BranchQueryContext::reset();

        $this->date = now()->toDateString();
        $this->main = $this->makeBranch(['is_main' => true]);
        $this->other = $this->makeBranch();
        $this->accountId = $this->makeRootAccount();

        // كشف الحساب يربط القيد بمنشئه بـ INNER JOIN، فقيد بلا منشئ لا يظهر فيه أصلاً.
        $this->authorId = Admin::factory()->global()->create()->id;

        $this->postEntry($this->main->id, 100);
        $this->postEntry($this->other->id, 250);
        $this->postEntry(null, 40);
    }

    protected function tearDown(): void
    {
        BranchQueryContext::reset();
        parent::tearDown();
    }

    /* ------------------------------------------------------------- fixtures */

    /**
     * القيود تُبنى بـ DB::table عمداً: الهدف قياس ما يقرأه التقرير، لا اختبار
     * hooks الموديل (إقفال السنة المالية، ترقيم القيد، الاعتماد).
     */
    private function makeRootAccount(): int
    {
        return (int) DB::table('fn_accounts')->insertGetId([
            'name' => 'حساب اختبار الفروع',
            'code' => (string) random_int(700000, 799999),
            'level' => 1,
            'status' => 'debtor',
            'type' => 1,
            'operation_type' => 1,
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function postEntry(?int $branchId, float $amount): void
    {
        $constraintId = (int) DB::table('fn_constraints')->insertGetId([
            'branch_id' => $branchId,
            'type' => 1,
            'value' => $amount,
            'date' => $this->date,
            'statement' => 'قيد اختبار',
            'code' => random_int(800000, 899999),
            'created_by_id' => $this->authorId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fn_constraint_details')->insert([
            'branch_id' => $branchId,
            'constraint_id' => $constraintId,
            'account_id' => $this->accountId,
            'date' => $this->date,
            'type' => 1,
            'debtor_value' => $amount,
            'creditor_value' => 0,
            'statement' => 'سطر اختبار',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function staffOf(?Branch $branch, bool $lock = true): Admin
    {
        $factory = Admin::factory();
        $factory = $branch ? $factory->forBranch($branch) : $factory->global();

        $admin = $factory->create();
        $admin->assignRole('Administrator');

        return $branch && $lock ? $this->lockToOwnBranch($admin) : $admin;
    }

    private function actingAsBranchStaff(Admin $admin): void
    {
        $this->actingAs($admin, 'admin');
        BranchQueryContext::flush();
    }

    /** مجموع الجانب المدين لحساب الاختبار كما يراه ميزان المراجعة الآن. */
    private function trialBalanceDebtor(): float
    {
        $rows = app(TrailBalanceService::class)
            ->getAccountSummaryForTrailBalance(1, $this->date, $this->date, false);

        return (float) ($rows->firstWhere('id', $this->accountId)->total_debtor ?? 0);
    }

    /** نفس المجموع من كشف الحساب — مسار استعلام مختلف تماماً. */
    private function accountStatementDebtor(): float
    {
        return (float) app(AccountStatementService::class)
            ->filterDoubleAccount(
                ['type' => 'both', 'from_date' => '', 'to_date' => ''],
                [$this->accountId]
            )
            ->sum('debtor_value');
    }

    /** الرصيد الذي تعرضه صفحة /finance/accounts في شجرة الحسابات. */
    private function accountTreeDebtor(): float
    {
        $constraints = app(AccountService::class)->getAccountTree()['constraintDetails'];

        return (float) ($constraints[$this->accountId]['debtor_value'] ?? 0);
    }

    /* ------------------------------------- الموديول معطّل: لا شيء يتغيّر أبداً */

    /** @test */
    public function trial_balance_is_untouched_while_the_branches_module_is_off(): void
    {
        $this->enableBranchesModule(false);
        $this->actingAsBranchStaff($this->staffOf($this->other));

        $this->assertSame(390.0, $this->trialBalanceDebtor(),
            'مع تعطيل الموديول يجب أن يبقى ميزان المراجعة كما كان قبل الفروع.');
    }

    /** @test */
    public function account_statement_is_untouched_while_the_branches_module_is_off(): void
    {
        $this->enableBranchesModule(false);
        $this->actingAsBranchStaff($this->staffOf($this->other));

        $this->assertSame(390.0, $this->accountStatementDebtor());
        $this->assertSame(390.0, $this->accountTreeDebtor());
    }

    /* ------------------------------------------- الموديول مفعّل: العزل يعمل */

    /** @test */
    public function branch_staff_only_see_their_own_branch_totals(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($this->other));

        $this->assertSame(250.0, $this->trialBalanceDebtor(),
            'ميزان مراجعة الفرع يجب ألا يحتوي ريالاً واحداً من فرع آخر.');
        $this->assertSame(250.0, $this->accountStatementDebtor());
        $this->assertSame(250.0, $this->accountTreeDebtor(),
            'رصيد شجرة الحسابات يجب أن يطابق عزل كشف الحساب لنفس الموظف.');
    }

    /** @test */
    public function main_branch_staff_also_get_the_legacy_entries(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($this->main));

        // 100 للفرع الرئيسي + 40 قيداً قديماً بلا فرع.
        $this->assertSame(140.0, $this->trialBalanceDebtor());
        $this->assertSame(140.0, $this->accountStatementDebtor());
        $this->assertSame(140.0, $this->accountTreeDebtor());
    }

    /** @test */
    public function head_office_sees_the_company_wide_total(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf(null));

        $this->assertSame(390.0, $this->trialBalanceDebtor(),
            'الإدارة العامة يجب أن ترى نفس رقم ما قبل الفروع بالضبط.');
        $this->assertSame(390.0, $this->accountStatementDebtor());
        $this->assertSame(390.0, $this->accountTreeDebtor());
    }

    /** @test */
    public function admins_holding_view_all_branches_see_the_company_wide_total(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($this->other, lock: false));

        $this->assertSame(390.0, $this->trialBalanceDebtor());
    }

    /* ---------------------------------------------- المخرج الصريح والـ console */

    /** @test */
    public function the_opening_balance_column_is_scoped_too(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($this->other));

        // الرصيد الافتتاحي فرع ثانٍ من الاستعلام له bindings منفصلة؛
        // انزياحها هو الخطر الأول في هذا الملف.
        $rows = app(TrailBalanceService::class)
            ->getAccountSummaryForTrailBalance(1, $this->date, $this->date, true);
        $row = $rows->firstWhere('id', $this->accountId);

        $this->assertSame(250.0, (float) $row->total_debtor,
            'إدراج شرط الفرع أزاح الـ bindings في استعلام الرصيد الافتتاحي.');
    }

    /** @test */
    public function reports_run_unscoped_inside_without_branch_scope(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($this->other));

        $total = BranchQueryContext::withoutBranchScope(fn () => $this->trialBalanceDebtor());

        $this->assertSame(390.0, $total, 'التقارير المقارنة تحتاج مخرجاً صريحاً من العزل.');
    }
}
