<?php

namespace Tests\Feature\Finance;

use App\Models\auth\Admin;
use App\Support\RecruitmentCostCenters;
use App\Support\RecruitmentSupplierSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Http\Controllers\CostCenterController;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\CostCenter;
use Nami\Finance\Services\AccountCostCenterService;
use Nami\Finance\Services\CostCenterService;
use Tests\TestCase;

/**
 * تصنيف مراكز تكلفة عقود الاستقدام تحت الدولة: عقود الاستقدام ـــ> الدولة ـــ> عقد.
 */
class RecruitmentCostCenterGroupingTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        ConfirmSetting::query()->where('title', RecruitmentSupplierSettings::COST_CENTER_SCOPE)->delete();
    }

    public function test_an_imported_country_node_is_reused_instead_of_creating_a_duplicate(): void
    {
        $this->groupByNationality();
        $root = RecruitmentCostCenters::root();
        $legacy = $this->node($root, 'طلبات استقدام دولة الاختبارستان');

        $center = RecruitmentCostCenters::forContract(991000001, 'اختبارستان');

        $this->assertSame($legacy->id, (int) $center->parent_id);
        $this->assertSame(0, CostCenter::query()
            ->where('parent_id', $root->id)
            ->where('title', RecruitmentCostCenters::NATIONALITY_TITLE_PREFIX . 'اختبارستان')
            ->count());
    }

    public function test_the_busiest_of_two_matching_country_nodes_is_used(): void
    {
        $this->groupByNationality();
        $root = RecruitmentCostCenters::root();
        $empty = $this->node($root, 'طلبات استقدام دولة الاختبارية');
        $busy = $this->node($root, 'طلبات استقدام دولة الاختبارية');
        $this->node($busy, 'طلب استقدام قديم 1');

        $center = RecruitmentCostCenters::forContract(991000002, 'الإختبارية');

        $this->assertSame($busy->id, (int) $center->parent_id);
        $this->assertNotSame($empty->id, (int) $center->parent_id);
    }

    public function test_a_new_country_node_is_named_after_the_country(): void
    {
        $this->groupByNationality();
        $root = RecruitmentCostCenters::root();

        $center = RecruitmentCostCenters::forContract(991000003, 'جديدستان');
        $node = CostCenter::query()->findOrFail($center->parent_id);

        $this->assertSame('عقود استقدام جديدستان', $node->title);
        $this->assertSame($root->id, (int) $node->parent_id);
        $this->assertSame('عقد استقدام #991000003 - جديدستان', $center->title);
    }

    public function test_an_existing_flat_centre_moves_under_its_country_and_keeps_its_id(): void
    {
        $flat = RecruitmentCostCenters::forContract(991000004, 'نقلستان');
        $this->assertSame(RecruitmentCostCenters::root()->id, (int) $flat->parent_id);

        $this->groupByNationality();
        $again = RecruitmentCostCenters::forContract(991000004, 'نقلستان');

        $this->assertSame($flat->id, $again->id);
        $this->assertSame('عقود استقدام نقلستان', CostCenter::query()->findOrFail($again->parent_id)->title);
        $this->assertSame(1, CostCenter::query()
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', 991000004)
            ->count());
    }

    public function test_a_centre_follows_the_contract_when_the_nationality_changes(): void
    {
        $this->groupByNationality();
        $first = RecruitmentCostCenters::forContract(991000005, 'أولستان');

        $moved = RecruitmentCostCenters::forContract(991000005, 'ثانيستان');

        $this->assertSame($first->id, $moved->id);
        $this->assertSame('عقود استقدام ثانيستان', CostCenter::query()->findOrFail($moved->parent_id)->title);
    }

    public function test_next_code_ignores_text_codes(): void
    {
        $root = RecruitmentCostCenters::root();
        $this->node($root, 'نص', 'RENTAL-999999');
        $this->node($root, 'رقم', '9900000');

        $this->assertSame('9900001', RecruitmentCostCenters::nextCode());
    }

    public function test_a_parent_statement_covers_every_centre_beneath_it(): void
    {
        $root = RecruitmentCostCenters::root();
        $country = $this->node($root, 'عقود استقدام شجرستان');
        $contract = $this->node($country, 'عقد استقدام #991000006 - شجرستان');
        $other = $this->node($root, 'عقود استقدام أخرى');

        $ids = app(AccountCostCenterService::class)->costCenterWithDescendantIds($country->id);

        $this->assertEqualsCanonicalizing([$country->id, $contract->id], $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_the_command_previews_moves_applies_them_and_undoes_them(): void
    {
        Storage::fake();
        $root = RecruitmentCostCenters::root();
        $country = $this->node($root, 'طلبات استقدام دولة أمرستان');
        // العقد غير موجود في جدول العقود، فالجنسية تُؤخذ من عنوان المركز.
        $center = CostCenter::query()->create([
            'title' => 'عقد استقدام #991000007 - أمرستان',
            'parent_id' => $root->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 991000007,
            'code' => RecruitmentCostCenters::nextCode(),
            'created_by_id' => $this->admin->id,
        ]);
        $database = DB::connection()->getDatabaseName();

        $this->artisan('finance:group-recruitment-cost-centers')->assertExitCode(0);
        $this->assertSame($root->id, (int) $center->fresh()->parent_id);

        $this->artisan('finance:group-recruitment-cost-centers', ['--apply' => true])->assertExitCode(1);
        $this->assertSame($root->id, (int) $center->fresh()->parent_id);

        $this->artisan('finance:group-recruitment-cost-centers', ['--apply' => true, '--database' => $database])
            ->assertExitCode(0);
        $this->assertSame($country->id, (int) $center->fresh()->parent_id);

        $log = collect(Storage::allFiles('cost-center-grouping'))->first();
        $this->assertNotNull($log);

        $this->artisan('finance:group-recruitment-cost-centers', ['--undo' => $log, '--database' => $database])
            ->assertExitCode(0);
        $this->assertSame($root->id, (int) $center->fresh()->parent_id);
    }

    public function test_a_centre_with_children_cannot_be_deleted(): void
    {
        $root = RecruitmentCostCenters::root();
        $country = $this->node($root, 'عقود استقدام حذفستان');
        $this->node($country, 'عقد استقدام #991000008 - حذفستان');

        $response = app(CostCenterController::class)->destroy($country->id, app(CostCenterService::class));

        $this->assertSame(422, $response->getStatusCode());
        $this->assertNotNull(CostCenter::query()->find($country->id));
    }

    public function test_an_empty_centre_can_still_be_deleted(): void
    {
        $empty = $this->node(RecruitmentCostCenters::root(), 'عقود استقدام فارغستان');

        $response = app(CostCenterController::class)->destroy($empty->id, app(CostCenterService::class));

        $this->assertLessThan(300, $response->getStatusCode());
        $this->assertNull(CostCenter::query()->find($empty->id));
    }

    private function groupByNationality(): void
    {
        ConfirmSetting::query()->where('title', RecruitmentSupplierSettings::COST_CENTER_SCOPE)->delete();
        ConfirmSetting::query()->create([
            'title' => RecruitmentSupplierSettings::COST_CENTER_SCOPE,
            'value' => RecruitmentSupplierSettings::SCOPE_NATIONALITY_THEN_CONTRACT,
            'created_by_id' => $this->admin->id,
        ]);
    }

    private function node(CostCenter $parent, string $title, ?string $code = null): CostCenter
    {
        return CostCenter::query()->create([
            'title' => $title,
            'parent_id' => $parent->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => $code ?? RecruitmentCostCenters::nextCode(),
            'created_by_id' => $this->admin->id,
        ]);
    }
}
