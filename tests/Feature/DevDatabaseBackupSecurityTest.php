<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use App\Services\DatabaseBackupVerificationService;
use Mockery;
use Tests\TestCase;

class DevDatabaseBackupSecurityTest extends TestCase
{
    private string $key = 'clickerp/database-backups/clickerp-manual-20260823-120000-abcdefgh.sql.gz';

    public function test_restore_endpoint_cannot_be_called_without_a_verification_code(): void
    {
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldNotReceive('restore');
        $verification = Mockery::mock(DatabaseBackupVerificationService::class);
        $verification->shouldNotReceive('verifyAndConsume');
        $this->app->instance(DatabaseBackupService::class, $backups);
        $this->app->instance(DatabaseBackupVerificationService::class, $verification);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->post(route('dev-superadmin.database-backups.restore'), [
                'key' => $this->key,
                'confirmation' => 'RESTORE',
            ]);

        $response->assertSessionHasErrors('verification_code');
    }

    public function test_delete_endpoint_cannot_be_called_without_a_verification_code_and_keyword(): void
    {
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldNotReceive('delete');
        $verification = Mockery::mock(DatabaseBackupVerificationService::class);
        $verification->shouldNotReceive('verifyAndConsume');
        $this->app->instance(DatabaseBackupService::class, $backups);
        $this->app->instance(DatabaseBackupVerificationService::class, $verification);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->delete(route('dev-superadmin.database-backups.destroy'), [
                'key' => $this->key,
            ]);

        $response->assertSessionHasErrors(['confirmation', 'verification_code']);
    }

    public function test_verified_restore_consumes_the_code_before_starting_restore(): void
    {
        $verification = Mockery::mock(DatabaseBackupVerificationService::class);
        $verification->shouldReceive('verifyAndConsume')
            ->once()
            ->with(Mockery::type('Illuminate\Http\Request'), 'restore', $this->key, '123456')
            ->ordered();
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldReceive('restore')->once()->with($this->key)->ordered();
        $this->app->instance(DatabaseBackupService::class, $backups);
        $this->app->instance(DatabaseBackupVerificationService::class, $verification);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->post(route('dev-superadmin.database-backups.restore'), [
                'key' => $this->key,
                'confirmation' => 'RESTORE',
                'verification_code' => '123456',
            ]);

        $response->assertRedirect(route('dev-superadmin.database-backups'));
        $response->assertSessionHasNoErrors();
    }
}
