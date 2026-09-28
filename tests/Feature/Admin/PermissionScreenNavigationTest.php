<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Permissions\PermissionsController;
use App\Models\Permission;
use App\Support\AdminPermissionNavigation;
use App\Support\PermissionLabelDictionary;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

class PermissionScreenNavigationTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function permission_screen_uses_the_sidebar_units_as_its_top_level(): void
    {
        $this->assertSame(
            ['crm', 'operations', 'hourly_rental', 'hr', 'finance', 'messages', 'settings'],
            array_keys(AdminPermissionNavigation::units())
        );
    }

    public function test_all_hourly_permissions_share_one_lane_despite_legacy_metadata(): void
    {
        $names = collect(\App\Support\AdminRoutePermissionMatrix::allPermissions())
            ->filter(fn ($name) => str_starts_with($name, 'hourly_'))->values();
        $this->assertNotEmpty($names);
        $permissions = $names->map(fn ($name) => $this->permission($name, 'settings', 'settings'));
        $controller = app(PermissionsController::class);
        $organized = $this->invoke($controller, 'organizePermissions', $permissions);
        $sections = $this->invoke($controller, 'buildPermissionMatrixSections', $organized);
        $lanes = $this->invoke($controller, 'buildPermissionMatrixLanes', $sections);

        $this->assertCount(1, $lanes);
        $this->assertSame('hourly_rental', $lanes[0]['key']);
        $this->assertSame('نظام التأجير بالساعة', $lanes[0]['title']);
        $this->assertSame($names->count(), $lanes[0]['permissions_count']);
        $groups = collect($lanes[0]['sections'][0]['groups'])->keyBy('key');
        $this->assertArrayHasKey('hourly_cities', $groups->all());
        $this->assertArrayHasKey('hourly_visits', $groups->all());
        $cityRows = $groups['hourly_cities']['rows'];
        $this->assertCount(1, $cityRows);
        $this->assertSame('المدن', $cityRows[0]['title']);
        $this->assertSame('hourly_cities_view', $cityRows[0]['crud']['read']['name']);
        $this->assertSame('hourly_cities_create', $cityRows[0]['crud']['create']['name']);
        $this->assertSame('hourly_cities_edit', $cityRows[0]['crud']['update']['name']);
        $this->assertSame(['section' => 'hourly_rental', 'group' => 'hourly_rental'],
            AdminPermissionNavigation::placementForPermission('hourly_future_view', null, null));
    }

    /** @test */
    public function legacy_and_missing_database_placements_are_normalized_to_real_screens(): void
    {
        $permissions = new Collection([
            $this->permission('clients_view', 'clients', 'clients'),
            $this->permission('clients_manage', '', ''),
            $this->permission('orders_view', 'orders', 'orders'),
            $this->permission('orders_analytics', '', ''),
            $this->permission('ingaz_delegations_view', 'modules', 'ingaz'),
            $this->permission('permissions_view', 'permissions', 'permissions'),
            $this->permission('admin_notifications_send', '', ''),
        ]);

        $controller = app(PermissionsController::class);
        $organized = $this->invoke($controller, 'organizePermissions', $permissions);
        $organized = $this->invoke($controller, 'sortOrganizedPermissions', $organized);
        $sections = $this->invoke($controller, 'buildPermissionMatrixSections', $organized);

        $groupLocations = [];
        foreach ($sections as $section) {
            foreach ($section['groups'] as $group) {
                $groupLocations[$group['key']][] = $section['key'];
            }
        }

        $this->assertSame(['clients'], $groupLocations['clients']);
        $this->assertSame(['orders'], $groupLocations['orders']);
        $this->assertSame(['recruitment_contracts'], $groupLocations['ingaz_delegations']);
        $this->assertSame(['users_management'], $groupLocations['permissions']);
        $this->assertSame(['messages'], $groupLocations['admin_notifications']);
    }

    /** @test */
    public function normalization_migration_persists_complete_arabic_metadata(): void
    {
        $migration = require database_path('migrations/2026_09_24_150000_normalize_admin_permission_navigation.php');
        $migration->up();

        $unclassified = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where(fn ($query) => $query->whereNull('section_name')->orWhere('section_name', '')
                ->orWhereNull('group_name')->orWhere('group_name', ''))
            ->count();
        $unnamed = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->where(fn ($query) => $query->whereNull('display_name')->orWhere('display_name', ''))
            ->count();

        $this->assertSame(0, $unclassified);
        $this->assertSame(0, $unnamed);
        $this->assertSame(
            'الرد على الشكاوى',
            DB::table('permissions')->where('name', 'complaint_response')->value('display_name')
        );
    }

    /** @test */
    public function every_admin_permission_label_is_fully_arabic(): void
    {
        $migration = require database_path('migrations/2026_09_24_160000_translate_admin_permission_display_names.php');
        $migration->up();

        $leaks = DB::table('permissions')
            ->where('guard_name', 'admin')
            ->pluck('display_name', 'name')
            ->filter(fn ($label) => PermissionLabelDictionary::needsTranslation($label));

        $this->assertSame(
            [],
            $leaks->all(),
            'مسميات صلاحيات ما زالت تحمل نصاً إنجليزياً: ' . $leaks->keys()->implode(' , ')
        );
    }

    /** @test */
    public function dictionary_translates_known_finance_and_hr_resources(): void
    {
        $this->assertSame('عرض ميزان المراجعة', PermissionLabelDictionary::label('finance_trail_balance_view'));
        $this->assertSame('حذف سندات القبض', PermissionLabelDictionary::label('finance_qabd_bonds_delete'));
        $this->assertSame('إضافة الموظفين دفعة واحدة', PermissionLabelDictionary::label('hr_mluti_employee_create'));
        $this->assertSame('عرض متابعاتي', PermissionLabelDictionary::label('crm_my_followups_view'));
        $this->assertSame('عرض الشكاوى', PermissionLabelDictionary::label('complaints_read'));
    }

    private function permission(string $name, string $section, string $group): Permission
    {
        return new Permission([
            'name' => $name,
            'guard_name' => 'admin',
            'section_name' => $section,
            'group_name' => $group,
        ]);
    }

    private function invoke(object $target, string $method, ...$arguments)
    {
        $reflection = new ReflectionMethod($target, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($target, ...$arguments);
    }
}
