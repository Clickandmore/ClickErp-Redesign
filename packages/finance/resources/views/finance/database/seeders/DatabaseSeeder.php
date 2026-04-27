<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Database\Seeders\finance\AccountSeeder;
use Database\Seeders\hr\HRTableSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AccountSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(CommandSeeder::class);
        $this->call(LaratrustSeeder::class);
    }
}
