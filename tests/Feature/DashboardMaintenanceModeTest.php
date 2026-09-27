<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Support\DashboardMaintenanceSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardMaintenanceModeTest extends TestCase
{
    private const SETTINGS_KEY = 'dashboard_maintenance';

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['router']
            ->get('/_tests/dashboard-maintenance', fn () => response('dashboard available'))
            ->middleware(['web', 'admin']);

        $this->setMaintenance(false);
    }

    protected function tearDown(): void
    {
        DB::table('dev_superadmin_settings')->where('key', self::SETTINGS_KEY)->delete();
        Cache::forget('superadmin.dashboard_maintenance');

        parent::tearDown();
    }

    public function test_authenticated_employee_sees_maintenance_page_when_enabled(): void
    {
        $admin = Admin::factory()->create();
        $this->setMaintenance(true, 'نجري الآن تحديثًا مهمًا وسيعود النظام قريبًا.');

        $this->actingAs($admin, 'admin')
            ->get('/_tests/dashboard-maintenance')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '300')
            ->assertSee('النظام تحت التحديث')
            ->assertSee('نجري الآن تحديثًا مهمًا وسيعود النظام قريبًا.');
    }

    public function test_dashboard_remains_available_when_maintenance_is_disabled(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin, 'admin')
            ->get('/_tests/dashboard-maintenance')
            ->assertOk()
            ->assertSee('dashboard available');
    }

    public function test_dev_superadmin_session_does_not_bypass_dashboard_maintenance(): void
    {
        $admin = Admin::factory()->create();
        $this->setMaintenance(true, 'لوحة الموظفين مغلقة أثناء التحديث.');

        $this->actingAs($admin, 'admin')
            ->withSession(['dev_superadmin_authenticated' => true])
            ->get('/_tests/dashboard-maintenance')
            ->assertStatus(503)
            ->assertSee('النظام تحت التحديث')
            ->assertSee('لوحة الموظفين مغلقة أثناء التحديث.');
    }

    public function test_ajax_request_receives_service_unavailable_json(): void
    {
        $admin = Admin::factory()->create();
        $this->setMaintenance(true, 'التحديث جارٍ الآن.');

        $this->actingAs($admin, 'admin')
            ->getJson('/_tests/dashboard-maintenance')
            ->assertStatus(503)
            ->assertJson([
                'maintenance' => true,
                'message' => 'التحديث جارٍ الآن.',
            ]);
    }

    public function test_modules_page_shows_maintenance_control(): void
    {
        $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('dev-superadmin.modules'))
            ->assertOk()
            ->assertSee('حالة دخول الموظفين')
            ->assertSee('تفعيل وضع التحديث وإغلاق اللوحة');
    }

    public function test_dev_superadmin_can_enable_maintenance_with_a_custom_message(): void
    {
        $message = 'سيتم تحديث النظام الليلة وإتاحته للموظفين بعد اكتمال العمل.';

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.maintenance-mode.update'), [
                'enabled' => '1',
                'message' => $message,
            ])
            ->assertRedirect(route('dev-superadmin.modules'));

        $this->assertSame([
            'enabled' => true,
            'message' => $message,
        ], DashboardMaintenanceSettings::all());
    }

    private function setMaintenance(bool $enabled, ?string $message = null): void
    {
        DB::table('dev_superadmin_settings')->updateOrInsert(
            ['key' => self::SETTINGS_KEY],
            [
                'value' => json_encode([
                    'enabled' => $enabled,
                    'message' => $message ?? DashboardMaintenanceSettings::all()['message'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Cache::forget('superadmin.dashboard_maintenance');
    }
}
