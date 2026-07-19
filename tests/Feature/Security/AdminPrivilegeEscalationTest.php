<?php

namespace Tests\Feature\Security;

use App\Models\auth\Admin;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Probes whether a low-privilege admin can reach the account-management endpoints.
 *
 * AdminController::__construct only gates index/create/store/edit/update/destroy/restore/
 * forceDelete, and AdminRoutePermissionMatrix has no entry for these four route names —
 * so EnsureAdminRoutePermission::resolve() returns [] and lets the request through.
 */
class AdminPrivilegeEscalationTest extends TestCase
{
    use DatabaseTransactions;

    private function lowPrivilegeAdmin(): Admin
    {
        // No roles, no permissions at all.
        return Admin::factory()->create();
    }

    /** @test */
    public function a_permissionless_admin_cannot_impersonate_another_admin(): void
    {
        $attacker = $this->lowPrivilegeAdmin();
        $victim = Admin::factory()->superAdmin()->create();

        $this->actingAs($attacker, 'admin')
            ->post(route('admins.impersonate', $victim->id))
            ->assertForbidden();
    }

    /** @test */
    public function a_permissionless_admin_cannot_reset_another_admins_password(): void
    {
        $attacker = $this->lowPrivilegeAdmin();
        $victim = Admin::factory()->superAdmin()->create();

        $this->actingAs($attacker, 'admin')
            ->post(route('admins.reset-password', $victim->id), [
                'password' => 'hijacked123',
                'password_confirmation' => 'hijacked123',
            ])
            ->assertForbidden();
    }

    /** @test */
    public function a_permissionless_admin_cannot_suspend_another_admin(): void
    {
        $attacker = $this->lowPrivilegeAdmin();
        $victim = Admin::factory()->superAdmin()->create();

        $this->actingAs($attacker, 'admin')
            ->post(route('admins.toggle-status', $victim->id))
            ->assertForbidden();
    }

    /** @test */
    public function a_permissionless_admin_cannot_terminate_another_admins_session(): void
    {
        $attacker = $this->lowPrivilegeAdmin();
        $victim = Admin::factory()->superAdmin()->create();

        $this->actingAs($attacker, 'admin')
            ->post(route('admins.terminate-session', $victim->id))
            ->assertForbidden();
    }
}
