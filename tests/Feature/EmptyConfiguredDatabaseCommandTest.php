<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use Mockery;
use Tests\TestCase;

class EmptyConfiguredDatabaseCommandTest extends TestCase
{
    public function test_exact_database_confirmation_is_required(): void
    {
        $database = (string) config('database.connections.' . config('database.default') . '.database');
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldNotReceive('emptyConfiguredDatabase');
        $this->app->instance(DatabaseBackupService::class, $backups);

        $this->artisan('database:empty-configured')
            ->expectsQuestion("Type EMPTY {$database} to continue", 'CANCEL')
            ->expectsOutput('Database emptying cancelled. Nothing was deleted.')
            ->assertExitCode(0);
    }

    public function test_force_option_calls_the_guarded_empty_service(): void
    {
        $backups = Mockery::mock(DatabaseBackupService::class);
        $backups->shouldReceive('emptyConfiguredDatabase')->once()->andReturn([
            'database' => 'example',
            'tables' => 12,
            'views' => 2,
            'safety_backup' => [
                'path' => 'database-backups/clickerp-pre-import-20260825-120000-abcdefgh.sql.gz',
                'filename' => 'clickerp-pre-import-20260825-120000-abcdefgh.sql.gz',
            ],
        ]);
        $this->app->instance(DatabaseBackupService::class, $backups);

        $this->artisan('database:empty-configured', ['--force' => true])
            ->expectsOutput('Database emptied successfully.')
            ->expectsOutput('Removed tables: 12')
            ->expectsOutput('Removed views: 2')
            ->assertExitCode(0);
    }
}
