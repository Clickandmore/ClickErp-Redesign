<?php

namespace Tests\Unit;

use App\Models\ingaz\User;
use App\Services\ShelterCompanyClientResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShelterCompanyClientResolverTest extends TestCase
{
    public function createApplication()
    {
        $previousServer = $_SERVER;
        $previousEnv = $_ENV;
        $_SERVER['DB_CONNECTION'] = $_ENV['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = $_ENV['DB_DATABASE'] = ':memory:';
        try {
            return parent::createApplication();
        } finally {
            $_SERVER = $previousServer;
            $_ENV = $previousEnv;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->softDeletes();
        });
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Company old', 'deleted_at' => null],
            ['id' => 2, 'name' => 'Company primary', 'deleted_at' => null],
            ['id' => 3, 'name' => 'Other client', 'deleted_at' => null],
        ]);
    }

    private function resolver(?int $primary, array $legacy = []): ShelterCompanyClientResolver
    {
        return new class($primary, $legacy) extends ShelterCompanyClientResolver {
            private object $settings;
            public function __construct($primary, $legacy)
            {
                $this->settings = (object) ['company_client_id' => $primary, 'company_legacy_client_ids' => $legacy];
            }
            protected function companySettings()
            {
                return $this->settings;
            }
        };
    }

    public function test_unconfigured_site_does_not_enable_explicit_identity(): void
    {
        $this->assertSame([], $this->resolver(null, [1])->configuredClientIds());
    }

    public function test_unconfigured_site_keeps_legacy_name_filter(): void
    {
        DB::table('users')->where('id', 1)->update(['name' => config('app.name')]);
        $query = User::query();
        $this->resolver(null)->applyCompanyIdentityFilter($query);
        $this->assertSame([1], $query->pluck('id')->all());
    }

    public function test_migration_preserves_existing_settings_and_defaults_to_disabled(): void
    {
        Schema::create('settings', function ($table) {
            $table->id();
            $table->string('phone')->nullable();
        });
        DB::table('settings')->insert(['id' => 1, 'phone' => '123456789']);
        $migration = require base_path('database/migrations/2026_10_04_120000_add_company_client_identity_to_settings.php');
        $migration->up();
        $setting = DB::table('settings')->first();
        $this->assertNull($setting->company_client_id);
        $this->assertNull($setting->company_legacy_client_ids);
        $this->assertSame('123456789', $setting->phone);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('settings', 'company_client_id'));
        $this->assertSame('123456789', DB::table('settings')->value('phone'));
    }

    public function test_legacy_clients_require_an_active_primary_client(): void
    {
        $rules = (new \App\Http\Requests\ingaz\SettingRequest())->rules();
        $rules = array_intersect_key($rules, array_flip(['company_client_id', 'company_legacy_client_ids', 'company_legacy_client_ids.*']));
        $this->assertTrue(validator(['company_legacy_client_ids' => [1]], $rules)->fails());
        $this->assertTrue(validator(['company_client_id' => 2, 'company_legacy_client_ids' => [1]], $rules)->passes());
        DB::table('users')->where('id', 2)->update(['deleted_at' => now()]);
        $this->assertTrue(validator(['company_client_id' => 2], $rules)->fails());
    }

    public function test_company_settings_persist_and_other_section_updates_preserve_them(): void
    {
        Schema::create('settings', function ($table) {
            $table->id();
            $table->string('phone')->nullable();
            $table->timestamps();
        });
        $migration = require base_path('database/migrations/2026_10_04_120000_add_company_client_identity_to_settings.php');
        $migration->up();
        Schema::create('settings_translations', function ($table) {
            $table->id();
            $table->unsignedBigInteger('setting_id');
            $table->string('locale');
        });
        \App\Models\ingaz\Setting::withoutEvents(function () {
            $setting = \App\Models\ingaz\Setting::create(['phone' => '123456789']);
            $action = new \App\Http\Actions\dashboard\SettingAction(new \App\Models\ingaz\Setting());
            $action->updateSetting($setting->id, ['company_client_id' => 2, 'company_legacy_client_ids' => [1], 'company_license_number' => 'LICENSE-1']);
            $action->updateSetting($setting->id, ['phone' => '987654321']);
        });
        $stored = DB::table('settings')->first();
        $this->assertSame(2, (int) $stored->company_client_id);
        $this->assertSame([1], json_decode($stored->company_legacy_client_ids, true));
        $this->assertSame('LICENSE-1', $stored->company_license_number);
    }

    public function test_single_client_company_reuses_existing_client(): void
    {
        $resolver = $this->resolver(2);
        $this->assertSame(2, $resolver->resolve()->id);
        $this->assertSame(3, DB::table('users')->count());
    }

    public function test_updated_views_compile_to_valid_php(): void
    {
        foreach (['admin/company-settings/index', 'admin/form-settings/index', 'admin/ingaz/RecruitmentContract/index', 'admin/ingaz/RecruitmentContract/create', 'admin/ingaz/RecruitmentContract/edit', 'admin/layout/inc/sidebar'] as $view) {
            $compiled = app('blade.compiler')->compileString(file_get_contents(resource_path('views/' . $view . '.blade.php')));
            $this->assertNotEmpty(token_get_all($compiled, TOKEN_PARSE));
        }
    }

    public function test_primary_and_legacy_clients_are_included_and_unrelated_clients_are_excluded(): void
    {
        $resolver = $this->resolver(2, [1, 2, 1]);
        $query = User::query();
        $resolver->applyCompanyIdentityFilter($query);
        $this->assertEqualsCanonicalizing([1, 2], $query->pluck('id')->all());
        $this->assertSame(2, $resolver->resolve()->id);
        $this->assertSame('Company old', DB::table('users')->where('id', 1)->value('name'));
    }

    public function test_deleted_primary_does_not_create_another_company_client(): void
    {
        DB::table('users')->where('id', 2)->update(['deleted_at' => now()]);
        try {
            $this->resolver(2, [1])->resolve();
            $this->fail('A deleted configured client must not be silently replaced.');
        } catch (ModelNotFoundException $exception) {
            $this->assertSame(3, DB::table('users')->count());
        }
    }
}
