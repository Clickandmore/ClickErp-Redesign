<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

/**
 * Provisions the test database used by phpunit.xml (DB_DATABASE=rafdain_testing).
 *
 * A plain `artisan migrate` cannot build the schema from scratch: app/ and
 * packages/hr/ both declare CreateComplaintsTable, so the migrator fatals. Instead
 * we clone the structure of the development database and copy over the reference
 * tables (permissions / roles) that virtually every test needs.
 */
class ProvisionTestDatabase extends Command
{
    protected $signature = 'test:db-provision
                            {--source= : Database to clone the schema from}
                            {--target= : Database to create}
                            {--skip-migrate : Clone the schema only, do not run pending migrations}';

    protected $description = 'Create/refresh the test database as a schema clone of the development database';

    /**
     * Small, environment-independent lookup tables copied with their rows.
     */
    private const REFERENCE_TABLES = [
        // The ledger must come across, otherwise `artisan migrate` would treat all 688
        // migrations as pending and fatal on the duplicate CreateComplaintsTable class.
        'migrations',
        'permissions',
        'roles',
        'role_has_permissions',
        'system_module_settings',
        'dev_superadmin_settings',
        // A `*` view composer shares Setting::first() as $settings; with an empty table
        // every page that reads it (the public homepage, for one) fatals.
        'settings',
        'new_settings',
    ];

    /**
     * new_settings keys that point at live external services.
     */
    private const LIVE_SERVICE_SETTINGS = [
        'r2_storage',
    ];

    public function handle(): int
    {
        $source = $this->option('source') ?: config('database.connections.mysql.database');
        $target = $this->option('target') ?: 'rafdain_testing';

        if ($source === $target) {
            $this->error("Source and target are both [{$source}]. Refusing to drop the source database.");

            return self::FAILURE;
        }

        $pdo = DB::connection('mysql')->getPdo();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->warn("Dropping and recreating [{$target}] from the schema of [{$source}]...");

        $pdo->exec("DROP DATABASE IF EXISTS `{$target}`");
        $pdo->exec("CREATE DATABASE `{$target}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

        $tables = $pdo->query(
            "SELECT table_name FROM information_schema.tables
             WHERE table_schema = '{$source}' AND table_type = 'BASE TABLE'"
        )->fetchAll(PDO::FETCH_COLUMN);

        $bar = $this->output->createProgressBar(count($tables));

        foreach ($tables as $table) {
            $pdo->exec("CREATE TABLE `{$target}`.`{$table}` LIKE `{$source}`.`{$table}`");
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        foreach (self::REFERENCE_TABLES as $table) {
            if (! in_array($table, $tables, true)) {
                continue;
            }

            $pdo->exec("INSERT INTO `{$target}`.`{$table}` SELECT * FROM `{$source}`.`{$table}`");
            $copied = $pdo->query("SELECT COUNT(*) FROM `{$target}`.`{$table}`")->fetchColumn();
            $this->line("  reference data: {$table} ({$copied} rows)");
        }

        // Settings that carry live credentials must not reach the test database, otherwise
        // tests upload to the real R2 bucket instead of the faked local disk.
        if (in_array('new_settings', $tables, true)) {
            $placeholders = implode(',', array_fill(0, count(self::LIVE_SERVICE_SETTINGS), '?'));
            $statement = $pdo->prepare("DELETE FROM `{$target}`.`new_settings` WHERE `key` IN ({$placeholders})");
            $statement->execute(self::LIVE_SERVICE_SETTINGS);
            $this->line('  stripped live-service settings: ' . implode(', ', self::LIVE_SERVICE_SETTINGS));
        }

        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

        // With the ledger copied, only genuinely-pending migrations run — which is how
        // the test schema stays ahead of a development database that is behind.
        if (! $this->option('skip-migrate')) {
            $this->newLine();
            $this->line('Applying pending migrations to the test database...');

            config(['database.connections.test_target' => array_merge(
                config('database.connections.mysql'),
                ['database' => $target]
            )]);

            $this->call('migrate', ['--force' => true, '--database' => 'test_target']);
        }

        $this->newLine();
        $this->info(sprintf('Test database [%s] ready: %d tables.', $target, count($tables)));

        return self::SUCCESS;
    }
}
