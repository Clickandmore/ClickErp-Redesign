<?php

namespace Tests\Feature;

use App\Support\DevSuperAdminSecuritySettings;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class DevSuperAdminLoginRedirectTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dev_superadmin.username' => 'redirect-test-admin',
            'dev_superadmin.password' => 'RedirectTest!123',
            'dev_superadmin.security_file' => sys_get_temp_dir() . '/superadmin-redirect-' . uniqid() . '.enc',
        ]);
        DevSuperAdminSecuritySettings::resetCache();
    }

    protected function tearDown(): void
    {
        DevSuperAdminSecuritySettings::resetCache();

        parent::tearDown();
    }

    public function test_login_opens_modules_when_core_tables_exist(): void
    {
        $this->mockSchema()->shouldReceive('hasTable')->andReturn(true);

        $this->post(route('superadmin.login.post'), $this->credentials())
            ->assertRedirect(route('dev-superadmin.modules'))
            ->assertSessionHas('dev_superadmin_authenticated', true);
    }

    public function test_authenticated_login_form_opens_modules_when_healthy(): void
    {
        $this->mockSchema()->shouldReceive('hasTable')->andReturn(true);

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('superadmin.login'))
            ->assertRedirect(route('dev-superadmin.modules'));
    }

    public function test_login_opens_recovery_when_a_core_table_is_missing(): void
    {
        $this->mockSchema()->shouldReceive('hasTable')->andReturnUsing(fn ($table) => $table !== 'users');

        $this->post(route('superadmin.login.post'), $this->credentials())
            ->assertRedirect(route('dev-superadmin.recovery'));
    }

    public function test_authenticated_login_opens_recovery_when_database_is_unavailable(): void
    {
        DB::shouldReceive('connection')->andThrow(new \RuntimeException('Database unavailable'));

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('superadmin.login'))
            ->assertRedirect(route('dev-superadmin.recovery'));
    }

    private function mockSchema(): \Mockery\MockInterface
    {
        $schema = Mockery::mock(\Illuminate\Database\Schema\MySqlBuilder::class);
        $connection = Mockery::mock(\Illuminate\Database\MySqlConnection::class);
        $connection->shouldReceive('getPdo')->andReturn(null);
        $connection->shouldReceive('getSchemaBuilder')->andReturn($schema);
        DB::shouldReceive('connection')->andReturn($connection);

        return $schema;
    }

    private function credentials(): array
    {
        return ['username' => 'redirect-test-admin', 'password' => 'RedirectTest!123'];
    }
}
