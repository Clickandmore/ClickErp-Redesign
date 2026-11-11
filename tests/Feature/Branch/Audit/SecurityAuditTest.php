<?php

namespace Tests\Feature\Branch\Audit;

use App\Models\auth\Admin;
use App\Models\ingaz\User;
use App\Models\Permission;
use App\Models\Role;
use App\NewPermKeys;
use App\PermKeys;

/**
 * @group branch-audit
 */
class SecurityAuditTest extends BranchAuditTestCase
{
    /** @test */
    public function c1_client_details_rejects_a_client_from_another_branch(): void
    {
        [$own, $other] = [$this->makeBranch(), $this->makeBranch()];
        $admin = $this->lockedBranchAdmin($own, NewPermKeys::REC_CONTRACTS_CREATE);
        $client = User::query()->forceCreate([
            'name' => 'Other branch client',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'branch_id' => $other->id,
        ]);

        $this->actingAsAdmin($admin)
            ->getJson(route('clients.details', $client->id))
            ->assertForbidden();
    }

    /** @test */
    public function c1_client_details_never_serializes_password_or_remember_token(): void
    {
        $branch = $this->makeBranch();
        $admin = $this->lockedBranchAdmin($branch, NewPermKeys::REC_CONTRACTS_CREATE);
        $client = User::query()->forceCreate([
            'name' => 'Own branch client',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => bcrypt('secret'),
            'remember_token' => 'secret-token',
            'branch_id' => $branch->id,
        ]);

        $this->actingAsAdmin($admin)
            ->getJson(route('clients.details', $client->id))
            ->assertOk()
            ->assertJsonMissing(['password' => $client->password])
            ->assertJsonMissing(['remember_token' => 'secret-token']);
    }

    /** @test */
    public function a1_branch_admin_cannot_impersonate_general_administration(): void
    {
        $branch = $this->makeBranch();
        $actor = $this->lockedBranchAdmin($branch, PermKeys::USERS_EDIT);
        $target = $this->globalAdmin();

        $this->actingAsAdmin($actor)
            ->postJson(route('admins.impersonate', $target->id))
            ->assertForbidden();
    }

    /** @test */
    public function a2_branch_admin_cannot_assign_a_role_that_can_view_all_branches(): void
    {
        $branch = $this->makeBranch();
        $actor = $this->lockedBranchAdmin($branch, PermKeys::USERS_EDIT);
        $target = $this->branchAdmin($branch);
        $permission = Permission::findOrCreate(PermKeys::BRANCHES_VIEW_ALL, 'admin');
        $role = Role::query()->create(['name' => 'branch-audit-protected-' . uniqid(), 'guard_name' => 'admin']);
        $role->givePermissionTo($permission);

        $this->actingAsAdmin($actor)
            ->postJson(route('admins.quick-assign-role', $target->id), ['role_id' => $role->id])
            ->assertForbidden();
    }

    /** @test */
    public function a3_branch_assignment_routes_require_branch_edit_permission(): void
    {
        $branch = $this->makeBranch();
        $actor = Admin::factory()->global()->create();
        $target = Admin::factory()->global()->create();

        $this->actingAsAdmin($actor)
            ->postJson(route('branches.assign-admin', $branch), ['admin_id' => $target->id])
            ->assertForbidden();
    }

    /** @test */
    public function a8_sending_a_password_reset_link_requires_user_edit_permission(): void
    {
        $actor = Admin::factory()->global()->create();
        $target = Admin::factory()->global()->create();

        $this->actingAsAdmin($actor)
            ->postJson(route('admins.send-reset-link', $target->id))
            ->assertForbidden();
    }

    /** @test */
    public function h1_hr_employee_updates_do_not_write_account_credentials(): void
    {
        $source = file_get_contents(base_path('packages/hr/src/Services/EmployeeService.php'));

        $this->assertStringNotContainsString('$employee->admin->update($adminData)', $source);
        $this->assertStringContainsString('بيانات تسجيل الدخول تُدار حصراً من شاشة المستخدمين', $source);
    }

    /** @test */
    public function a4_branch_with_employees_cannot_be_deleted(): void
    {
        $branch = $this->makeBranch();
        $this->branchAdmin($branch);
        $actor = $this->grant($this->globalAdmin(), PermKeys::BRANCHES_DELETE);

        $this->actingAsAdmin($actor)
            ->deleteJson(route('branches.destroy', $branch))
            ->assertUnprocessable();

        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
    }
}
