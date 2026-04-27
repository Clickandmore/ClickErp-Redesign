<?php

namespace Nami\Finance\Database\Seeders;

use Illuminate\Database\Seeder;

class FinanceDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            AccountSeeder::class,
            RecruitmentRentalAccountsSeeder::class,
        ]);
    }
}
