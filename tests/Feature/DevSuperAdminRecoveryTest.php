<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DevSuperAdminRecoveryTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function the_standalone_superadmin_login_is_reachable(): void
    {
        $this->get(route('superadmin.login'))
            ->assertOk()
            ->assertSee('لوحة Super Admin التطويرية');
    }

    /** @test */
    public function recovery_requires_the_independent_superadmin_session(): void
    {
        $this->get(route('dev-superadmin.recovery'))
            ->assertRedirect(route('superadmin.login'));
    }

    /** @test */
    public function authenticated_superadmin_can_open_database_recovery(): void
    {
        $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('dev-superadmin.recovery'))
            ->assertOk()
            ->assertSee('طبقة استعادة ClickERP')
            ->assertSee('رقع قاعدة البيانات')
            ->assertSee((string) config('database.connections.' . config('database.default') . '.database'));
    }

    /** @test */
    public function migrations_cannot_run_without_database_confirmation_and_password(): void
    {
        $this->withSession(['dev_superadmin_authenticated' => true])
            ->post(route('dev-superadmin.recovery.migrate'), [])
            ->assertSessionHasErrors(['confirmation', 'current_password']);
    }
}
