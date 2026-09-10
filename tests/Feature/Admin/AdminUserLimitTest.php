<?php

namespace Tests\Feature\Admin;

use App\Models\auth\Admin;
use App\PermKeys;
use App\Support\SystemModuleManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserLimitTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        SystemModuleManager::updateUserLimit(null);
        parent::tearDown();
    }

    public function test_admin_creation_is_blocked_when_the_copy_user_limit_is_reached(): void
    {
        Permission::findOrCreate(PermKeys::USERS_CREATE, 'admin');
        $actor = Admin::factory()->create();
        $actor->givePermissionTo(PermKeys::USERS_CREATE);
        $role = Role::create(['name' => 'user-limit-role-' . uniqid(), 'guard_name' => 'admin']);

        SystemModuleManager::updateUserLimit(Admin::query()->count());

        $this->actingAs($actor, 'admin')
            ->postJson(route('admins.store'), $this->validPayload($role->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors('users_limit');
    }

    public function test_admin_creation_is_allowed_below_the_copy_user_limit(): void
    {
        Permission::findOrCreate(PermKeys::USERS_CREATE, 'admin');
        $actor = Admin::factory()->create();
        $actor->givePermissionTo(PermKeys::USERS_CREATE);
        $role = Role::create(['name' => 'user-limit-role-' . uniqid(), 'guard_name' => 'admin']);
        $email = 'allowed-' . uniqid() . '@example.com';

        SystemModuleManager::updateUserLimit(Admin::query()->count() + 1);

        $this->actingAs($actor, 'admin')
            ->postJson(route('admins.store'), $this->validPayload($role->id, $email))
            ->assertOk();

        $this->assertDatabaseHas('admins', ['email' => $email, 'deleted_at' => null]);
    }

    private function validPayload(int $roleId, ?string $email = null): array
    {
        return [
            'name' => 'Limited User',
            'phone' => '05' . random_int(10000000, 99999999),
            'email' => $email ?? 'blocked-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'admin_type' => 'customer_service',
            'role_id' => $roleId,
            'sex_type' => 0,
        ];
    }
}
