<?php

namespace Database\Seeders;

use App\Models\Hourly\{HourlyPackage, HourlyPackageTier, HourlyServiceCategory, ServiceDistrict};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Sample catalog only: preserves existing prices, coverage, workers and orders on reruns. */
class HourlyBookingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->call(HourlyPackageTiersSeeder::class);
            $districts = ServiceDistrict::where('is_active', true)
                ->whereHas('city', fn ($q) => $q->saudi()->active())->get();
            $definitions = [
                ['hourly-cleaning', 'خدمة كل ساعة', 'Hourly cleaning', 4, 1, 200],
                ['villa-cleaning', 'تنظيف الفلل', 'Villa cleaning', 6, 2, 600],
                ['live-in-service', 'خدمة الإقامة', 'Live-in service', 8, 1, 400],
                ['office-cleaning', 'تنظيف المكاتب', 'Office cleaning', 4, 2, 350],
            ];
            foreach ($definitions as $index => [$slug, $ar, $en, $hours, $workers, $price]) {
                $category = HourlyServiceCategory::withTrashed()
                    ->where(fn ($q) => $q->where('slug', $slug)->orWhere('name_ar', $ar))->first();
                if (!$category) {
                    $category = HourlyServiceCategory::create([
                        'slug'=>$slug, 'name_ar'=>$ar, 'name_en'=>$en, 'is_active'=>true,
                        'icon_type'=>'library', 'icon'=>'fa-broom', 'sort_order'=>$index + 1,
                    ]);
                    $category->districts()->attach($districts->pluck('id')->mapWithKeys(fn ($id) => [$id=>['is_active'=>true]])->all());
                }
                if ($category->trashed()) continue;
                $cityIds = $category->districts()->where('hourly_service_districts.is_active', true)
                    ->wherePivot('is_active', true)->whereHas('city', fn ($q) => $q->saudi()->active())
                    ->pluck('city_id')->unique()->values()->all();
                foreach ([1, 4] as $visits) {
                    $code = 'STARTER-'.strtoupper($slug).'-'.$visits;
                    if (HourlyPackage::withTrashed()->where('code', $code)->exists()) continue;
                    $item = HourlyPackage::create([
                        'code'=>$code, 'category'=>$ar, 'service_category_id'=>$category->id,
                        'package_tier_id'=>HourlyPackageTier::where('code', $visits === 1 ? 'one_off' : 'weekly_1')->value('id'),
                        'hours_per_visit'=>$hours, 'duration_minutes'=>$hours * 60,
                        'workers_per_visit'=>$workers, 'visits_count'=>$visits, 'validity_days'=>$visits === 1 ? 30 : 60,
                        'price_per_visit'=>$visits === 1 ? $price : $price * 0.9,
                        'extra_worker_price'=>round(($visits === 1 ? $price : $price * 0.9) / $workers, 2),
                        'extra_hour_price'=>50, 'tax_rate'=>15,
                        'transport_included'=>true, 'is_active'=>true, 'for_home'=>$slug !== 'office-cleaning',
                        'for_company'=>$slug === 'office-cleaning', 'show_on_app'=>true,
                        'start_times'=>['08:00'=>'all', '08:30'=>'all', '09:00'=>'all', '10:00'=>'all'],
                    ]);
                    foreach (['ar'=>$ar.' - '.($visits === 1 ? 'زيارة واحدة' : 'باقة 4 زيارات'),
                        'en'=>$en.' - '.($visits === 1 ? 'Single visit' : '4 visits')] as $locale=>$title) {
                        $item->translations()->create(['locale'=>$locale, 'title'=>$title,
                            'description'=>$locale === 'ar' ? 'عنصر تجريبي؛ راجع السعر والمدة قبل الاستخدام الفعلي.' : 'Sample item; review price and duration before actual use.']);
                    }
                    $item->cities()->sync($cityIds);
                }
            }
        });
        $this->command?->info('تم تجهيز كتالوج الخدمات الأربع. الأسعار تجريبية، وخدمة الإقامة ممثلة بزيارات 8 ساعات.');
    }
}
