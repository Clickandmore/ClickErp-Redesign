<?php

namespace App\Console\Commands;

use Database\Seeders\IngazDemoSeeder;
use Illuminate\Console\Command;

class RefreshDemoData extends Command
{
    protected $signature = 'ingaz:refresh-demo-data {--seed-only : Regenerate demo rows without running migrate:fresh}';

    protected $description = 'Flush and refill the Ingaz dashboard database with realistic fake data';

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('This command is disabled in the production environment.');
            return Command::FAILURE;
        }

        if (! $this->option('seed-only')) {
            if (! $this->confirm('This will DROP all tables, run migrate:fresh and reseed. Continue?')) {
                return Command::FAILURE;
            }

            $this->info('Running migrate:fresh with the default DatabaseSeeder...');
            $this->call('migrate:fresh', ['--seed' => true]);
        } else {
            $this->info('Skipping migrate:fresh and only refreshing demo data.');
        }

        $this->call('db:seed', ['--class' => IngazDemoSeeder::class]);

        $this->info('Demo dashboard data is ready. Open http://127.0.0.1:8001/dashboard to review it.');

        return Command::SUCCESS;
    }
}
