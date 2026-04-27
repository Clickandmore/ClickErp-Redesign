<?php

namespace Nami\Hr\Database\Seeders;

use Illuminate\Database\Seeder;

class HrDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            HRTableSeeder::class,
            // ضيف أي Seeder تاني هنا 👆


        ]);
    }
}
