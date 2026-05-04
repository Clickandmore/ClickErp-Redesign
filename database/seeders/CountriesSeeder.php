<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Locale;
use ResourceBundle;

class CountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $countries = $this->countries();
        $nextSortOrder = ((int) Country::max('sort_order')) + 1;

        foreach ($countries as $country) {
            $model = Country::firstOrNew(['code' => $country['code']]);

            foreach (['name_ar', 'name_en'] as $key) {
                if (!$model->exists || blank($model->{$key})) {
                    $model->{$key} = $country[$key];
                }
            }

            if (!$model->exists) {
                $model->is_active = $country['is_active'];
                $model->sort_order = $nextSortOrder++;
            }

            foreach (['phone_code', 'currency', 'currency_symbol'] as $key) {
                if (!$model->exists || $country[$key] !== null) {
                    $model->{$key} = $country[$key];
                }
            }

            $model->save();
        }
    }

    private function countries(): array
    {
        $dataFile = base_path('countries_data.php');

        if (is_file($dataFile)) {
            return require $dataFile;
        }

        if (!class_exists(ResourceBundle::class) || !class_exists(Locale::class)) {
            return $this->fallbackCountries();
        }

        $bundle = ResourceBundle::create('en', 'ICUDATA-region');
        $countryBundle = $bundle?->get('Countries');

        if (!$countryBundle) {
            return $this->fallbackCountries();
        }

        $countries = [];

        foreach ($countryBundle as $code => $name) {
            if (!preg_match('/^[A-Z]{2}$/', (string) $code)) {
                continue;
            }

            $countries[] = [
                'name_ar' => Locale::getDisplayRegion('-' . $code, 'ar') ?: $name,
                'name_en' => Locale::getDisplayRegion('-' . $code, 'en') ?: $name,
                'code' => $code,
                'phone_code' => null,
                'currency' => null,
                'currency_symbol' => null,
                'is_active' => true,
                'sort_order' => 0,
            ];
        }

        usort($countries, static fn ($left, $right) => strcmp($left['name_en'], $right['name_en']));

        foreach ($countries as $index => $country) {
            $countries[$index]['sort_order'] = $index + 1;
        }

        return $countries;
    }

    private function fallbackCountries(): array
    {
        return [
            [
                'name_ar' => 'المملكة العربية السعودية',
                'name_en' => 'Saudi Arabia',
                'code' => 'SA',
                'phone_code' => '966',
                'currency' => 'SAR',
                'currency_symbol' => 'SAR',
                'is_active' => true,
                'sort_order' => 1,
            ],
        ];
    }
}
