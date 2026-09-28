<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/** Run only this module's migrations; Click's unrelated pending migrations are untouched. */
class InstallHourlyModule extends Command
{
    protected $signature = 'hourly:install {--check : Read-only prerequisite check} {--force : Run migrations in production}';
    protected $description = 'Check/install hourly rental tables without running unrelated ERP migrations.';

    public function handle(): int
    {
        $missing = [];
        foreach (['new_settings','maids','users','user_addresses','shelter_places','cities','countries','nationalities'] as $table) {
            if (!Schema::hasTable($table)) $missing[] = $table;
        }
        if ($missing) {
            $this->error('Missing Click prerequisites: '.implode(', ', $missing));
            return self::FAILURE;
        }
        foreach (config('hourly_migrations', []) as $file) {
            if (!is_file(base_path($file))) {
                $this->error('Missing module migration: '.$file);
                return self::FAILURE;
            }
        }
        $this->info('Hourly prerequisites are available. Existing ERP settings and records will be retained.');
        if ($this->option('check')) return self::SUCCESS;
        $result = $this->call('migrate', ['--path'=>config('hourly_migrations'), '--force'=>(bool)$this->option('force')]);
        if ($result === self::SUCCESS) {
            $this->info('Installed. Use permissions:prepare-server to synchronize permissions, then configure Hourly Rental in Super Admin.');
        }
        return $result;
    }
}
