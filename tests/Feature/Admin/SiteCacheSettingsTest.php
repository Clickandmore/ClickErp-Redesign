<?php

namespace Tests\Feature\Admin;

use App\Models\ingaz\Setting;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\ExternalSiteCacheSettings;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SiteCacheSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::query()->where('key', ExternalSiteCacheSettings::STORAGE_KEY)->delete();
    }

    public function test_api_returns_safe_defaults_before_anything_is_saved(): void
    {
        $this->getJson('/api/client/site-cache-settings')
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.version', 1)
            ->assertJsonPath('data.ttl.settings', 300)
            ->assertJsonPath('data.ttl.home', 120)
            ->assertJsonPath('data.ttl.workers', 120)
            ->assertJsonPath('data.ttl.content', 300)
            ->assertJsonPath('data.stale.enabled', true)
            ->assertJsonPath('data.stale.ttl', 86400)
            ->assertJsonPath('data.failure_backoff', 30)
            ->assertJsonPath('data.timeout', 8)
            ->assertJsonPath('data.connect_timeout', 3)
            ->assertJsonPath('data.refresh', ExternalSiteCacheSettings::SITE_REFRESH_SECONDS);
    }

    public function test_saved_values_are_clamped_and_exposed_in_seconds(): void
    {
        ExternalSiteCacheSettings::save([
            'enabled' => false,
            'stale_fallback_enabled' => true,
            'settings_ttl_minutes' => 10,
            'home_ttl_minutes' => 99999,
            'workers_ttl_minutes' => 0,
            'content_ttl_minutes' => 15,
            'stale_ttl_hours' => 48,
            'failure_backoff_seconds' => 60,
            'request_timeout_seconds' => 5,
            'connect_timeout_seconds' => 2,
        ]);

        $this->getJson('/api/client/site-cache-settings')
            ->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.ttl.settings', 600)
            ->assertJsonPath('data.ttl.home', 20 * 60)
            ->assertJsonPath('data.ttl.workers', 60)
            ->assertJsonPath('data.ttl.content', 900)
            ->assertJsonPath('data.stale.ttl', 48 * 3600)
            ->assertJsonPath('data.failure_backoff', 60)
            ->assertJsonPath('data.timeout', 5)
            ->assertJsonPath('data.connect_timeout', 2);
    }

    public function test_flush_bumps_the_version_and_saving_keeps_it(): void
    {
        $this->assertSame(2, ExternalSiteCacheSettings::flushSites());
        $this->assertSame(3, ExternalSiteCacheSettings::flushSites());
        $this->assertNotNull(ExternalSiteCacheSettings::all()['flushed_at']);

        ExternalSiteCacheSettings::save(['enabled' => true] + $this->validForm());

        $this->getJson('/api/client/site-cache-settings')->assertJsonPath('data.version', 3);
    }

    public function test_corrupt_stored_value_falls_back_to_defaults(): void
    {
        Setting::setValue(ExternalSiteCacheSettings::STORAGE_KEY, 'not-json', ExternalSiteCacheSettings::GROUP);

        $this->assertSame(ExternalSiteCacheSettings::DEFAULTS, ExternalSiteCacheSettings::all());
    }

    public function test_admin_routes_are_gated_for_settings_and_website_managers(): void
    {
        $this->assertSame(
            [PermKeys::SETTINGS_VIEW, PermKeys::WEBSITE_SETTINGS_CONTROL],
            AdminRoutePermissionMatrix::resolve('settings.site-cache.edit')
        );

        foreach (['settings.site-cache.update', 'settings.site-cache.flush'] as $route) {
            $this->assertSame(
                [PermKeys::SETTINGS_CONTROL, PermKeys::WEBSITE_SETTINGS_CONTROL],
                AdminRoutePermissionMatrix::resolve($route)
            );
        }
    }

    public function test_website_manager_can_open_save_and_flush_from_the_page(): void
    {
        $this->actingAsAdminWith([PermKeys::WEBSITE_SETTINGS_CONTROL]);

        $this->get(route('settings.site-cache.edit'))
            ->assertOk()
            ->assertSee('كاش المواقع الخارجية')
            ->assertSee(route('settings.site-cache.flush'), false);

        $this->put(route('settings.site-cache.update'), ['enabled' => '0'] + $this->validForm())
            ->assertRedirect(route('settings.site-cache.edit'));
        $this->assertFalse(ExternalSiteCacheSettings::all()['enabled']);

        $this->post(route('settings.site-cache.flush'))->assertRedirect(route('settings.site-cache.edit'));
        $this->assertSame(2, ExternalSiteCacheSettings::all()['version']);
    }

    public function test_connect_timeout_cannot_exceed_request_timeout(): void
    {
        $this->actingAsAdminWith([PermKeys::SETTINGS_CONTROL, PermKeys::SETTINGS_VIEW]);

        $this->from(route('settings.site-cache.edit'))
            ->put(route('settings.site-cache.update'), [
                'request_timeout_seconds' => 3,
                'connect_timeout_seconds' => 5,
            ] + $this->validForm())
            ->assertSessionHasErrors('connect_timeout_seconds');

        $this->assertNull(Setting::getValue(ExternalSiteCacheSettings::STORAGE_KEY));
    }

    public function test_admin_without_settings_permissions_cannot_change_or_flush(): void
    {
        $this->actingAsAdminWith([]);

        $this->put(route('settings.site-cache.update'), $this->validForm())->assertForbidden();
        $this->post(route('settings.site-cache.flush'))->assertForbidden();

        $this->assertNull(Setting::getValue(ExternalSiteCacheSettings::STORAGE_KEY));
    }

    private function actingAsAdminWith(array $permissions): void
    {
        foreach ([PermKeys::SETTINGS_VIEW, PermKeys::SETTINGS_CONTROL, PermKeys::WEBSITE_SETTINGS_CONTROL] as $name) {
            \Spatie\Permission\Models\Permission::findOrCreate($name, 'admin');
        }

        $admin = \App\Models\auth\Admin::factory()->create();
        $admin->givePermissionTo($permissions);
        $this->actingAs($admin, 'admin');
    }

    private function validForm(): array
    {
        return [
            'stale_fallback_enabled' => true,
            'settings_ttl_minutes' => 5,
            'home_ttl_minutes' => 2,
            'workers_ttl_minutes' => 2,
            'content_ttl_minutes' => 5,
            'stale_ttl_hours' => 24,
            'failure_backoff_seconds' => 30,
            'request_timeout_seconds' => 8,
            'connect_timeout_seconds' => 3,
        ];
    }
}
