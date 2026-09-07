<?php

namespace Tests\Unit;

use App\Services\SchedulerMonitorService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class SchedulerMonitorServiceTest extends TestCase
{
    public function test_restricted_binary_probes_produce_an_actionable_error_instead_of_a_warning(): void
    {
        // A child process keeps open_basedir changes away from the test runner.
        $script = <<<'PHP'
require 'vendor/autoload.php';
$container = new Illuminate\Container\Container();
Illuminate\Container\Container::setInstance($container);
$container->instance('config', new Illuminate\Config\Repository([
    'scheduler' => ['php_binary' => '/usr/sbin/php-fpm8.3'],
]));
$method = new ReflectionMethod(App\Services\SchedulerMonitorService::class, 'phpBinary');
$method->setAccessible(true);
$service = new App\Services\SchedulerMonitorService();
config('scheduler.php_binary');
set_error_handler(function ($level, $message, $file, $line) {
    if (error_reporting() & $level) {
        throw new ErrorException($message, 0, $level, $file, $line);
    }
});
ini_set('open_basedir', __DIR__ . DIRECTORY_SEPARATOR . 'tests');
try {
    $method->invoke($service);
    exit(2);
} catch (RuntimeException $exception) {
    echo $exception->getMessage();
}
PHP;
        $process = new \Symfony\Component\Process\Process(
            [PHP_BINARY, '-r', $script], dirname(__DIR__, 2)
        );
        $process->mustRun();

        $this->assertStringContainsString('SCHEDULER_PHP_BINARY', $process->getOutput());
        $this->assertSame('', $process->getErrorOutput());
    }

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
