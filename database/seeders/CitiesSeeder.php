<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $cities = require_once __DIR__ . '/../../cities_data.php';

        foreach ($cities as $cityData) {
            $country = \App\Models\Country::where('code', $cityData['country_code'])->first();

            if ($country) {
                \App\Models\City::updateOrCreate([
                    'country_id' => $country->id,
                    'name_en' => $cityData['name_en']
                ], [
                    'name_ar' => $cityData['name_ar'],
                    'name_en' => $cityData['name_en'],
                    'country_id' => $country->id,
                    'is_capital' => $cityData['is_capital'],
                    'latitude' => $cityData['latitude'] ?? null,
                    'longitude' => $cityData['longitude'] ?? null,
                    'is_active' => true,
                    'sort_order' => $cityData['sort_order'],
                ]);
            }
        }
    }
}
