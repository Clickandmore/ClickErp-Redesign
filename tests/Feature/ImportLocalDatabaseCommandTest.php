<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ImportLocalDatabaseCommandTest extends TestCase
{
    public function test_filename_is_resolved_from_the_configured_local_disk(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('database-backups/selected.sql.gz', 'placeholder');
        $path = Storage::disk('local')->path('database-backups/selected.sql.gz');
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldReceive('importFile')->once()->with(realpath($path));
        $this->app->instance(DatabaseBackupService::class, $backups);

        $this->artisan('database:import-local', ['file' => 'selected.sql.gz', '--force' => true])
            ->assertExitCode(0);
    }

    public function test_fpm_is_never_selected_for_terminal_commands(): void
    {
        config(['database.backup_binaries.php' => '/usr/sbin/php-fpm8.3']);
        $method = new \ReflectionMethod(DatabaseBackupService::class, 'phpCliBinary');
        $method->setAccessible(true);
        $binary = $method->invoke(new DatabaseBackupService(), '/usr/sbin/php-fpm8.3', 'fpm-fcgi');

        $this->assertDoesNotMatchRegularExpression('/fpm|cgi/i', $binary);
    }

    public function test_terminal_commands_use_the_configured_cli_binary(): void
    {
        config(['database.backup_binaries.php' => PHP_BINARY]);
        $service = new DatabaseBackupService();
        $this->assertStringContainsString(escapeshellarg(PHP_BINARY), $service->localImportCommandPrefix());
        $this->assertStringContainsString(escapeshellarg(PHP_BINARY), $service->emptyDatabaseCommand());
    }

    public function test_force_option_imports_an_existing_local_sql_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'clickerp-command-import-') . '.sql';
        file_put_contents($path, 'SELECT 1;');

        try {
            $backups = Mockery::mock(DatabaseBackupService::class);
            $backups->shouldReceive('importFile')->once()->with(realpath($path));
            $this->app->instance(DatabaseBackupService::class, $backups);

            $this->artisan('database:import-local', ['file' => $path, '--force' => true])
                ->expectsOutput('Database imported successfully. The local safety backup was preserved.')
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

    public function test_missing_file_argument_prompts_for_a_finder_path(): void
    {
        $directory = sys_get_temp_dir() . '/clickerp command import ' . uniqid();
        mkdir($directory);
        $path = $directory . '/backup file.sql';
        file_put_contents($path, 'SELECT 1;');
        $draggedPath = str_replace(' ', '\\ ', $path);

        try {
            $backups = Mockery::mock(DatabaseBackupService::class);
            $backups->shouldReceive('importFile')->once()->with(realpath($path));
            $this->app->instance(DatabaseBackupService::class, $backups);

            $this->artisan('database:import-local', ['--force' => true])
                ->expectsQuestion('Drag the SQL or SQL.GZ file here, then press Enter', $draggedPath)
                ->expectsOutput('Database imported successfully. The local safety backup was preserved.')
                ->assertExitCode(0);
        } finally {
            @unlink($path);
            @rmdir($directory);
        }
    }

    public function test_a_selected_filename_is_found_in_a_configured_search_directory(): void
    {
        $directory = sys_get_temp_dir() . '/clickerp-command-search-' . uniqid();
        mkdir($directory);
        $filename = 'selected-backup.sql.gz';
        $path = $directory . '/' . $filename;
        file_put_contents($path, 'compressed-placeholder');
        config(['database.backup_import_search_paths' => [$directory]]);

        try {
            $backups = Mockery::mock(DatabaseBackupService::class);
            $backups->shouldReceive('importFile')->once()->with(realpath($path));
            $this->app->instance(DatabaseBackupService::class, $backups);

            $this->artisan('database:import-local', ['file' => $filename, '--force' => true])
                ->expectsOutput('Database imported successfully. The local safety backup was preserved.')
                ->assertExitCode(0);
        } finally {
            @unlink($path);
            @rmdir($directory);
        }
    }
}
