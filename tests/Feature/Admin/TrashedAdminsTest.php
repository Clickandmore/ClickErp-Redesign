<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * صفحة الموظفين المحذوفين: صلاحياتها المستقلة (عرض / استعادة / حذف نهائي)،
 * ورسالة التعارض الواضحة عند إنشاء مستخدم ببريد يعود لحساب محذوف.
 */
class TrashedAdminsTest extends TestCase
{
    use DatabaseTransactions;

    private function actorWith(array $permissions): Admin
    {
        foreach ([PermKeys::USERS_VIEW, PermKeys::USERS_CREATE, PermKeys::USERS_EDIT, PermKeys::USERS_DELETE, PermKeys::USERS_CONTROL, PermKeys::USERS_TRASHED_VIEW, PermKeys::USERS_RESTORE, PermKeys::USERS_FORCE_DELETE] as $name) {
            Permission::findOrCreate($name, 'admin');
        }

        $actor = Admin::factory()->create();
        if ($permissions !== []) {
            $actor->givePermissionTo($permissions);
        }

        return $actor;
    }

    private function trashedAdmin(array $attributes = []): Admin
    {
        $admin = Admin::factory()->create($attributes);
        $admin->delete();

        return $admin->fresh();
    }

    public function test_matrix_gates_the_trashed_page_and_its_buttons_with_dedicated_permissions(): void
    {
        $this->assertSame(
            [PermKeys::USERS_TRASHED_VIEW, PermKeys::USERS_CONTROL],
            AdminRoutePermissionMatrix::resolve('admins.trashed', Request::create('/'))
        );
        $this->assertSame(
            [PermKeys::USERS_RESTORE, PermKeys::USERS_CONTROL],
            AdminRoutePermissionMatrix::resolve('admins.restore', Request::create('/'))
        );
        $this->assertSame(
            [PermKeys::USERS_FORCE_DELETE, PermKeys::USERS_CONTROL],
            AdminRoutePermissionMatrix::resolve('admins.force-delete', Request::create('/'))
        );
    }

    public function test_users_delete_alone_does_not_open_the_trashed_page(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_VIEW, PermKeys::USERS_DELETE]);

        $this->actingAs($actor, 'admin')
            ->get(route('admins.trashed'))
            ->assertForbidden();
    }

    public function test_trashed_page_lists_only_soft_deleted_admins(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_TRASHED_VIEW]);
        $trashed = $this->trashedAdmin(['name' => 'Trashed Employee ' . uniqid()]);
        $active = Admin::factory()->create(['name' => 'Active Employee ' . uniqid()]);

        $this->actingAs($actor, 'admin')
            ->get(route('admins.trashed'))
            ->assertOk()
            ->assertSee(__('admin.trashed_admins.title'));

        $response = $this->actingAs($actor, 'admin')
            ->getJson(route('admins.trashed'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertContains($trashed->id, $ids);
        $this->assertNotContains($active->id, $ids);

        // بدون صلاحية الاستعادة/الحذف النهائي لا تُعرض الأزرار
        $row = collect($response->json('data'))->firstWhere('id', $trashed->id);
        $this->assertStringNotContainsString('trashed-restore-button', $row['actions']);
        $this->assertStringNotContainsString('trashed-force-delete-button', $row['actions']);
    }

    public function test_restore_requires_its_own_permission(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_VIEW, PermKeys::USERS_DELETE, PermKeys::USERS_TRASHED_VIEW]);
        $trashed = $this->trashedAdmin();

        $this->actingAs($actor, 'admin')
            ->postJson(route('admins.restore', $trashed->id))
            ->assertForbidden();

        $this->assertNotNull(Admin::withTrashed()->find($trashed->id)->deleted_at);
    }

    public function test_restore_brings_the_admin_back(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_RESTORE]);
        $trashed = $this->trashedAdmin();

        $this->actingAs($actor, 'admin')
            ->postJson(route('admins.restore', $trashed->id))
            ->assertOk()
            ->assertJson(['status' => true, 'code' => 200]);

        $this->assertNull(Admin::find($trashed->id)->deleted_at);
    }

    public function test_restore_is_blocked_when_an_active_admin_already_uses_the_same_email(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_CONTROL]);
        $email = 'shared-' . uniqid() . '@example.com';
        $trashed = $this->trashedAdmin(['email' => $email]);
        Admin::factory()->create(['email' => $email, 'name' => 'Newer Holder']);

        $this->actingAs($actor, 'admin')
            ->postJson(route('admins.restore', $trashed->id))
            ->assertStatus(422)
            ->assertJsonPath('message', __('admin.trashed_admins.restore_blocked'));

        $this->assertNotNull(Admin::withTrashed()->find($trashed->id)->deleted_at);
    }

    public function test_force_delete_requires_its_own_permission(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_VIEW, PermKeys::USERS_DELETE, PermKeys::USERS_RESTORE]);
        $trashed = $this->trashedAdmin();

        $this->actingAs($actor, 'admin')
            ->deleteJson(route('admins.force-delete', $trashed->id))
            ->assertForbidden();

        $this->assertNotNull(Admin::withTrashed()->find($trashed->id));
    }

    public function test_force_delete_removes_the_row_and_frees_the_email(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_FORCE_DELETE, PermKeys::USERS_CREATE]);
        $email = 'freed-' . uniqid() . '@example.com';
        $trashed = $this->trashedAdmin(['email' => $email]);

        $this->actingAs($actor, 'admin')
            ->deleteJson(route('admins.force-delete', $trashed->id))
            ->assertOk();

        $this->assertNull(Admin::withTrashed()->find($trashed->id));

        $role = Role::create(['name' => 'trashed-test-role-' . uniqid(), 'guard_name' => 'admin']);
        $this->actingAs($actor, 'admin')
            ->postJson(route('admins.store'), $this->validStorePayload($email, $role->id))
            ->assertOk();

        $this->assertDatabaseHas('admins', ['email' => $email, 'deleted_at' => null]);
    }

    public function test_creating_an_admin_with_a_trashed_email_explains_the_conflict_and_points_to_the_trashed_page(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_CREATE]);
        $email = 'ghost-' . uniqid() . '@example.com';
        $trashed = $this->trashedAdmin(['email' => $email, 'name' => 'Ghost Employee']);
        $role = Role::create(['name' => 'trashed-test-role-' . uniqid(), 'guard_name' => 'admin']);

        $response = $this->actingAs($actor, 'admin')
            ->postJson(route('admins.store'), $this->validStorePayload($email, $role->id))
            ->assertStatus(422);

        $expected = __('admin.unique_conflict.trashed', [
            'field' => __('auth.email'),
            'value' => $email,
            'name' => 'Ghost Employee',
            'date' => $trashed->deleted_at->format('Y-m-d'),
        ]);

        $response->assertJsonPath('errors.email.0', $expected)
            ->assertJsonPath('trashed_conflict.id', $trashed->id)
            ->assertJsonPath('trashed_conflict.field', 'email')
            ->assertJsonPath('trashed_conflict.trashed_url', route('admins.trashed', ['search' => $email]));

        $this->assertSame(1, Admin::withTrashed()->where('email', $email)->count());
    }

    public function test_creating_an_admin_with_an_active_email_names_the_current_holder(): void
    {
        $actor = $this->actorWith([PermKeys::USERS_CREATE]);
        $email = 'holder-' . uniqid() . '@example.com';
        Admin::factory()->create(['email' => $email, 'name' => 'Current Holder']);
        $role = Role::create(['name' => 'trashed-test-role-' . uniqid(), 'guard_name' => 'admin']);

        $response = $this->actingAs($actor, 'admin')
            ->postJson(route('admins.store'), $this->validStorePayload($email, $role->id))
            ->assertStatus(422);

        $response->assertJsonPath('errors.email.0', __('admin.unique_conflict.active', [
            'field' => __('auth.email'),
            'value' => $email,
            'name' => 'Current Holder',
        ]));
        $this->assertArrayNotHasKey('trashed_conflict', $response->json());
    }

    private function validStorePayload(string $email, int $roleId): array
    {
        return [
            'name' => 'New Employee',
            'phone' => '0512345678',
            'email' => $email,
            'password' => 'secret123',
            'admin_type' => 'customer_service',
            'role_id' => $roleId,
            'sex_type' => 0,
        ];
    }
}
