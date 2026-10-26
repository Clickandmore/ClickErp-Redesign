<?php

namespace Tests\Unit;

use App\Services\DatabaseBackupService;
use App\Services\ingaz\ImportSafetyBackupService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ImportSafetyBackupServiceTest extends TestCase
{
    public function test_it_creates_a_pre_import_backup(): void
    {
        $backup = ['filename' => 'clickerp-pre-import-20261014-120000-abcdefgh.sql.gz'];
        $service = $this->mock(DatabaseBackupService::class);
        $service->shouldReceive('create')->once()->with('pre-import')->andReturn($backup);

        $this->assertSame($backup, (new ImportSafetyBackupService($service))->create());
    }

    public function test_a_backup_failure_blocks_approval_with_a_validation_error(): void
    {
        $service = $this->mock(DatabaseBackupService::class);
        $service->shouldReceive('create')->once()->with('pre-import')
            ->andThrow(new \RuntimeException('R2 unavailable'));

        try {
            (new ImportSafetyBackupService($service))->create();
            $this->fail('Backup failure must stop approval.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('backup', $exception->errors());
            $this->assertStringContainsString('لم يتم الاعتماد', $exception->errors()['backup'][0]);
        }
    }
}
