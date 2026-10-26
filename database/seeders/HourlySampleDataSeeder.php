<?php

namespace Database\Seeders;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\City;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\Hourly\HourlyDriver;
use App\Models\Hourly\HourlyPackage;
use App\Models\Hourly\HourlyPackageTier;
use App\Models\Hourly\HourlyServiceCategory;
use App\Models\Hourly\HourlyTeam;
use App\Models\Hourly\HourlyTeamArea;
use App\Models\Hourly\ServiceDistrict;
use App\Models\ingaz\Maid;
use App\Models\ShelterPlaces;
use App\Services\Hourly\DriverTeamService;
use App\Services\Hourly\HourlyCategorySettings;
use App\Services\Hourly\MaidTeamService;
use App\Services\Rental\RentalMaidQuery;
use App\Services\Rental\RentalModeSwitcher;
use App\Services\ShelterCompanyClientResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * بيانات أساسية نظيفة لنظام التأجير بالساعة:
 *   خدمتان، باقتان لكل خدمة، 4 سائقين، 6 عاملات ساعات، و3 أحياء في الرياض و3 في الدمام.
 *
 *     php artisan db:seed --class=HourlySampleDataSeeder
 *
 * - تمر العاملات بالمسار الرسمي: عقد إيواء باسم الشركة ثم التحويل للساعات عبر RentalModeSwitcher،
 *   فتظهر في الإيواء وتُرشَّح للزيارات كأي عاملة حقيقية.
 * - قابلة لإعادة التشغيل دون تكرار: كل سجل يُعرّف بمعرّف ثابت (slug/code/جواز/رقم موظف)،
 *   وما وُجد لا يُعدَّل فتبقى تعديلات الإدارة عليه. لا تحذف أي بيانات.
 * - حي موجود مسبقًا بنفس الاسم في المدينة يُعاد استخدامه بدل إنشاء مكرر.
 * - لا طلبات ولا عملاء ولا كلمات مرور افتراضية: حسابات تطبيق السائقين والعاملات معطلة حتى تفعّلها الإدارة.
 */
class HourlySampleDataSeeder extends Seeder
{
    /** المدن وأحياؤها: الاسم => [lat, lng, الاسم الإنجليزي]. المضلعات تقريبية حول مركز الحي. */
    private const CITIES = [
        'riyadh' => [
            'ar' => 'الرياض', 'en' => 'Riyadh', 'lat' => 24.7136, 'lng' => 46.6753,
            'team' => ['HS-RUH', 'فريق الرياض', '#0B6461'],
            'districts' => [
                'العليا' => [24.6930, 46.6830, 'Al Olaya'],
                'الملقا' => [24.8005, 46.6120, 'Al Malqa'],
                'الياسمين' => [24.8260, 46.6480, 'Al Yasmin'],
            ],
        ],
        'dammam' => [
            'ar' => 'الدمام', 'en' => 'Dammam', 'lat' => 26.4207, 'lng' => 50.0888,
            'team' => ['HS-DMM', 'فريق الدمام', '#1D4ED8'],
            'districts' => [
                'الشاطئ' => [26.4560, 50.1120, 'Ash Shati'],
                'الفيصلية' => [26.4090, 50.0520, 'Al Faisaliyah'],
                'الريان' => [26.3880, 50.0660, 'Ar Rayyan'],
            ],
        ],
    ];

    private array $report = [];

    public function run(): void
    {
        if (app()->environment('production') && !filter_var(env('HOURLY_SEED_ALLOW', getenv('HOURLY_SEED_ALLOW')), FILTER_VALIDATE_BOOLEAN)) {
            $this->line('error', 'رُفض التشغيل: APP_ENV=production. البذرة تنشئ عاملات وعقود إيواء، فلا تعمل على خادم العملاء إلا بإذن صريح:');
            $this->line('line', '  HOURLY_SEED_ALLOW=1 php artisan db:seed --class=HourlySampleDataSeeder --force');

            return;
        }
        foreach (['hourly_teams', 'hourly_service_districts', 'hourly_service_categories', 'hourly_packages', 'hourly_drivers', 'hourly_team_members'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->line('error', "الجدول {$table} غير موجود — شغّل ترحيلات موديول الساعات أولًا.");

                return;
            }
        }
        if (!RentalModeSwitcher::hourlyAvailable()) {
            $this->line('error', 'موديول التأجير بالساعة غير مفعّل. فعّله من «الموديلات» في لوحة Super Admin ثم أعد التشغيل.');

            return;
        }
        $shelter = ShelterPlaces::query()->orderBy('id')->first();
        if (!$shelter) {
            $this->line('error', 'لا يوجد مكان إيواء؛ تنطلق منه الفرق وتُسكن فيه العاملات. أضف مكان إيواء أولًا.');

            return;
        }

        DB::transaction(function () use ($shelter) {
            $cities = [];
            $teams = [];
            $districts = [];
            foreach (self::CITIES as $key => $def) {
                $cities[$key] = $this->city($def);
                $teams[$key] = $this->team($def, $cities[$key], $shelter);
                $districts[$key] = $this->districts($def, $cities[$key], $teams[$key]);
            }

            $categories = $this->categories($districts, $teams);
            $this->packages($categories, $cities);
            $this->drivers($teams);
            $this->maids($teams, $categories, $shelter);
        });
        app(HourlyCategorySettings::class)->forget();

        foreach ($this->report as $line) {
            $this->line('line', $line);
        }
        $this->line('info', 'اكتملت بيانات الساعات. ابدأ من /admin/hourly/teams ثم /admin/hourly/maids');
    }

    // ------------------------------------------------------------------ المدن والفرق والأحياء

    private function city(array $def): City
    {
        $city = City::query()->where('name_ar', 'like', '%' . $def['ar'] . '%')->orWhere('name_en', 'like', '%' . $def['en'] . '%')->first();
        if (!$city) {
            // مدينة ناقصة تُضاف لنفس دولة المدن الموجودة، ولا تُخترع دولة.
            $countryId = City::query()->whereNotNull('country_id')->value('country_id')
                ?: (Schema::hasTable('countries') ? DB::table('countries')->orderBy('id')->value('id') : null);
            if (!$countryId) {
                throw new \RuntimeException("مدينة {$def['ar']} غير موجودة ولا توجد دولة لإضافتها إليها.");
            }
            $city = City::query()->create(['name_ar' => $def['ar'], 'name_en' => $def['en'], 'country_id' => $countryId,
                'latitude' => $def['lat'], 'longitude' => $def['lng'], 'is_active' => true]);
            $this->report[] = "أُضيفت مدينة {$def['ar']}.";
        } elseif ($city->latitude === null || $city->longitude === null) {
            $city->update(['latitude' => $def['lat'], 'longitude' => $def['lng']]);
        }

        return $city;
    }

    private function team(array $def, City $city, ShelterPlaces $shelter): HourlyTeam
    {
        [$code, $name, $color] = $def['team'];
        $team = HourlyTeam::withTrashed()->where('code', $code)->first();
        if ($team) {
            if ($team->trashed()) $team->restore();

            return $team;
        }

        return HourlyTeam::create([
            'shelter_place_id' => $shelter->id, 'city_id' => $city->id, 'name' => $name, 'code' => $code, 'color' => $color,
            'description' => 'فريق الساعات في ' . $def['ar'] . '.', 'base_lat' => $def['lat'], 'base_lng' => $def['lng'],
            'working_start' => '07:00', 'working_end' => '20:00', 'is_active' => true,
            'sort_order' => (int) HourlyTeam::withTrashed()->max('sort_order') + 1,
        ]);
    }

    /** @return array<string, ServiceDistrict> */
    private function districts(array $def, City $city, HourlyTeam $team): array
    {
        $districts = [];
        foreach ($def['districts'] as $name => [$lat, $lng, $nameEn]) {
            $district = ServiceDistrict::query()->where('city_id', $city->id)
                ->where(fn ($q) => $q->where('name', $name)->orWhere('name_en', $nameEn))->first();
            if (!$district) {
                $district = ServiceDistrict::create([
                    'city_id' => $city->id, 'name' => $name, 'name_en' => $nameEn,
                    'slug' => $this->uniqueSlug(Str::slug($def['en'] . '-' . $nameEn)),
                    'latitude' => $lat, 'longitude' => $lng, 'polygon' => $this->polygon($lat, $lng), 'is_active' => true,
                ]);
            }
            $districts[$name] = $district;

            // الحي ضمن نطاق الفريق، ومنطقة على الخريطة بمضلع الحي.
            $team->districts()->syncWithoutDetaching([$district->id => ['is_active' => true]]);
            if (!HourlyTeamArea::query()->where('hourly_team_id', $team->id)->where('name', $name)->exists() && !empty($district->polygon)) {
                $area = new HourlyTeamArea(['hourly_team_id' => $team->id, 'name' => $name, 'is_active' => true,
                    'sort_order' => HourlyTeamArea::query()->where('hourly_team_id', $team->id)->count()]);
                $area->fillGeometry($district->polygon)->save();
            }
        }
        $this->report[] = $def['ar'] . ': ' . implode('، ', array_keys($districts)) . ' — ' . $team->name;

        return $districts;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base !== '' ? $base : 'district';
        for ($i = 2; ServiceDistrict::query()->where('slug', $slug)->exists(); $i++) {
            $slug = $base . '-' . $i;
        }

        return $slug;
    }

    /** مربع صغير (نحو 1.5 كم) حول مركز الحي؛ يكفي لاختبار التغطية ويمكن تعديله من الخريطة. */
    private function polygon(float $lat, float $lng): array
    {
        $d = 0.0135;

        return [[$lat - $d, $lng - $d], [$lat - $d, $lng + $d], [$lat + $d, $lng + $d], [$lat + $d, $lng - $d]];
    }

    // ------------------------------------------------------------------ الخدمات والباقات

    /** @return array<string, HourlyServiceCategory> */
    private function categories(array $districts, array $teams): array
    {
        $defs = [
            'home' => ['home-cleaning', 'التنظيف المنزلي', 'Home cleaning', 'fa-broom', '#0B6461',
                'عاملة منزلية لتنظيف وترتيب المنزل بالساعة.', 'A housekeeper to clean and tidy your home by the hour.'],
            'deep' => ['deep-cleaning', 'التنظيف العميق', 'Deep cleaning', 'fa-spray-can-sparkles', '#B45309',
                'تنظيف شامل للمنازل والفلل بعاملتين أو أكثر.', 'Thorough cleaning for homes and villas with two or more workers.'],
        ];
        $categories = [];
        foreach ($defs as $key => [$slug, $ar, $en, $icon, $color, $descAr, $descEn]) {
            $category = HourlyServiceCategory::withTrashed()->where('slug', $slug)->first();
            if ($category?->trashed()) $category->restore();
            if (!$category) {
                $category = HourlyServiceCategory::create([
                    'slug' => $slug, 'name_ar' => $ar, 'name_en' => $en, 'description_ar' => $descAr, 'description_en' => $descEn,
                    'icon_type' => 'library', 'icon' => $icon, 'color' => $color, 'is_active' => true,
                    'sort_order' => (int) HourlyServiceCategory::withTrashed()->max('sort_order') + 1,
                ]);
                $this->paymentMethods($category);
            }
            $categories[$key] = $category;

            // تغطية الخدمة: كل حي بفريق مدينته؛ رابط قائم لا يُغيَّر.
            foreach ($districts as $cityKey => $cityDistricts) {
                foreach ($cityDistricts as $district) {
                    if (!DB::table('hourly_category_district')->where('service_category_id', $category->id)->where('district_id', $district->id)->exists()) {
                        DB::table('hourly_category_district')->insert([
                            'service_category_id' => $category->id, 'district_id' => $district->id, 'team_id' => $teams[$cityKey]->id,
                            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
        $this->report[] = 'الخدمات: ' . collect($categories)->pluck('name_ar')->implode('، ') . ' — تغطي الأحياء الستة';

        return $categories;
    }

    private function paymentMethods(HourlyServiceCategory $category): void
    {
        if (!Schema::hasTable('hourly_category_payment_methods')) {
            return;
        }
        // ميسر من الموقع والتطبيق فقط؛ البقية من كل القنوات.
        foreach (['cash' => [1, 1, 1], 'card_on_delivery' => [1, 1, 1], 'bank_transfer' => [1, 1, 1], 'moyasar' => [1, 0, 0]] as $method => [$web, $employee, $branch]) {
            DB::table('hourly_category_payment_methods')->insertOrIgnore([
                'service_category_id' => $category->id, 'payment_method' => $method, 'web_app' => $web, 'employee' => $employee, 'branch' => $branch,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function packages(array $categories, array $cities): void
    {
        $tiers = HourlyPackageTier::query()->pluck('id', 'code');
        // الكود => [الخدمة، المستوى، العنوان، العنوان EN، ساعات، عاملات، زيارات، صلاحية بالأيام، سعر الزيارة، العاملة الإضافية]
        $defs = [
            'HS-HOME-4H' => ['home', 'one_off', 'زيارة واحدة — 4 ساعات', 'Single visit — 4 hours', 4, 1, 1, 30, 120.00, 60.00],
            'HS-HOME-W4' => ['home', 'weekly_1', 'باقة أسبوعية — 4 زيارات', 'Weekly package — 4 visits', 4, 1, 4, 45, 110.00, 55.00],
            'HS-DEEP-6H' => ['deep', 'one_off', 'تنظيف عميق — عاملتان 6 ساعات', 'Deep clean — 2 workers, 6 hours', 6, 2, 1, 30, 360.00, 160.00],
            'HS-DEEP-M4' => ['deep', 'monthly', 'باقة شهرية — 4 زيارات بعاملتين', 'Monthly — 4 visits, 2 workers', 6, 2, 4, 120, 340.00, 150.00],
        ];
        $created = 0;
        foreach ($defs as $code => [$cat, $tier, $ar, $en, $hours, $workers, $visits, $validity, $price, $extraWorker]) {
            if (HourlyPackage::withTrashed()->where('code', $code)->exists()) {
                continue;
            }
            $values = [
                'code' => $code, 'service_category_id' => $categories[$cat]->id, 'category' => $categories[$cat]->name_ar,
                'hours_per_visit' => $hours, 'workers_per_visit' => $workers, 'visits_count' => $visits, 'validity_days' => $validity,
                'price_per_visit' => $price, 'extra_worker_price' => $extraWorker, 'extra_hour_price' => round($price / $hours / $workers, 2),
                'transport_fee' => 0, 'tax_rate' => 15, 'transport_included' => true, 'expiry_policy' => 'forfeit',
                'is_active' => true, 'sort_order' => (int) HourlyPackage::withTrashed()->max('sort_order') + 1,
            ];
            foreach (['package_type' => $tier, 'package_tier_id' => $tiers[$tier] ?? null, 'total_price' => round($price * $visits, 2),
                'duration_minutes' => $hours * 60, 'for_home' => true, 'for_company' => false, 'show_on_app' => true] as $column => $value) {
                if (Schema::hasColumn('hourly_packages', $column)) {
                    $values[$column] = $value;
                }
            }
            $package = HourlyPackage::create($values);
            $package->translations()->createMany([
                ['locale' => 'ar', 'title' => $ar, 'description' => $categories[$cat]->description_ar],
                ['locale' => 'en', 'title' => $en, 'description' => $categories[$cat]->description_en],
            ]);
            $package->cities()->syncWithoutDetaching(collect($cities)->pluck('id')->all());
            $created++;
        }
        $this->report[] = "الباقات: 4 (باقتان لكل خدمة) — أُنشئ منها الآن {$created}";
    }

    // ------------------------------------------------------------------ السائقون والعاملات

    private function drivers(array $teams): void
    {
        $schedule = $this->schedule('06:30', '20:30');
        // [الاسم، رقم الموظف، المدينة، سعة المركبة]
        $defs = [
            ['خالد العتيبي', 'HS-DRV-01', 'riyadh', 7],
            ['محمد الشهري', 'HS-DRV-02', 'riyadh', 7],
            ['عبدالله الدوسري', 'HS-DRV-03', 'dammam', 7],
            ['سعيد الغامدي', 'HS-DRV-04', 'dammam', 7],
        ];
        $service = app(DriverTeamService::class);
        $created = 0;
        foreach ($defs as $n => [$name, $number, $cityKey, $capacity]) {
            if (HourlyDriver::withTrashed()->where('employee_number', $number)->exists()) {
                continue;
            }
            $driver = HourlyDriver::create([
                'name' => $name, 'employee_number' => $number, 'gender' => 'male',
                'phone' => '0557000' . str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT),
                'work_start_date' => now()->toDateString(), 'contract_start_date' => now()->toDateString(),
                'contract_end_date' => now()->addYears(2)->toDateString(), 'residency_expiry_date' => now()->addYear()->toDateString(),
                'vehicle_capacity' => $capacity, 'work_schedule' => $schedule, 'is_active' => true,
                // الحساب معطل وكلمة المرور عشوائية: تحدد الإدارة بيانات الدخول قبل تفعيل التطبيق.
                'account_enabled' => false, 'login_access' => 'mobile', 'username' => strtolower(str_replace('-', '.', $number)),
                'password' => Hash::make(Str::random(40)),
            ]);
            $service->assign($driver, $teams[$cityKey], 'primary');
            $created++;
        }
        $this->report[] = "السائقون: 4 (اثنان لكل مدينة) — أُنشئ منهم الآن {$created}";
    }

    private function maids(array $teams, array $categories, ShelterPlaces $shelter): void
    {
        $jobType = RentalMaidQuery::rentalJobTypeId();
        if (!$jobType || !DB::table('job_types')->where('id', $jobType)->exists()) {
            throw new \RuntimeException('نوع العمل «إيجار» غير موجود؛ لا تُرشَّح عاملات الساعات بدونه.');
        }
        $religion = DB::table('religions')->orderBy('id')->value('id');
        $nationalities = $this->nationalities();
        if (!$religion || $nationalities === []) {
            throw new \RuntimeException('يلزم وجود جنسية وديانة واحدة على الأقل لإنشاء العاملات.');
        }

        // [الاسم، رقم الجواز، المدينة، مفتاح الجنسية، العمر]
        $defs = [
            ['ماريا سانتوس', 'HSP0000001', 'riyadh', 'philippines', 29],
            ['سيتي نورهاليزا', 'HSP0000002', 'riyadh', 'indonesia', 31],
            ['جريس وانجيكو', 'HSP0000003', 'riyadh', 'kenya', 27],
            ['أنا ريس', 'HSP0000004', 'dammam', 'philippines', 33],
            ['ديوي لستاري', 'HSP0000005', 'dammam', 'indonesia', 28],
            ['فيث أكينيي', 'HSP0000006', 'dammam', 'kenya', 30],
        ];
        $switcher = app(RentalModeSwitcher::class);
        $teamService = app(MaidTeamService::class);
        $ownership = app(ShelterCompanyClientResolver::class)->ownership();
        $categoryIds = collect($categories)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $created = 0;

        foreach ($defs as $n => [$name, $passport, $cityKey, $nationalityKey, $age]) {
            $existing = Maid::withTrashed()->where('passport_num', $passport)->first();
            if ($existing) {
                continue;
            }

            $maid = new Maid();
            $maid->forceFill(array_intersect_key([
                'name' => $name, 'gender' => 'female', 'passport_num' => $passport,
                'nationality_id' => $nationalities[$nationalityKey] ?? reset($nationalities), 'religion_id' => $religion,
                'age' => $age, 'height' => (string) (152 + $n * 2), 'weight' => (string) (52 + $n), 'cv_file' => '',
                'price' => 0, 'salary' => 1500, 'status' => 'available', 'available' => true, 'available_at' => now()->toDateString(),
                // لا تظهر في سير الاستقدام على الموقع؛ عاملة ساعات فقط.
                'is_show' => false, 'maid_accommodation_type' => 2,
            ], array_flip(Schema::getColumnListing('maids'))))->save();
            DB::table('maid_jobs')->insertOrIgnore(['maid_id' => $maid->id, 'job_type_id' => $jobType]);

            // عقد إيواء باسم الشركة كما في MaidAction عند إضافة عاملة إيجار.
            $contract = Contract::query()->create($ownership + [
                'maid_id' => $maid->id, 'status' => ContractStatusEnum::Shelter->value, 'contract_date' => now()->toDateString(),
            ]);
            ContractInfo::query()->create([
                'contract_id' => $contract->id, 'contract_date' => now()->toDateString(), 'msaned_number' => '',
                'shelter_id' => $shelter->id, 'wants_to_work' => 'yes', 'receipt_source' => 'company', 'is_outside_shelter' => false,
            ]);
            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::Shelter->value, 'date' => now()->toDateString(), 'notes' => 'عاملة ساعات — بيانات أساسية لنظام الساعات',
            ]);

            // التحويل الرسمي للساعات يتحقق من الإيواء والجاهزية، ثم الخدمات والجدول والفريق.
            $maid = $switcher->toHourly($maid->fresh());
            $maid->forceFill(['hourly_settings' => array_merge($maid->hourly_settings ?? [], [
                'service_category_ids' => $categoryIds,
                'work_schedule' => $this->schedule('07:00', '20:00'),
            ])])->save();
            $teamService->assign($maid, $teams[$cityKey], 'primary');
            $created++;
        }
        $this->report[] = "عاملات الساعات: 6 (ثلاث لكل مدينة) — أُنشئ منهن الآن {$created}. حساب تطبيق العاملة يُفعّل من صفحتها.";
    }

    /** جنسيات العاملات من بيانات النظام بالاسم، وإلا أول الجنسيات المتاحة. */
    private function nationalities(): array
    {
        $ids = DB::table('nationalities')->orderBy('id')->limit(3)->pluck('id')->all();
        $keys = ['philippines' => ['الفلبين', 'فلبين', 'Philippin'], 'indonesia' => ['إندونيسيا', 'اندونيسيا', 'Indonesia'], 'kenya' => ['كينيا', 'Kenya']];
        $found = [];
        $hasTranslations = Schema::hasTable('nationalities_translations');
        foreach ($keys as $key => $names) {
            $id = null;
            if ($hasTranslations) {
                $id = DB::table('nationalities_translations')->where(function ($q) use ($names) {
                    foreach ($names as $name) $q->orWhere('title', 'like', '%' . $name . '%');
                })->value('nationality_id');
            }
            $found[$key] = $id ?: ($ids[count($found) % max(count($ids), 1)] ?? null);
        }

        return array_filter($found);
    }

    /** دوام من السبت إلى الخميس، والجمعة إجازة. */
    private function schedule(string $start, string $end): array
    {
        $schedule = [];
        foreach (array_keys(HourlyDriver::DAYS) as $day) {
            $schedule[$day] = $day === 'friday' ? ['mode' => 'off'] : ['mode' => 'hours', 'start' => $start, 'end' => $end];
        }

        return $schedule;
    }

    private function line(string $style, string $text): void
    {
        $this->command?->{$style}($text);
    }
}
