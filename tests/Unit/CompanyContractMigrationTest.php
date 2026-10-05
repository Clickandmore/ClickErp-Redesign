<?php

namespace Tests\Unit;

use App\Models\CompanyProfile;
use App\Models\Contract;
use App\Models\ingaz\User;
use App\Services\CompanyContractMigrationService;
use App\Services\ShelterCompanyClientResolver;
use App\Support\CompanyContractParty;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CompanyContractMigrationTest extends TestCase
{
    public function test_company_settings_permissions_are_registered_grouped_and_enforced_independently(): void
    {
        Schema::create('permissions', function ($table) {
            $table->id(); $table->string('name'); $table->string('guard_name');
            $table->string('display_name')->nullable(); $table->text('description')->nullable();
            $table->string('group_name')->nullable(); $table->string('section_name')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('roles', function ($table) { $table->id(); $table->string('name'); $table->string('guard_name'); $table->timestamps(); });
        foreach (['model_has_permissions' => 'permission_id', 'model_has_roles' => 'role_id'] as $name => $key) {
            Schema::create($name, function ($table) use ($key) { $table->integer($key); $table->string('model_type'); $table->integer('model_id'); });
        }
        Schema::create('role_has_permissions', function ($table) { $table->integer('role_id'); $table->integer('permission_id'); });
        $migration = require database_path('migrations/2026_10_04_140000_add_company_settings_permissions.php');
        $migration->up(); $migration->up();
        $this->assertSame(5, DB::table('permissions')->where('group_name', 'company_settings')->count());
        foreach ([\App\PermKeys::SETTINGS_VIEW, \App\PermKeys::SETTINGS_CONTROL] as $name) {
            DB::table('permissions')->insert(['name' => $name, 'guard_name' => 'admin', 'is_active' => true]);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertSame('إعدادات الشركة', \App\Support\AdminPermissionNavigation::groupTitle('company_settings'));
        $permissions = DB::table('permissions')->pluck('id', 'name');
        $admin = new \App\Models\auth\Admin(); $admin->id = 7; $admin->exists = true;
        auth('admin')->setUser($admin);
        $controller = new \App\Http\Controllers\admin\CompanySettingsController();
        $run = function (string $action, array $grants) use ($controller, $admin, $permissions) {
            DB::table('model_has_permissions')->delete();
            foreach ($grants as $grant) {
                DB::table('model_has_permissions')->insert(['permission_id' => $permissions[$grant], 'model_type' => get_class($admin), 'model_id' => 7]);
            }
            $admin->unsetRelation('permissions')->unsetRelation('roles');
            $pipes = collect($controller->getMiddleware())->filter(function ($entry) use ($action) {
                $options = $entry['options'];
                return (!isset($options['only']) || in_array($action, (array) $options['only']))
                    && (!isset($options['except']) || !in_array($action, (array) $options['except']));
            })->map(function ($entry) {
                $spec = substr($entry['middleware'], strlen('permission:'));
                return function ($request, $next) use ($spec) {
                    [$permission, $guard] = explode(',', $spec);
                    return app(\Spatie\Permission\Middleware\PermissionMiddleware::class)->handle($request, $next, $permission, $guard);
                };
            })->all();
            try {
                return app(\Illuminate\Pipeline\Pipeline::class)->send(\Illuminate\Http\Request::create('/'))->through($pipes)->then(fn () => 200);
            } catch (\Spatie\Permission\Exceptions\UnauthorizedException $e) { return $e->getStatusCode(); }
        };
        $view = \App\PermKeys::COMPANY_SETTINGS_VIEW;
        $this->assertSame(403, $run('index', []));
        $this->assertSame(403, $run('index', [\App\PermKeys::SETTINGS_VIEW, \App\PermKeys::SETTINGS_CONTROL]));
        $this->assertSame(200, $run('index', [$view]));
        foreach (['update' => \App\PermKeys::COMPANY_SETTINGS_UPDATE, 'previewMigration' => \App\PermKeys::COMPANY_SETTINGS_MIGRATION_PREVIEW, 'migrate' => \App\PermKeys::COMPANY_SETTINGS_MIGRATION_EXECUTE] as $action => $permission) {
            $this->assertSame(403, $run($action, [$view]));
            $this->assertSame(403, $run($action, [$permission]));
            $this->assertSame(200, $run($action, [$view, $permission]));
            $this->assertSame(403, $run($action, [$view, \App\PermKeys::COMPANY_SETTINGS_HISTORY_VIEW]));
        }
        $this->assertSame([\App\PermKeys::COMPANY_SETTINGS_MIGRATION_EXECUTE], \App\Support\AdminRoutePermissionMatrix::resolve('company-settings.migration.execute'));
    }

    public function createApplication()
    {
        $server = $_SERVER;
        $env = $_ENV;
        $_SERVER['DB_CONNECTION'] = $_ENV['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $_ENV['DB_DATABASE'] = ':memory:';
        try { return parent::createApplication(); }
        finally { $_SERVER = $server; $_ENV = $env; }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function ($table) {
            $table->id(); $table->string('name'); $table->unsignedBigInteger('account_id')->nullable();
            $table->softDeletes(); $table->timestamps();
        });
        Schema::create('contracts', function ($table) {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status'); $table->integer('branch_id')->nullable(); $table->timestamps();
        });
        Schema::create('contract_infos', function ($table) { $table->id(); $table->unsignedBigInteger('contract_id'); });
        Schema::table('contract_infos', function ($table) { $table->string('msaned_number')->nullable(); $table->string('visa_number')->nullable(); });
        Schema::create('admins', function ($table) { $table->id(); });
        DB::table('admins')->insert(['id' => 7]);
        Schema::create('fn_accounts', function ($table) { $table->id(); $table->string('name'); $table->integer('parent_id')->nullable(); });
        DB::table('fn_accounts')->insert([['id' => 10, 'name' => 'Historical account'], ['id' => 99, 'name' => 'Company account']]);
        Schema::create('orders', function ($table) { $table->id(); $table->unsignedBigInteger('user_id'); });
        Schema::create('invoices', function ($table) {
            $table->id(); $table->unsignedBigInteger('contract_id')->nullable();
            $table->unsignedBigInteger('client_id')->nullable(); $table->decimal('total_due', 12, 2);
        });
        Schema::create('sponsorship_transfers', function ($table) {
            $table->id(); $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('old_sponsor_id'); $table->unsignedBigInteger('new_sponsor_id');
            $table->string('status'); $table->softDeletes(); $table->timestamps();
        });
        Schema::create('transfer_service_contracts', function ($table) {
            $table->id(); $table->unsignedBigInteger('sponsorship_transfer_id');
            $table->unsignedBigInteger('old_sponsor_id');
        });
        $migration = require base_path('database/migrations/2026_10_04_130000_create_company_profiles_and_contract_ownership.php');
        $migration->up();
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Legacy company A', 'account_id' => 10],
            ['id' => 2, 'name' => 'Legacy company B', 'account_id' => 20],
            ['id' => 3, 'name' => 'Other customer', 'account_id' => 30],
        ]);
        DB::table('contracts')->insert([
            ['id' => 1, 'user_id' => 1, 'status' => 'new', 'branch_id' => 1],
            ['id' => 2, 'user_id' => 2, 'status' => 'completed', 'branch_id' => 2],
            ['id' => 3, 'user_id' => 3, 'status' => 'new', 'branch_id' => 1],
        ]);
        DB::table('orders')->insert(['id' => 1, 'user_id' => 1]);
        DB::table('invoices')->insert(['id' => 1, 'contract_id' => 1, 'client_id' => 1, 'total_due' => 12345.67]);
    }

    private function company(): CompanyProfile
    {
        return CompanyProfile::create(['setting_id' => 1, 'name' => 'Configured company', 'phone' => '0111234567', 'account_id' => 99]);
    }

    public function test_preview_is_read_only_and_lists_all_selected_sources_and_branches(): void
    {
        $preview = (new CompanyContractMigrationService())->preview($this->company(), [2, 1]);
        $this->assertSame([1, 2], $preview['source_user_ids']);
        $this->assertSame([1, 2], array_column($preview['contracts'], 'id'));
        $this->assertSame(1, $preview['related']['contract_invoices']);
        $this->assertSame(3, User::count());
        $this->assertSame(0, DB::table('company_contract_migrations')->count());
    }

    public function test_migration_detaches_customer_ownership_preserves_financial_history_and_other_customers(): void
    {
        $company = $this->company();
        $service = new CompanyContractMigrationService();
        $beforeInvoice = DB::table('invoices')->first();
        $preview = $service->preview($company, [1, 2]);
        $service->migrate($company, [1, 2], $preview['fingerprint'], 7);
        $contract = DB::table('contracts')->where('id', 1)->first();
        $this->assertNull($contract->user_id);
        $this->assertSame($company->id, (int) $contract->company_profile_id);
        $this->assertSame(1, (int) $contract->legacy_company_user_id);
        $this->assertSame(10, (int) $contract->company_finance_account_id);
        $this->assertSame([3], User::query()->pluck('id')->all());
        $this->assertSame(3, DB::table('users')->count());
        $this->assertEquals($beforeInvoice, DB::table('invoices')->first());
        $this->assertSame(1, (int) DB::table('orders')->value('user_id'));
        $this->assertSame(3, (int) DB::table('contracts')->where('id', 3)->value('user_id'));
        $this->assertSame(1, DB::table('company_contract_migrations')->count());
    }

    public function test_stale_preview_rolls_back_without_archiving_or_moving_anything(): void
    {
        $company = $this->company(); $service = new CompanyContractMigrationService();
        $preview = $service->preview($company, [1]);
        DB::table('contracts')->where('id', 1)->update(['status' => 'completed']);
        try {
            $service->migrate($company, [1], $preview['fingerprint'], 7);
            $this->fail('Stale preview must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(3, User::count());
            $this->assertSame(1, (int) DB::table('contracts')->where('id', 1)->value('user_id'));
            $this->assertSame(0, DB::table('company_contract_migrations')->count());
        }
    }

    public function test_archived_sources_cannot_be_migrated_twice(): void
    {
        $company = $this->company(); $service = new CompanyContractMigrationService();
        $preview = $service->preview($company, [1]);
        $service->migrate($company, [1], $preview['fingerprint'], 7);
        $this->expectException(ValidationException::class);
        $service->migrate($company, [1], $preview['fingerprint'], 7);
    }

    public function test_company_contracts_read_company_profile_and_preserve_historical_account(): void
    {
        $company = $this->company(); $service = new CompanyContractMigrationService();
        $preview = $service->preview($company, [1]);
        $service->migrate($company, [1], $preview['fingerprint'], 7);
        $contract = Contract::findOrFail(1);
        $this->assertInstanceOf(CompanyContractParty::class, $contract->user);
        $this->assertNull($contract->user->id);
        $this->assertSame('Configured company', $contract->user->name);
        $this->assertSame(10, $contract->user->account_id);
        $this->assertSame('Configured company', $contract->toArray()['user']['name']);
        $company->update(['name' => 'Updated company']);
        $this->assertSame('Updated company', $contract->fresh()->user->name);
        $this->expectException(\LogicException::class);
        $contract->user->save();
    }

    public function test_automatic_company_ownership_never_creates_a_customer(): void
    {
        $company = $this->company();
        $ownership = (new ShelterCompanyClientResolver())->ownership();
        $this->assertSame(['company_profile_id' => $company->id, 'user_id' => null, 'company_finance_account_id' => 99], $ownership);
        $this->assertSame(3, User::count());
    }

    public function test_historical_transfers_use_company_identity_without_changing_current_customer_or_amounts(): void
    {
        DB::table('sponsorship_transfers')->insert(['id' => 1, 'contract_id' => 3, 'old_sponsor_id' => 1, 'new_sponsor_id' => 3, 'status' => 'completed']);
        DB::table('transfer_service_contracts')->insert(['id' => 1, 'sponsorship_transfer_id' => 1, 'old_sponsor_id' => 1]);
        $company = $this->company(); $service = new CompanyContractMigrationService();
        $preview = $service->preview($company, [1]);
        $this->assertCount(1, $preview['transfers']);
        $service->migrate($company, [1], $preview['fingerprint'], 7);
        $transfer = \App\Models\SponsorshipTransfer::findOrFail(1);
        $this->assertSame(1, $transfer->old_sponsor_id);
        $this->assertSame(3, $transfer->new_sponsor_id);
        $this->assertSame('Configured company', $transfer->oldSponsor->name);
        $this->assertSame(10, $transfer->oldSponsor->account_id);
        $this->assertSame(3, Contract::findOrFail(3)->user_id);
        $this->assertSame('Other customer', Contract::findOrFail(3)->user->name);
        $this->assertSame('Configured company', \App\Models\TransferServiceContract::findOrFail(1)->oldSponsor->name);
    }

    public function test_company_contract_request_needs_no_customer_and_rejects_customer_or_order_injection(): void
    {
        $company = $this->company();
        $input = ['client_type' => 'internal_company', 'company_profile_id' => $company->id, 'contract_date' => '2026-10-04', 'msaned_number' => 'M-1', 'visa_number' => 'V-1', 'contract_duration' => 24, 'marketer_id' => 7];
        $request = \App\Http\Requests\ingaz\StoreContractRequest::create('/', 'POST', $input);
        $rules = $request->rules();
        $this->assertTrue(validator($input, $rules)->passes());
        $this->assertTrue(validator($input + ['client_id' => 1], $rules)->fails());
        $this->assertTrue(validator($input + ['order_id' => 1], $rules)->fails());
        $this->assertTrue(validator(array_diff_key($input, ['company_profile_id' => true]), $rules)->fails());
    }

    public function test_invoice_account_resolution_uses_company_account_and_historical_account_without_creating_users(): void
    {
        $company = $this->company(); $service = new CompanyContractMigrationService();
        $preview = $service->preview($company, [1]);
        $service->migrate($company, [1], $preview['fingerprint'], 7);
        $method = new \ReflectionMethod(\App\Http\Controllers\ingaz\InvoiceController::class, 'ensureClientAccountId');
        $method->setAccessible(true);
        $controller = new \App\Http\Controllers\ingaz\InvoiceController();
        $this->assertSame(10, $method->invoke($controller, Contract::findOrFail(1)));
        DB::table('contracts')->insert(['id' => 4, 'status' => 'new', 'company_profile_id' => $company->id]);
        $this->assertSame(99, $method->invoke($controller, Contract::findOrFail(4)));
        $this->assertSame(3, DB::table('users')->count());
    }

    public function test_archived_company_customer_cannot_be_restored_or_deleted(): void
    {
        $company = $this->company(); $service = new CompanyContractMigrationService();
        $preview = $service->preview($company, [1]);
        $service->migrate($company, [1], $preview['fingerprint'], 7);
        foreach (['restore', 'forceDelete'] as $operation) {
            try {
                User::withTrashed()->findOrFail(1)->{$operation}();
                $this->fail('Historical company customer must be protected.');
            } catch (\LogicException $exception) {
                $this->assertSame(3, DB::table('users')->count());
                $this->assertNotNull(DB::table('users')->where('id', 1)->value('deleted_at'));
            }
        }
    }

    public function test_creating_a_company_recruitment_contract_does_not_create_a_customer_or_order(): void
    {
        $company = $this->company();
        Schema::table('admins', function ($table) { $table->string('status')->nullable(); $table->softDeletes(); });
        Schema::create('maids', function ($table) { $table->id(); $table->softDeletes(); });
        Schema::table('contracts', function ($table) {
            foreach (['maid_id', 'marketer_id', 'required_maid_nationality_id', 'required_maid_religion_id', 'required_maid_age_min', 'required_maid_age_max', 'required_maid_experience_min', 'required_maid_job_title_id'] as $field) {
                $table->integer($field)->nullable();
            }
            $table->text('required_maid_notes')->nullable(); $table->date('contract_date')->nullable();
        });
        Schema::table('contract_infos', function ($table) {
            foreach (['contract_date', 'tafwed_number', 'visa_milad_date', 'visa_hijry_date', 'contract_duration', 'manual_maid_name', 'manual_maid_passport_num', 'manual_maid_nationality_id', 'manual_maid_office_id'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('contract_status_logs', function ($table) { $table->id(); $table->integer('contract_id'); $table->string('status'); $table->timestamps(); });
        $admin = new class extends \App\Models\auth\Admin {
            public function can($abilities, $arguments = []) { return true; }
        };
        $admin->id = 7;
        $this->actingAs($admin, 'admin');
        $request = \App\Http\Requests\ingaz\StoreContractRequest::create('/', 'POST', [
            'client_type' => 'internal_company', 'company_profile_id' => $company->id,
            'contract_date' => '2026-10-04', 'msaned_number' => 'M-1', 'visa_number' => 'V-1',
            'contract_duration' => 24, 'marketer_id' => 7,
            'maid_selection_mode' => 'postponed', 'maid_selection_postponed' => '1',
        ]);
        $response = \Illuminate\Database\Eloquent\Model::withoutEvents(fn () => (new \App\Http\Controllers\ingaz\RecruitmentContractController())->store($request));
        $body = $response->getData(true);
        $this->assertSame(200, $response->getStatusCode(), json_encode($body));
        $created = DB::table('contracts')->where('id', $body['contract_id'])->first();
        $this->assertNull($created->user_id);
        $this->assertSame($company->id, (int) $created->company_profile_id);
        $this->assertSame(99, (int) $created->company_finance_account_id);
        $this->assertSame(3, DB::table('users')->count());
        $this->assertSame(1, DB::table('orders')->count());
    }

    public function test_company_settings_save_independent_profile_and_document_identity_without_creating_a_customer(): void
    {
        Schema::create('settings', function ($table) {
            $table->id();
            foreach (['phone', 'other_phone', 'email', 'tax_number', 'document_logo', 'company_license_number'] as $field) { $table->string($field)->nullable(); }
            $table->timestamps();
        });
        Schema::create('settings_translations', function ($table) {
            $table->id(); $table->integer('setting_id'); $table->string('locale');
            $table->string('document_company_name')->nullable(); $table->text('location')->nullable();
        });
        DB::table('settings')->insert(['id' => 1]);
        $request = \App\Http\Requests\ingaz\SettingRequest::create('/', 'PUT', [
            'company_name' => 'Independent company', 'company_name_en' => 'English company',
            'phone' => '0111234567', 'other_phone' => '0501234567', 'email' => 'company@example.test',
            'company_address' => 'Company address', 'company_city' => 'Riyadh',
            'tax_number' => '123456789012345', 'company_registration_number' => 'CR-123',
            'company_license_number' => 'LICENSE-123', 'company_account_id' => 99,
        ]);
        $response = \Illuminate\Database\Eloquent\Model::withoutEvents(fn () => (new \App\Http\Controllers\admin\CompanySettingsController())->update($request, new \App\Http\Actions\dashboard\SettingAction(new \App\Models\ingaz\Setting())));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Independent company', CompanyProfile::current()->name);
        $this->assertSame('CR-123', CompanyProfile::current()->registration_number);
        $this->assertSame('Independent company', documentSetting('document_company_name', null, 'ar'));
        $this->assertSame('English company', documentSetting('document_company_name', null, 'en'));
        $this->assertSame('0111234567', documentSetting('phone'));
        $this->assertSame(3, DB::table('users')->count());
        $this->assertSame(0, DB::table('company_contract_migrations')->count());
    }
}
