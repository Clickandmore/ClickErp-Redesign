<?php

namespace Tests\Unit;

use App\Support\DatabaseBackupSettings;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DatabaseBackupSettingsTest extends TestCase
{
    public function test_it_normalizes_backup_schedule_and_retention(): void
    {
        $settings = $this->normalize([
            'enabled' => '1',
            'daily_time' => '23:45',
            'retention_days' => 999,
            'local_enabled' => '1',
            'local_retention_days' => 99,
            'path' => '/company/db_backups/',
        ]);

        $this->assertTrue($settings['enabled']);
        $this->assertSame('23:45', $settings['daily_time']);
        $this->assertSame(365, $settings['retention_days']);
        $this->assertTrue($settings['local_enabled']);
        $this->assertSame(30, $settings['local_retention_days']);
        $this->assertSame('company/db_backups', $settings['path']);
    }

    public function test_it_falls_back_to_safe_defaults_for_invalid_values(): void
    {
        $settings = $this->normalize([
            'daily_time' => '27:80',
            'retention_days' => 0,
            'path' => '../unsafe path',
        ]);

        $this->assertSame('03:30', $settings['daily_time']);
        $this->assertSame(1, $settings['retention_days']);
        $this->assertSame('database-backups', $settings['path']);
    }

    public function test_it_migrates_the_legacy_retention_count_to_days(): void
    {
        $settings = $this->normalize(['retention_count' => 14]);

        $this->assertSame(14, $settings['retention_days']);
        $this->assertArrayNotHasKey('retention_count', $settings);
    }

    private function normalize(array $values): array
    {
        $method = new ReflectionMethod(DatabaseBackupSettings::class, 'normalize');
        $method->setAccessible(true);

        return $method->invoke(null, $values);
    }
}
