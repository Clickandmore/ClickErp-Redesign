<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use Mockery;
use Tests\TestCase;

class ImportLocalDatabaseCommandTest extends TestCase
{
    public function test_force_option_imports_an_existing_local_sql_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'clickerp-command-import-') . '.sql';
        file_put_contents($path, 'SELECT 1;');

        try {
            $backups = Mockery::mock(DatabaseBackupService::class);
            $backups->shouldReceive('importFile')->once()->with(realpath($path));
            $this->app->instance(DatabaseBackupService::class, $backups);

            $this->artisan('database:import-local', ['file' => $path, '--force' => true])
                ->expectsOutput('تم استيراد قاعدة البيانات بنجاح وحفظ نسخة الأمان المحلية.')
                ->assertExitCode(0);
        } finally {
            @unlink($path);
        }
    }

    public function test_missing_file_is_rejected_without_calling_the_service(): void
    {
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldNotReceive('importFile');
        $this->app->instance(DatabaseBackupService::class, $backups);

        $this->artisan('database:import-local', ['file' => '/tmp/clickerp-file-does-not-exist.sql', '--force' => true])
            ->assertExitCode(1);
    }
}
