<?php

namespace Tests\Feature;

use App\Models\ingaz\User;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\HourlyTestCase as TestCase;

/**
 * قراءة موقع القادم إلى العميل (api/app/hourly/orders/{order}/visits/{visit}/tracking)،
 * وتسامح صيغة الوقت في إنشاء الطلب وإعادة الجدولة.
 */
class HourlyVisitTrackingTest extends TestCase
{
    use BuildsHourlyCategorySchema;
    use BuildsHourlyWorkerSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHourlyCategorySchema();
        $this->buildWorkers();
        Carbon::setTestNow(Carbon::parse('2030-01-06 07:00:00'));
        Schema::create('users', function (Blueprint $t) { $t->softDeletes(); $t->id(); $t->string('name'); });
        DB::table('users')->insert([['id' => 1, 'name' => 'عميل'], ['id' => 2, 'name' => 'عميل آخر']]);
        Schema::table('user_addresses', function (Blueprint $t) { foreach (['title','name','phone','notes'] as $f) $t->string($f)->default(''); });
        // أعمدة السائقين على الزيارة تأتي مع ترحيل معالج الحجز.
        foreach (['2026_09_20_000001_add_hourly_booking_wizard', '2026_09_21_000001_extend_hourly_order_management'] as $migration) {
            (require base_path("database/migrations/{$migration}.php"))->up();
        }
        (require base_path('database/migrations/2026_09_28_000001_create_hourly_staff_app_tables.php'))->up();
        Schema::create('hourly_drivers', function (Blueprint $t) { $t->id(); $t->string('name'); $t->string('phone')->nullable(); $t->boolean('is_active')->default(true); $t->timestamps(); $t->softDeletes(); });
        Schema::create('location_pings', function (Blueprint $t) {
            $t->id(); $t->string('user_type', 16); $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('hourly_visit_id')->nullable();
            $t->decimal('lat', 10, 7); $t->decimal('lng', 10, 7); $t->decimal('accuracy', 8, 2)->nullable();
            $t->decimal('speed', 6, 2)->nullable(); $t->unsignedSmallInteger('heading')->nullable();
            $t->string('app_version', 30)->nullable(); $t->string('device_type', 40)->nullable();
            $t->unsignedBigInteger('city_id')->nullable(); $t->dateTime('recorded_at'); $t->timestamp('created_at')->nullable();
        });
        HourlyRentalSettings::persist(['is_enabled' => true, 'min_notice_hours' => 0, 'tracking_enabled' => true, 'tracking_interval_seconds' => 10]);
        auth('api')->setUser(new User(['id' => 1]));
        $this->withHeader('lang', 'ar');
    }

    public function test_driver_web_location_records_current_time_and_rejects_other_drivers_visit(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\EnsureHourlyDriverAccess::class);
        [, $visitId]=$this->order();
        $driver=\App\Models\Hourly\HourlyDriver::create(['name'=>'Driver','is_active'=>true]);
        $this->actingAs($driver,'hourly_driver');
        DB::table('hourly_visits')->where('id',$visitId)->update(['delivery_driver_id'=>$driver->id]);
        $this->postJson('/hourly-driver/location',['lat'=>24.7,'lng'=>46.7,'visit_id'=>$visitId])->assertOk()->assertJsonPath('saved',1);
        $this->assertDatabaseHas('location_pings',['user_type'=>'driver','user_id'=>$driver->id,'hourly_visit_id'=>$visitId,'recorded_at'=>now()->format('Y-m-d H:i:s')]);
        DB::table('hourly_visits')->where('id',$visitId)->update(['delivery_driver_id'=>999]);
        $this->postJson('/hourly-driver/location',['lat'=>24.7,'lng'=>46.7,'visit_id'=>$visitId])->assertNotFound();
        HourlyRentalSettings::persist(['tracking_enabled'=>false]);
        $this->postJson('/hourly-driver/location',['lat'=>24.7,'lng'=>46.7])->assertForbidden();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    /** طلب بزيارة واحدة مجدولة 2030-01-07 08:00، ويُعيد [معرف الطلب, معرف الزيارة]. */
    private function order(string $start = '2030-01-07 08:00'): array
    {
        static $n = 0; $n++;
        $lat = 24.7 + $n / 10;
        $category = $this->category(['name_ar' => 'تنظيف '.$n]);
        $district = $this->district('حي '.$n, $lat, 46.7);
        $category->districts()->attach($district->id, ['is_active' => true]);
        $item = $this->item($category, ['total_price' => 200, 'start_times' => ['08:00' => 'all']]);
        $order = $this->postJson('/api/app/hourly/orders', ['hourly_package_id' => $item->id, 'address_id' => $this->address($lat, 46.7),
            'payment_method' => 'cash', 'workers_per_visit' => 1, 'scheduled_starts' => [$start]])->assertCreated()->json('data');

        return [$order['id'], $order['visits'][0]['id']];
    }

    private function ping(string $type, int $id, int $visit, string $ago = '30 seconds', array $more = []): void
    {
        DB::table('location_pings')->insert($more + ['user_type' => $type, 'user_id' => $id, 'hourly_visit_id' => $visit, 'lat' => 24.75, 'lng' => 46.72,
            'recorded_at' => now()->sub($ago)->toDateTimeString(), 'created_at' => now()]);
    }

    private function track(int $order, int $visit): array
    {
        return $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}/tracking")->assertOk()->json('data');
    }

    // ───────────────────────── صيغة الوقت ─────────────────────────

    public function test_order_accepts_the_iso_t_separator_the_app_sends(): void
    {
        [, $visit] = $this->order('2030-01-07T08:00');
        $this->assertSame('2030-01-07T08:00:00+03:00', DB::table('hourly_visits')->where('id', $visit)->value('scheduled_start_at')
            ? Carbon::parse(DB::table('hourly_visits')->where('id', $visit)->value('scheduled_start_at'))->toIso8601String() : null);
    }

    public function test_order_accepts_the_seconds_that_slots_returns(): void
    {
        [$order, $visit] = $this->order('2030-01-07 08:00:00');
        $this->assertSame('08:00', substr((string) $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}")->json('data.scheduled_start_at'), 11, 5));
    }

    public function test_order_converts_an_explicit_offset_to_the_app_timezone(): void
    {
        // 05:00 بتوقيت UTC هي 08:00 بتوقيت الرياض، فالموعد نفسه لا موعد آخر.
        [$order, $visit] = $this->order('2030-01-07T05:00:00Z');
        $this->assertSame('08:00', substr((string) $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}")->json('data.scheduled_start_at'), 11, 5));
    }

    public function test_a_genuinely_wrong_format_is_still_rejected(): void
    {
        $category = $this->category();
        $category->districts()->attach($this->district('حي الرفض', 24.9, 46.7)->id, ['is_active' => true]);
        $item = $this->item($category, ['start_times' => ['08:00' => 'all']]);
        $this->postJson('/api/app/hourly/orders', ['hourly_package_id' => $item->id, 'address_id' => $this->address(24.9, 46.7),
            'payment_method' => 'cash', 'scheduled_starts' => ['2030/01/07 08:00']])
            ->assertStatus(422)->assertJsonValidationErrors('scheduled_starts.0');
    }

    public function test_reschedule_accepts_the_t_separator(): void
    {
        [$order, $visit] = $this->order();
        HourlyRentalSettings::persist(['allow_reschedule' => true, 'max_reschedules_per_visit' => 2, 'reschedule_hours_before' => 1]);
        $this->postJson("/api/app/hourly/orders/{$order}/visits/{$visit}/reschedule", ['scheduled_start' => '2030-01-08T08:00'])
            ->assertOk()->assertJsonPath('data.status', 'scheduled');
        $this->assertSame('2030-01-08', substr((string) DB::table('hourly_visits')->where('id', $visit)->value('scheduled_start_at'), 0, 10));
    }

    // ───────────────────────── التتبع ─────────────────────────

    public function test_tracking_is_closed_before_anyone_sets_off(): void
    {
        [$order, $visit] = $this->order();
        $data = $this->track($order, $visit);
        $this->assertSame(['closed', null], [$data['state'], $data['subject']]);
        $this->assertTrue($data['enabled']);
        $row = DB::table('hourly_visits')->where('id', $visit)->first(['latitude', 'longitude']);
        $this->assertSame([(float) $row->latitude, (float) $row->longitude], [$data['destination']['lat'], $data['destination']['lng']]);
    }

    public function test_tracking_reports_the_delivery_driver_once_on_the_way(): void
    {
        [$order, $visit] = $this->order();
        $driver = DB::table('hourly_drivers')->insertGetId(['name' => 'سائق التوصيل', 'phone' => '0550000000', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('hourly_visits')->where('id', $visit)->update(['status' => 'en_route', 'delivery_driver_id' => $driver]);
        $this->ping('driver', $driver, $visit, '30 seconds', ['speed' => 42.5, 'heading' => 90, 'accuracy' => 8]);

        $data = $this->track($order, $visit);
        $this->assertSame('live', $data['state']);
        $this->assertSame(['delivery', 'driver', 'سائق التوصيل', '0550000000'],
            [$data['subject']['role'], $data['subject']['type'], $data['subject']['name'], $data['subject']['phone']]);
        $this->assertSame([24.75, 46.72, 42.5, 90, false],
            [$data['subject']['point']['lat'], $data['subject']['point']['lng'], $data['subject']['point']['speed'],
                $data['subject']['point']['heading'], $data['subject']['point']['is_stale']]);
        $this->assertSame(10, $data['poll_after_seconds']);
        $this->assertSame(['on_the_way', true, 10, 24.75, 46.72, 'سائق التوصيل'],
            [$data['stage'], $data['is_live'], $data['poll_seconds'], $data['location']['lat'], $data['location']['lng'], $data['driver']['name']]);
        $this->assertNotEmpty($data['location']['updated_at']);
        $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}")->assertOk()->assertJsonPath('data.can_track', true);
    }

    public function test_an_old_point_is_flagged_stale_and_a_dead_one_is_withheld(): void
    {
        [$order, $visit] = $this->order();
        $driver = DB::table('hourly_drivers')->insertGetId(['name' => 'سائق', 'phone' => '0550000001', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('hourly_visits')->where('id', $visit)->update(['status' => 'en_route', 'delivery_driver_id' => $driver]);

        $this->ping('driver', $driver, $visit, '5 minutes');
        $this->assertTrue($this->track($order, $visit)['subject']['point']['is_stale']);

        DB::table('location_pings')->update(['recorded_at' => now()->subMinutes(45)->toDateTimeString()]);
        $data = $this->track($order, $visit);
        $this->assertSame(['waiting', null], [$data['state'], $data['subject']['point']]);
    }

    public function test_the_worker_is_tracked_when_no_driver_carries_her_and_her_phone_stays_private(): void
    {
        [$order, $visit] = $this->order();
        DB::table('maids')->insertOrIgnore(['id' => 9, 'name' => 'عاملة الزيارة', 'rental_mode' => 'hourly', 'rental_source' => 'outside', 'arrival_date' => today()->toDateString()]);
        DB::table('hourly_visit_workers')->insert(['hourly_visit_id' => $visit, 'maid_id' => 9, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('hourly_visits')->where('id', $visit)->update(['status' => 'en_route']);
        $this->ping('maid', 9, $visit);

        $subject = $this->track($order, $visit)['subject'];
        $this->assertSame(['maid', 'عاملة الزيارة', null], [$subject['type'], $subject['name'], $subject['phone']]);
    }

    public function test_the_pickup_driver_is_tracked_after_the_worker_finishes(): void
    {
        [$order, $visit] = $this->order();
        $driver = DB::table('hourly_drivers')->insertGetId(['name' => 'سائق الاستلام', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('hourly_visits')->where('id', $visit)->update(['status' => 'worker_finished', 'pickup_driver_id' => $driver]);
        $this->ping('driver', $driver, $visit);

        $this->assertSame('pickup', $this->track($order, $visit)['subject']['role']);
    }

    public function test_an_unassigned_trip_says_so_instead_of_returning_nothing(): void
    {
        [$order, $visit] = $this->order();
        DB::table('hourly_visits')->where('id', $visit)->update(['status' => 'worker_finished']);
        $data = $this->track($order, $visit);
        $this->assertSame('waiting', $data['state']);
        $this->assertNotEmpty($data['message']);
    }

    public function test_tracking_disabled_in_settings_is_not_available(): void
    {
        [$order, $visit] = $this->order();
        DB::table('hourly_visits')->where('id', $visit)->update(['status' => 'en_route']);
        HourlyRentalSettings::persist(['tracking_enabled' => false]);

        $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}/tracking")->assertNotFound();
        $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}")->assertOk()->assertJsonPath('data.can_track', false);
    }

    public function test_category_can_disable_tracking_and_exposes_the_flag_in_config(): void
    {
        [$order, $visit] = $this->order();
        DB::table('hourly_visits')->where('id', $visit)->update(['status' => 'en_route']);
        $categoryId = DB::table('hourly_rental_orders')->where('id', $order)->value('package_snapshot');
        $categoryId = json_decode($categoryId, true)['service_category_id'];
        DB::table('hourly_category_settings')->updateOrInsert(['service_category_id' => $categoryId], [
            'settings' => json_encode(['tracking_enabled' => false]), 'updated_by' => 7, 'created_at' => now(), 'updated_at' => now(),
        ]);
        app(\App\Services\Hourly\HourlyCategorySettings::class)->forget();

        $this->getJson("/api/app/hourly/categories/{$categoryId}/config")
            ->assertOk()->assertJsonPath('data.tracking_enabled', false);
        $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}")
            ->assertOk()->assertJsonPath('data.can_track', false);
        $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}/tracking")->assertNotFound();
    }

    public function test_another_customers_visit_is_not_trackable(): void
    {
        [$order, $visit] = $this->order();
        auth('api')->setUser(new User(['id' => 2]));
        $this->getJson("/api/app/hourly/orders/{$order}/visits/{$visit}/tracking")->assertNotFound();
    }

    public function test_customer_location_is_linked_only_to_an_owned_visit(): void
    {
        [$order,$visit]=$this->order();
        $this->postJson('/api/app/hourly/location',['lat'=>24.71,'lng'=>46.71,'visit_id'=>$visit])
            ->assertOk()->assertJsonPath('saved',1);
        $this->assertDatabaseHas('location_pings',['user_type'=>'customer','user_id'=>1,'hourly_visit_id'=>$visit]);

        auth('api')->setUser(new User(['id'=>2]));
        $this->postJson('/api/app/hourly/location',['lat'=>24.72,'lng'=>46.72,'visit_id'=>$visit])->assertNotFound();
        $this->assertSame(1,DB::table('location_pings')->where('user_type','customer')->count());
    }

    public function test_location_retention_deletes_only_points_older_than_thirty_days(): void
    {
        DB::table('location_pings')->insert([
            ['user_type' => 'driver', 'user_id' => 1, 'lat' => 24.7, 'lng' => 46.7,
                'recorded_at' => now()->subDays(31), 'created_at' => now()->subDays(31)],
            ['user_type' => 'driver', 'user_id' => 1, 'lat' => 24.8, 'lng' => 46.8,
                'recorded_at' => now()->subDays(29), 'created_at' => now()->subDays(29)],
        ]);

        $this->artisan('hourly:prune-location-pings --days=30')->assertExitCode(0);
        $this->assertSame(1, DB::table('location_pings')->count());
        $this->assertSame(24.8, (float) DB::table('location_pings')->value('lat'));
    }

    public function test_tracking_notification_has_the_customer_deep_link_contract(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();$table->unsignedBigInteger('user_id');$table->string('type');
            $table->text('title');$table->text('description');$table->timestamps();
        });
        [$order, $visit] = $this->order();
        $push = \Mockery::mock(\App\Services\Firebase\FirebasePushService::class);
        $push->shouldReceive('sendToUser')->once()->withArgs(function ($userId, $title, $body, $data) use ($order, $visit) {
            return $userId === 1 && $title !== '' && $body !== ''
                && $data['type'] === 'hourly_visit'
                && $data['hourly_order_id'] === (string) $order
                && $data['visit_id'] === (string) $visit
                && $data['stage'] === 'on_the_way';
        })->andReturn(\App\Services\Firebase\FirebasePushResult::skipped('test'));
        $this->app->instance(\App\Services\Firebase\FirebasePushService::class, $push);

        app(\App\Services\Hourly\CustomerTrackingPushService::class)->notify($visit, 'on_the_way');
    }

    public function test_tracking_notification_is_listed_with_its_hourly_order_number_and_visit(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();$table->unsignedBigInteger('user_id');$table->unsignedBigInteger('order_id')->nullable();$table->string('type');
            $table->text('title');$table->text('description');$table->timestamps();
        });
        (require base_path('database/migrations/2026_11_03_110000_add_hourly_refs_to_notifications.php'))->up();
        [$order, $visit] = $this->order();
        $push = \Mockery::mock(\App\Services\Firebase\FirebasePushService::class);
        $push->shouldReceive('sendToUser')->andReturn(\App\Services\Firebase\FirebasePushResult::skipped('test'));
        $this->app->instance(\App\Services\Firebase\FirebasePushService::class, $push);

        app(\App\Services\Hourly\CustomerTrackingPushService::class)->notify($visit, 'early_finish');

        $notification = \App\Models\ingaz\Notification::query()->latest('id')->firstOrFail();
        $this->assertSame($order, (int) $notification->hourly_order_id);
        $this->assertSame($visit, (int) $notification->hourly_visit_id);
        $item = (new \App\Http\Resources\Api\NotificationResource($notification))->toArray(request());
        $this->assertSame($order, $item['hourly_order']['id']);
        $this->assertSame(\App\Models\Hourly\HourlyRentalOrder::findOrFail($order)->order_number, $item['hourly_order']['order_number']);
        $this->assertSame($visit, $item['hourly_order']['visit_id']);
        $this->assertSame((int) \App\Models\Hourly\HourlyVisit::findOrFail($visit)->visit_number, (int) $item['hourly_order']['visit_number']);
    }
}
