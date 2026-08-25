<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use App\Services\DatabaseBackupVerificationService;
use App\Support\DevSuperAdminSecuritySettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class DevDatabaseBackupSecurityTest extends TestCase
{
    private string $key = 'clickerp/database-backups/clickerp-manual-20260823-120000-abcdefgh.sql.gz';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'dev_superadmin.password' => 'CurrentPassword!123',
            'dev_superadmin.security_file' => sys_get_temp_dir() . '/clickerp-missing-import-security-' . uniqid() . '.enc',
        ]);
        DevSuperAdminSecuritySettings::resetCache();
    }

    protected function tearDown(): void
    {
        DevSuperAdminSecuritySettings::resetCache();
        parent::tearDown();
    }

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

    public function test_import_endpoint_cannot_be_called_without_current_password_and_keyword(): void
    {
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldNotReceive('importUploaded');
        $this->app->instance(DatabaseBackupService::class, $backups);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->post(route('dev-superadmin.database-backups.import'), [
                'database_file' => UploadedFile::fake()->createWithContent('backup.sql', 'SELECT 1;'),
            ]);

        $response->assertSessionHasErrors(['confirmation', 'current_password']);
    }

    public function test_password_confirmed_sql_upload_is_passed_to_the_import_service(): void
    {
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldReceive('importUploaded')
            ->once()
            ->with(Mockery::type(UploadedFile::class))
            ->andReturn([
                'path' => 'database-backups/clickerp-uploaded-20260825-120000-abcdefgh.sql.gz',
                'filename' => 'clickerp-uploaded-20260825-120000-abcdefgh.sql.gz',
                'size' => 1024,
                'created_at' => now()->getTimestamp(),
            ]);
        $this->app->instance(DatabaseBackupService::class, $backups);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->post(route('dev-superadmin.database-backups.import'), [
                'database_file' => UploadedFile::fake()->createWithContent('backup.sql', 'SELECT 1;'),
                'confirmation' => 'IMPORT',
                'current_password' => 'CurrentPassword!123',
            ]);

        $response->assertRedirect(route('dev-superadmin.database-backups'));
        $response->assertSessionHasNoErrors();
    }

    public function test_manually_uploaded_backups_are_included_in_the_local_backup_list(): void
    {
        Storage::fake('local');
        $path = 'database-backups/clickerp-uploaded-20260825-120000-abcdefgh.sql.gz';
        Storage::disk('local')->put($path, 'compressed-backup');

        $backups = app(DatabaseBackupService::class)->localBackups();

        $this->assertCount(1, $backups);
        $this->assertSame($path, $backups[0]['path']);
        $this->assertSame('clickerp-uploaded-20260825-120000-abcdefgh.sql.gz', $backups[0]['filename']);
    }

    public function test_plain_sql_upload_is_saved_as_a_valid_compressed_local_backup(): void
    {
        Storage::fake('local');
        $source = tempnam(storage_path('app'), 'uploaded-sql-test-');
        file_put_contents($source, "CREATE TABLE example (id INT);\n");

        try {
            $method = new ReflectionMethod(DatabaseBackupService::class, 'storeUploadedBackup');
            $method->setAccessible(true);
            $backup = $method->invoke(app(DatabaseBackupService::class), $source, 'customer-backup.sql');

            Storage::disk('local')->assertExists($backup['path']);
            $gzip = gzopen(Storage::disk('local')->path($backup['path']), 'rb');
            $this->assertIsResource($gzip);
            $this->assertStringContainsString('CREATE TABLE example', gzread($gzip, 4096));
            gzclose($gzip);
        } finally {
            @unlink($source);
        }
    }

    public function test_wrong_super_admin_password_cannot_start_import(): void
    {
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldNotReceive('importUploaded');
        $this->app->instance(DatabaseBackupService::class, $backups);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->post(route('dev-superadmin.database-backups.import'), [
                'database_file' => UploadedFile::fake()->createWithContent('backup.sql', 'SELECT 1;'),
                'confirmation' => 'IMPORT',
                'current_password' => 'WrongPassword!123',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_password_confirmed_manual_upload_can_be_deleted(): void
    {
        $path = 'database-backups/clickerp-uploaded-20260825-120000-abcdefgh.sql.gz';
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldReceive('deleteLocalUploaded')->once()->with($path)->andReturnUsing(function (): void {
        });
        $this->app->instance(DatabaseBackupService::class, $backups);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->delete(route('dev-superadmin.database-backups.local.destroy'), [
                'path' => $path,
                'confirmation' => 'DELETE',
                'current_password' => 'CurrentPassword!123',
            ]);

        $response->assertRedirect(route('dev-superadmin.database-backups'));
        $response->assertSessionHasNoErrors();
    }

    public function test_wrong_password_cannot_delete_a_manual_upload(): void
    {
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldNotReceive('deleteLocalUploaded');
        $this->app->instance(DatabaseBackupService::class, $backups);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->delete(route('dev-superadmin.database-backups.local.destroy'), [
                'path' => 'database-backups/clickerp-uploaded-20260825-120000-abcdefgh.sql.gz',
                'confirmation' => 'DELETE',
                'current_password' => 'WrongPassword!123',
            ]);

        $response->assertSessionHasErrors('current_password');
    }

    public function test_delete_local_uploaded_removes_only_a_manual_upload(): void
    {
        Storage::fake('local');
        $path = 'database-backups/clickerp-uploaded-20260825-120000-abcdefgh.sql.gz';
        Storage::disk('local')->put($path, 'compressed-backup');

        app(DatabaseBackupService::class)->deleteLocalUploaded($path);

        Storage::disk('local')->assertMissing($path);
    }

    public function test_delete_local_uploaded_refuses_to_remove_a_safety_backup(): void
    {
        Storage::fake('local');
        $path = 'database-backups/clickerp-pre-import-20260825-120000-abcdefgh.sql.gz';
        Storage::disk('local')->put($path, 'safety-backup');

        try {
            app(DatabaseBackupService::class)->deleteLocalUploaded($path);
            $this->fail('The safety backup deletion should have been rejected.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('النسخ المرفوعة يدويًا فقط', $exception->getMessage());
        }

        Storage::disk('local')->assertExists($path);
    }
}
