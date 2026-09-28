<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\HourlyTestCase as TestCase;

class HourlyRentalSettingsAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.connections.hourly_settings_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'hourly_settings_test',
            'superadmin_modules.hourly_rental.default_enabled' => true, 'activitylog.enabled' => false,
        ]);
        Schema::create('new_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value');
            $table->timestamps();
        });
        HourlyRentalSettings::flush();
    }

    protected function tearDown(): void
    {
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    public function test_guests_cannot_read_or_save_settings(): void
    {
        $this->get(route('dev-superadmin.hourly-rental-settings.index'))
            ->assertRedirect(route('superadmin.login'));
        $this->put(route('dev-superadmin.hourly-rental-settings.update'), HourlyRentalSettings::defaults())
            ->assertRedirect(route('superadmin.login'));
        $this->assertDatabaseCount('new_settings', 0);
    }

    public function test_employee_permissions_do_not_grant_super_admin_access(): void
    {
        $admin = \Mockery::mock(Admin::class)->makePartial();
        $admin->id = 7;
        $admin->shouldReceive('can')->andReturn(true);
        $this->actingAs($admin, 'admin');

        $this->get(route('dev-superadmin.hourly-rental-settings.index'))
            ->assertRedirect(route('superadmin.login'));
        $this->put(route('dev-superadmin.hourly-rental-settings.update'), HourlyRentalSettings::defaults())
            ->assertRedirect(route('superadmin.login'));
        $this->assertDatabaseCount('new_settings', 0);
    }

    public function test_super_admin_can_open_and_save_settings_without_an_employee_account(): void
    {
        $this->assertFalse(auth('admin')->check());
        $this->withSession(['dev_superadmin_authenticated' => true]);
        $this->get(route('dev-superadmin.hourly-rental-settings.index'))
            ->assertOk()
            ->assertSee(route('dev-superadmin.hourly-rental-settings.update'), false)
            ->assertSee('maps-key-box');

        $payload = HourlyRentalSettings::defaults();
        $payload['min_notice_hours'] = 36;
        $this->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.hourly-rental-settings.update'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dev-superadmin.hourly-rental-settings.index'));
        HourlyRentalSettings::flush();
        $this->assertSame(36, HourlyRentalSettings::all()['min_notice_hours']);
    }

    public function test_old_customer_settings_endpoints_are_removed(): void
    {
        $this->get('/admin/hourly-rental-settings')->assertNotFound();
        $this->put('/admin/hourly-rental-settings', [])->assertNotFound();
    }
    public function test_incomplete_installation_cannot_be_enabled(): void
    {
        $payload = HourlyRentalSettings::defaults();
        $payload['is_enabled'] = true;
        $this->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.hourly-rental-settings.update'), $payload)
            ->assertSessionHasErrors('is_enabled');
        $this->assertDatabaseCount('new_settings', 0);
    }

    public function test_installed_module_can_be_enabled_without_future_earnings_stage(): void
    {
        foreach (['user_addresses', 'hourly_packages', 'hourly_rental_orders', 'hourly_order_payments',
            'hourly_visits', 'hourly_visit_workers', 'hourly_worker_leaves', 'hourly_visit_batches',
            'maids', 'hourly_drivers', 'hourly_route_stops', 'location_pings',
            'hourly_customer_profiles', 'hourly_wallet_transactions', 'worker_daily_movements'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                if ($name === 'user_addresses') $table->decimal('latitude')->nullable();
                if ($name === 'maids') $table->string('rental_mode')->nullable();
                if ($name === 'hourly_rental_orders') {
                    $table->string('booking_token')->nullable();
                    $table->unsignedBigInteger('renewed_from_order_id')->nullable();
                }
            });
        }

        $payload = HourlyRentalSettings::defaults();
        $payload['is_enabled'] = true;
        $this->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.hourly-rental-settings.update'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dev-superadmin.hourly-rental-settings.index'));
        HourlyRentalSettings::flush();
        $this->assertTrue(HourlyRentalSettings::isEnabled());
        $this->assertFalse(Schema::hasTable('hourly_visit_earnings'));
    }
}
