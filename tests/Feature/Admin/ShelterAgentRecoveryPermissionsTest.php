<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\Role;
use App\Models\auth\Admin;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ShelterAgentRecoveryPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_permissions_are_visible_and_can_be_granted_and_revoked_independently(): void
    {
        (require database_path('migrations/2026_09_13_140000_expose_shelter_agent_recovery_permissions.php'))->up();
        $admin = Admin::factory()->create();
        foreach (['permissions_view', 'permissions_assign', PermKeys::SET_PERMS] as $name) {
            $permission = Permission::withoutGlobalScopes()->updateOrCreate(['name' => $name, 'guard_name' => 'admin'],
                ['display_name' => $name, 'is_active' => true]);
            $admin->givePermissionTo($permission);
        }
        $role = Role::query()->create(['name' => 'shelter-recovery-test-' . uniqid(), 'guard_name' => 'admin']);
        $operator = Admin::factory()->create();
        $operator->assignRole($role);
        $this->actingAs($admin, 'admin');
        $this->get(route('dash.perms.setPerms', $role->id))->assertOk()
            ->assertSee('استرداد تكلفة الوكيل من الإيواء')
            ->assertSee('عكس استرداد تكلفة الوكيل')
            ->assertSee('value="shelter_agent_cost_recovery"', false)
            ->assertSee('value="shelter_agent_cost_recovery_reverse"', false);
        $names = [PermKeys::SHELTER_AGENT_COST_RECOVERY, PermKeys::SHELTER_AGENT_COST_RECOVERY_REVERSE];
        $this->post(route('dash.perms.storePerms'), ['role_id' => $role->id, 'perms' => $names])->assertRedirect();
        $this->assertTrue($operator->fresh()->can(PermKeys::SHELTER_AGENT_COST_RECOVERY));
        $this->assertTrue($operator->fresh()->can(PermKeys::SHELTER_AGENT_COST_RECOVERY_REVERSE));
        $this->post(route('dash.perms.storePerms'), ['role_id' => $role->id, 'perms' => [PermKeys::SHELTER_AGENT_COST_RECOVERY]])->assertRedirect();
        $this->assertTrue($operator->fresh()->can(PermKeys::SHELTER_AGENT_COST_RECOVERY));
        $this->assertFalse($operator->fresh()->can(PermKeys::SHELTER_AGENT_COST_RECOVERY_REVERSE));
        $this->post(route('dash.perms.storePerms'), ['role_id' => $role->id, 'perms' => []])->assertRedirect();
        $this->assertFalse($operator->fresh()->can(PermKeys::SHELTER_AGENT_COST_RECOVERY));
    }
}
