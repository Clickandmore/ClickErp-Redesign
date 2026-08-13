<?php

namespace Tests\Unit;

use App\Services\SchedulerMonitorService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SchedulerMonitorServiceTest extends TestCase
{
    public function test_destructive_business_workflows_are_marked_high_risk(): void
    {
        $this->assertSame('high', $this->audit('orders:cancel-expired')['risk']);
        $this->assertSame('high', $this->audit('orders:handle-expired')['risk']);
    }

    public function test_database_backup_documents_its_file_only_retention_deletion(): void
    {
        $audit = $this->audit('database:backup-r2');

        $this->assertSame('low', $audit['risk']);
        $this->assertStringContainsString('ملفات النسخ', $audit['deletion']);
        $this->assertStringContainsString('لا يحذف جداول', $audit['safety_note']);
    }

    public function test_unknown_jobs_are_not_silently_marked_safe(): void
    {
        $this->assertSame('unknown', $this->audit('unknown:job')['risk']);
    }

    private function audit(string $job): array
    {
        $method = new ReflectionMethod(SchedulerMonitorService::class, 'auditFor');
        $method->setAccessible(true);

        return $method->invoke(new SchedulerMonitorService(), $job);
    }
}
