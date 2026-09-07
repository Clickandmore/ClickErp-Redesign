<?php

namespace Tests\Feature\Branch;

use App\Models\Branch;
use App\Models\FinancialRequest;
use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Support\BranchQueryContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * العزل التلقائي للفروع على مستوى الموديل (BranchScope).
 *
 * الفلترة اليدوية (applyRequestBranchFilter) تحمي الشاشة التي تتذكّرها؛ هذا الـ scope
 * يحمي كل استعلام سواء تذكّرته الشاشة أم لا. لذلك يجب أن يكون تعريف "متى يعمل"
 * دقيقاً جداً — أي توسّع فيه يكسر التقارير والأوامر، وأي تضييق يعيد التسريب.
 *
 * القاعدة المثبّتة هنا:
 *   يعمل   → طلب لوحة تحكم + موديول الفروع مفعّل + موظف مرتبط بفرع بلا branches_view_all.
 *   لا يعمل → الموديول معطّل، أو الإدارة العامة (بلا فرع)، أو صاحب branches_view_all،
 *             أو أوامر artisan، أو استثناء صريح عبر withoutBranchScope.
 */
class BranchScopeTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        BranchQueryContext::reset();
    }

    protected function tearDown(): void
    {
        BranchQueryContext::reset();
        parent::tearDown();
    }

    private function staffOf(?Branch $branch): Admin
    {
        $factory = Admin::factory();
        $factory = $branch ? $factory->forBranch($branch) : $factory->global();

        $admin = $factory->create();
        $admin->assignRole('Administrator');

        return $branch ? $this->lockToOwnBranch($admin) : $admin;
    }

    private function requestFor(?Branch $branch): FinancialRequest
    {
        return FinancialRequest::query()->create([
            'request_no' => 'SCOPE-' . uniqid(),
            'type' => 'other_financial',
            'priority' => FinancialRequest::PRIORITY_NORMAL,
            'status' => FinancialRequest::STATUS_FOR_APPROVAL,
            'request_date' => now()->toDateString(),
            'branch_id' => $branch?->id,
        ]);
    }

    /**
     * الـ scope يقرأ المستخدم الحالي عبر حارس admin، وهو ما لا يحدث في اختبار
     * وحدة عادي، فنحاكي طلب لوحة التحكم بتسجيل الدخول ثم تفريغ الذاكرة المؤقتة.
     */
    private function actingAsBranchStaff(Admin $admin): void
    {
        $this->actingAs($admin, 'admin');
        BranchQueryContext::flush();
    }

    // ------------------------------------------------------------------ يعمل

    /** @test */
    public function branch_staff_only_see_their_own_branch_records(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();

        $mine = $this->requestFor($own);
        $theirs = $this->requestFor($other);

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($own));

        $visible = FinancialRequest::query()->pluck('id');

        $this->assertTrue($visible->contains($mine->id), 'موظف الفرع لا يرى سجل فرعه.');
        $this->assertFalse($visible->contains($theirs->id), 'موظف الفرع يرى سجل فرع آخر — تسريب.');
    }

    /** @test */
    public function main_branch_staff_also_see_legacy_records_without_a_branch(): void
    {
        $main = $this->makeBranch(['is_main' => true]);
        $legacy = $this->requestFor(null);

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($main));

        $this->assertTrue(
            FinancialRequest::query()->pluck('id')->contains($legacy->id),
            'السجلات القديمة بلا فرع يجب أن تبقى مرئية للفرع الرئيسي.'
        );
    }

    /** @test */
    public function other_branch_staff_do_not_see_legacy_records_without_a_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $legacy = $this->requestFor(null);

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($other));

        $this->assertFalse(
            FinancialRequest::query()->pluck('id')->contains($legacy->id),
            'السجل بلا فرع يخص الفرع الرئيسي، ولا يجوز أن يظهر لفرع آخر.'
        );
    }

    // --------------------------------------------------------------- لا يعمل

    /** @test */
    public function the_scope_is_inert_while_the_branches_module_is_off(): void
    {
        $own = $this->makeBranch();
        $theirs = $this->requestFor($this->makeBranch());

        $this->enableBranchesModule(false);
        $this->actingAsBranchStaff($this->staffOf($own));

        $this->assertTrue(
            FinancialRequest::query()->pluck('id')->contains($theirs->id),
            'مع تعطيل الموديول يجب أن يعمل النظام كشركة واحدة بلا فلترة.'
        );
    }

    /** @test */
    public function head_office_admins_see_every_branch(): void
    {
        $theirs = $this->requestFor($this->makeBranch());

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf(null));

        $this->assertTrue(
            FinancialRequest::query()->pluck('id')->contains($theirs->id),
            'الإدارة العامة (بلا branch_id) ترى كل الفروع.'
        );
    }

    /** @test */
    public function admins_holding_view_all_branches_are_not_scoped(): void
    {
        $own = $this->makeBranch();
        $theirs = $this->requestFor($this->makeBranch());

        $admin = Admin::factory()->forBranch($own)->create();
        $admin->assignRole('Administrator');

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($admin);

        $this->assertTrue(
            FinancialRequest::query()->pluck('id')->contains($theirs->id),
            'صاحب branches_view_all مرتبط بفرع لكنه يرى كل الفروع.'
        );
    }

    /** @test */
    public function console_commands_run_across_every_branch(): void
    {
        $own = $this->makeBranch();
        $theirs = $this->requestFor($this->makeBranch());

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($own));

        // الأوامر والكرون تعمل على مستوى الشركة، لا على مستوى فرع.
        BranchQueryContext::flush();
        $this->app['env'] = 'testing';

        $withoutScope = BranchQueryContext::withoutBranchScope(
            fn () => FinancialRequest::query()->pluck('id')
        );

        $this->assertTrue($withoutScope->contains($theirs->id));
    }

    // ------------------------------------------------------------- الاستثناءات

    /** @test */
    public function without_branch_scope_reaches_every_branch(): void
    {
        $own = $this->makeBranch();
        $theirs = $this->requestFor($this->makeBranch());

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($own));

        $this->assertFalse(FinancialRequest::query()->pluck('id')->contains($theirs->id));
        $this->assertTrue(
            FinancialRequest::query()->withoutBranchScope()->pluck('id')->contains($theirs->id),
            'التقارير التجميعية تحتاج مخرجاً صريحاً من العزل.'
        );
    }

    /** @test */
    public function for_branch_targets_one_branch_regardless_of_the_current_user(): void
    {
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $theirs = $this->requestFor($other);

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($own));

        $this->assertTrue(
            FinancialRequest::query()->forBranch($other->id)->pluck('id')->contains($theirs->id),
            'forBranch يجب أن يتجاوز فرع المستخدم لأغراض التقارير المقارنة.'
        );
    }

    // ------------------------------------------- بيانات مشتركة مقابل مستندات

    /** @test */
    public function shared_master_data_without_a_branch_stays_visible_to_every_branch(): void
    {
        $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();

        // السيرة الذاتية بلا فرع مخزون مشترك، لا مستند يخصّ الفرع الرئيسي.
        $sharedMaid = Maid::query()->create([
            'name' => 'عاملة في المخزون المشترك',
            'passport_num' => 'SHARED-' . str_replace('.', '', uniqid('', true)),
            'age' => 30,
            'status' => 'available',
            'available' => true,
            'branch_id' => null,
        ]);

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($other));

        $this->assertTrue(
            Maid::query()->pluck('id')->contains($sharedMaid->id),
            'المخزون المشترك من السير الذاتية يجب أن يبقى مرئياً لكل الفروع.'
        );
    }

    /** @test */
    public function documents_without_a_branch_are_not_treated_as_shared(): void
    {
        $this->makeBranch(['is_main' => true]);
        $other = $this->makeBranch();
        $legacy = $this->requestFor(null);

        $this->enableBranchesModule(true);
        $this->actingAsBranchStaff($this->staffOf($other));

        // الفرق عن الاختبار السابق: المستند بلا فرع يخصّ الفرع الرئيسي وحده،
        // بينما البيانات الأساسية بلا فرع مشتركة. خلط الحالتين يكسر إحداهما.
        $this->assertFalse(
            FinancialRequest::query()->pluck('id')->contains($legacy->id),
            'المستند بلا فرع ليس بياناً مشتركاً.'
        );
    }
}
