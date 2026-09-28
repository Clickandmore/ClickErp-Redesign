<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Hourly\{HourlyDriver, HourlyPackage, HourlyRentalOrder, HourlyServiceCategory, HourlyTeam, HourlyVisit, ServiceDistrict};
use App\Services\Hourly\DriverTeamService;
use App\Models\ingaz\Maid;
use App\Models\Hourly\HourlyTeamMember;
use App\Services\Rental\RentalMaidQuery;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\{DB, Hash, Schema};
use Illuminate\Support\Str;

/** Isolated routing fixtures. Run explicitly: php artisan db:seed --class=HourlyRoutingDemoSeeder */
class HourlyRoutingDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('هذا السيدر مخصص لبيئة local أو testing فقط.');
        }

        DB::transaction(function () {
            $city = City::where('name_ar', 'like', '%الرياض%')->orWhere('name_en', 'like', '%Riyadh%')->firstOrFail();
            $teams = [];
            $districts = [];
            // Approximate test polygons, not official district boundaries.
            $definitions = [
                ['N', 'شمال', '#0B6461', [['الملقا', 24.8005, 46.6120], ['الياسمين', 24.8260, 46.6480]]],
                ['E', 'شرق', '#7C3AED', [['الروضة', 24.7330, 46.7720], ['النسيم', 24.7350, 46.8300]]],
                ['W', 'غرب', '#BE123C', [['عرقة', 24.6850, 46.5820], ['ظهرة لبن', 24.6330, 46.5600]]],
                ['C', 'وسط', '#B45309', [['العليا', 24.6930, 46.6830], ['الملز', 24.6660, 46.7300]]],
            ];
            $shelter = DB::table('shelter_places')->orderBy('id')->value('id');
            foreach ($definitions as $i => [$code, $name, $color, $spots]) {
                $team = HourlyTeam::withTrashed()->updateOrCreate(['code' => 'ROUTE-TEST-'.$code], [
                    'name' => '[ROUTE-TEST] فريق '.$name.' الرياض', 'city_id' => $city->id,
                    'shelter_place_id' => $shelter, 'description' => 'بيانات اختبار المسارات',
                    'base_lat' => $spots[0][1], 'base_lng' => $spots[0][2], 'color' => $color,
                    'working_start' => '00:00', 'working_end' => '23:59', 'is_active' => true,
                    'sort_order' => $i, 'deleted_at' => null,
                ]);
                $teams[] = $team;
                foreach ($spots as $j => [$districtName, $lat, $lng]) {
                    $district = ServiceDistrict::updateOrCreate(['slug' => 'route-test-'.strtolower($code).'-'.$j], [
                        'name' => '[ROUTE-TEST] '.$districtName, 'name_en' => 'Routing test '.$code.'-'.$j,
                        'city_id' => $city->id, 'latitude' => $lat, 'longitude' => $lng, 'is_active' => true,
                        'polygon' => [[$lat - .008, $lng - .008], [$lat - .008, $lng + .008], [$lat + .008, $lng + .008], [$lat + .008, $lng - .008]],
                    ]);
                    $team->districts()->syncWithoutDetaching([$district->id => ['is_active' => true]]);
                    $districts[] = [$district, $team];
                }
            }

            $names = ['خالد العتيبي', 'محمد أحمد', 'عبدالله الشهري', 'عمر علي', 'سعيد القحطاني', 'حسن إبراهيم', 'فهد الدوسري', 'يوسف محمود', 'راشد سالم', 'إبراهيم حسن'];
            $drivers = [];
            foreach ($names as $i => $name) {
                $shift = $i < 8 ? ($i % 2 === 0 ? ['mode' => 'all_day'] : ['mode' => 'hours', 'start' => '10:00', 'end' => '14:00'])
                    : ['mode' => 'hours', 'start' => $i === 8 ? '06:00' : '14:00', 'end' => $i === 8 ? '10:00' : '22:00'];
                $driver = HourlyDriver::withTrashed()->firstOrNew(['employee_number' => 'ROUTE-TEST-'.($i + 1)]);
                $driver->fill([
                    'name' => '[ROUTE-TEST] '.$name, 'gender' => 'male', 'phone' => '055990'.sprintf('%04d', $i + 1),
                    'vehicle_capacity' => 2, 'work_schedule' => array_fill_keys(array_keys(HourlyDriver::DAYS), $shift),
                    'is_active' => true, 'account_enabled' => false, 'login_access' => 'mobile',
                    'username' => 'route.test.driver.'.($i + 1), 'notes' => '[ROUTE-TEST] سائق اختبار المسارات',
                    'work_start_date' => today()->subMonths($i + 1)->toDateString(),
                    'contract_end_date' => today()->addMonths($i + 1)->toDateString(), 'deleted_at' => null,
                ]);
                if (!$driver->exists) $driver->password = Hash::make(Str::random(40));
                $driver->save();
                app(DriverTeamService::class)->sync($driver, $teams[$i < 8 ? intdiv($i, 2) : $i - 8]->id, []);
                $drivers[] = $driver;
            }

            $category = HourlyServiceCategory::withTrashed()->updateOrCreate(['slug' => 'route-test-cleaning'], [
                'name_ar' => '[ROUTE-TEST] تنظيف تجريبي', 'name_en' => '[ROUTE-TEST] Cleaning', 'is_active' => true, 'deleted_at' => null,
            ]);
            $this->workers($teams, $category, $shelter);
            foreach ($districts as [$district, $team]) {
                DB::table('hourly_category_district')->updateOrInsert(['service_category_id' => $category->id, 'district_id' => $district->id], [
                    'team_id' => $team->id, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $package = HourlyPackage::withTrashed()->updateOrCreate(['code' => 'ROUTE-TEST-2H'], [
                'service_category_id' => $category->id, 'category' => $category->name_ar, 'hours_per_visit' => 2,
                'workers_per_visit' => 2, 'visits_count' => 1, 'validity_days' => 30, 'price_per_visit' => 100,
                'tax_rate' => 15, 'is_active' => true, 'deleted_at' => null,
            ]);
            foreach (['ar' => '[ROUTE-TEST] زيارة ساعتين', 'en' => '[ROUTE-TEST] Two hour visit'] as $locale => $title) {
                $package->translations()->updateOrCreate(['locale' => $locale], ['title' => $title]);
            }
            $package->cities()->syncWithoutDetaching([$city->id]);

            // Direct fixtures intentionally bypass booking/payment/notification workflows.
            // Workers remain unassigned, also exercising the understaffed section of the board.
            $cases = [
                ['صباحية', '08:00', 0, 0, 24.8040, 46.6160, 'scheduled'],
                ['ظهرية', '12:00', 1, 3, 24.7380, 46.7760, 'scheduled'],
                ['بعيدة أكثر من 50 كم', '16:00', 0, 0, 25.5000, 46.6120, 'scheduled'],
                ['ملغاة', '11:00', 1, 2, 24.7360, 46.7800, 'cancelled'],
            ];
            foreach ($cases as $i => [$label, $time, $teamIndex, $driverIndex, $lat, $lng, $status]) {
                $phone = '599100'.sprintf('%03d', $i + 1);
                $user = DB::table('users')->where('phone', $phone)->where('name', '[ROUTE-TEST] عميل '.($i + 1))->value('id');
                if (!$user) {
                    if (DB::table('users')->where('phone', $phone)->exists()) throw new \RuntimeException('رقم العميل التجريبي مستخدم بالفعل.');
                    $values = ['name' => '[ROUTE-TEST] عميل '.($i + 1), 'first_name' => 'عميل', 'last_name' => 'اختبار',
                        'phone_code' => '966', 'phone' => $phone, 'password' => Hash::make(Str::random(40)),
                        'register_from' => 'app', 'status' => 1, 'is_blocked' => 0, 'created_at' => now(), 'updated_at' => now()];
                    $user = DB::table('users')->insertGetId(array_intersect_key($values, array_flip(Schema::getColumnListing('users'))));
                }
                $start = today()->setTimeFromTimeString($time);
                $address = '[ROUTE-TEST] '.$label.' — عنوان افتراضي للاختبار';
                $order = HourlyRentalOrder::firstOrCreate(['order_number' => 'ROUTE-TEST-ORDER-'.($i + 1)], [
                    'invoice_number' => 'ROUTE-TEST-INV-'.($i + 1), 'user_id' => $user, 'city_id' => $city->id,
                    'hourly_package_id' => $package->id, 'service_category_id' => $category->id, 'source' => 'admin',
                    'package_snapshot' => $package->toArray(), 'address_text' => $address, 'latitude' => $lat, 'longitude' => $lng,
                    'hours_per_visit' => 2, 'workers_per_visit' => 2, 'visits_total' => 1, 'price_per_visit' => 100,
                    'subtotal' => 100, 'tax_rate' => 15, 'tax_amount' => 15, 'total_amount' => 115,
                    'starts_at' => $start, 'expires_at' => $start->copy()->addDays(30), 'status' => 'active',
                    'payment_method' => 'cash', 'payment_status' => 'unpaid', 'notes' => '[ROUTE-TEST] '.$label,
                ]);
                // Preserve manual assignments and test progress on repeated runs.
                HourlyVisit::firstOrCreate(['hourly_rental_order_id' => $order->id, 'visit_number' => 1], [
                    'hourly_team_id' => $teams[$teamIndex]->id, 'scheduled_date' => $start->toDateString(),
                    'scheduled_start_at' => $start, 'scheduled_end_at' => $start->copy()->addHours(2),
                    'planned_hours' => 2, 'workers_count' => 2, 'visit_price' => 100,
                    'address_text' => $address, 'latitude' => $lat, 'longitude' => $lng, 'city_id' => $city->id,
                    'status' => $status, 'delivery_driver_id' => $drivers[$driverIndex]->id, 'pickup_driver_id' => $drivers[$driverIndex]->id,
                ]);
            }
        });
        $this->command?->info('تم تجهيز 4 فرق و10 سائقين و16 عاملة و8 مناطق و4 زيارات [ROUTE-TEST]. العاملات متاحات للتعيين؛ الزيارات الموجودة تحتفظ بتاريخها وتعديلاتها.');
    }

    private function workers(array $teams, HourlyServiceCategory $category, ?int $shelter): void
    {
        $nationalities = DB::table('nationalities')->orderBy('id')->limit(4)->pluck('id');
        $jobType = RentalMaidQuery::rentalJobTypeId();
        if (!$shelter || $nationalities->isEmpty() || !DB::table('job_types')->where('id', $jobType)->exists()) {
            throw new \RuntimeException('يلزم وجود مكان إيواء وجنسية ونوع سيرة الإيجار لإنشاء العاملات التجريبيات.');
        }
        $religion = DB::table('religions')->orderBy('id')->value('id');
        $names = ['مريم', 'سارة', 'آنا', 'روث', 'هانا', 'ليزا', 'أمينة', 'فاطمة', 'رحمة', 'نورا', 'ماريا', 'لينا', 'نعيمة', 'سميرة', 'روزا', 'هيلين'];
        foreach ($names as $i => $name) {
            $passport = 'RTMAID'.sprintf('%04d', $i + 1);
            // Only create missing fixtures: reruns preserve manual changes and real workers.
            $maid = Maid::withTrashed()->where('passport_num', $passport)->first();
            if ($maid) {
                if (!str_starts_with($maid->name, '[ROUTE-TEST] ')) {
                    throw new \RuntimeException('رقم جواز العاملة التجريبي مستخدم بالفعل: '.$passport);
                }
                continue;
            }
            $team = $teams[intdiv($i, 4)];
            $id = DB::table('maids')->insertGetId([
                'name' => '[ROUTE-TEST] '.$name, 'passport_num' => $passport,
                'nationality_id' => $nationalities[$i % $nationalities->count()], 'religion_id' => $religion,
                'age' => 25 + $i, 'height' => (string) (155 + $i), 'weight' => (string) (50 + $i),
                'cv_file' => '', 'price' => 0, 'salary' => 1200 + ($i % 4) * 100,
                'status' => 'available', 'available' => true, 'available_at' => today()->toDateString(), 'is_show' => false,
                'rental_mode' => 'hourly', 'hourly_is_suspended' => false,
                'hourly_settings' => json_encode([
                    'requires_driver' => true, 'account_enabled' => false,
                    'service_category_ids' => [$category->id],
                    'work_schedule' => array_fill_keys(array_keys(HourlyDriver::DAYS), ['mode' => 'all_day']),
                ]), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('maid_jobs')->insert(['maid_id' => $id, 'job_type_id' => $jobType]);
            // Candidate selection requires an inside-shelter record with willingness to work.
            $contract = DB::table('contracts')->insertGetId([
                'maid_id' => $id, 'status' => 'shelter', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('contract_infos')->insert([
                'contract_id' => $contract, 'shelter_id' => $shelter, 'is_outside_shelter' => false,
                'wants_to_work' => 'yes', 'created_at' => now(), 'updated_at' => now(),
            ]);
            HourlyTeamMember::create([
                'hourly_team_id' => $team->id, 'member_type' => 'maid', 'member_id' => $id,
                'role' => 'primary', 'is_active' => true, 'joined_at' => today()->toDateString(),
            ]);
        }
    }
}
