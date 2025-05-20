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
        $countries = [
            [
                'name_ar' => 'المملكة العربية السعودية',
                'name_en' => 'Saudi Arabia',
                'code' => 'SA',
                'phone_code' => '966',
                'currency' => 'SAR',
                'currency_symbol' => '﷼',
                'is_active' => 1,
                'sort_order' => 1,
            ],
            [
                'name_ar' => 'مصر',
                'name_en' => 'Egypt',
                'code' => 'EG',
                'phone_code' => '20',
                'currency' => 'EGP',
                'currency_symbol' => 'ج.م',
                'is_active' => 1,
                'sort_order' => 2,
            ],
            [
                'name_ar' => 'الإمارات العربية المتحدة',
                'name_en' => 'United Arab Emirates',
                'code' => 'AE',
                'phone_code' => '971',
                'currency' => 'AED',
                'currency_symbol' => 'د.إ',
                'is_active' => 1,
                'sort_order' => 3,
            ],
            [
                'name_ar' => 'الكويت',
                'name_en' => 'Kuwait',
                'code' => 'KW',
                'phone_code' => '965',
                'currency' => 'KWD',
                'currency_symbol' => 'د.ك',
                'is_active' => 1,
                'sort_order' => 4,
            ],
            [
                'name_ar' => 'قطر',
                'name_en' => 'Qatar',
                'code' => 'QA',
                'phone_code' => '974',
                'currency' => 'QAR',
                'currency_symbol' => 'ر.ق',
                'is_active' => 1,
                'sort_order' => 5,
            ],
            [
                'name_ar' => 'البحرين',
                'name_en' => 'Bahrain',
                'code' => 'BH',
                'phone_code' => '973',
                'currency' => 'BHD',
                'currency_symbol' => 'د.ب',
                'is_active' => 1,
                'sort_order' => 6,
            ],
            [
                'name_ar' => 'عمان',
                'name_en' => 'Oman',
                'code' => 'OM',
                'phone_code' => '968',
                'currency' => 'OMR',
                'currency_symbol' => 'ر.ع',
                'is_active' => 1,
                'sort_order' => 7,
            ],
            [
                'name_ar' => 'الأردن',
                'name_en' => 'Jordan',
                'code' => 'JO',
                'phone_code' => '962',
                'currency' => 'JOD',
                'currency_symbol' => 'د.ا',
                'is_active' => 1,
                'sort_order' => 8,
            ],
            [
                'name_ar' => 'المغرب',
                'name_en' => 'Morocco',
                'code' => 'MA',
                'phone_code' => '212',
                'currency' => 'MAD',
                'currency_symbol' => 'د.م.',
                'is_active' => 1,
                'sort_order' => 9,
            ],
            [
                'name_ar' => 'تونس',
                'name_en' => 'Tunisia',
                'code' => 'TN',
                'phone_code' => '216',
                'currency' => 'TND',
                'currency_symbol' => 'د.ت',
                'is_active' => 1,
                'sort_order' => 10,
            ],
            // ... أضف باقي دول العالم هنا ...
        ];

        foreach ($countries as $country) {
            \App\Models\Country::updateOrCreate([
                'code' => $country['code']
            ], $country);
        }
    }
}
