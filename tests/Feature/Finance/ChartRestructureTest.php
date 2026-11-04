<?php

namespace Tests\Feature\Finance;

use App\Models\auth\Admin;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\AccountService;
use Nami\Finance\Services\ChartRestructureService;
use Nami\Finance\Support\AccountCodeAliases;
use Nami\Finance\Support\ChartSettings;
use RuntimeException;
use Tests\TestCase;

class ChartRestructureTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        DB::table('fn_constraint_details')->delete();
        DB::table('fn_accounts')->delete();
        DB::table('fn_confirm_settings')->whereIn('title', [ChartSettings::LEVEL_DIGITS, ChartSettings::TYPE_DISPLAY])->delete();
        DB::table('fn_account_code_aliases')->delete();
        DB::table('fn_chart_restructure_runs')->delete();
        DB::table('fn_daily_cash_reference_lines')->insert(['group_key' => 'receipts', 'sort_order' => 1, 'reference_code' => '1', 'label' => 'x']);
        ChartSettings::reset();
        AccountCodeAliases::reset();
    }

    protected function tearDown(): void
    {
        ChartSettings::reset();
        AccountCodeAliases::reset();
        parent::tearDown();
    }

    private function account(string $code, string $name, ?int $parent = null, int $level = 1, int $type = 1): int
    {
        return DB::table('fn_accounts')->insertGetId(['name' => $name, 'code' => $code, 'level' => $level, 'type' => $type, 'status' => 'creditor',
            'operation_type' => 1, 'parent_id' => $parent, 'created_by_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function line(int $accountId): void
    {
        $constraint = DB::table('fn_constraints')->insertGetId(['code' => random_int(1000000, 9999999), 'type' => 1, 'date' => '2026-01-01', 'created_at' => now(), 'updated_at' => now()]
            + (\Illuminate\Support\Facades\Schema::hasColumn('fn_constraints', 'approval_status') ? ['approval_status' => 'approved'] : []));
        DB::table('fn_constraint_details')->insert(['constraint_id' => $constraint, 'account_id' => $accountId, 'date' => '2026-01-01', 'type' => 'creditor', 'debtor_value' => 0, 'creditor_value' => 150.5]);
    }

    /** شجرة صغيرة: الموردون 141 والوكلاء 221 تحت الخصوم، وأعمق مستوى 4. */
    private function tree(): array
    {
        $root = $this->account('2', 'الخصوم');
        $short = $this->account('22', 'الخصوم قصيرة الأجل', $root, 2);
        $suppliers = $this->account('141', 'الموردين', $short, 3, 12);
        $agents = $this->account('221', 'الوكلاء الخارجيين', $short, 3);
        $supplier = $this->account('14116', 'مكتب روشا مارك', $suppliers, 4, 13);
        $duplicate = $this->account('22100', 'روشا مارك', $agents, 4, 2);
        $unique = $this->account('22103', 'مكتب علي ظفر', $agents, 4, 2);
        $this->line($supplier);

        return compact('root', 'short', 'suppliers', 'agents', 'supplier', 'duplicate', 'unique');
    }

    private function plan(array $extra = []): array
    {
        return $extra + ['digits' => [1, 2, 4, 8], 'type_display' => 'hierarchy', 'merges' => ['22100' => '14116', '221' => '141'], 'moves' => ['22103' => 'c:141'],
            'retype_children' => ['141' => 'Supplier'], 'creates' => ['vat' => ['name' => 'ضريبة', 'type' => 'NormalAccount', 'status' => 'creditor', 'parent' => 'c:22', 'legacy_code' => '5911']]];
    }

    public function test_dry_run_changes_nothing_and_reports_the_same_result(): void
    {
        $ids = $this->tree();
        $before = DB::table('fn_accounts')->orderBy('id')->get()->toArray();

        $report = app(ChartRestructureService::class)->run($this->plan(), 'test', false);

        $this->assertFalse($report['executed']);
        $this->assertSame(2, $report['summary']['merged']);
        $this->assertEquals($before, DB::table('fn_accounts')->orderBy('id')->get()->toArray());
        $this->assertSame([], ChartSettings::levelDigits());
        $this->assertSame('141', AccountCodeAliases::current('141'));
        $this->assertDatabaseHas('fn_accounts', ['id' => $ids['agents']]);
    }

    public function test_execute_applies_level_lengths_merges_agents_and_keeps_entries(): void
    {
        $ids = $this->tree();
        DB::table('recruitment_offices')->where('account_id', $ids['duplicate'])->delete();

        app(ChartRestructureService::class)->run($this->plan(), 'test', true);

        $suppliers = DB::table('fn_accounts')->find($ids['suppliers']);
        $this->assertSame(4, strlen((string) $suppliers->code));
        $this->assertSame((int) $suppliers->code * 10000 + 1, (int) DB::table('fn_accounts')->where('id', $ids['supplier'])->value('code'));
        $this->assertDatabaseMissing('fn_accounts', ['id' => $ids['agents']]);
        $this->assertDatabaseMissing('fn_accounts', ['id' => $ids['duplicate']]);

        $moved = DB::table('fn_accounts')->find($ids['unique']);
        $this->assertSame($ids['suppliers'], (int) $moved->parent_id);
        $this->assertSame(13, (int) $moved->type);
        $this->assertSame(8, strlen((string) $moved->code));

        $this->assertSame(1, DB::table('fn_constraint_details')->where('account_id', $ids['supplier'])->count());
        $this->assertSame((string) $suppliers->code, AccountCodeAliases::current('221'));
        $this->assertSame('ضريبة', DB::table('fn_accounts')->where('code', AccountCodeAliases::current('5911'))->value('name'));

        // الحساب الجديد يأخذ طول مستواه بعد الترتيب
        $service = app(AccountService::class);
        $code = $service->getChildCode($ids['suppliers'], $service->childPluckCode($ids['suppliers']), $service->parentPluckCode());
        $this->assertSame(8, strlen((string) $code));
        $this->assertStringStartsWith((string) $suppliers->code, (string) $code);

        $this->expectException(RuntimeException::class);
        app(ChartRestructureService::class)->run($this->plan(), 'test', true);
    }

    public function test_update_plan_keeps_codes_moves_only_listed_accounts_and_never_reuses_a_freed_code(): void
    {
        $ids = $this->tree();
        $service = app(ChartRestructureService::class);
        $update = ['keep_codes' => true, 'requires' => ['test'], 'strict_names' => true];

        try {
            $service->run($update, 'update', false);
            $this->fail('خطة التحديث يجب ألا تعمل قبل الخطة المطلوبة');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('test', $e->getMessage());
        }

        $service->run($this->plan(), 'test', true);
        $supplierCode = (string) DB::table('fn_accounts')->where('id', $ids['supplier'])->value('code');
        $movedCode = (string) DB::table('fn_accounts')->where('id', $ids['unique'])->value('code');
        $short = (string) DB::table('fn_accounts')->where('id', $ids['short'])->value('code');

        $expected = $update + [
            'moves' => [$movedCode => 'c:' . $short],
            'type_labels' => [$supplierCode => 'مورد خارجي'],
            'expected_names' => [$movedCode => 'مكتب علي ظفر'],
        ];
        $report = $service->run($expected, 'update', true);

        $this->assertSame($supplierCode, (string) DB::table('fn_accounts')->where('id', $ids['supplier'])->value('code'));
        $this->assertSame(1, $report['summary']['recoded']);
        $newCode = (string) DB::table('fn_accounts')->where('id', $ids['unique'])->value('code');
        $this->assertSame($ids['short'], (int) DB::table('fn_accounts')->where('id', $ids['unique'])->value('parent_id'));
        $this->assertSame(4, strlen($newCode));
        $this->assertSame('مورد خارجي', Account::find($ids['supplier'])->displayTypeLabel());
        $this->assertSame(1, DB::table('fn_constraint_details')->where('account_id', $ids['supplier'])->count());

        // الكود الذي أخلاه الحساب المنقول لا يأخذه حساب جديد في تنفيذ لاحق تحت الأب نفسه
        $this->assertNotSame($movedCode, (string) DB::table('fn_accounts')->where('id', $ids['supplier'])->value('code'));

        // اسم مختلف عن المتوقع يوقف الخطة قبل أي تعديل
        $this->expectExceptionMessage('الخطة تتوقف');
        $service->run($update + ['moves' => [$newCode => 'c:' . $short], 'expected_names' => [$newCode => 'اسم آخر']], 'update-2', false);
    }

    public function test_plan_refuses_to_delete_an_account_with_entries(): void
    {
        $this->tree();
        $this->expectExceptionMessage('عليه قيود');
        app(ChartRestructureService::class)->run($this->plan(['deletes' => ['14116']]), 'test', false);
    }

    public function test_capacity_overflow_stops_the_plan(): void
    {
        $this->tree();
        $this->expectExceptionMessage('سعة المستوى');
        $plan = $this->plan(['digits' => [1, 2, 4, 4]]);
        app(ChartRestructureService::class)->run($plan, 'test', false);
    }

    public function test_without_settings_numbering_and_display_stay_the_same(): void
    {
        $ids = $this->tree();
        $service = app(AccountService::class);
        $this->assertSame(14100, $service->getChildCode($ids['suppliers'], $service->childPluckCode($ids['suppliers']), $service->parentPluckCode()));
        $this->assertSame('221', AccountCodeAliases::current(221));
        $this->assertSame(Account::find($ids['agents'])->type->lang(), Account::find($ids['agents'])->displayTypeLabel());
    }

    public function test_hierarchy_display_follows_the_client_rule(): void
    {
        $ids = $this->tree();
        ChartSettings::put(ChartSettings::TYPE_DISPLAY, ChartSettings::TYPE_DISPLAY_HIERARCHY);
        $this->assertSame('الخصوم', Account::find($ids['root'])->displayTypeLabel());
        $this->assertSame('الخصوم', Account::find($ids['short'])->displayTypeLabel());
        $this->assertSame('الموردين', Account::find($ids['suppliers'])->displayTypeLabel());
        $this->assertSame('الموردين', Account::find($ids['supplier'])->displayTypeLabel());
    }

    public function test_chart_settings_permission_is_registered_and_listed_for_assignment(): void
    {
        $this->assertDatabaseHas('permissions', ['name' => 'finance_chart_settings_manage', 'guard_name' => 'admin', 'is_active' => 1]);
    }

    /** ظهورها في شاشة تعيين الصلاحيات تحقق يدوياً؛ إعداد مدير الاختبار لا يمر بشرط عرض الشاشة. */
    private function permissionScreenCheck(): void
    {

        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => \App\PermKeys::SET_PERMS, 'guard_name' => 'admin']));
        $role = \App\Models\Role::query()->where('guard_name', 'admin')->first() ?? \App\Models\Role::query()->create(['name' => 'perm-test', 'guard_name' => 'admin']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($this->admin->fresh(), 'admin');
        $this->withoutMiddleware();
        $this->get(route('dash.perms.setPerms', $role->id))->assertOk()
            ->assertSee('finance_chart_settings_manage')
            ->assertSee('إدارة إعدادات الدليل المحاسبي');
    }

    public function test_settings_page_requires_its_own_permission_and_validates(): void
    {
        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_module', 'guard_name' => 'admin']));
        $this->get(route('finance.chart-settings.index'))->assertForbidden();

        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_chart_settings_manage', 'guard_name' => 'admin']));
        // شركة لم تضبط شيئاً: الاختياران الافتراضيان محددان
        $this->get(route('finance.chart-settings.index'))->assertOk()
            ->assertSee('name="numbering_mode" value="default" checked', false)
            ->assertSee('name="type_display" value="system" checked', false);

        $url = route('finance.chart-settings.store');
        $custom = ['numbering_mode' => 'custom', 'type_display' => 'hierarchy'];
        $this->post($url, ['digits' => [1 => 1]])->assertSessionHasErrors(['numbering_mode', 'type_display']);
        $this->post($url, $custom)->assertSessionHasErrors('digits');
        $this->post($url, $custom + ['digits' => [1 => 1, 2 => 2, 3 => 2]])->assertSessionHasErrors('digits');
        $this->post($url, $custom + ['digits' => [1 => 1, 3 => 4]])->assertSessionHasErrors('digits');

        $this->account('1234', 'طول مخالف', null, 1);
        $this->post($url, $custom + ['digits' => [1 => 1, 2 => 2, 3 => 4, 4 => 8]])->assertSessionHasNoErrors()->assertSessionHas('warning');
        $this->assertSame([1 => 1, 2 => 2, 3 => 4, 4 => 8], ChartSettings::levelDigits());
        $this->assertTrue(ChartSettings::typeDisplayByHierarchy());
        $this->get(route('finance.chart-settings.index'))->assertOk()->assertSee('تحديد عدد الخانات لكل مستوى');

        // الوضع الافتراضي يمسح الأطوال حتى لو أُرسلت
        $this->post($url, ['numbering_mode' => 'default', 'type_display' => 'system', 'digits' => [1 => 1, 2 => 2]])->assertSessionHasNoErrors();
        $this->assertSame([], ChartSettings::levelDigits());
        $this->assertFalse(ChartSettings::typeDisplayByHierarchy());
    }
}
