<?php

namespace Tests\Feature\Branch;

use App\Models\Concerns\BranchScoped;
use App\Models\auth\Admin;
use App\Support\BranchQueryContext;
use App\Support\BranchSharingSettings;
use App\Support\SystemModuleManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * الضمان الذي تعتمد عليه كل نسخة لا تستعمل الفروع.
 *
 * الاختبارات الأخرى تثبت أن العزل *يعمل*؛ هذا الملف يثبت أنه *لا يعمل* متى
 * وجب ذلك. وهو يفحص كل موديل معزول تلقائياً — لا عيّنة منها — عبر قراءة
 * التريت من الشيفرة نفسها، فأي موديل يُضاف لاحقاً يدخل الفحص وحده.
 *
 * الحالات المغطّاة، وكلها يجب أن تُنتج صفر فلترة:
 *   - موديول branches معطّل (الوضع الافتراضي لأي نسخة جديدة).
 *   - لا صف إطلاقاً في system_module_settings (تنصيب نظيف).
 *   - إدارة عامة بلا branch_id.
 *   - أوامر artisan والكرون.
 *   - حراس web/api (مسار العميل والتطبيق).
 */
class BranchesDisabledRegressionTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    /** @var list<class-string<Model>> */
    private static ?array $scopedModels = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        BranchSharingSettings::persist(['share_external_offices' => false]);
        BranchSharingSettings::flush();
        BranchQueryContext::reset();
    }

    protected function tearDown(): void
    {
        BranchQueryContext::reset();
        parent::tearDown();
    }

    /**
     * كل موديل يستعمل BranchScoped، مقروءاً من الشيفرة لا من قائمة يدوية،
     * حتى لا يفلت موديل جديد من هذا الضمان.
     *
     * @return list<class-string<Model>>
     */
    private function scopedModels(): array
    {
        if (self::$scopedModels !== null) {
            return self::$scopedModels;
        }

        $roots = array_filter([
            app_path('Models'),
            base_path('packages/finance/src/Models'),
            base_path('packages/hr/src/Models'),
            base_path('packages/crm/src/Models'),
        ], fn ($path) => is_dir($path));

        $models = [];

        foreach (Finder::create()->files()->in($roots)->name('*.php') as $file) {
            $class = $this->classFromFile($file);

            if (! $class) {
                continue;
            }

            try {
                if (! class_exists($class)) {
                    continue;
                }

                $reflection = new ReflectionClass($class);
            } catch (\Throwable) {
                continue;
            }

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Model::class)) {
                continue;
            }

            if (in_array(BranchScoped::class, $this->traitsOf($class), true)) {
                $models[] = $class;
            }
        }

        return self::$scopedModels = $models;
    }

    private function classFromFile(SplFileInfo $file): ?string
    {
        $contents = file_get_contents($file->getRealPath()) ?: '';

        if (! preg_match('/^namespace\s+([^;]+);/m', $contents, $namespace)) {
            return null;
        }

        if (! preg_match('/^(?:final\s+|abstract\s+)?class\s+(\w+)/m', $contents, $class)) {
            return null;
        }

        return trim($namespace[1]) . '\\' . $class[1];
    }

    private function traitsOf(string $class): array
    {
        $traits = [];

        for ($current = $class; $current; $current = get_parent_class($current)) {
            foreach (class_uses($current) ?: [] as $trait) {
                $traits[] = $trait;
            }
        }

        return array_unique($traits);
    }

    /**
     * الموديلات التي لا تُضيف شرط فرع إلى استعلامها.
     *
     * الفحص على SQL المُولَّد وليس على النتائج: هذا يغطي كل موديل معزول دفعةً
     * واحدة دون الحاجة لبناء بيانات صالحة لكل جدول، ويكشف الشرط حتى لو صادف
     * أن البيانات الحالية لا تُظهر أثره.
     *
     * @return list<string>
     */
    private function modelsAddingABranchCondition(): array
    {
        $offenders = [];

        foreach ($this->scopedModels() as $class) {
            try {
                $sql = $class::query()->toSql();
            } catch (\Throwable) {
                continue;
            }

            if (Str::contains($sql, 'branch_id')) {
                $offenders[] = class_basename($class) . ' => ' . $sql;
            }
        }

        return $offenders;
    }

    private function branchStaff(): Admin
    {
        $branch = $this->makeBranch();
        $admin = Admin::factory()->forBranch($branch)->create();
        $admin->assignRole('Administrator');

        return $this->lockToOwnBranch($admin);
    }

    /* ------------------------------------------------- الموديول معطّل صراحةً */

    /** @test */
    public function no_model_filters_by_branch_while_the_module_is_off(): void
    {
        $this->enableBranchesModule(false);

        $this->actingAs($this->branchStaff(), 'admin');
        BranchQueryContext::flush();

        $offenders = $this->modelsAddingABranchCondition();

        $this->assertSame([], $offenders, sprintf(
            "مع تعطيل موديول الفروع يجب ألا يضيف أي موديل شرط فرع.\n%d موديل خالف ذلك:\n  - %s",
            count($offenders),
            implode("\n  - ", $offenders)
        ));
    }

    /** @test */
    public function the_scoped_model_set_is_not_empty(): void
    {
        // حارس على الاختبار نفسه: لو فشل اكتشاف الموديلات لأصبح الاختبار
        // أعلاه ينجح دائماً بلا أن يفحص شيئاً.
        $this->assertGreaterThan(40, count($this->scopedModels()));
    }

    /* --------------------------------------------------- تنصيب نظيف بلا صف */

    /** @test */
    public function a_fresh_install_with_no_module_row_is_not_filtered(): void
    {
        DB::table('system_module_settings')->where('key', 'branches')->delete();
        Cache::flush();
        BranchQueryContext::reset();

        $this->assertFalse(SystemModuleManager::isEnabled('branches'),
            'موديول الفروع يجب أن يكون معطّلاً افتراضياً في نسخة جديدة.');

        $this->actingAs($this->branchStaff(), 'admin');
        BranchQueryContext::flush();

        $this->assertSame([], $this->modelsAddingABranchCondition());
    }

    /* -------------------------------------- الموديول مفعّل لكن بلا فرع نشط */

    /** @test */
    public function head_office_admins_are_not_filtered_even_while_the_module_is_on(): void
    {
        $this->enableBranchesModule(true);

        $admin = Admin::factory()->global()->create();
        $admin->assignRole('Administrator');

        $this->actingAs($admin, 'admin');
        BranchQueryContext::flush();

        $this->assertNull(BranchQueryContext::activeBranchId());
        $this->assertSame([], $this->modelsAddingABranchCondition());
    }

    /** @test */
    public function guests_and_other_guards_are_never_filtered(): void
    {
        $this->enableBranchesModule(true);
        BranchQueryContext::reset();

        // لا مستخدم على حارس admin — وهو حال حراس web/api وأوامر artisan.
        $this->assertNull(BranchQueryContext::activeBranchId());
        $this->assertSame([], $this->modelsAddingABranchCondition());
    }

    /* ------------------------------------------------ الطرف المقابل يعمل */

    /** @test */
    public function the_same_models_do_filter_once_a_branch_user_is_active(): void
    {
        $this->enableBranchesModule(true);

        $this->actingAs($this->branchStaff(), 'admin');
        BranchQueryContext::flush();

        $notFiltering = count($this->scopedModels()) - count($this->modelsAddingABranchCondition());

        // تحكّم إيجابي: بدونه قد تنجح كل الاختبارات أعلاه لأن العزل معطّل أصلاً.
        $this->assertSame(0, $notFiltering, sprintf(
            'كل الموديلات المعزولة (%d) يجب أن تُضيف شرط الفرع لموظف فرع، و%d منها لم يفعل.',
            count($this->scopedModels()),
            $notFiltering
        ));
    }
}
