<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Nami\Finance\Http\Requests\TaxSeetingRequest;
use Nami\Finance\Services\ConfirmSettingService;
use Nami\Finance\Services\TaxAccountResolver;
use Tests\TestCase;

class TaxAccountResolverTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'tax_test');
            $app['config']->set('database.connections.tax_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'tax_test', 'database.connections.tax_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('admins', function (Blueprint $table) { $table->id(); });
        DB::table('admins')->insert(['id' => 1]);
        Schema::create('fn_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id')->nullable();
            $table->string('status');
        });
        Schema::create('fn_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('account_id');
            $table->integer('value');
            $table->integer('created_by_id')->nullable();
            $table->timestamps();
        });
        Schema::create('fn_confirm_settings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('value')->nullable();
            $table->integer('created_by_id')->nullable();
            $table->timestamps();
        });
        DB::table('fn_accounts')->insert([
            ['id' => 1, 'parent_id' => null, 'status' => 'creditor'],
            ['id' => 2, 'parent_id' => null, 'status' => 'creditor'],
            ['id' => 3, 'parent_id' => null, 'status' => 'debtor'],
            ['id' => 4, 'parent_id' => null, 'status' => 'creditor'],
            ['id' => 5, 'parent_id' => 4, 'status' => 'creditor'],
        ]);
        DB::table('fn_tax_settings')->insert(['id' => 99, 'name' => 'VAT', 'account_id' => 2, 'value' => 15]);
    }

    public function test_selected_definition_wins_over_conflicting_legacy_account_and_tracks_edits(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', 99);
        $this->assertSame(2, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
        $this->assertSame(2, app(ConfirmSettingService::class)->getConfirmSetting('revenue_tax_account')->value);
        DB::table('fn_tax_settings')->where('id', 99)->update(['account_id' => 5]);
        $this->assertSame(5, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
    }

    public function test_legacy_account_is_supported_until_a_definition_is_selected(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', null);
        $this->assertSame(1, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
    }

    public function test_invalid_selection_falls_back_to_the_configured_legacy_account(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', 99);

        // حساب مدين، حساب رئيسي، حساب غير موجود — كلها اختيارات غير صالحة،
        // فيبقى الحساب القديم المضبوط هو حساب الترحيل بدل تعطيل العملية.
        foreach ([3, 4, 999] as $invalidAccount) {
            DB::table('fn_tax_settings')->where('id', 99)->update(['account_id' => $invalidAccount]);
            $this->assertSame(1, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
        }

        DB::table('fn_tax_settings')->delete();
        $this->assertSame(1, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
    }

    public function test_no_account_is_guessed_when_nothing_valid_is_configured(): void
    {
        $this->setting('revenue_tax_account', 3);
        $this->setting('tax_rate_revenue', null);
        $this->assertNull(app(TaxAccountResolver::class)->resolve('revenue_tax_account'));
    }

    public function test_expense_definition_requires_a_debtor_leaf_account(): void
    {
        $this->setting('tax_rate_expenditure', 99);
        $this->assertNull(app(TaxAccountResolver::class)->resolve('expense_tax_account'));
        DB::table('fn_tax_settings')->update(['account_id' => 3]);
        $this->assertSame(3, app(TaxAccountResolver::class)->resolve('expense_tax_account')->id);
    }

    public function test_default_selection_rejects_wrong_account_before_saving(): void
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ConfirmSettingService::class)->storeData(['data' => [
            'tax_rate_expenditure' => ['title' => 'tax_rate_expenditure', 'value' => 99],
        ]]);
    }

    public function test_tax_definition_rejects_parent_accounts_and_out_of_range_rates(): void
    {
        $request = TaxSeetingRequest::create('/finance/tax-setting', 'POST');
        foreach ([['account_id' => 4, 'value' => 15], ['account_id' => 2, 'value' => 101], ['account_id' => 2, 'value' => -1]] as $data) {
            $this->assertTrue(Validator::make(['name' => 'VAT'] + $data, $request->rules())->fails());
        }
        $this->assertTrue(Validator::make(['name' => 'VAT', 'account_id' => 2, 'value' => 15], $request->rules())->passes());
    }

    public function test_declaration_uses_percentage_instead_of_definition_id(): void
    {
        $this->setting('tax_rate_revenue', 99);
        $controller = new \Nami\Finance\Http\Controllers\TaxDeclarationController();
        $method = new \ReflectionMethod($controller, 'settingRate');
        $method->setAccessible(true);
        $this->assertSame(15.0, $method->invoke($controller, app(ConfirmSettingService::class), 'tax_rate_revenue'));
    }

    public function test_saving_a_default_keeps_the_legacy_account_as_a_fallback(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->actAsAdmin();

        $this->storeSettings([
            ['title' => 'tax_rate_revenue', 'value' => 99],
            ['title' => 'revenue_tax_account', 'value' => 1],
        ]);

        // التعريف المختار يحكم الترحيل، والحساب القديم يبقى محفوظاً كاحتياطي.
        $this->assertSame(2, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
        $this->assertSame('1', DB::table('fn_confirm_settings')->where('title', 'revenue_tax_account')->value('value'));
    }

    public function test_resubmitting_the_settings_form_does_not_clear_the_tax_configuration(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', 99);
        $this->actAsAdmin();

        // صفحة الإعدادات ترسل كل الحقول عند حفظ أي إعداد آخر، والضريبة تصل فارغة.
        $this->storeSettings([
            ['title' => 'cost_center', 'value' => 7],
            ['title' => 'tax_rate_revenue', 'value' => null],
            ['title' => 'revenue_tax_account', 'value' => null],
        ]);

        $this->assertSame(2, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
        $this->assertSame('99', DB::table('fn_confirm_settings')->where('title', 'tax_rate_revenue')->value('value'));
        $this->assertSame('1', DB::table('fn_confirm_settings')->where('title', 'revenue_tax_account')->value('value'));
        $this->assertSame('7', DB::table('fn_confirm_settings')->where('title', 'cost_center')->value('value'));
    }

    public function test_historical_reads_cover_every_account_ever_configured(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', 99);

        // الحساب الحالي 2 والقديم 1 — تفسير القيود التاريخية يشمل الاثنين.
        $this->assertSame([2, 1], app(TaxAccountResolver::class)->historicalAccountIds('revenue_tax_account'));
        $this->assertSame(
            ['definition' => 2, 'legacy' => 1],
            app(TaxAccountResolver::class)->conflict('revenue_tax_account')
        );
    }

    public function test_active_default_cannot_be_set_to_a_zero_rate(): void
    {
        $this->setting('tax_rate_revenue', 99);
        $request = TaxSeetingRequest::create('/finance/tax-setting/99', 'PUT');
        $route = new \Illuminate\Routing\Route('PUT', 'finance/tax-setting/{tax_setting}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $this->assertTrue(Validator::make(['name' => 'VAT', 'account_id' => 2, 'value' => 0], $request->rules())->fails());
        $this->assertTrue(Validator::make(['name' => 'VAT', 'account_id' => 2, 'value' => 5], $request->rules())->passes());
    }

    public function test_editing_active_definition_cannot_switch_to_wrong_account_nature(): void
    {
        $this->setting('tax_rate_revenue', 99);
        $request = TaxSeetingRequest::create('/finance/tax-setting/99', 'PUT');
        $route = new \Illuminate\Routing\Route('PUT', 'finance/tax-setting/{tax_setting}', fn () => null);
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);
        $this->assertTrue(Validator::make(['name' => 'VAT', 'account_id' => 3, 'value' => 15], $request->rules())->fails());
    }

    public function test_migration_binds_the_definition_to_the_account_actually_in_use(): void
    {
        // الحالة الفعلية قبل الإصلاح: حسابان قديمان مضبوطان ولا تعريف ضريبة مختار،
        // والتعريف الوحيد الموجود يشير إلى حساب المصروفات فقط.
        $this->setting('revenue_tax_account', 1);
        $this->setting('expense_tax_account', 3);
        $this->setting('tax_rate_revenue', null);
        $this->setting('tax_rate_expenditure', null);
        DB::table('fn_tax_settings')->delete();
        DB::table('fn_tax_settings')->insert(['id' => 99, 'name' => 'VAT', 'account_id' => 3, 'value' => 15]);

        $this->runConsolidationMigration();

        // حساب الترحيل لم يتغيّر، لكنه صار يُقرأ من تعريف الضريبة.
        $this->assertSame(1, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
        $this->assertSame(3, app(TaxAccountResolver::class)->resolve('expense_tax_account')->id);
        $this->assertNull(app(TaxAccountResolver::class)->conflict('revenue_tax_account'));
        $this->assertNull(app(TaxAccountResolver::class)->conflict('expense_tax_account'));

        // التعريف القائم على حساب المصروفات أُعيد استخدامه بدل إنشاء نسخة مكررة.
        $this->assertSame('99', (string) DB::table('fn_confirm_settings')->where('title', 'tax_rate_expenditure')->value('value'));
        $this->assertSame(1, DB::table('fn_tax_settings')->where('account_id', 3)->count());

        // إعادة التشغيل لا تنشئ تعريفات أو صفوف إعدادات جديدة.
        $before = [DB::table('fn_tax_settings')->count(), DB::table('fn_confirm_settings')->count()];
        $this->runConsolidationMigration();
        $this->assertSame($before, [DB::table('fn_tax_settings')->count(), DB::table('fn_confirm_settings')->count()]);
    }

    public function test_migration_preserves_the_legacy_account_when_the_two_sources_disagree(): void
    {
        // التعريف المختار يشير إلى حساب مختلف عن الحساب المستخدم فعلاً (1).
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', 99);
        $this->assertSame(2, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);

        $this->runConsolidationMigration();

        // الترحيل يعيد الاختيار إلى الحساب الذي كانت العمليات تُرحَّل عليه قبل التوحيد.
        $this->assertSame(1, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
    }

    public function test_zero_rate_definition_cannot_be_selected_as_default(): void
    {
        DB::table('fn_tax_settings')->where('id', 99)->update(['value' => 0]);
        $this->actAsAdmin();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->storeSettings([['title' => 'tax_rate_revenue', 'value' => 99]]);
    }

    public function test_account_history_survives_repeated_definition_and_selection_changes(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', 99);
        $this->actAsAdmin();
        \Nami\Finance\Models\TaxSetting::withoutEvents(fn () => app(\Nami\Finance\Services\TaxSettingService::class)->update(99, ['account_id' => 5]));
        DB::table('fn_tax_settings')->insert(['id' => 100, 'name' => 'VAT', 'account_id' => 1, 'value' => 15]);
        $this->storeSettings([['title' => 'tax_rate_revenue', 'value' => 100]]);
        $ids = app(TaxAccountResolver::class)->historicalAccountIds('revenue_tax_account');
        foreach ([1, 2, 5] as $id) {
            $this->assertContains($id, $ids);
        }
        $this->assertSame(1, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
        $this->assertSame([], app(TaxAccountResolver::class)->historicalAccountIds('expense_tax_account'));
    }

    public function test_migration_preserves_rate_when_legacy_account_has_a_different_rate(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', 99);
        DB::table('fn_tax_settings')->insert(['id' => 100, 'name' => 'Old VAT', 'account_id' => 1, 'value' => 5]);
        $this->runConsolidationMigration();
        $id = DB::table('fn_confirm_settings')->where('title', 'tax_rate_revenue')->value('value');
        $this->assertSame(15, (int) DB::table('fn_tax_settings')->where('id', $id)->value('value'));
        $this->assertSame(1, (int) DB::table('fn_tax_settings')->where('id', $id)->value('account_id'));
        $this->assertSame(5, (int) DB::table('fn_tax_settings')->where('id', 100)->value('value'));
    }

    public function test_ambiguous_rate_rolls_back_both_sides(): void
    {
        $this->setting('revenue_tax_account', 1);
        $this->setting('tax_rate_revenue', 99);
        $this->setting('expense_tax_account', 3);
        DB::table('fn_tax_settings')->insert(['id' => 100, 'name' => 'Other VAT', 'account_id' => 5, 'value' => 5]);
        $before = DB::table('fn_confirm_settings')->get()->toJson();
        try {
            $this->runConsolidationMigration();
            $this->fail('Ambiguous migration must stop.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('نسبة ضريبة', $e->getMessage());
        }
        $this->assertSame($before, DB::table('fn_confirm_settings')->get()->toJson());
        $this->assertSame(2, DB::table('fn_tax_settings')->count());
    }

    public function test_company_with_different_chart_and_admin_ids_is_supported(): void
    {
        DB::table('admins')->delete();
        DB::table('admins')->insert(['id' => 777]);
        DB::table('fn_accounts')->insert(['id' => 98765, 'status' => 'creditor']);
        $this->setting('revenue_tax_account', 98765);
        $this->runConsolidationMigration();
        $this->assertSame(98765, app(TaxAccountResolver::class)->resolve('revenue_tax_account')->id);
        $this->assertSame(777, (int) DB::table('fn_tax_settings')->where('account_id', 98765)->value('created_by_id'));
    }

    public function test_already_configured_company_retains_selected_definition(): void
    {
        $this->setting('tax_rate_revenue', 99);
        $this->runConsolidationMigration();
        $this->assertSame(99, (int) DB::table('fn_confirm_settings')->where('title', 'tax_rate_revenue')->value('value'));
        $this->assertSame(1, DB::table('fn_tax_settings')->count());
    }

    public function test_unconfigured_company_is_not_assigned_an_arbitrary_account(): void
    {
        $this->runConsolidationMigration();
        $this->assertSame(0, DB::table('fn_confirm_settings')->count());
        $this->artisan('finance:tax-accounts-audit')->assertExitCode(1);
    }

    private function runConsolidationMigration(): void
    {
        (require __DIR__ . '/../../packages/finance/database/migrations/2026_09_06_130000_consolidate_tax_account_settings.php')->up();
    }

    private function actAsAdmin(): void
    {
        $admin = new \App\Models\auth\Admin();
        $admin->id = 1;
        $this->actingAs($admin, 'admin');
    }

    private function storeSettings(array $rows): void
    {
        \Nami\Finance\Models\ConfirmSetting::withoutEvents(function () use ($rows) {
            app(ConfirmSettingService::class)->storeData(['data' => $rows]);
        });
    }

    private function setting(string $title, ?int $value): void
    {
        DB::table('fn_confirm_settings')->insert(['title' => $title, 'value' => $value, 'updated_at' => now()]);
    }
}
