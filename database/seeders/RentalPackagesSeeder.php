<?php

namespace Database\Seeders;

use App\Models\ingaz\Nationality;
use App\Models\ingaz\Package;
use Illuminate\Database\Seeder;

class RentalPackagesSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            $this->packageData('باقة شهرية عامة', 'General monthly package', null, 'monthly', 'month', 1, 3450, 3200, 8, 'باقة إيجار شهرية مناسبة لجميع الجنسيات.'),
            $this->packageData('باقة 3 أشهر عامة', 'General 3 month package', null, 'monthly', 'month', 3, 9900, 9300, 8, 'عرض إيجار ربع سنوي بسعر إجمالي مخفض.'),
            $this->packageData('باقة ساعات يومية', 'Daily hourly package', null, 'hourly', 'hour', 8, 260, 220, 8, 'باقة بالساعات ليوم عمل واحد.'),
        ];

        Nationality::with('translations')
            ->where('id', '>', 0)
            ->orderBy('id')
            ->limit(4)
            ->get()
            ->each(function (Nationality $nationality, int $index) use (&$packages) {
                $baseMonthly = (float) ($nationality->rental_cost ?: $nationality->monthly_salary ?: 2800);
                $netMonthly = max(2200, round($baseMonthly + 450 + ($index * 120), -1));
                $originalMonthly = $netMonthly + 250;

                $title = $nationality->sub_title ?: $nationality->title ?: ('جنسية ' . $nationality->id);

                $packages[] = $this->packageData(
                    'باقة شهرية - ' . $title,
                    'Monthly package - ' . $title,
                    $nationality->id,
                    'monthly',
                    'month',
                    1,
                    $originalMonthly,
                    $netMonthly,
                    8,
                    'باقة شهرية مخصصة لجنسية ' . $title . '.'
                );
            });

        foreach ($packages as $package) {
            $row = Package::where('nationality_id', $package['nationality_id'])
                ->where('rental_type', $package['rental_type'])
                ->where('duration_unit', $package['duration_unit'])
                ->where('duration_count', $package['duration_count'])
                ->whereTranslation('title', $package['title:ar'], 'ar')
                ->first();

            $row ? $row->update($package) : Package::create($package);
        }
    }

    private function packageData(
        string $titleAr,
        string $titleEn,
        ?int $nationalityId,
        string $rentalType,
        string $durationUnit,
        int $durationCount,
        float $originalPrice,
        float $netPrice,
        int $workingHours,
        string $description
    ): array {
        return [
            'title:ar' => $titleAr,
            'title:en' => $titleEn,
            'nationality_id' => $nationalityId,
            'rental_type' => $rentalType,
            'duration_unit' => $durationUnit,
            'duration_count' => $durationCount,
            'days_count' => $this->daysCount($durationUnit, $durationCount),
            'working_hours_per_day' => $workingHours,
            'original_price' => $originalPrice,
            'price_after_discount' => $netPrice,
            'price_with_tax' => round($netPrice * 1.15, 2),
            'is_show' => true,
            'description' => $description,
        ];
    }

    private function daysCount(string $durationUnit, int $durationCount): int
    {
        return match ($durationUnit) {
            'hour' => 1,
            'day' => $durationCount,
            'week' => $durationCount * 7,
            'year' => $durationCount * 365,
            default => $durationCount * 30,
        };
    }
}
