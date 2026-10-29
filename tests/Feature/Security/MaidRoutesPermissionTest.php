<?php

namespace Tests\Feature\Security;

use App\Models\auth\Admin;
use App\Models\Permission;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * مسارات العاملات: كل مسار في المصفوفة، والسيرة السريعة لا تُحفظ إلا بصلاحية «إضافة سيرة»
 * وتُحفظ بها كما كانت.
 */
class MaidRoutesPermissionTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(string ...$permissions): Admin
    {
        $admin = Admin::factory()->create();
        foreach ($permissions as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'admin'));
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    /** @test */
    public function every_admin_maid_route_has_a_matrix_entry(): void
    {
        $missing = collect(app('router')->getRoutes())
            // middleware() لا يحمّل الكنترولر (يوجد مسار قديم لكنترولر محذوف يُسقط gatherMiddleware).
            ->filter(fn ($route) => in_array('admin.route.permission', (array) $route->middleware(), true))
            ->map(fn ($route) => $route->getName())
            ->filter(fn ($name) => $name && (str_starts_with($name, 'maids.') || $name === 'showMaidDetails'))
            ->filter(fn ($name) => AdminRoutePermissionMatrix::resolve($name) === [])
            ->values()->all();

        $this->assertSame([], $missing);
    }

    /** @test */
    public function bulk_import_screens_are_no_longer_open_to_everyone(): void
    {
        $this->assertSame([PermKeys::MAIDS_CREATE, PermKeys::DATA_IMPORT_UPLOAD], AdminRoutePermissionMatrix::resolve('maids.bulk-import.create'));
        $this->assertSame([PermKeys::MAIDS_CREATE, PermKeys::DATA_IMPORT_UPLOAD], AdminRoutePermissionMatrix::resolve('maids.bulk-import.template'));
    }

    /** @test */
    public function the_rapid_cv_screen_still_saves_for_admins_who_may_add_cvs_only(): void
    {
        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $jobType = DB::table('job_types')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $jobTitle = DB::table('job_titles')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $office = DB::table('recruitment_offices')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $passport = 'R' . random_int(1000000, 9999999);
        $payload = [
            'name:en' => 'Rapid Screen', 'gender' => 'female', 'nationality_id' => $nationality, 'religion_id' => $religion,
            'passport_num' => $passport, 'type_id' => $jobType, 'job_id' => [$jobTitle], 'office_id' => $office,
        ];

        $this->actingAs($this->admin(PermKeys::MAIDS_VIEW), 'admin')
            ->postJson(route('maids.store_rapid_cv'), $payload)
            ->assertStatus(403);
        $this->assertDatabaseMissing('maids', ['passport_num' => $passport]);

        $creator = $this->admin(PermKeys::MAIDS_CREATE);
        $this->actingAs($creator, 'admin')
            ->postJson(route('maids.store_rapid_cv'), $payload)
            ->assertOk()
            ->assertJsonPath('code', 200);
        $this->assertDatabaseHas('maids', ['passport_num' => $passport, 'name' => 'Rapid Screen', 'status' => 'available', 'added_by' => $creator->id]);
    }
}
