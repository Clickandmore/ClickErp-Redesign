<?php

namespace Tests\Feature;

use App\Models\Hourly\HourlyDriver;
use App\Models\Hourly\HourlyPackage;
use App\Models\Hourly\HourlyServiceCategory;
use App\Models\Hourly\HourlyTeam;
use App\Models\Hourly\ServiceDistrict;
use App\Models\ingaz\Maid;
use App\Services\Hourly\WorkerBookability;
use App\Support\HourlyRentalSettings;
use Database\Seeders\HourlySampleDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** بيانات الساعات الأساسية: الأعداد المطلوبة، عدم التكرار، وصلاحية العاملات للحجز فعلًا. */
class HourlySampleDataSeederTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $now = ['created_at' => now(), 'updated_at' => now()];
        $country = DB::table('countries')->insertGetId(['name_ar' => 'السعودية', 'name_en' => 'Saudi Arabia', 'code' => 'SA-T'] + $now);
        DB::table('cities')->insert(['name_ar' => 'الرياض', 'name_en' => 'Riyadh', 'country_id' => $country, 'is_active' => true] + $now);
        foreach (['الفلبين' => 'Philippines', 'إندونيسيا' => 'Indonesia', 'كينيا' => 'Kenya'] as $ar => $en) {
            $id = DB::table('nationalities')->insertGetId($now);
            DB::table('nationalities_translations')->insert([
                ['nationality_id' => $id, 'title' => $ar, 'locale' => 'ar'] + $now,
                ['nationality_id' => $id, 'title' => $en, 'locale' => 'en'] + $now,
            ]);
        }
        $religion = DB::table('religions')->insertGetId($now);
        DB::table('religions_translations')->insert(['religion_id' => $religion, 'title' => 'مسلمة', 'locale' => 'ar'] + $now);
        $job = DB::table('job_types')->insertGetId($now);
        DB::table('job_types_translations')->insert(['job_type_id' => $job, 'title' => 'إيجار', 'locale' => 'ar'] + $now);
        DB::table('shelter_places')->insert(['address' => 'إيواء الرياض', 'is_active' => true] + $now);
        // الموديول يُفعّل من «الموديلات» في Super Admin.
        DB::table('system_module_settings')->updateOrInsert(['key' => 'hourly_rental'],
            ['name' => 'hourly_rental', 'is_enabled' => true, 'updated_at' => now(), 'created_at' => now()]);
        Cache::flush();
        HourlyRentalSettings::flush();
    }

    protected function tearDown(): void
    {
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    public function test_it_seeds_the_requested_catalog_once_and_every_worker_is_bookable(): void
    {
        // حي حقيقي موجود بنفس الاسم يُعاد استخدامه بدل إنشاء مكرر.
        $riyadh = DB::table('cities')->where('name_ar', 'الرياض')->value('id');
        $existing = ServiceDistrict::create(['city_id' => $riyadh, 'name' => 'العليا', 'name_en' => 'Olaya existing', 'slug' => 'olaya-existing', 'is_active' => true]);

        Artisan::call('db:seed', ['--class' => HourlySampleDataSeeder::class]);
        $output = Artisan::output();
        $this->assertStringContainsString('اكتملت بيانات الساعات', $output, $output);
        // إعادة التشغيل لا تكرر شيئًا.
        $this->seed(HourlySampleDataSeeder::class);

        $categories = HourlyServiceCategory::whereIn('slug', ['home-cleaning', 'deep-cleaning'])->get();
        $this->assertCount(2, $categories);
        foreach ($categories as $category) {
            $this->assertSame(2, HourlyPackage::where('service_category_id', $category->id)->count());
            $this->assertSame(6, DB::table('hourly_category_district')->where('service_category_id', $category->id)->count());
            $this->assertSame(4, DB::table('hourly_category_payment_methods')->where('service_category_id', $category->id)->count());
        }
        $this->assertSame(8, DB::table('hourly_package_translations')->whereIn('hourly_package_id', HourlyPackage::where('code', 'like', 'HS-%')->pluck('id'))->count());

        $dammam = DB::table('cities')->where('name_ar', 'الدمام')->value('id');
        $this->assertNotNull($dammam, 'تُضاف الدمام إن لم تكن موجودة.');
        $this->assertSame(3, ServiceDistrict::where('city_id', $riyadh)->count());
        $this->assertSame(3, ServiceDistrict::where('city_id', $dammam)->count());
        $this->assertSame(1, ServiceDistrict::where('city_id', $riyadh)->where('name', 'العليا')->count());
        $this->assertTrue(HourlyTeam::where('code', 'HS-RUH')->firstOrFail()->districts()->whereKey($existing->id)->exists());

        $this->assertSame(4, HourlyDriver::where('employee_number', 'like', 'HS-DRV-%')->count());
        foreach (HourlyDriver::where('employee_number', 'like', 'HS-DRV-%')->get() as $driver) {
            $this->assertFalse((bool) $driver->account_enabled);
            $this->assertTrue(DB::table('hourly_team_members')->where('member_type', 'driver')->where('member_id', $driver->id)->where('is_active', true)->exists());
        }

        $maids = Maid::where('passport_num', 'like', 'HSP%')->get();
        $this->assertCount(6, $maids);
        foreach ($maids as $maid) {
            $this->assertSame('hourly', $maid->rental_mode);
            $this->assertFalse((bool) $maid->is_show);
            $this->assertSame(1, DB::table('contracts')->where('maid_id', $maid->id)->where('status', 'shelter')->count());
            $report = app(WorkerBookability::class)->report($maid->fresh());
            $this->assertSame([], $report['blockers'], 'العاملة ' . $maid->name . ': ' . json_encode($report['blockers'], JSON_UNESCAPED_UNICODE));
            $this->assertSame(3, $report['served_districts']);
        }
    }
}
