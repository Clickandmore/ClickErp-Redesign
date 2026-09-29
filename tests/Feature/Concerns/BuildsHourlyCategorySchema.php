<?php

namespace Tests\Feature\Concerns;

use App\Models\Hourly\{HourlyPackage, HourlyServiceCategory, HourlyTeam, ServiceDistrict};
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

/**
 * مخطط SQLite في الذاكرة لاختبارات طبقة فئات الخدمة: المدن السعودية والأحياء والفرق
 * وجداول التجارة ثم كل ترحيلات 2026_09_18 بالترتيب.
 */
trait BuildsHourlyCategorySchema
{
    protected function setUpHourlyCategorySchema(): void
    {
        config(['database.connections.hourly_categories' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'database.default' => 'hourly_categories', 'superadmin_modules.hourly_rental.default_enabled' => true, 'activitylog.enabled' => false, 'jwt.secret' => str_repeat('hourly-category-secret', 3)]);
        Schema::create('new_settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->text('value'); $t->timestamps(); });
        Schema::create('countries', function (Blueprint $t) { $t->id(); $t->string('code'); });
        Schema::create('cities', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('country_id')->default(1); $t->string('name_ar'); $t->string('name_en')->nullable(); $t->boolean('is_active')->default(true); $t->integer('sort_order')->default(0); $t->decimal('latitude')->nullable(); $t->decimal('longitude')->nullable(); $t->timestamps(); });
        Schema::create('nationalities', function (Blueprint $t) { $t->id(); });
        Schema::create('user_addresses', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('city_id'); $t->text('address'); $t->decimal('latitude', 10, 7)->nullable(); $t->decimal('longitude', 10, 7)->nullable(); foreach (['district', 'building', 'unit', 'contact_phone'] as $c) $t->string($c)->nullable(); $t->timestamps(); });
        DB::table('countries')->insert([['id' => 1, 'code' => 'SA'], ['id' => 2, 'code' => 'AE']]);
        DB::table('cities')->insert([['id' => 1, 'name_ar' => 'الرياض', 'name_en' => 'Riyadh'], ['id' => 2, 'name_ar' => 'جدة', 'name_en' => 'Jeddah']]);

        require_once base_path('database/migrations/2026_09_16_000001_create_hourly_teams_tables.php');
        (new \CreateHourlyTeamsTables)->up();
        foreach (['2026_09_16_000006_create_hourly_commerce_tables', '2026_09_17_000002_extend_hourly_team_management', '2026_09_17_000003_add_hourly_district_geometry', '2026_09_17_000004_extend_hourly_district_details', '2026_09_17_000008_extend_hourly_service_items'] as $migration) {
            (require base_path("database/migrations/{$migration}.php"))->up();
        }
        Schema::table('hourly_order_payments', fn (Blueprint $t) => $t->decimal('refunded_amount',12,2)->default(0));
        $layer = glob(base_path('database/migrations/2026_09_18_*.php'));
        sort($layer);
        foreach ($layer as $file) (require $file)->up();

        HourlyRentalSettings::flush();
        HourlyRentalSettings::persist(['is_enabled' => true, 'min_notice_hours' => 0]);
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class, \App\Http\Middleware\EnsureAdminRoutePermission::class, \App\Http\Middleware\Admin::class]);
        app('view')->getFinder()->prependLocation(base_path('tests/Fixtures/hourly-views'));
        $admin = \Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id = 7; $admin->name = 'مشرف'; $admin->shouldReceive('can')->andReturn(true);
        auth('admin')->setUser($admin);
    }

    protected function category(array $attributes = []): HourlyServiceCategory
    {
        static $n = 0; $n++;
        return HourlyServiceCategory::create($attributes + ['name_ar' => 'فئة '.$n, 'name_en' => 'Category '.$n, 'slug' => 'category-'.$n, 'icon' => 'fa-broom', 'is_active' => true]);
    }

    protected function item(HourlyServiceCategory $category, array $attributes = []): HourlyPackage
    {
        $attributes += ['package_tier_id'=>\App\Models\Hourly\HourlyPackageTier::where('code',($attributes['visits_count']??1)>1?'weekly_1':'one_off')->value('id')];
        $item = HourlyPackage::create($attributes + ['code' => uniqid('I'), 'service_category_id' => $category->id, 'category' => $category->name_ar, 'hours_per_visit' => 4, 'workers_per_visit' => 1, 'visits_count' => 1, 'validity_days' => 30, 'price_per_visit' => 115, 'extra_worker_price' => 50, 'tax_rate' => 15, 'is_active' => true, 'transport_included' => true]);
        $item->translations()->create(['locale' => 'ar', 'title' => 'عنصر '.$item->code]);
        $item->cities()->attach(1);
        return $item->fresh();
    }

    /** حي مربع حول (lat,lng) بنصف ضلع 0.01 درجة. */
    protected function district(string $name, float $lat, float $lng, int $cityId = 1, bool $active = true): ServiceDistrict
    {
        $d = 0.01;
        return ServiceDistrict::create(['city_id' => $cityId, 'name' => $name, 'is_active' => $active, 'polygon' => [[$lat - $d, $lng - $d], [$lat - $d, $lng + $d], [$lat + $d, $lng + $d], [$lat + $d, $lng - $d]]]);
    }

    protected function address(float $lat, float $lng, ?string $district = null, int $cityId = 1): int
    {
        return DB::table('user_addresses')->insertGetId(['user_id' => 1, 'city_id' => $cityId, 'address' => 'عنوان', 'latitude' => $lat, 'longitude' => $lng, 'district' => $district, 'created_at' => now(), 'updated_at' => now()]);
    }

    protected function team(string $name = 'فريق'): HourlyTeam
    {
        return HourlyTeam::create(['name' => $name, 'is_active' => true]);
    }
}
