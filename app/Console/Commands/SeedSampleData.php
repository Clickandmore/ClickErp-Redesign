<?php

namespace App\Console\Commands;

use Database\Seeders\IngazSampleDataSeeder;
use Illuminate\Console\Command;

class SeedSampleData extends Command
{
    protected $signature = 'ingaz:seed-sample-data';

    protected $description = 'Populate the core modules with demo users, maids, orders, contracts, and rental data without truncating tables';

    public function handle(): int
    {
        $this->info('Seeding demo users, maids, orders, shelter contracts, and rental data...');

        $this->call('db:seed', ['--class' => IngazSampleDataSeeder::class]);

        $this->info('Sample data inserted successfully. You can now browse the dashboards safely.');

        return self::SUCCESS;
    }
}
