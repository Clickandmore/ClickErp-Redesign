<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Hourly\HourlyCategorySetting;
use App\Models\Hourly\HourlyDriver;
use App\Models\Hourly\HourlyPackage;
use App\Models\Hourly\HourlyPackageTier;
use App\Models\Hourly\HourlyRentalOrder;
use App\Models\Hourly\HourlyServiceCategory;
use App\Models\Hourly\HourlyTeam;
use App\Models\Hourly\HourlyTeamArea;
use App\Models\Hourly\HourlyTeamMember;
use App\Models\Hourly\ServiceDistrict;
use App\Models\ingaz\Maid;
use App\Models\ingaz\UserAddress;
use App\Models\ShelterPlaces;
use App\Services\Hourly\DriverTeamService;
use App\Services\Hourly\HourlyCategorySettings;
use App\Services\Hourly\HourlyOrderService;
use App\Services\Hourly\HourlyPaymentService;
use App\Services\Rental\RentalMaidQuery;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * بيانات تجريبية كاملة لموديل التأجير بالساعة — للبيئة المحلية وبيئة الاختبار فقط.
 *
 *     php artisan db:seed --class=HourlyDemoSeeder
 *
 * - قابلة لإعادة التشغيل: تحذف ما أنشأته (الموسوم بـ [DEMO] أو DEMO-) ثم تعيد إنشاءه، ولا تمس غيره.
 * - الكتالوج يُدخل مباشرة، أما الطلبات والتحصيل فتمر عبر HourlyOrderService و HourlyPaymentService
 *   الحقيقيتين، فإنشاؤها اختبار للمنطق نفسه؛ أي رفض يُطبع ولا يوقف البذرة.
 * - العاملات المفعّلات للساعات يُعاد وضعهن الأصلي عند الحذف.
 */
class HourlyDemoSeeder extends Seeder
{
    private const TAG = '[DEMO]';
    /** نطاق هواتف محجوز للعملاء التجريبيين حتى لا يُربط أي شيء تجريبي بعميل حقيقي. */
    private const DEMO_PHONE_PREFIX = '5990000';

    /** الحي => [lat, lng, نصف ارتفاع, نصف عرض, رمز الفريق] */
    private const DISTRICTS = [
        'الملقا' => [24.8005, 46.6120, 0.020, 0.024, 'RYD-N', 'Al Malqa'],
        'الياسمين' => [24.8260, 46.6480, 0.018, 0.022, 'RYD-N', 'Al Yasmin'],
        'النرجس' => [24.8500, 46.6850, 0.020, 0.024, 'RYD-N', 'An Narjis'],
        'العليا' => [24.6930, 46.6830, 0.016, 0.014, 'RYD-C', 'Al Olaya'],
        'السليمانية' => [24.7050, 46.7080, 0.013, 0.014, 'RYD-C', 'As Sulimaniyah'],
        'الملز' => [24.6660, 46.7300, 0.016, 0.018, 'RYD-C', 'Al Malaz'],
        'الروضة' => [24.7330, 46.7720, 0.016, 0.020, 'RYD-E', 'Ar Rawdah'],
        'النسيم' => [24.7350, 46.8300, 0.020, 0.026, 'RYD-E', 'An Nasim'],
        'عرقة' => [24.6850, 46.5820, 0.018, 0.022, 'RYD-W', 'Irqah'],
        'ظهرة لبن' => [24.6330, 46.5600, 0.020, 0.024, 'RYD-W', 'Dhahrat Laban'],
        'العزيزية' => [24.5930, 46.7600, 0.020, 0.026, 'RYD-S', 'Al Aziziyah'],
    ];

    private array $report = [];

    public function run()
    {
        // حماية الإنتاج: البذرة تحوّل عاملات حقيقيات إلى وضع الساعات وتنشئ طلبات، فلا تعمل على بيئة production
        // إلا بإذن صريح لخادم اختبار ضُبط APP_ENV فيه على production:
        //     HOURLY_DEMO_ALLOW=1 php artisan db:seed --class=HourlyDemoSeeder --force
        if (app()->environment('production') && !filter_var(env('HOURLY_DEMO_ALLOW', getenv('HOURLY_DEMO_ALLOW')), FILTER_VALIDATE_BOOLEAN)) {
            $this->command->error('رُفض التشغيل: APP_ENV=production. لم تُضف أي بيانات.');
            $this->command->line('إن كان هذا خادم اختبار لا خادم العملاء الفعلي، شغّل:');
            $this->command->line('  HOURLY_DEMO_ALLOW=1 php artisan db:seed --class=HourlyDemoSeeder --force');
            $this->command->line('تنبيه: البذرة تحوّل حتى 12 عاملة جاهزة للإيجار إلى وضع الساعات (وتعيدهن عند إعادة التشغيل/الحذف).');

            return;
        }
        foreach (['hourly_teams', 'hourly_service_districts', 'hourly_service_categories', 'hourly_packages', 'hourly_drivers', 'hourly_rental_orders'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->command->error("الجدول {$table} غير موجود — شغّل هجرات الموديل أولًا.");

                return;
            }
        }
        $shelters = ShelterPlaces::query()->orderBy('id')->get();
        if ($shelters->isEmpty()) {
            $this->command->error('لا يوجد أي مكان إيواء لتنبثق منه الفرق.');

            return;
        }
        if (!HourlyRentalSettings::isEnabled()) {
            HourlyRentalSettings::persist(['is_enabled' => true]);
            $this->command->warn('فُعِّل موديل التأجير بالساعة لأن البيانات التجريبية تحتاجه.');
        }

        $this->purge();

        [$city, $teams, $districts, $categories, $packages] = DB::transaction(function () use ($shelters) {
            $city = $this->riyadh();
            $teams = $this->teams($shelters, $city);
            $districts = $this->districts($city, $teams);
            $categories = $this->categories($districts, $teams);
            $packages = $this->packages($categories, $city);
            $this->drivers($teams);
            $this->hourlyMaids($teams, $categories);

            return [$city, $teams, $districts, $categories, $packages];
        });

        $addresses = $this->addresses($city);
        $this->orders($packages, $addresses);

        $this->command->newLine();
        foreach ($this->report as $line) {
            $this->command->line($line);
        }
        $this->command->info('انتهت البيانات التجريبية. ابدأ من /admin/hourly/teams ثم /admin/hourly/orders');
    }

    // ------------------------------------------------------------------ الحذف

    private function purge(): void
    {
        DB::transaction(function () {
            $orderIds = HourlyRentalOrder::query()->where('notes', 'like', self::TAG . '%')->pluck('id');
            if ($orderIds->isNotEmpty()) {
                $visitIds = DB::table('hourly_visits')->whereIn('hourly_rental_order_id', $orderIds)->pluck('id');
                DB::table('hourly_visit_workers')->whereIn('hourly_visit_id', $visitIds)->delete();
                DB::table('hourly_visits')->whereIn('id', $visitIds)->delete();
                DB::table('hourly_order_payments')->whereIn('hourly_rental_order_id', $orderIds)->delete();
                DB::table('hourly_order_status_logs')->whereIn('hourly_rental_order_id', $orderIds)->delete();
                DB::table('hourly_rental_orders')->whereIn('id', $orderIds)->delete();
            }
            UserAddress::query()->where('notes', 'like', self::TAG . '%')->delete();
            // العملاء التجريبيون فقط: هواتفهم في نطاق محجوز ولا طلبات أخرى لهم
            $demoUsers = DB::table('users')->where('phone', 'like', self::DEMO_PHONE_PREFIX . '%')->where('name', 'like', self::TAG . '%')->pluck('id');
            if ($demoUsers->isNotEmpty() && !DB::table('hourly_rental_orders')->whereIn('user_id', $demoUsers)->exists()) {
                DB::table('user_addresses')->whereIn('user_id', $demoUsers)->delete();
                DB::table('users')->whereIn('id', $demoUsers)->delete();
            }

            $packageIds = HourlyPackage::withTrashed()->where('code', 'like', 'DEMO-%')->pluck('id');
            if ($packageIds->isNotEmpty()) {
                DB::table('hourly_package_translations')->whereIn('hourly_package_id', $packageIds)->delete();
                DB::table('hourly_package_city')->whereIn('hourly_package_id', $packageIds)->delete();
                DB::table('hourly_package_nationality')->whereIn('hourly_package_id', $packageIds)->delete();
                if (Schema::hasTable('hourly_package_logs')) {
                    DB::table('hourly_package_logs')->whereIn('hourly_package_id', $packageIds)->delete();
                }
                HourlyPackage::withTrashed()->whereIn('id', $packageIds)->forceDelete();
            }

            $categoryIds = HourlyServiceCategory::withTrashed()->where('description_ar', 'like', self::TAG . '%')->pluck('id');
            if ($categoryIds->isNotEmpty()) {
                foreach (['hourly_category_district', 'hourly_category_settings', 'hourly_category_payment_methods', 'hourly_category_notes', 'hourly_category_closures', 'hourly_service_category_logs'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->whereIn('service_category_id', $categoryIds)->delete();
                    }
                }
                HourlyServiceCategory::withTrashed()->whereIn('id', $categoryIds)->forceDelete();
            }

            $districtIds = ServiceDistrict::query()->where('slug', 'like', 'demo-%')->pluck('id');
            if ($districtIds->isNotEmpty()) {
                DB::table('hourly_team_district')->whereIn('district_id', $districtIds)->delete();
                ServiceDistrict::query()->whereIn('id', $districtIds)->delete();
            }

            $driverIds = HourlyDriver::withTrashed()->where('notes', 'like', self::TAG . '%')->pluck('id');
            if ($driverIds->isNotEmpty()) {
                HourlyTeamMember::query()->where('member_type', 'driver')->whereIn('member_id', $driverIds)->delete();
                DB::table('hourly_team_driver_leaders')->whereIn('driver_id', $driverIds)->delete();
                HourlyDriver::withTrashed()->whereIn('id', $driverIds)->forceDelete();
            }

            // إعادة العاملات إلى وضعهن قبل البذرة
            Maid::query()->where('hourly_settings->demo', true)->get()->each(function (Maid $maid) {
                $previous = $maid->hourly_settings['demo_previous_mode'] ?? 'regular';
                $maid->forceFill(['rental_mode' => $previous, 'hourly_settings' => null, 'hourly_username' => null, 'hourly_password' => null])->save();
            });

            $teamIds = HourlyTeam::withTrashed()->where('description', 'like', self::TAG . '%')->pluck('id');
            if ($teamIds->isNotEmpty()) {
                foreach (['hourly_team_district', 'hourly_team_leaders', 'hourly_team_driver_leaders'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->whereIn('hourly_team_id', $teamIds)->delete();
                    }
                }
                HourlyTeamMember::whereIn('hourly_team_id', $teamIds)->delete();
                HourlyTeamArea::whereIn('hourly_team_id', $teamIds)->delete();
                HourlyTeam::withTrashed()->whereIn('id', $teamIds)->forceDelete();
                $this->command->warn('حُذفت بيانات تجريبية سابقة.');
            }
        });
        app(HourlyCategorySettings::class)->forget();
    }

    // ------------------------------------------------------------------ الكتالوج

    private function riyadh(): City
    {
        $city = City::query()->where('name_ar', 'like', '%الرياض%')->orWhere('name_en', 'like', '%Riyadh%')->first();
        if (!$city) {
            throw new \RuntimeException('مدينة الرياض غير موجودة في جدول المدن.');
        }
        if ($city->latitude === null || $city->longitude === null) {
            $city->update(['latitude' => 24.7136, 'longitude' => 46.6753]);
        }

        return $city;
    }

    private function teams($shelters, City $city): array
    {
        $main = $shelters->first();
        $second = $shelters->get(1) ?: $main;
        $defs = [
            'RYD-N' => ['فريق شمال الرياض', '#0B6461', $main, [24.8605, 46.8120], ['07:00', '16:00'], true],
            'RYD-C' => ['فريق وسط الرياض', '#B45309', $main, [24.8605, 46.8120], ['07:30', '16:30'], true],
            'RYD-E' => ['فريق شرق الرياض', '#7C3AED', $main, [24.8605, 46.8120], ['07:00', '16:00'], true],
            'RYD-W' => ['فريق غرب الرياض', '#BE123C', $second, [24.7000, 46.6200], ['08:00', '17:00'], true],
            'RYD-S' => ['فريق جنوب الرياض', '#475569', $main, [24.8605, 46.8120], ['07:00', '16:00'], false],
        ];
        $teams = [];
        $i = 0;
        foreach ($defs as $code => [$name, $color, $shelter, $base, $hours, $active]) {
            $teams[$code] = HourlyTeam::create([
                'shelter_place_id' => $shelter->id, 'city_id' => $city->id, 'name' => $name, 'code' => $code, 'color' => $color,
                'description' => self::TAG . ' بيانات تجريبية — تُحذف بإعادة تشغيل البذرة.',
                'base_lat' => $base[0], 'base_lng' => $base[1], 'working_start' => $hours[0], 'working_end' => $hours[1],
                'is_active' => $active, 'sort_order' => ++$i,
            ]);
        }
        $this->report[] = 'الفرق: ' . count($teams) . ' (واحد متوقف)';

        return $teams;
    }

    private function districts(City $city, array $teams): array
    {
        $districts = [];
        $order = [];
        foreach (self::DISTRICTS as $name => [$lat, $lng, $dLat, $dLng, $teamCode, $nameEn]) {
            $polygon = $this->blob($lat, $lng, $dLat, $dLng, crc32($name));
            $district = ServiceDistrict::create([
                'city_id' => $city->id, 'name' => $name, 'name_en' => $nameEn, 'slug' => 'demo-' . strtolower(str_replace(' ', '-', $nameEn)),
                'latitude' => $lat, 'longitude' => $lng, 'polygon' => $polygon, 'is_active' => true,
            ]);
            $districts[$name] = $district;
            $team = $teams[$teamCode];

            $area = new HourlyTeamArea(['hourly_team_id' => $team->id, 'name' => $name, 'is_active' => true, 'sort_order' => $order[$teamCode] = ($order[$teamCode] ?? -1) + 1]);
            $area->fillGeometry($polygon)->save();
            DB::table('hourly_team_district')->insert(['hourly_team_id' => $team->id, 'district_id' => $district->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        $this->report[] = 'الأحياء بمضلعاتها: ' . count($districts) . ' — مربوطة بفرقها';

        return $districts;
    }

    private function categories(array $districts, array $teams): array
    {
        $defs = [
            'cleaning' => ['demo-hourly-cleaning', 'خدمة كل ساعة', 'Hourly cleaning', 'fa-broom', '#0B6461', true,
                ['auto_assign' => ['web_app' => false, 'panel' => true], 'auto_confirm_postpaid' => ['web_app' => false, 'panel' => true], 'customer_worker_selection' => true]],
            'villa' => ['demo-villa-cleaning', 'تنظيف الفلل', 'Villa cleaning', 'fa-house-chimney', '#B45309', true,
                ['auto_assign' => ['web_app' => false, 'panel' => true]]],
            'office' => ['demo-office-cleaning', 'تنظيف المكاتب', 'Office cleaning', 'fa-building', '#475569', false, []],
        ];
        $categories = [];
        $i = 0;
        foreach ($defs as $key => [$slug, $ar, $en, $icon, $color, $active, $settings]) {
            $category = HourlyServiceCategory::create([
                'slug' => $slug, 'name_ar' => $ar, 'name_en' => $en, 'description_ar' => self::TAG . ' فئة تجريبية.', 'description_en' => 'Demo category.',
                'icon_type' => 'library', 'icon' => $icon, 'color' => $color, 'sort_order' => ++$i, 'is_active' => $active,
            ]);
            $categories[$key] = $category;

            foreach (self::DISTRICTS as $name => $def) {
                $team = $teams[$def[4]];
                // حي العزيزية يبقى خارج تغطية الفئات عمدًا لاختبار الرفض
                if ($name === 'العزيزية') {
                    continue;
                }
                DB::table('hourly_category_district')->insert([
                    'service_category_id' => $category->id, 'district_id' => $districts[$name]->id, 'team_id' => $team->id,
                    'is_active' => $name !== 'ظهرة لبن', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach (['cash' => [1, 1, 1], 'card_on_delivery' => [1, 1, 1], 'bank_transfer' => [1, 1, 1], 'moyasar' => [1, 0, 0]] as $method => [$web, $employee, $branch]) {
                DB::table('hourly_category_payment_methods')->insert([
                    'service_category_id' => $category->id, 'payment_method' => $method, 'web_app' => $web, 'employee' => $employee, 'branch' => $branch,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ([['يرجى توفير أدوات ومواد التنظيف ما لم تُطلب ضمن الخدمة.', 'Please provide cleaning tools unless included.'], ['تصل العاملة مع سائق الشركة وتُستلم عند انتهاء المدة.', 'The worker arrives with the company driver.']] as $n => [$textAr, $textEn]) {
                DB::table('hourly_category_notes')->insert(['service_category_id' => $category->id, 'text_ar' => $textAr, 'text_en' => $textEn, 'sort_order' => $n + 1, 'created_at' => now(), 'updated_at' => now()]);
            }
            if ($settings) {
                HourlyCategorySetting::updateOrCreate(['service_category_id' => $category->id], ['settings' => $settings]);
            }
        }
        app(HourlyCategorySettings::class)->forget();
        $this->report[] = 'الفئات: 3 (واحدة متوقفة) — بمناطق خدمة وطرق دفع وملاحظات. «ظهرة لبن» موقوفة و«العزيزية» خارج التغطية عمدًا';

        return $categories;
    }

    private function packages(array $categories, City $city): array
    {
        $tiers = HourlyPackageTier::query()->pluck('id', 'code');
        $defs = [
            'DEMO-ONE-4H' => ['cleaning', 'one_off', 'زيارة واحدة — 4 ساعات', 'Single visit — 4 hours', 4, 1, 1, 30, 115.00, 57.50],
            'DEMO-ONE-8H' => ['cleaning', 'one_off', 'زيارة واحدة — 8 ساعات', 'Single visit — 8 hours', 8, 1, 1, 30, 210.00, 105.00],
            'DEMO-W1-4V' => ['cleaning', 'weekly_1', 'باقة أسبوعية — 4 زيارات', 'Weekly package — 4 visits', 4, 1, 4, 45, 105.00, 52.50],
            'DEMO-W2-8V' => ['cleaning', 'weekly_2', 'باقة مرتين أسبوعيًا — 8 زيارات', 'Twice weekly — 8 visits', 4, 1, 8, 60, 99.00, 49.50],
            'DEMO-VILLA-6H' => ['villa', 'one_off', 'تنظيف فيلا — عاملتان 6 ساعات', 'Villa cleaning — 2 workers, 6 hours', 6, 2, 1, 30, 330.00, 150.00],
            'DEMO-OFFICE-4H' => ['office', 'one_off', 'تنظيف مكتب — 4 ساعات', 'Office cleaning — 4 hours', 4, 1, 1, 30, 140.00, 70.00],
        ];
        $packages = [];
        $i = 0;
        foreach ($defs as $code => [$cat, $tier, $ar, $en, $hours, $workers, $visits, $validity, $price, $extraWorker]) {
            $values = [
                'code' => $code, 'service_category_id' => $categories[$cat]->id, 'category' => $categories[$cat]->name_ar,
                'hours_per_visit' => $hours, 'workers_per_visit' => $workers, 'visits_count' => $visits, 'validity_days' => $validity,
                'price_per_visit' => $price, 'extra_worker_price' => $extraWorker, 'extra_hour_price' => round($price / $hours, 2),
                'transport_fee' => 0, 'tax_rate' => 15, 'transport_included' => true, 'expiry_policy' => 'forfeit',
                'is_active' => $cat !== 'office', 'sort_order' => ++$i,
            ];
            foreach (['package_type' => $tier, 'package_tier_id' => $tiers[$tier] ?? null, 'total_price' => round($price * $visits, 2), 'duration_minutes' => $hours * 60, 'for_home' => true, 'for_company' => $cat === 'office', 'show_on_app' => true] as $column => $value) {
                if (Schema::hasColumn('hourly_packages', $column)) {
                    $values[$column] = $value;
                }
            }
            $package = HourlyPackage::create($values);
            $package->translations()->createMany([['locale' => 'ar', 'title' => $ar, 'description' => self::TAG . ' ' . $ar], ['locale' => 'en', 'title' => $en, 'description' => 'Demo: ' . $en]]);
            $package->cities()->attach($city->id);
            $packages[$code] = $package;
        }
        $this->report[] = 'عناصر الخدمة والباقات: ' . count($packages) . ' (عنصر المكاتب متوقف مع فئته)';

        return $packages;
    }

    private function drivers(array $teams): void
    {
        $schedule = [];
        foreach (array_keys(HourlyDriver::DAYS) as $day) {
            $schedule[$day] = $day === 'friday' ? ['mode' => 'off'] : ['mode' => 'hours', 'start' => '06:00', 'end' => '18:00'];
        }
        $defs = [
            ['خالد العتيبي', 'DRV-101', 'RYD-N', 12, now()->addMonths(10)],
            ['محمد إقبال', 'DRV-102', 'RYD-C', 7, now()->addDays(18)],      // إقامة توشك على الانتهاء → تنبيه
            ['عبدالله الشهري', 'DRV-103', 'RYD-E', 12, now()->addMonths(8)],
            ['راجو كومار', 'DRV-104', 'RYD-W', 4, now()->subDays(5)],        // إقامة منتهية
            ['سعيد القحطاني', 'DRV-105', null, 7, now()->addMonths(6)],      // بلا فريق
        ];
        $service = app(DriverTeamService::class);
        foreach ($defs as $n => [$name, $number, $teamCode, $capacity, $residency]) {
            $driver = HourlyDriver::create([
                'name' => $name, 'employee_number' => $number, 'gender' => 'male', 'phone' => '05500001' . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
                'work_start_date' => now()->subMonths(6 + $n)->toDateString(), 'contract_start_date' => now()->subMonths(6 + $n)->toDateString(),
                'contract_end_date' => now()->addMonths(18)->toDateString(), 'residency_expiry_date' => Carbon::parse($residency)->toDateString(),
                'residency_number' => '24' . random_int(10000000, 99999999), 'vehicle_capacity' => $capacity, 'work_schedule' => $schedule,
                'is_active' => $n !== 3, 'account_enabled' => true, 'login_access' => 'mobile', 'username' => 'demo.driver' . ($n + 1),
                'login_phone' => '5500001' . str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'password' => Hash::make('Demo@12345'),
                'notes' => self::TAG . ' سائق تجريبي — كلمة المرور Demo@12345',
            ]);
            if ($teamCode) {
                try {
                    $service->assign($driver, $teams[$teamCode], 'primary');
                } catch (Throwable $e) {
                    $this->report[] = "  ! تعذر ربط السائق {$name} بالفريق {$teamCode}: " . mb_substr($e->getMessage(), 0, 120);
                }
            }
        }
        $this->report[] = 'السائقون: 5 — أحدهم بإقامة توشك على الانتهاء، وآخر متوقف بإقامة منتهية، وثالث بلا فريق';
    }

    /** العاملات المؤهلات فعلًا للإيجار تُفعَّل للساعات وتوزَّع على الفرق. */
    private function hourlyMaids(array $teams, array $categories): void
    {
        try {
            $ids = RentalMaidQuery::applyReadyForRental(RentalMaidQuery::applyArrivalGate(Maid::query()))
                ->whereHas('jobTypes', fn ($q) => $q->where('job_types.id', RentalMaidQuery::rentalJobTypeId()))
                ->orderBy('maids.id')->limit(12)->pluck('maids.id');
        } catch (Throwable $e) {
            $this->report[] = '  ! تعذر استعلام العاملات الجاهزات للإيجار: ' . mb_substr($e->getMessage(), 0, 140);

            return;
        }
        if ($ids->isEmpty()) {
            $this->report[] = '  ! لا توجد عاملات جاهزات للإيجار في القاعدة — الطلبات ذات الإسناد الآلي ستُرفض.';

            return;
        }

        $schedule = [];
        foreach (array_keys(HourlyDriver::DAYS) as $day) {
            $schedule[$day] = $day === 'friday' ? ['mode' => 'off'] : ['mode' => 'hours', 'start' => '07:00', 'end' => '19:00'];
        }
        $plan = ['RYD-C', 'RYD-C', 'RYD-C', 'RYD-C', 'RYD-N', 'RYD-N', 'RYD-N', 'RYD-E', 'RYD-E', 'RYD-E', 'RYD-W', 'RYD-W'];
        $counts = [];
        foreach ($ids->values() as $n => $maidId) {
            $maid = Maid::find($maidId);
            $teamCode = $plan[$n] ?? 'RYD-C';
            // حساب تطبيق العاملة مفعّل بكلمة المرور نفسها المستخدمة للسائقين التجريبيين (Demo@12345) حتى تُختبر واجهات الجوال فورًا.
            $maid->forceFill([
                'rental_mode' => 'hourly',
                'hourly_username' => $maid->hourly_username ?: 'HM-' . $maid->id,
                'hourly_password' => $maid->hourly_password ?: Hash::make('Demo@12345'),
                'hourly_is_suspended' => false,
                'hourly_settings' => [
                    'demo' => true, 'demo_previous_mode' => $maid->rental_mode ?: 'regular',
                    'requires_driver' => $n % 5 !== 4, 'max_orders_per_hour' => 1, 'account_enabled' => true, 'login_access' => 'mobile',
                    'work_schedule' => $schedule,
                ],
            ])->save();
            HourlyTeamMember::create([
                'hourly_team_id' => $teams[$teamCode]->id, 'member_type' => HourlyTeamMember::TYPE_MAID, 'member_id' => $maid->id,
                'role' => HourlyTeamMember::ROLE_PRIMARY, 'is_leader' => ($counts[$teamCode] ?? 0) === 0, 'is_active' => true,
                'joined_at' => now()->subDays(30)->toDateString(),
            ]);
            $counts[$teamCode] = ($counts[$teamCode] ?? 0) + 1;
        }
        $this->report[] = 'عاملات الساعات: ' . $ids->count() . ' مفعّلة وموزعة — ' . collect($counts)->map(fn ($c, $t) => "$t:$c")->implode(' · ') . ' — دخول التطبيق: HM-<id> / Demo@12345';
    }

    // ------------------------------------------------------------------ العملاء والطلبات

    private function addresses(City $city): array
    {
        // عملاء تجريبيون خاصون بالبذرة: لا يُربط أي عنوان أو طلب تجريبي بعميل حقيقي
        $users = collect();
        foreach (['سارة المطيري', 'نورة العنزي', 'فهد الدوسري'] as $n => $name) {
            $phone = self::DEMO_PHONE_PREFIX . str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT);
            $id = DB::table('users')->where('phone', $phone)->value('id');
            if (!$id) {
                [$first, $last] = explode(' ', $name, 2);
                $values = ['name' => self::TAG . ' ' . $name, 'first_name' => $first, 'last_name' => $last, 'phone_code' => '966', 'phone' => $phone,
                    'password' => Hash::make(\Illuminate\Support\Str::random(32)), 'register_from' => 'app', 'status' => 1, 'is_blocked' => 0,
                    'created_at' => now(), 'updated_at' => now()];
                $id = DB::table('users')->insertGetId(array_intersect_key($values, array_flip(Schema::getColumnListing('users'))));
            }
            $users->push($id);
        }
        $spots = [
            'olaya' => ['العليا', 24.6930, 46.6830], 'malaz' => ['الملز', 24.6660, 46.7300], 'malqa' => ['الملقا', 24.8005, 46.6120],
            'rawdah' => ['الروضة', 24.7330, 46.7720], 'laban' => ['ظهرة لبن', 24.6330, 46.5600], 'aziziyah' => ['العزيزية', 24.5930, 46.7600],
        ];
        $addresses = [];
        $n = 0;
        foreach ($spots as $key => [$district, $lat, $lng]) {
            $userId = $users[$n % $users->count()];
            $values = [
                'user_id' => $userId, 'title' => 'المنزل — ' . $district, 'address' => "حي {$district}، الرياض — عنوان تجريبي",
                'name' => 'عميل تجريبي', 'phone' => '0550000' . (100 + $n), 'notes' => self::TAG . ' عنوان تجريبي للتأجير بالساعة',
                'latitude' => $lat, 'longitude' => $lng, 'city_id' => $city->id, 'district' => $district,
            ];
            foreach (['building' => 'فيلا ' . (12 + $n), 'unit' => (string) ($n + 1), 'contact_phone' => '0550000' . (100 + $n)] as $column => $value) {
                if (Schema::hasColumn('user_addresses', $column)) {
                    $values[$column] = $value;
                }
            }
            $addresses[$key] = UserAddress::create(array_intersect_key($values, array_flip(Schema::getColumnListing('user_addresses'))));
            $n++;
        }
        $this->report[] = 'العملاء التجريبيون: ' . $users->count() . ' — عناوينهم: ' . count($addresses) . ' بإحداثيات داخل الأحياء';

        return $addresses;
    }

    private function orders(array $packages, array $addresses): void
    {
        $adminId = (int) DB::table('admins')->orderBy('id')->value('id');
        $orders = app(HourlyOrderService::class);
        $payments = app(HourlyPaymentService::class);

        $cases = [
            ['زيارة واحدة نقدًا في العليا — إسناد وتأكيد آليان', 'DEMO-ONE-4H', 'olaya', 'cash', [$this->slot(2, '09:00')], null, null],
            // التحويل البنكي لا يُحصَّل بلا إثبات ولا يُفعَّل قبل تحصيله: يبقى معلقًا عند الصرّاف، وهي الحالة الواقعية للاختبار اليدوي
            ['باقة 4 زيارات بتحويل بنكي في الملز — معلقة بانتظار إثبات التحويل', 'DEMO-W1-4V', 'malaz', 'bank_transfer', [$this->slot(3, '10:00'), $this->slot(10, '10:00')], null, null],
            ['زيارة 8 ساعات ببطاقة عند التسليم في الملقا — مسددة بالكامل', 'DEMO-ONE-8H', 'malqa', 'card_on_delivery', [$this->slot(4, '08:00')], null, 'full'],
            ['باقة 8 زيارات نقدًا في الروضة — بلا مواعيد', 'DEMO-W2-8V', 'rawdah', 'cash', [], null, null],
            ['فيلا بعاملتين في العليا', 'DEMO-VILLA-6H', 'olaya', 'cash', [$this->slot(5, '09:00')], 'confirm', null],
            ['يجب أن يُرفض: الحي موقوف مؤقتًا', 'DEMO-ONE-4H', 'laban', 'cash', [], null, null],
            ['يجب أن يُرفض: الحي خارج التغطية', 'DEMO-ONE-4H', 'aziziyah', 'cash', [], null, null],
            ['يجب أن يُرفض: فئة المكاتب متوقفة', 'DEMO-OFFICE-4H', 'olaya', 'cash', [], null, null],
        ];

        $this->report[] = 'الطلبات (عبر HourlyOrderService):';
        foreach ($cases as [$label, $code, $addressKey, $method, $starts, $then, $collect]) {
            $expectReject = str_starts_with($label, 'يجب أن يُرفض');
            try {
                $address = $addresses[$addressKey];
                $order = $orders->create((int) $address->user_id, [
                    'hourly_package_id' => $packages[$code]->id, 'address_id' => $address->id, 'payment_method' => $method,
                    'payment_channel' => 'employee', 'scheduled_starts' => $starts, 'notes' => self::TAG . ' ' . $label,
                ], $adminId);

                if ($then === 'confirm' && $order->fresh()->status === 'pending_confirmation') {
                    $orders->confirm($order->fresh(), $adminId);
                }
                if ($collect) {
                    $fresh = $order->fresh();
                    $amount = $collect === 'full' ? (float) $fresh->total_amount : (float) $collect;
                    $payments->collect($fresh, ['amount' => $amount, 'method' => $method], $adminId);
                }
                $fresh = $order->fresh();
                $workers = DB::table('hourly_visit_workers')->whereIn('hourly_visit_id', $fresh->visits()->pluck('id'))->count();
                $this->report[] = sprintf('  %s #%d %s — %s/%s — %s ر.س — زيارات %d — عاملات مسندة %d',
                    $expectReject ? '✗ كان يجب رفضه' : '✓', $fresh->id, $label, $fresh->status, $fresh->payment_status,
                    number_format((float) $fresh->total_amount, 2), $fresh->visits()->count(), $workers);
            } catch (Throwable $e) {
                $message = $e instanceof \Illuminate\Validation\ValidationException ? collect($e->errors())->flatten()->implode(' ') : get_class($e) . ': ' . $e->getMessage();
                $this->report[] = sprintf('  %s %s → %s', $expectReject ? '✓ رُفض كما يجب' : '✗ فشل', $label, mb_substr($message, 0, 170));
            }
        }
    }

    /** موعد بعد عدد أيام، مع تجاوز الجمعة لأن جدول العاملات التجريبي يغلقها. */
    private function slot(int $days, string $time): string
    {
        $date = now()->addDays($days);
        if ($date->isFriday()) {
            $date->addDay();
        }

        return $date->format('Y-m-d') . ' ' . $time;
    }

    /** مضلع غير منتظم من 9 نقاط حول مركز — ثابت الشكل لنفس الاسم حتى لا يتغير بين التشغيلات. */
    private function blob(float $lat, float $lng, float $dLat, float $dLng, int $seed): array
    {
        mt_srand($seed);
        $points = [];
        $n = 9;
        for ($i = 0; $i < $n; $i++) {
            $angle = 2 * M_PI * $i / $n;
            $r = 0.78 + (mt_rand(0, 1000) / 1000) * 0.32;
            $points[] = [round($lat + sin($angle) * $dLat * $r, 7), round($lng + cos($angle) * $dLng * $r, 7)];
        }
        mt_srand();

        return $points;
    }
}
