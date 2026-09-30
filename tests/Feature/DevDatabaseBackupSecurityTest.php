<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use App\Services\DatabaseBackupVerificationService;
use App\Support\DevSuperAdminSecuritySettings;
use App\Support\R2StorageSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use ReflectionMethod;
use ReflectionProperty;
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
        $this->setR2SettingsCache(null);
        DevSuperAdminSecuritySettings::resetCache();
        parent::tearDown();
    }

    public function test_manual_create_falls_back_to_a_local_backup_when_r2_is_not_ready(): void
    {
        $this->setR2SettingsCache([
            'enabled' => false,
            'verified_at' => null,
            'endpoint' => '',
            'bucket' => '',
            'access_key_id' => '',
            'secret_access_key' => '',
        ]);

        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldNotReceive('create');
        $backups->shouldReceive('createLocal')->once()->with('manual')->andReturn([
            'path' => 'database-backups/clickerp-manual-20260825-120000-abcdefgh.sql.gz',
            'filename' => 'clickerp-manual-20260825-120000-abcdefgh.sql.gz',
        ]);
        $this->app->instance(DatabaseBackupService::class, $backups);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->from(route('dev-superadmin.database-backups'))
            ->post(route('dev-superadmin.database-backups.create'));

        $response->assertRedirect(route('dev-superadmin.database-backups'));
        $response->assertSessionHas('success', function (string $message): bool {
            return str_contains($message, 'محليًا على السيرفر');
        });
    }

    public function test_backup_page_shows_create_and_download_actions_without_r2(): void
    {
        $this->setR2SettingsCache([
            'enabled' => false,
            'verified_at' => null,
            'endpoint' => '',
            'bucket' => '',
            'access_key_id' => '',
            'secret_access_key' => '',
        ]);

        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldReceive('localBackups')->once()->andReturn([]);
        $backups->shouldReceive('currentDatabaseDetails')->once()->andReturn([
            'connected' => true,
            'database' => 'rafdain_testing',
            'connected_database' => 'rafdain_testing',
        ]);
        $backups->shouldReceive('localImportCommandPrefix')->once()->andReturn('php artisan database:import-local ');
        $backups->shouldReceive('terminalShell')->once()->andReturn('terminal');
        $backups->shouldReceive('emptyDatabaseCommand')->once()->andReturn('php artisan database:empty-configured');
        $this->app->instance(DatabaseBackupService::class, $backups);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('dev-superadmin.database-backups'));

        $response->assertOk();
        $response->assertSee('تنزيل نسخة جديدة');
        $response->assertSee('إنشاء نسخة محلية');
        $response->assertSee('/tmp/clickerp-database-upload-', false);
        $response->assertSee('sudo -u www-data cp -- ', false);
    }

    public function test_local_backup_download_stays_behind_super_admin_authentication(): void
    {
        $this->get(route('dev-superadmin.database-backups.local.download', [
            'path' => 'database-backups/clickerp-manual-20260825-120000-abcdefgh.sql.gz',
        ]))->assertRedirect(route('superadmin.login'));
    }

    public function test_super_admin_can_download_an_existing_local_backup(): void
    {
        $path = 'database-backups/clickerp-manual-20260825-120000-abcdefgh.sql.gz';
        $stream = fopen('php://temp', 'w+b');
        fwrite($stream, 'backup-bytes');
        rewind($stream);

        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldReceive('readLocalStream')->once()->with($path)->andReturn($stream);
        $this->app->instance(DatabaseBackupService::class, $backups);

        $response = $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('dev-superadmin.database-backups.local.download', ['path' => $path]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/gzip');
        ob_start();
        $response->baseResponse->sendContent();
        $contents = ob_get_clean();
        $this->assertSame('backup-bytes', $contents);
    }

    public function test_local_backup_reader_rejects_directory_traversal(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('مسار النسخة المحلية غير صالح');

        app(DatabaseBackupService::class)->readLocalStream(
            'database-backups/../clickerp-manual-20260825-120000-abcdefgh.sql.gz'
        );
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
        $this->assertStringContainsString('artisan database:import-local', $backups[0]['terminal_command']);
        $this->assertStringContainsString(Storage::disk('local')->path($path), $backups[0]['terminal_command']);
        $this->assertStringNotContainsString('--force', $backups[0]['terminal_command']);
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

    public function test_current_database_details_never_expose_the_database_password(): void
    {
        $details = app(DatabaseBackupService::class)->currentDatabaseDetails();

        $this->assertSame(config('database.default'), $details['connection']);
        $this->assertSame(config('database.connections.' . config('database.default') . '.database'), $details['database']);
        $this->assertArrayHasKey('password_configured', $details);
        $this->assertArrayNotHasKey('password', $details);
    }

    private function setR2SettingsCache(?array $settings): void
    {
        $property = new ReflectionProperty(R2StorageSettings::class, 'cached');
        $property->setAccessible(true);
        $property->setValue(null, $settings);
    }

}
