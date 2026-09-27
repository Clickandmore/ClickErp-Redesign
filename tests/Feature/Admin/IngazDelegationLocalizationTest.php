<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\Models\IngazDelegation;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Tests\TestCase;

class IngazDelegationLocalizationTest extends TestCase
{
    use DatabaseTransactions;

    public function createApplication()
    {
        $previous = getenv('ROUTING_LOCALE');
        putenv('ROUTING_LOCALE=en');
        try {
            return parent::createApplication();
        } finally {
            putenv($previous === false ? 'ROUTING_LOCALE' : 'ROUTING_LOCALE=' . $previous);
        }
    }

    public function test_english_listing_renders_translated_content_and_localized_links(): void
    {
        $admin = Admin::factory()->create();
        $permission = Permission::firstOrCreate(['name' => PermKeys::INGAZ_DELEGATIONS_VIEW, 'guard_name' => 'admin']);
        $admin->givePermissionTo($permission);

        $this->actingAs($admin, 'admin')
            ->get('/en/admin/ingaz/ingaz-delegations')
            ->assertOk()
            ->assertSee('Ingaz delegations')
            ->assertSee('Delegation date from')
            ->assertSee('Invoice and transfer')
            ->assertSee('/en/admin/ingaz/ingaz-delegations', false)
            ->assertSee('en-GB.json', false)
            ->assertDontSee('إدارة تفاويض الإنجاز');
    }

    public function test_all_delegation_routes_keep_the_locale_and_authentication(): void
    {
        foreach (['index', 'create', 'show', 'edit', 'print', 'update-status', 'find-maid-by-passport', 'export.excel', 'export.pdf'] as $action) {
            $route = app('router')->getRoutes()->getByName('ingaz-delegations.' . $action);
            $this->assertStringStartsWith('en/admin/ingaz/', $route->uri());
            $this->assertContains('auth:admin', $route->gatherMiddleware());
            $this->assertContains('localeSessionRedirect', $route->gatherMiddleware());
        }

        $url = LaravelLocalization::getLocalizedURL('ar', route('ingaz-delegations.index') . '?status=executed', [], true);
        $this->assertStringContainsString('/ar/admin/ingaz/ingaz-delegations?status=executed', $url);

        // Re-register as a fresh Arabic request would, including the original unprefixed URL.
        foreach (['ar', ''] as $locale) {
            $request = Request::create('/' . ($locale ? $locale . '/' : '') . 'admin/ingaz/ingaz-delegations');
            $this->app->instance('request', $request);
            $this->app->forgetInstance(\Mcamara\LaravelLocalization\LaravelLocalization::class);
            LaravelLocalization::clearResolvedInstance('laravellocalization');
            app('router')->group(['middleware' => 'web'], base_path('routes/web.php'));
            $route = app('router')->getRoutes()->match($request);
            $this->assertSame('ingaz-delegations.index', $route->getName());
            $this->assertSame('ar', app()->getLocale());
        }
    }

    public function test_stage_labels_follow_language_changes_without_stale_cached_labels(): void
    {
        \App\Models\IngazDelegationStage::updateOrCreate(
            ['key' => IngazDelegation::STATUS_UNDER_PROCESS],
            ['name_ar' => 'تحت المعالجة', 'name_en' => 'Under Process', 'is_active' => true, 'sort_order' => 1]
        );
        IngazDelegation::clearStatusOptionsCache();
        app()->setLocale('ar');
        $arabic = IngazDelegation::statusOptions();
        app()->setLocale('en');
        $english = IngazDelegation::statusOptions();
        $this->assertSame('تحت المعالجة', $arabic[IngazDelegation::STATUS_UNDER_PROCESS]);
        $this->assertSame('Under Process', $english[IngazDelegation::STATUS_UNDER_PROCESS]);
        IngazDelegation::clearStatusOptionsCache();
    }
}
