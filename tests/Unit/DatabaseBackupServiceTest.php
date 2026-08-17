<?php

namespace Tests\Unit;

use App\Services\DatabaseBackupService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DatabaseBackupServiceTest extends TestCase
{
    public function test_restore_input_cannot_switch_to_database_named_in_dump(): void
    {
        $source = fopen('php://temp', 'w+b');
        $destination = fopen('php://temp', 'w+b');

        fwrite($source, implode("\n", [
            '-- Current Database: `production_database`',
            'DROP DATABASE IF EXISTS `production_database`;',
            'CREATE DATABASE /*!32312 IF NOT EXISTS*/ `production_database` /*!40100 DEFAULT CHARACTER SET utf8mb4 */;',
            'USE `production_database`;',
            'CREATE TABLE `users` (`id` bigint unsigned NOT NULL);',
            "INSERT INTO `users` VALUES (1);",
        ]));
        rewind($source);

        $method = new ReflectionMethod(DatabaseBackupService::class, 'prepareRestoreInput');
        $method->setAccessible(true);
        $method->invoke(new DatabaseBackupService(), $source, $destination);
        rewind($destination);
        $sql = stream_get_contents($destination);

        fclose($source);
        fclose($destination);

        $this->assertStringNotContainsString('CREATE DATABASE', $sql);
        $this->assertStringNotContainsString('DROP DATABASE', $sql);
        $this->assertStringNotContainsString('USE `production_database`', $sql);
        $this->assertStringContainsString('CREATE TABLE `users`', $sql);
        $this->assertStringContainsString('INSERT INTO `users`', $sql);
    }
}
