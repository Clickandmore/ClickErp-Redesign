<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\auth\Admin;
use App\PermKeys;
use App\Support\BranchQueryContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\AccountStatementService;
use Nami\Finance\Services\FinancialPositionService;
use Nami\Finance\Services\TrailBalanceService;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * فصل التقرير على فرع واحد لمن يرى كل الفروع.
 *
 * المحاسب الذي يملك صلاحية الفرعين لا يقع عليه عزل تلقائي — وهذا صحيح، لأن
 * رقمه الافتراضي هو رقم الشركة. لكنه عند المراجعة يحتاج قائمة دخل وكشف حساب
 * وميزان مراجعة لفرع بعينه، فيختار الفرع من شاشة التقرير (`branch_id`).
 *
 * ما يُقاس هنا ثلاثة أشياء، كلها بمبالغ محددة لا بوجود صف:
 *   1. الاختيار يفصل فعلاً، وبنفس دلالة موظف ذلك الفرع (الرئيسي يضمّ القديم).
 *   2. الاختيار لا يفتح ثغرة: موظف مقيّد بفرع لا يصل لفرع آخر بتمرير branch_id.
 *   3. بلا اختيار — ومع تعطيل الموديول — يبقى الرقم كما كان قبل هذه الميزة.
 *
 * توزيع البيانات (نفس توزيع FinanceReportBranchScopeTest):
 *   الفرع الرئيسي  → 100
 *   الفرع الآخر    → 250
 *   بلا فرع (قديم) →  40
 *   المجموع        → 390
 */
class FinanceReportBranchSelectionTest extends TestCase
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
        $this->authorId = Admin::factory()->global()->create()->id;

        $this->postEntry($this->main->id, 100);
        $this->postEntry($this->other->id, 250);
        $this->postEntry(null, 40);
    }

    protected function tearDown(): void
    {
        BranchQueryContext::reset();
        $this->forgetRequest();
        parent::tearDown();
    }

    /* ------------------------------------------------------------- fixtures */

    private function makeRootAccount(): int
    {
        return (int) DB::table('fn_accounts')->insertGetId([
            'name' => 'حساب اختبار فصل الفروع',
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

    /**
     * محاسب يرى الفروع كلها: إما بلا فرع، أو بفرع مع صلاحية branches_view_all.
     *
     * الصلاحية تُمنح للموظف صراحةً ولا تُترك لما يحمله دور Administrator المزروع،
     * لأن موضوع هذا الملف هو "من يرى كل الفروع" لا "ما الذي يحمله الدور اليوم".
     */
    private function actingAsAccountantSeeingAllBranches(?Branch $branch = null): void
    {
        $factory = Admin::factory();
        $admin = ($branch ? $factory->forBranch($branch) : $factory->global())->create();
        $admin->assignRole('Administrator');

        if ($branch) {
            $this->grantViewAllBranches($admin);
        }

        $this->actingAs($admin, 'admin');
        BranchQueryContext::flush();
    }

    private function grantViewAllBranches(Admin $admin): void
    {
        $permissionId = DB::table('permissions')
            ->where('name', PermKeys::BRANCHES_VIEW_ALL)
            ->where('guard_name', 'admin')
            ->value('id');

        if ($permissionId) {
            DB::table('model_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'model_type' => $admin->getMorphClass(),
                'model_id' => $admin->id,
            ]);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** موظف مقيّد بفرعه وحده. */
    private function actingAsBranchStaff(Branch $branch): void
    {
        $admin = Admin::factory()->forBranch($branch)->create();
        $admin->assignRole('Administrator');
        $admin = $this->lockToOwnBranch($admin);

        $this->actingAs($admin, 'admin');
        BranchQueryContext::flush();
    }

    /**
     * اختيار الفرع يصل من شاشة التقرير داخل الطلب نفسه، فالاختبار يبني الطلب
     * بدل تمرير المعرّف كوسيط — وهو المسار الذي يمرّ منه المستخدم فعلاً.
     */
    private function selectBranchInReport(?int $branchId, string $method = 'GET'): void
    {
        $request = Request::create('/finance/trail-balance', $method, ['branch_id' => $branchId]);
        $request->setUserResolver(fn () => auth('admin')->user());

        $this->app->instance('request', $request);
        BranchQueryContext::flush();
    }

    private function forgetRequest(): void
    {
        $this->app->instance('request', Request::create('/'));
        BranchQueryContext::flush();
    }

    /* -------------------------------------------------------- قراءات التقارير */

    private function trialBalanceDebtor(): float
    {
        $rows = app(TrailBalanceService::class)
            ->getAccountSummaryForTrailBalance(1, $this->date, $this->date, false);

        return (float) ($rows->firstWhere('id', $this->accountId)->total_debtor ?? 0);
    }

    private function accountStatementDebtor(): float
    {
        return (float) app(AccountStatementService::class)
            ->filterDoubleAccount(
                ['type' => 'both', 'from_date' => '', 'to_date' => ''],
                [$this->accountId]
            )
            ->sum('debtor_value');
    }

    /* ------------------------------------- بلا اختيار: لا شيء يتغيّر عمّا كان */

    /** @test */
    public function an_accountant_seeing_every_branch_still_gets_the_company_total_by_default(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAccountantSeeingAllBranches();

        $this->assertSame(390.0, $this->trialBalanceDebtor(),
            'بلا اختيار فرع يجب أن يبقى الرقم على مستوى الشركة كما كان.');
        $this->assertSame(390.0, $this->accountStatementDebtor());
    }

    /** @test */
    public function selecting_a_branch_does_nothing_while_the_module_is_off(): void
    {
        $this->enableBranchesModule(false);
        $this->actingAsAccountantSeeingAllBranches();
        $this->selectBranchInReport($this->other->id);

        $this->assertSame(390.0, $this->trialBalanceDebtor(),
            'مع تعطيل الموديول لا معنى لاختيار الفرع، ولا يجوز أن يغيّر رقماً.');
        $this->assertSame(390.0, $this->accountStatementDebtor());
    }

    /* ------------------------------------------------- الاختيار يفصل التقرير */

    /** @test */
    public function selecting_a_branch_splits_the_report_onto_that_branch_alone(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAccountantSeeingAllBranches();
        $this->selectBranchInReport($this->other->id);

        $this->assertSame(250.0, $this->trialBalanceDebtor(),
            'ميزان مراجعة الفرع المختار يجب ألا يحمل ريالاً من فرع آخر.');
        $this->assertSame(250.0, $this->accountStatementDebtor(),
            'كشف الحساب يجب أن يعطي نفس رقم ميزان المراجعة لنفس الاختيار.');
    }

    /** @test */
    public function selecting_the_main_branch_carries_the_legacy_entries_with_it(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAccountantSeeingAllBranches();
        $this->selectBranchInReport($this->main->id);

        // 100 للفرع الرئيسي + 40 قيداً قديماً بلا فرع — نفس دلالة موظف الفرع الرئيسي.
        $this->assertSame(140.0, $this->trialBalanceDebtor());
        $this->assertSame(140.0, $this->accountStatementDebtor());
    }

    /** @test */
    public function the_two_branch_reports_add_up_to_the_company_total(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAccountantSeeingAllBranches();

        $this->selectBranchInReport($this->main->id);
        $mainTotal = $this->trialBalanceDebtor();

        $this->selectBranchInReport($this->other->id);
        $otherTotal = $this->trialBalanceDebtor();

        $this->assertSame(390.0, $mainTotal + $otherTotal,
            'مجموع تقريري الفرعين يجب أن يساوي تقرير الشركة، وإلا ضاع قيد أو احتُسب مرتين.');
    }

    /** @test */
    public function an_accountant_attached_to_one_branch_but_holding_view_all_can_still_split(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAccountantSeeingAllBranches($this->main);
        $this->selectBranchInReport($this->other->id);

        $this->assertSame(250.0, $this->trialBalanceDebtor(),
            'صلاحية رؤية كل الفروع تعني أيضاً القدرة على فصل فرع غير فرعه.');
    }

    /* ------------------------------------------- الاختيار ليس ثغرة وصول */

    /** @test */
    public function branch_staff_cannot_reach_another_branch_by_passing_a_branch_id(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->other);
        $this->selectBranchInReport($this->main->id);

        $this->assertSame(250.0, $this->trialBalanceDebtor(),
            'العزل التلقائي يسبق الاختيار اليدوي — وإلا صار الرابط بوابة لفرع آخر.');
        $this->assertSame(250.0, $this->accountStatementDebtor());
    }

    /** @test */
    public function a_write_request_carrying_a_branch_id_does_not_filter_the_reports(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAccountantSeeingAllBranches();
        $this->selectBranchInReport($this->other->id, 'POST');

        // في طلبات الحفظ يعني branch_id الفرع الذي يُنشأ فيه القيد، لا فلتر تقرير.
        $this->assertSame(390.0, $this->trialBalanceDebtor(),
            'قراءة branch_id من طلب كتابة كانت ستفلتر أرصدة الحفظ على فرع السجل الجديد.');
    }

    /** @test */
    public function an_unknown_branch_id_is_ignored_instead_of_emptying_the_report(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAccountantSeeingAllBranches();
        $this->selectBranchInReport(999999);

        $this->assertSame(390.0, $this->trialBalanceDebtor(),
            'معرّف فرع غير موجود يجب أن يُهمل، لا أن يُخرج تقريراً فارغاً يبدو صحيحاً.');
    }

    /* ------------------------------------------------- قائمة المركز المالي */

    /** @test */
    public function the_financial_position_statement_is_scoped_to_the_selected_branch(): void
    {
        $this->enableBranchesModule(true);
        $this->actingAsAccountantSeeingAllBranches();

        $this->selectBranchInReport($this->other->id);
        $scoped = app(FinancialPositionService::class)->report($this->date);

        $this->forgetRequest();
        $this->actingAsAccountantSeeingAllBranches();
        $all = app(FinancialPositionService::class)->report($this->date);

        $this->assertSame(
            250.0,
            $this->positionBalanceFor($scoped),
            'قائمة المركز المالي كانت تقرأ القيود بلا شرط فرع إطلاقاً.'
        );
        $this->assertSame(390.0, $this->positionBalanceFor($all));
    }

    /** رصيد حساب الاختبار كما تعرضه قائمة المركز المالي (تحسب بالهللات). */
    private function positionBalanceFor(array $report): float
    {
        foreach ($report['sections'] ?? [] as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                if ((int) ($row['id'] ?? 0) === $this->accountId) {
                    return ((int) $row['own']) / 100;
                }
            }
        }

        return 0.0;
    }
}
