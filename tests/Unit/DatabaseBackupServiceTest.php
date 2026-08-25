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

    public function test_restore_input_is_prefixed_with_schema_replacement_sql(): void
    {
        $source = fopen('php://temp', 'w+b');
        $destination = fopen('php://temp', 'w+b');
        fwrite($source, "CREATE TABLE `about_us` (`id` bigint);\n");
        rewind($source);

        $service = new DatabaseBackupService();
        $resetMethod = new ReflectionMethod($service, 'schemaResetSqlForObjects');
        $resetMethod->setAccessible(true);
        $prefix = $resetMethod->invoke(
            $service,
            [
                (object) ['TABLE_NAME' => 'about_us', 'TABLE_TYPE' => 'BASE TABLE'],
                (object) ['TABLE_NAME' => 'active_users', 'TABLE_TYPE' => 'VIEW'],
            ],
            [
                (object) ['ROUTINE_NAME' => 'refresh_users', 'ROUTINE_TYPE' => 'PROCEDURE'],
                (object) ['ROUTINE_NAME' => 'user_total', 'ROUTINE_TYPE' => 'FUNCTION'],
            ],
            [(object) ['EVENT_NAME' => 'nightly_cleanup']]
        );

        $prepareMethod = new ReflectionMethod($service, 'prepareRestoreInput');
        $prepareMethod->setAccessible(true);
        $prepareMethod->invoke($service, $source, $destination, $prefix);
        rewind($destination);
        $sql = stream_get_contents($destination);

        fclose($source);
        fclose($destination);

        $this->assertStringContainsString('DROP VIEW IF EXISTS `active_users`;', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `about_us`;', $sql);
        $this->assertStringContainsString('DROP PROCEDURE IF EXISTS `refresh_users`;', $sql);
        $this->assertStringContainsString('DROP FUNCTION IF EXISTS `user_total`;', $sql);
        $this->assertStringContainsString('DROP EVENT IF EXISTS `nightly_cleanup`;', $sql);
        $this->assertLessThan(
            strpos($sql, 'CREATE TABLE `about_us`'),
            strpos($sql, 'DROP TABLE IF EXISTS `about_us`')
        );
    }

    public function test_schema_object_names_are_safely_quoted(): void
    {
        $method = new ReflectionMethod(DatabaseBackupService::class, 'schemaResetSqlForObjects');
        $method->setAccessible(true);
        $sql = $method->invoke(
            new DatabaseBackupService(),
            [(object) ['TABLE_NAME' => 'odd`table', 'TABLE_TYPE' => 'BASE TABLE']],
            [],
            []
        );

        $this->assertStringContainsString('DROP TABLE IF EXISTS `odd``table`;', $sql);
    }

    public function test_mariadb_text_literal_default_is_made_valid_for_mysql_8(): void
    {
        $method = new ReflectionMethod(DatabaseBackupService::class, 'normalizeLargeObjectLiteralDefault');
        $method->setAccessible(true);
        $line = "  `map_link` text DEFAULT '#',\n";

        $normalized = $method->invoke(new DatabaseBackupService(), $line, 'expression');

        $this->assertSame("  `map_link` text DEFAULT ('#'),\n", $normalized);
    }

    public function test_unsupported_text_literal_default_is_removed_for_older_mysql(): void
    {
        $method = new ReflectionMethod(DatabaseBackupService::class, 'normalizeLargeObjectLiteralDefault');
        $method->setAccessible(true);
        $line = "  `map_link` text DEFAULT '#',\n";

        $normalized = $method->invoke(new DatabaseBackupService(), $line, 'remove');

        $this->assertSame("  `map_link` text,\n", $normalized);
    }

    public function test_regular_column_defaults_and_null_text_defaults_are_not_changed(): void
    {
        $method = new ReflectionMethod(DatabaseBackupService::class, 'normalizeLargeObjectLiteralDefault');
        $method->setAccessible(true);
        $service = new DatabaseBackupService();

        $this->assertSame(
            "  `title` varchar(255) DEFAULT '#',\n",
            $method->invoke($service, "  `title` varchar(255) DEFAULT '#',\n", 'expression')
        );
        $this->assertSame(
            "  `payload` longtext DEFAULT NULL,\n",
            $method->invoke($service, "  `payload` longtext DEFAULT NULL,\n", 'expression')
        );
    }
}
