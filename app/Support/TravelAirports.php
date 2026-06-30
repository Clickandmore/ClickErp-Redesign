<?php

namespace App\Support;

use App\Models\airport as Airport;
use App\Models\City;
use App\Models\Country;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class TravelAirports
{
    public static function options(): array
    {
        if (!Schema::hasTable('airports')) {
            return self::defaults()
                ->map(fn (array $airport) => [
                    'value' => $airport['name'],
                    'label' => $airport['name'],
                ])
                ->unique('value')
                ->values()
                ->all();
        }

        self::ensureDefaultsPersisted();

        return Airport::query()
                ->orderBy('name')
                ->get(['name'])
                ->map(fn (Airport $airport) => [
                    'value' => self::displayName($airport->name),
                    'label' => self::displayName($airport->name),
                ])
                ->filter(fn ($airport) => !empty($airport['value']) && !empty($airport['label']))
                ->unique('value')
                ->values()
                ->all();
    }

    public static function label(?string $airport): string
    {
        if (!$airport) {
            return 'غير محدد';
        }

        return self::displayName($airport);
    }

    public static function valuesFor(?string $airport): array
    {
        $label = self::displayName($airport);

        if ($label === '') {
            return [];
        }

        return self::legacyLabels()
            ->filter(fn (string $legacyLabel) => $legacyLabel === $label)
            ->keys()
            ->push($airport)
            ->push($label)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public static function datatableRows(): Collection
    {
        if (!Schema::hasTable('airports')) {
            return collect();
        }

        self::ensureDefaultsPersisted();

        return Airport::with(['country', 'city'])
                ->latest()
                ->get()
                ->map(fn (Airport $airport) => (object) [
                    'id' => $airport->id,
                    'name' => self::displayName($airport->name),
                    'country_name' => $airport->country?->title ?: '-',
                    'city_name' => $airport->city?->name ?: '-',
                    'created_at' => $airport->created_at,
                    'is_default' => false,
                ])
            ->unique('name')
            ->values();
    }

    public static function ensureDefaultsPersisted(): void
    {
        if (!Schema::hasTable('airports') || !Schema::hasTable('countries') || !Schema::hasTable('cities')) {
            return;
        }

        $country = Country::firstOrCreate(
            ['code' => 'SA'],
            [
                'name_ar' => 'السعودية',
                'name_en' => 'Saudi Arabia',
                'phone_code' => '+966',
                'currency' => 'SAR',
                'currency_symbol' => 'ر.س',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        foreach (self::defaults()->unique('name') as $airport) {
            $city = City::firstOrCreate(
                [
                    'country_id' => $country->id,
                    'name_ar' => $airport['city'],
                ],
                [
                    'name_en' => $airport['city_en'],
                    'code' => $airport['city_code'],
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );

            Airport::firstOrCreate(
                ['name' => $airport['name']],
                [
                    'country_id' => $country->id,
                    'city_id' => $city->id,
                ]
            );
        }
    }

    public static function cleanName(?string $name): string
    {
        $name = trim((string) $name);

        return trim((string) preg_replace('/\s*\([A-Z0-9_]{2,16}\)\s*$/u', '', $name));
    }

    private static function displayName(?string $name): string
    {
        $name = self::cleanName($name);

        return self::legacyLabels()->get($name, $name);
    }

    private static function legacyLabels(): Collection
    {
        return self::defaults()
            ->mapWithKeys(fn (array $airport) => [$airport['value'] => $airport['name']]);
    }

    private static function defaults(): Collection
    {
        return collect([
            ['value' => 'RUH', 'name' => 'مطار الملك خالد الدولي - الرياض', 'city' => 'الرياض', 'city_en' => 'Riyadh', 'city_code' => 'RUH'],
            ['value' => 'JED', 'name' => 'مطار الملك عبدالعزيز الدولي - جدة', 'city' => 'جدة', 'city_en' => 'Jeddah', 'city_code' => 'JED'],
            ['value' => 'DMM', 'name' => 'مطار الملك فهد الدولي - الدمام', 'city' => 'الدمام', 'city_en' => 'Dammam', 'city_code' => 'DMM'],
            ['value' => 'MED', 'name' => 'مطار الأمير محمد بن عبدالعزيز الدولي - المدينة المنورة', 'city' => 'المدينة المنورة', 'city_en' => 'Madinah', 'city_code' => 'MED'],
            ['value' => 'TUU', 'name' => 'مطار تبوك الدولي - تبوك', 'city' => 'تبوك', 'city_en' => 'Tabuk', 'city_code' => 'TUU'],
            ['value' => 'AHB', 'name' => 'مطار أبها الإقليمي - أبها', 'city' => 'أبها', 'city_en' => 'Abha', 'city_code' => 'AHB'],
            ['value' => 'TIF', 'name' => 'مطار الطائف الإقليمي - الطائف', 'city' => 'الطائف', 'city_en' => 'Taif', 'city_code' => 'TIF'],
            ['value' => 'ELQ', 'name' => 'مطار القصيم الإقليمي - بريدة', 'city' => 'بريدة', 'city_en' => 'Buraydah', 'city_code' => 'ELQ'],
            ['value' => 'AJF', 'name' => 'مطار الجوف الإقليمي - سكاكا', 'city' => 'سكاكا', 'city_en' => 'Sakaka', 'city_code' => 'AJF'],
            ['value' => 'URY', 'name' => 'مطار الجوف الإقليمي - سكاكا', 'city' => 'سكاكا', 'city_en' => 'Sakaka', 'city_code' => 'AJF'],
            ['value' => 'HAS', 'name' => 'مطار حائل الإقليمي - حائل', 'city' => 'حائل', 'city_en' => 'Hail', 'city_code' => 'HAS'],
            ['value' => 'BHH', 'name' => 'مطار الباحة الإقليمي - الباحة', 'city' => 'الباحة', 'city_en' => 'Al Baha', 'city_code' => 'BHH'],
            ['value' => 'GIZ', 'name' => 'مطار جازان الإقليمي - جازان', 'city' => 'جازان', 'city_en' => 'Jazan', 'city_code' => 'GIZ'],
            ['value' => 'EAM', 'name' => 'مطار نجران الإقليمي - نجران', 'city' => 'نجران', 'city_en' => 'Najran', 'city_code' => 'EAM'],
            ['value' => 'URY_QURAYYAT', 'name' => 'مطار القريات الإقليمي - القريات', 'city' => 'القريات', 'city_en' => 'Qurayyat', 'city_code' => 'URY'],
        ]);
    }
}
