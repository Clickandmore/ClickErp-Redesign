<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\EnsureAdminRoutePermission;
use App\Http\Requests\ingaz\SettingRequest;
use App\Models\auth\Admin;
use App\PermKeys;
use App\Support\WebsiteSettingsAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class WebsiteSettingsPermissionTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'website_permission_test');
            $app['config']->set('database.connections.website_permission_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Route::middleware(EnsureAdminRoutePermission::class)->group(function () {
            Route::get('/_test/website-settings/{section}', fn () => response()->json(['ok' => true]))
                ->name('settings.section');
            Route::put('/_test/website-settings', fn (SettingRequest $request) => response()->json(['ok' => true]))
                ->name('settings.update');
            Route::get('/_test/internal-settings', fn () => response()->json(['ok' => true]))
                ->name('dash.settings');
        });
    }

    protected function tearDown(): void
    {
        DB::purge('website_permission_test');
        parent::tearDown();
    }

    private function grant(array $permissions): void
    {
        $admin = Mockery::mock(Admin::class)->makePartial();
        $admin->shouldReceive('hasAnyPermission')->andReturnUsing(
            fn ($required) => count(array_intersect($permissions, (array) $required)) > 0
        );
        $admin->shouldReceive('can')->andReturn(false);
        $this->app['auth']->guard('admin')->setUser($admin);
    }

    public function test_website_manager_can_open_external_sections_but_not_internal_settings(): void
    {
        $this->grant([PermKeys::WEBSITE_SETTINGS_CONTROL]);
        foreach (WebsiteSettingsAccess::SECTIONS as $section) {
            $this->getJson('/_test/website-settings/' . $section)->assertOk();
        }
        foreach (['dashboard', 'translations', 'unknown'] as $section) {
            $this->getJson('/_test/website-settings/' . $section)->assertForbidden();
        }
        $this->getJson('/_test/internal-settings')->assertForbidden();
    }

    public function test_website_manager_can_save_seo_but_cannot_submit_internal_or_mixed_fields(): void
    {
        $this->grant([PermKeys::WEBSITE_SETTINGS_CONTROL]);
        $seo = ['meta_title:ar' => 'عنوان الموقع', 'seo_sitemap_cache_minutes' => 60];
        $this->putJson('/_test/website-settings', $seo)->assertOk();
        foreach (['admin_quick_links', 'admin_logo', 'dashboard_footer_url', 'rental_cv_transfer_enabled', 'delivery_form_title:ar', 'ar'] as $field) {
            $this->putJson('/_test/website-settings', $seo + [$field => 'unauthorized'])->assertForbidden();
        }
    }

    public function test_view_only_and_blog_permissions_do_not_allow_settings_writes(): void
    {
        foreach ([PermKeys::SETTINGS_VIEW, PermKeys::BLOGS_CONTROL] as $permission) {
            $this->grant([$permission]);
            $this->putJson('/_test/website-settings', ['meta_title:ar' => 'عنوان'])->assertForbidden();
        }
    }

    public function test_existing_settings_control_can_still_save_internal_fields(): void
    {
        $this->grant([PermKeys::SETTINGS_CONTROL]);
        $this->putJson('/_test/website-settings', ['dashboard_footer_url' => 'https://example.com'])->assertOk();
    }
}
