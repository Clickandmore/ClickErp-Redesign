<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $countries = require_once __DIR__ . '/../../countries_data.php';

        foreach ($countries as $country) {
            \App\Models\Country::updateOrCreate([
                'code' => $country['code']
            ], $country);
        }
    }
}
