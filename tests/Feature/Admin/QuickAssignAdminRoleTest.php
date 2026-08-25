<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class QuickAssignAdminRoleTest extends TestCase
{
    use DatabaseTransactions;

    public function test_quick_action_assigns_the_selected_admin_role_by_id(): void
    {
        $actor = Admin::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate('users_edit', 'admin'));
        $target = Admin::factory()->create();
        $role = Role::create([
            'name' => 'quick-role-' . uniqid(),
            'guard_name' => 'admin',
        ]);

        $this->actingAs($actor, 'admin')
            ->postJson(route('admins.quick-assign-role', $target->id), [
                'role_id' => $role->id,
            ])
            ->assertOk()
            ->assertJson([
                'status' => true,
                'code' => 200,
                'message' => __('admin.updated_successfully'),
            ]);

        $target->refresh();
        $this->assertSame($role->id, (int) $target->role_id);
        $this->assertTrue($target->roles()->whereKey($role->id)->exists());
    }

    public function test_quick_action_rejects_a_role_from_another_guard(): void
    {
        $actor = Admin::factory()->create();
        $actor->givePermissionTo(Permission::findOrCreate('users_edit', 'admin'));
        $target = Admin::factory()->create();
        $foreignRole = Role::create([
            'name' => 'foreign-role-' . uniqid(),
            'guard_name' => 'web',
        ]);

        $this->actingAs($actor, 'admin')
            ->postJson(route('admins.quick-assign-role', $target->id), [
                'role_id' => $foreignRole->id,
            ])
            ->assertStatus(422);

        $this->assertNull($target->fresh()->role_id);
        $this->assertFalse($target->roles()->exists());
    }
}
