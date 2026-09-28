<?php

namespace Tests\Feature;

use App\Support\AdminRoutePermissionMatrix;
use App\Support\PermissionLabelDictionary;
use Tests\HourlyTestCase;

class HourlyPermissionCoverageTest extends HourlyTestCase
{
    public function test_read_only_city_access_cannot_edit_cities_or_open_other_pages(): void
    {
        config(['database.connections.permission_guard' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'permission_guard', 'superadmin_modules.hourly_rental.default_enabled' => true]);
        $admin = \Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id = 99;
        $admin->shouldReceive('hasAnyPermission')->andReturnUsing(fn ($names) => in_array('hourly_cities_view', $names));
        $admin->shouldReceive('can')->andReturnUsing(fn ($name) => $name === 'hourly_cities_view');
        $this->actingAs($admin, 'admin');
        foreach (['admin.hourly-cities.index' => 200, 'admin.hourly-cities.update' => 403,
            'admin.hourly-districts.index' => 403, 'admin.hourly-teams.index' => 403,
            'admin.hourly-maids.login-as' => 403] as $name => $status) {
            $route = app('router')->getRoutes()->getByName($name);
            $request = \Illuminate\Http\Request::create('/admin/hourly/cities', 'GET');
            $request->headers->set('Accept', 'application/json');
            $request->setRouteResolver(fn () => $route);
            $response = app(\App\Http\Middleware\EnsureAdminRoutePermission::class)->handle($request, fn () => response('allowed'));
            $this->assertSame($status, $response->getStatusCode(), $name);
        }
        $this->assertTrue(\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-cities.index'));
        $this->assertFalse(\App\Support\HourlyPermissionCatalog::canRoute('admin.hourly-districts.index'));
    }

    public function test_page_permissions_are_independent_of_legacy_parent_pages(): void
    {
        $expected = [
            'admin.hourly-cities.index' => 'hourly_cities_view',
            'admin.hourly-districts.store' => 'hourly_districts_create',
            'admin.hourly-renewals.index' => 'hourly_renewals_view',
            'admin.hourly-schedule.index' => 'hourly_schedule_view',
            'admin.hourly-schedule.settings' => 'hourly_schedule_settings',
            'admin.hourly-sos.index' => 'hourly_sos_view',
            'admin.hourly-visits.index' => 'hourly_visits_view',
            'admin.hourly-visits.unassigned' => 'hourly_unassigned_visits_view',
            'admin.hourly-teams.map' => 'hourly_team_map_view',
            'admin.hourly-package-tiers.index' => 'hourly_package_tiers_view',
            'admin.hourly-customer-groups.store' => 'hourly_customer_groups_create',
            'admin.hourly-orders.operations' => 'hourly_orders_edit',
        ];
        foreach ($expected as $route => $permission) {
            $this->assertSame([$permission], AdminRoutePermissionMatrix::resolve($route), $route);
        }
    }

    public function test_migration_preserves_role_user_and_branch_grants_without_runtime_fallback(): void
    {
        config(['database.connections.permission_split' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'database.default' => 'permission_split']);
        $schema = \Illuminate\Support\Facades\Schema::getFacadeRoot();
        $schema->create('permissions', function ($table) { $table->id(); $table->string('name'); $table->string('guard_name'); $table->string('display_name')->nullable(); $table->string('group_name')->nullable(); $table->string('section_name')->nullable(); });
        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $name) {
            $schema->create($name, function ($table) use ($name) {
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger($name === 'model_has_permissions' ? 'model_id' : 'role_id');
                if ($name === 'model_has_permissions') $table->string('model_type');
                if ($name === 'role_branch_permissions') $table->unsignedBigInteger('branch_id');
            });
        }
        $db = \Illuminate\Support\Facades\DB::getFacadeRoot();
        $db->table('permissions')->insert([
            ['id' => 1, 'name' => 'hourly_teams_view', 'guard_name' => 'admin'],
            ['id' => 2, 'name' => 'hourly_orders_collect', 'guard_name' => 'admin'],
        ]);
        $db->table('role_has_permissions')->insert(['role_id' => 7, 'permission_id' => 1]);
        $db->table('role_has_permissions')->insert(['role_id' => 8, 'permission_id' => 2]);
        $db->table('model_has_permissions')->insert(['model_id' => 9, 'model_type' => 'admin', 'permission_id' => 1]);
        $db->table('role_branch_permissions')->insert(['role_id' => 7, 'branch_id' => 3, 'permission_id' => 1]);
        $migration = require database_path('migrations/2026_09_28_180000_split_hourly_page_permissions.php');
        $migration->up();
        $cityId = $db->table('permissions')->where('name', 'hourly_cities_view')->value('id');
        $this->assertDatabaseHas('role_has_permissions', ['role_id' => 7, 'permission_id' => $cityId]);
        $this->assertDatabaseHas('model_has_permissions', ['model_id' => 9, 'permission_id' => $cityId]);
        $this->assertDatabaseHas('role_branch_permissions', ['role_id' => 7, 'branch_id' => 3, 'permission_id' => $cityId]);
        $editId = $db->table('permissions')->where('name', 'hourly_visits_edit')->value('id');
        $this->assertDatabaseMissing('role_has_permissions', ['role_id' => 8, 'permission_id' => $editId]);
        $db->table('role_has_permissions')->where('role_id', 7)->where('permission_id', $cityId)->delete();
        $migration->up();
        $this->assertDatabaseMissing('role_has_permissions', ['role_id' => 7, 'permission_id' => $cityId]);
    }

    public function test_every_hourly_admin_route_has_permission_enforcement(): void
    {
        $count = 0;
        foreach (app('router')->getRoutes() as $route) {
            if (!str_starts_with($route->getName() ?? '', 'admin.hourly-')) continue;
            $count++;
            $this->assertContains('admin.route.permission', $route->gatherMiddleware(), $route->getName());
            $this->assertNotEmpty(AdminRoutePermissionMatrix::resolve($route->getName()), $route->getName());
        }
        $this->assertGreaterThan(0, $count);
    }

    public function test_every_hourly_permission_has_an_arabic_label_and_resource(): void
    {
        $names = array_filter(AdminRoutePermissionMatrix::allPermissions(), fn ($name) => str_starts_with($name, 'hourly_'));
        $this->assertNotEmpty($names);
        foreach ($names as $name) {
            $this->assertFalse(PermissionLabelDictionary::needsTranslation(PermissionLabelDictionary::label($name)), $name);
            $resource = PermissionLabelDictionary::split($name)['resource'];
            $this->assertFalse(PermissionLabelDictionary::needsTranslation(PermissionLabelDictionary::resourceLabel($resource)), $name);
        }
        $this->assertSame('عرض طلبات التأجير بالساعة', PermissionLabelDictionary::label('hourly_orders_view'));
        $this->assertSame('الدخول إلى تطبيق السائق نيابة عنه', PermissionLabelDictionary::label('hourly_drivers_impersonate'));
    }
}
