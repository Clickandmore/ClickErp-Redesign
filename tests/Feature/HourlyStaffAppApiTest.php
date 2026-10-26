<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyDriver, HourlyRentalOrder, HourlyVisit};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\HourlyTestCase as TestCase;

/**
 * تطبيق الفريق الميداني: دخول السائق والعاملة برمز تحقق، مشاوير اليوم ومحطاتها،
 * التسليم برمز العميل والتحصيل النقدي، طلب الاستلام والعودة، ساعات العاملة وإجازاتها ونداء النجدة وأرباحها.
 */
class HourlyStaffAppApiTest extends TestCase
{
    use BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema;

    private $team;

    protected function setUp(): void
    {
        parent::setUp();
        config(['otp.fallback_code'=>'12345','otp.fallback_phones'=>[]]);
        Carbon::setTestNow(Carbon::parse('2026-09-22 07:00'));
        $this->setUpHourlyCategorySchema();
        $this->buildWorkers();
        Schema::create('admins', function (Blueprint $t) { $t->softDeletes(); $t->id(); $t->string('name'); });
        DB::table('admins')->insert(['id' => 7, 'name' => 'مشرف']);
        Schema::create('users', function (Blueprint $t) { $t->softDeletes(); $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('phone'); $t->string('phone_code')->default('966'); $t->string('password')->nullable(); $t->string('register_from')->nullable(); $t->boolean('is_blocked')->default(false); $t->timestamps(); });
        Schema::table('user_addresses', function (Blueprint $t) { foreach (['name', 'phone', 'title'] as $c) $t->string($c)->nullable(); });
        foreach (['2026_09_20_000001_add_hourly_booking_wizard', '2026_09_21_000001_extend_hourly_order_management',
            '2026_09_21_000002_add_hourly_visit_operations', '2026_09_22_000001_create_hourly_customer_sources'] as $migration) {
            (require base_path('database/migrations/'.$migration.'.php'))->up();
        }
        Schema::create('hourly_drivers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('employee_number')->nullable(); $t->string('phone')->nullable();
            $t->string('username')->nullable(); $t->string('login_phone')->nullable(); $t->string('password')->nullable();
            $t->string('login_access', 10)->default('both'); $t->boolean('account_enabled')->default(true);
            $t->unsignedInteger('auth_version')->default(1); $t->unsignedSmallInteger('vehicle_capacity')->nullable();
            $t->boolean('is_active')->default(true); $t->json('work_schedule')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('location_pings', function (Blueprint $t) {
            $t->id(); $t->string('user_type', 16); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('hourly_visit_id')->nullable(); $t->decimal('lat', 10, 7); $t->decimal('lng', 10, 7);
            $t->decimal('accuracy', 8, 2)->nullable(); $t->decimal('speed', 6, 2)->nullable(); $t->unsignedSmallInteger('heading')->nullable();
            $t->string('app_version', 30)->nullable(); $t->string('device_type', 40)->nullable(); $t->unsignedBigInteger('city_id')->nullable();
            $t->dateTime('recorded_at'); $t->timestamp('created_at')->nullable();
        });
        Schema::create('hourly_route_stops', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('hourly_visit_id'); $t->string('role', 10); $t->unsignedBigInteger('hourly_driver_id');
            $t->date('route_date'); $t->unsignedInteger('display_order'); $t->timestamps(); $t->unique(['hourly_visit_id', 'role']);
        });
        // ترحيل تطبيق الفريق: الأجهزة والرموز والمشاوير وأعمدة حالة المحطة والعاملة ورمز التسليم.
        (require base_path('database/migrations/2026_09_28_000001_create_hourly_staff_app_tables.php'))->up();
        (require base_path('database/migrations/2026_09_29_000001_create_hourly_worker_sos_alerts_table.php'))->up();
        (require base_path('database/migrations/2026_09_30_000001_create_hourly_staff_notification_reads_table.php'))->up();
        (require base_path('database/migrations/2026_09_30_000002_extend_hourly_sos_alerts_for_drivers.php'))->up();
        (require base_path('database/migrations/2026_10_18_000005_create_hourly_early_finishes.php'))->up();
        // Return holds and self-arrival read the physical movement log on every worker action.
        if(!Schema::hasTable('worker_daily_movements'))(require base_path('database/migrations/2026_09_28_000001_create_worker_daily_movements_table.php'))->up();
        // سجل الرسائل الذي يكتب فيه StaffPushService كل إشعار، وهو مصدر صندوق الإشعارات.
        Schema::create('communication_logs', function (Blueprint $t) {
            $t->id(); $t->uuid('batch_id')->nullable(); $t->string('channel', 20); $t->string('recipient_type', 20);
            $t->unsignedBigInteger('user_id')->nullable(); $t->unsignedBigInteger('admin_id')->nullable();
            $t->unsignedBigInteger('notification_id')->nullable(); $t->string('recipient_name')->nullable();
            $t->string('recipient_phone', 30)->nullable(); $t->string('title')->nullable(); $t->text('message')->nullable();
            $t->string('provider', 30)->nullable(); $t->string('status', 20)->nullable(); $t->text('response')->nullable();
            $t->json('meta')->nullable(); $t->dateTime('sent_at')->nullable(); $t->timestamps();
        });
        DB::table('users')->insert(['id' => 1, 'name' => 'عميل الساعات', 'phone' => '0500000000', 'email' => 'client@example.test', 'hourly_wallet_balance' => 1000]);
        DB::table('cities')->where('id', 1)->update(['latitude' => 24.7136, 'longitude' => 46.6753]);
        // Eloquent يخزّن قائمة أعمدة كل موديل في ذاكرة ثابتة عبر الاختبارات؛ صنف اختبار سابق أنشأ نفس الجدول
        // بأعمدة أقل فتُهمَل الأعمدة الجديدة بصمت عند الإسناد الجماعي. إلغاء الحماية هنا يحصّن هذا الملف.
        // حدود الطلبات تُخزَّن في كاش الملفات المشترك مع الخادم المحلي، فتتراكم بين التشغيلات وتُفشل الدخول بـ429.
        // الحدود نفسها مقصودة في الإنتاج، لكن الاختبار يتحقق من العقد لا من المحدِّد.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        \Illuminate\Database\Eloquent\Model::unguard();
        $this->team = $this->team('فريق الرياض');
        $this->team->update(['base_lat' => 24.71, 'base_lng' => 46.70]);
    }

    protected function tearDown(): void
    {
        \Illuminate\Database\Eloquent\Model::reguard();
        Carbon::setTestNow();
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    private function driver(array $attributes = []): HourlyDriver
    {
        $driver = HourlyDriver::create($attributes + [
            'name' => 'سالم السائق', 'employee_number' => 'D-1', 'username' => 'HD-1', 'login_phone' => '0512345678',
            'is_active' => true, 'account_enabled' => true, 'login_access' => 'both', 'vehicle_capacity' => 6,
            'work_schedule' => array_fill_keys(array_keys(HourlyDriver::DAYS), ['mode' => 'all_day']),
        ]);
        DB::table('hourly_team_members')->insert(['hourly_team_id' => $this->team->id, 'member_type' => 'driver', 'member_id' => $driver->id, 'role' => 'primary', 'is_active' => true]);
        return $driver;
    }

    /** طلب نشط بزيارة واحدة مجدولة اليوم 08:00 مع عاملة واحدة. */
    private function order(int $worker = 1, string $at = '2026-09-22 08:00', array $workerSettings = [], array $packageSettings = []): HourlyRentalOrder
    {
        // أحياء متباعدة 0.1 درجة: مضلعات الأحياء (±0.01) لا تتقاطع فيُحلّ حي العنوان بلا لبس.
        static $n = 0; $n++;
        $category = $this->category(['name_ar' => 'تنظيف '.$n]);
        $district = $this->district('الملز '.$n, 24.7 + $n / 10, 46.7);
        $category->districts()->attach($district->id, ['is_active' => true, 'team_id' => $this->team->id]);
        $item = $this->item($category);
        if($packageSettings)$item->update($packageSettings);
        DB::table('maids')->insertOrIgnore(['id' => $worker, 'name' => 'عاملة '.$worker, 'rental_mode' => 'hourly', 'rental_source' => 'outside',
            'arrival_date' => today()->toDateString(), 'hourly_login_phone' => '05000000'.str_pad((string) $worker, 2, '0', STR_PAD_LEFT),
            'hourly_username' => 'HM-'.$worker,
            'hourly_settings' => json_encode($workerSettings + ['account_enabled' => true, 'login_access' => 'both', 'requires_driver' => true,
                'work_schedule' => array_fill_keys(array_keys(HourlyDriver::DAYS), ['mode' => 'all_day'])])]);
        if (!DB::table('maid_jobs')->where('maid_id', $worker)->exists()) DB::table('maid_jobs')->insert(['maid_id' => $worker, 'job_type_id' => 2]);
        DB::table('contracts')->insertOrIgnore(['id' => $worker, 'maid_id' => $worker, 'status' => 'shelter']);
        if (!DB::table('contract_infos')->where('contract_id', $worker)->exists()) DB::table('contract_infos')->insert(['contract_id' => $worker, 'shelter_id' => 1, 'is_outside_shelter' => false, 'wants_to_work' => 'yes']);
        if (!DB::table('hourly_team_members')->where('member_type', 'maid')->where('member_id', $worker)->exists()) {
            DB::table('hourly_team_members')->insert(['hourly_team_id' => $this->team->id, 'member_type' => 'maid', 'member_id' => $worker, 'role' => 'primary', 'is_active' => true]);
        }
        $order = app(\App\Services\Hourly\PanelBookingService::class)->create(['booking_token' => (string) Str::uuid(), 'customer_mode' => 'existing',
            'user_id' => 1, 'address_mode' => 'existing', 'address_id' => $this->address(24.7 + $n / 10, 46.7, 'الملز '.$n),
            'hourly_package_id' => $item->id, 'workers_per_visit' => 1, 'payment_method' => 'cash', 'collect_now' => false,
            'all_workers' => false, 'notify_email' => false, 'notify_app' => false, 'visits' => [['starts_at' => $at, 'worker_ids' => [$worker]]]], 7);
        $order->update(['status' => 'active']);
        return $order->fresh();
    }

    private function assign(HourlyVisit $visit, HourlyDriver $driver): HourlyVisit
    {
        $visit->update(['delivery_driver_id' => $driver->id, 'pickup_driver_id' => $driver->id]);
        return $visit->fresh();
    }

    private function loginDriver(HourlyDriver $driver, string $phone = '0512345678'): string
    {
        $this->postJson('/api/driver/login', ['phone' => $phone])->assertOk()->assertJsonPath('data.debug_code', '12345');
        $token = $this->postJson('/api/driver/verify-otp', ['phone' => $phone, 'otp' => '12345'])->assertOk()
            ->assertJsonPath('code', 200)->assertJsonPath('status', true)
            ->assertJsonPath('data.driver.id', $driver->id)->json('data.token');
        auth()->forgetGuards();
        return $token;
    }

    private function loginWorker(int $maid = 1, string $phone = '0500000001'): string
    {
        $this->postJson('/api/worker/login', ['phone' => $phone])->assertOk()->assertJsonPath('data.debug_code', '12345');
        $token = $this->postJson('/api/worker/verify-otp', ['phone' => $phone, 'otp' => '12345'])->assertOk()
            ->assertJsonPath('data.worker.id', $maid)->json('data.token');
        auth()->forgetGuards();
        return $token;
    }

    public function test_driver_logs_in_with_a_code_and_registers_its_device(): void
    {
        $driver = $this->driver();
        // رمز قبل دقيقة لا يُعاد إرساله.
        $this->postJson('/api/driver/login', ['phone' => '0512345678'])->assertOk();
        $this->postJson('/api/driver/login', ['phone' => '0512345678'])->assertStatus(422)
            ->assertJsonPath('status', false)->assertJsonPath('message', 'تم إرسال رمز قبل قليل. انتظر دقيقة ثم أعد المحاولة.');
        $this->postJson('/api/driver/verify-otp', ['phone' => '0512345678', 'otp' => '99999'])->assertStatus(422)->assertJsonPath('message', 'الرمز غير صحيح.');
        $token = $this->postJson('/api/driver/verify-otp', ['phone' => '0512345678', 'otp' => '12345'])->assertOk()->json('data.token');
        auth()->forgetGuards();

        $this->withToken($token)->postJson('/api/driver/devices', ['fcm_token' => 'fcm-abc', 'platform' => 'android'])->assertOk()->assertJsonPath('data.registered', true);
        $this->assertDatabaseHas('hourly_staff_devices', ['guard' => 'driver', 'staff_id' => $driver->id, 'fcm_token' => 'fcm-abc', 'platform' => 'android']);
        auth()->forgetGuards();
        // نفس المسار بالبادئة الأصلية للموديول.
        $this->withToken($token)->getJson('/api/app/hourly/driver/me')->assertOk()->assertJsonPath('data.id', $driver->id)->assertJsonPath('data.is_online', false);
        $this->postJson('/api/driver/login', ['phone' => '0555555555'])->assertStatus(422)->assertJsonPath('message', 'لا يوجد حساب سائق بهذا الرقم. راجع الإدارة.');
    }

    public function test_driver_location_is_bound_only_to_an_assigned_visit(): void
    {
        $driver = $this->driver();
        $visit = $this->assign($this->order()->visits()->first(), $driver);
        $token = $this->loginDriver($driver);

        $this->withToken($token)->postJson('/api/driver/location', [
            'visit_id' => $visit->id, 'lat' => 24.7136, 'lng' => 46.6753, 'accuracy' => 8,
        ])->assertOk()->assertJsonPath('data.saved', 1);
        $this->assertDatabaseHas('location_pings', [
            'user_type' => 'driver', 'user_id' => $driver->id, 'hourly_visit_id' => $visit->id,
        ]);

        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/location', [
            'visit_id' => 999999, 'lat' => 24.7136, 'lng' => 46.6753,
        ])->assertNotFound();
    }

    public function test_trip_stops_follow_the_visit_lifecycle_to_completion(): void
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $driver = $this->driver();
        $order = $this->order();
        $visit = $this->assign($order->visits()->first(), $driver);
        $token = $this->loginDriver($driver);
        Carbon::setTestNow('2026-09-22 08:00:00');

        $trips = $this->withToken($token)->getJson('/api/driver/trips/today')->assertOk()
            ->assertJsonPath('data.0.type', 'delivery')->assertJsonPath('data.0.status', 'accepted')
            ->assertJsonPath('data.0.stops.0.action', 'shelter_pickup')->assertJsonPath('data.0.stops.0.status', 'pending')
            ->assertJsonPath('data.1.type', 'pickup')->json('data');
        // طلب دفع نقدي غير مسدَّد: المحطة تحمل المبلغ المطلوب تحصيله.
        $this->assertGreaterThan(0, (float) $trips[0]['stops'][0]['cash_due']);
        $delivery = $trips[0]['stops'][0]['id'];
        $pickup = $trips[1]['stops'][0]['id'];
        auth()->forgetGuards();

        $this->withToken($token)->postJson('/api/driver/trips/'.$trips[0]['id'].'/start')->assertOk()->assertJsonPath('data.trip.status', 'started');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/stops/'.$delivery.'/arrived', ['latitude' => 24.71, 'longitude' => 46.7])->assertOk();
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/stops/'.$delivery.'/shelter-pickup')->assertOk()
            ->assertJsonPath('data.trip.stops.0.action', 'drop_off')
            // محطة التسليم تُبلّغ التطبيق أن الرمز مطلوب.
            ->assertJsonPath('data.trip.stops.0.requires_code', true)->assertJsonPath('data.trip.stops.0.cash_collection.can_collect',true);
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'en_route']);
        auth()->forgetGuards();

        // التسليم يحتاج رمز العميل الصحيح.
        $code = $visit->fresh()->delivery_code;
        $this->assertMatchesRegularExpression('/^\d{4}$/', (string) $code);
        $this->withToken($token)->postJson('/api/driver/stops/'.$delivery.'/deliver', ['otp_code' => '0000' === $code ? '1111' : '0000'])
            ->assertStatus(422)->assertJsonPath('message', 'رمز التسليم غير صحيح.');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/stops/'.$delivery.'/deliver', ['otp_code' => $code, 'cash_collected_amount' => 100])
            ->assertOk()->assertJsonPath('data.cash_collected', 100)->assertJsonPath('data.trip.stops.0.status', 'completed');
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'delivered']);
        $this->assertDatabaseHas('hourly_order_payments', ['hourly_rental_order_id' => $order->id, 'amount' => 100, 'collected_by_type' => 'driver', 'collected_by_id' => $driver->id]);
        $this->assertSame('partial', $order->fresh()->payment_status);
        auth()->forgetGuards();

        // الاستلام لا يجوز قبل إعلان العاملة انتهاءها.
        $this->withToken($token)->postJson('/api/driver/stops/'.$pickup.'/pick-up')->assertStatus(422)
            ->assertJsonPath('message', 'لم تُعلن العاملة انتهاءها بعد؛ الاستلام غير متاح.');
        $visit->fresh()->update(['status' => 'worker_finished']);
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/driver/pickup-requests')->assertOk()
            ->assertJsonPath('data.requests.0.stop_id', $pickup)->assertJsonPath('data.onboard', []);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/stops/'.$pickup.'/pick-up')->assertOk();
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'picked_up']);
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/driver/pickup-requests')->assertOk()->assertJsonPath('data.onboard.0.stop_id', $pickup);
        auth()->forgetGuards();
        // «غير متاح» ممنوع ما دام في المشوار محطة مفتوحة.
        $this->withToken($token)->postJson('/api/driver/shift', ['is_online' => false])->assertStatus(422)
            ->assertJsonPath('message', 'لديك مشوار جارٍ؛ أكمل محطاته قبل التحول إلى «غير متاح».');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/stops/'.$pickup.'/shelter-return')->assertOk();
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'completed']);
        $this->assertSame('completed', $order->fresh()->status);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/shift', ['is_online' => true])->assertOk()->assertJsonPath('data.is_online', true);
    }

    public function test_password_login_keeps_a_long_session_and_refreshes_silently(): void
    {
        $driver = $this->driver(['password' => \Illuminate\Support\Facades\Hash::make('Field@12345')]);
        HourlyRentalSettings::persist(['staff_app_session_days' => 30]);

        // دخول باسم المستخدم وكلمة المرور على نفس مسار الدخول (بلا رمز تحقق).
        $long = $this->postJson('/api/driver/login', ['login' => 'HD-1', 'password' => 'Field@12345'])->assertOk()
            ->assertJsonPath('data.remember', true)->assertJsonPath('data.session_days', 30)
            ->assertJsonPath('data.expires_in', 30 * 24 * 60 * 60)->json('data');
        $this->assertNotEmpty($long['expires_at']);
        auth()->forgetGuards();

        // بالهاتف أيضًا، ومع remember=false تعود المدة القصيرة العامة (60 دقيقة).
        $short = $this->postJson('/api/driver/login', ['login' => '0512345678', 'password' => 'Field@12345', 'remember' => false])->assertOk()
            ->assertJsonPath('data.remember', false)->json('data');
        $this->assertSame(config('jwt.ttl') * 60, $short['expires_in']);
        auth()->forgetGuards();

        // كلمة مرور خاطئة تُرفض برسالة واحدة لا تكشف أي الحقلين خطأ.
        $this->postJson('/api/driver/login', ['login' => 'HD-1', 'password' => 'wrong-pass'])->assertStatus(422)
            ->assertJsonPath('message', 'بيانات الدخول غير صحيحة أو الحساب غير متاح على هذه المنصة.');
        auth()->forgetGuards();

        // تجديد صامت: توكن جديد بنفس المدة، ويعمل فعلًا، بلا مطالبة ببيانات الدخول.
        $refreshed = $this->withToken($long['token'])->postJson('/api/driver/refresh')->assertOk()
            ->assertJsonPath('data.remember', true)->assertJsonPath('data.driver.id', $driver->id)->json('data');
        $this->assertNotSame($long['token'], $refreshed['token']);
        auth()->forgetGuards();
        $this->withToken($refreshed['token'])->getJson('/api/driver/me')->assertOk()->assertJsonPath('data.id', $driver->id);
        auth()->forgetGuards();

        // سحب الوصول من لوحة التحكم (رفع auth_version) يُبطل الجلسة الطويلة والتجديد فورًا.
        // زيادة على مستوى قاعدة البيانات: قيمة العمود الافتراضية لا تُحمَّل في الموديل بعد create.
        HourlyDriver::whereKey($driver->id)->increment('auth_version');
        $this->withToken($long['token'])->postJson('/api/driver/refresh')->assertUnauthorized();
        auth()->forgetGuards();
        $this->withToken($refreshed['token'])->getJson('/api/driver/me')->assertUnauthorized();
    }

    public function test_worker_password_login_and_refresh_work_the_same(): void
    {
        $this->order(1, '2026-09-22 08:00', ['password_ready' => true]);
        DB::table('maids')->where('id', 1)->update(['hourly_password' => \Illuminate\Support\Facades\Hash::make('Field@12345')]);
        $login = $this->postJson('/api/worker/login', ['login' => 'HM-1', 'password' => 'Field@12345'])->assertOk()
            ->assertJsonPath('data.remember', true)->assertJsonPath('data.worker.id', 1)->json('data');
        $this->assertSame(30 * 24 * 60 * 60, $login['expires_in']);
        auth()->forgetGuards();
        $refreshed = $this->withToken($login['token'])->postJson('/api/worker/refresh')->assertOk()
            ->assertJsonPath('data.worker.id', 1)->json('data');
        auth()->forgetGuards();
        $this->withToken($refreshed['token'])->getJson('/api/worker/hours')->assertOk();
    }

    public function test_suspended_worker_cannot_log_in_with_password(): void
    {
        $this->order(1, '2026-09-22 08:00', ['password_ready' => true]);
        DB::table('maids')->where('id', 1)->update(['hourly_password' => \Illuminate\Support\Facades\Hash::make('Field@12345'), 'hourly_is_suspended' => true]);
        $this->postJson('/api/worker/login', ['login' => 'HM-1', 'password' => 'Field@12345'])->assertUnprocessable()
            ->assertJsonPath('status', false)->assertJsonPath('errors.login.0', 'الحساب موقوف مؤقتًا. راجع الإدارة.');
    }

    public function test_envelope_keeps_keys_beside_data(): void
    {
        $response = (new \App\Http\Middleware\HourlyStaffApiResponse)->handle(\Illuminate\Http\Request::create('/api/worker/offers'),
            fn () => response()->json(['data' => [], 'next_page_url' => 'http://x/api/worker/offers?page=2']));
        $this->assertSame(['code' => 200, 'status' => true, 'message' => '', 'data' => [], 'next_page_url' => 'http://x/api/worker/offers?page=2'], $response->getData(true));
    }

    public function test_trips_history_covers_other_days_and_explains_an_empty_list(): void
    {
        $driver = $this->driver();
        // زيارة اليوم، وأخرى بعد ثلاثة أيام على طلب لم يُؤكَّد بعد.
        $today = $this->assign($this->order(1, '2026-09-22 08:00')->visits()->first(), $driver);
        $later = $this->order(2, '2026-09-25 08:00');
        $later->update(['status' => 'pending_confirmation']);
        $future = $this->assign($later->visits()->first(), $driver);
        $token = $this->loginDriver($driver);
        Carbon::setTestNow('2026-09-22 08:00:00');

        // مشوار اليوم لا يرى غير اليوم.
        $this->withToken($token)->getJson('/api/driver/trips/today')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('data.0.route_date', '2026-09-22')->assertJsonPath('data.0.actionable', true);
        auth()->forgetGuards();

        // المدى يرى الأيام الأخرى، والطلب غير المؤكَّد يظهر للعلم بلا إجراء.
        $history = $this->withToken($token)->getJson('/api/driver/trips?from=2026-09-20&to=2026-09-27')->assertOk()
            ->assertJsonPath('data.from', '2026-09-20')->assertJsonPath('data.to', '2026-09-27')->json('data');
        $dates = array_column($history['days'], 'date');
        $this->assertSame(['2026-09-22', '2026-09-25'], $dates);
        $upcoming = collect($history['trips'])->firstWhere('route_date', '2026-09-25');
        $this->assertSame('pending_confirmation', $upcoming['order_status']);
        $this->assertFalse($upcoming['actionable']);
        $this->assertSame(0, $history['diagnostics']['excluded_cancelled_or_expired_orders']);
        $this->assertSame(2, $history['diagnostics']['assigned_visits_total']);
        $this->assertNull($history['diagnostics']['hint']);
        auth()->forgetGuards();

        // محطة على طلب غير مؤكَّد ترفض الإجراء برسالة واضحة.
        $stop = DB::table('hourly_route_stops')->where('hourly_visit_id', $future->id)->where('role', 'delivery')->value('id');
        $this->withToken($token)->postJson('/api/driver/stops/'.$stop.'/shelter-pickup')->assertStatus(422)
            ->assertJsonPath('message', 'الطلب غير نشط ولا يقبل تحديثات من السائق.');
        auth()->forgetGuards();

        // سائق بلا إسناد: القائمة فارغة والتشخيص يشرح السبب.
        $other = $this->driver(['name' => 'سائق بلا مهام', 'employee_number' => 'D-2', 'username' => 'HD-2', 'login_phone' => '0512345679']);
        $otherToken = $this->loginDriver($other, '0512345679');
        $this->withToken($otherToken)->getJson('/api/driver/trips/today')->assertOk()->assertJsonPath('data', []);
        auth()->forgetGuards();
        $this->withToken($otherToken)->getJson('/api/driver/trips/diagnostics')->assertOk()
            ->assertJsonPath('data.assigned_visits_total', 0)
            ->assertJsonPath('data.hint', 'لا توجد زيارة مُسنَدة لهذا السائق إطلاقًا (delivery_driver_id / pickup_driver_id).');
        auth()->forgetGuards();
        // قائد فريق فقط: التشخيص يميّزها عن الإسناد الحقيقي.
        HourlyVisit::whereKey($today->id)->update(['team_leader_id' => $other->id]);
        $this->withToken($otherToken)->getJson('/api/driver/trips/diagnostics')->assertOk()
            ->assertJsonPath('data.team_leader_only_visits', 1)
            ->assertJsonPath('data.hint', 'السائق قائد فريق على 1 زيارة لكنه ليس سائق توصيل ولا استلام فيها؛ استخدم «تعيين سائق» لدور التوصيل أو الاستلام.');
    }

    public function test_stop_actions_respect_their_turn_and_the_geofence(): void
    {
        HourlyRentalSettings::persist(['staff_app_stop_radius_meters' => 300]);
        $driver = $this->driver();
        $visit = $this->assign($this->order()->visits()->first(), $driver);
        $token = $this->loginDriver($driver);
        Carbon::setTestNow('2026-09-22 08:00:00');
        $stop = $this->withToken($token)->getJson('/api/driver/trips/today')->assertOk()->json('data.0.stops.0.id');
        auth()->forgetGuards();

        // التسليم ليس دور المحطة قبل الاستلام من الإيواء.
        $this->withToken($token)->postJson('/api/driver/stops/'.$stop.'/deliver', ['otp_code' => $visit->delivery_code])
            ->assertStatus(422)->assertJsonPath('message', 'هذا الإجراء ليس دور هذه المحطة الآن (استلام من الإيواء).');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/stops/'.$stop.'/shelter-pickup')->assertOk();
        auth()->forgetGuards();
        // بعيد عن العميل: يُرفض التسليم.
        $this->withToken($token)->postJson('/api/driver/stops/'.$stop.'/deliver', ['otp_code' => $visit->fresh()->delivery_code, 'latitude' => 25.5, 'longitude' => 46.7])
            ->assertStatus(422)->assertJsonPath('status', false);
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'en_route']);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/stops/'.$stop.'/fail', ['reason' => 'العميل غير موجود'])->assertOk();
        $this->assertDatabaseHas('hourly_route_stops', ['id' => $stop, 'status' => 'failed', 'fail_reason' => 'العميل غير موجود']);
    }

    public function test_offers_appear_only_when_acceptance_is_required(): void
    {
        $driver = $this->driver();
        $visit = $this->assign($this->order()->visits()->first(), $driver);
        $token = $this->loginDriver($driver);
        $this->withToken($token)->getJson('/api/driver/offers')->assertOk()->assertJsonPath('data', []);
        auth()->forgetGuards();

        HourlyRentalSettings::persist(['staff_app_driver_acceptance' => true, 'offer_timeout_minutes' => 15]);
        DB::table('hourly_driver_trips')->delete();
        $offers = $this->withToken($token)->getJson('/api/driver/offers')->assertOk()
            ->assertJsonPath('data.0.type', 'delivery')->assertJsonPath('data.0.stops_count', 1)->json('data');
        $this->assertNotNull($offers[0]['expires_at']);
        auth()->forgetGuards();
        // المحطات مقفلة قبل القبول.
        $stop = DB::table('hourly_route_stops')->where('hourly_visit_id', $visit->id)->where('role', 'delivery')->value('id');
        $this->withToken($token)->postJson('/api/driver/stops/'.$stop.'/shelter-pickup')->assertStatus(422)
            ->assertJsonPath('message', 'اقبل المشوار أولًا ثم ابدأه.');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/trips/'.$offers[0]['trip_id'].'/accept')->assertOk()->assertJsonPath('data.trip.status', 'accepted');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/trips/'.$offers[0]['trip_id'].'/accept')->assertStatus(422)
            ->assertJsonPath('message', 'العرض لم يعد متاحًا أو سبق الرد عليه.');
        auth()->forgetGuards();
        $pickupTrip = DB::table('hourly_driver_trips')->where('role', 'pickup')->value('id');
        $this->withToken($token)->postJson('/api/driver/trips/'.$pickupTrip.'/reject', ['reason' => 'المركبة ممتلئة'])->assertOk();
        $this->assertDatabaseHas('hourly_driver_trips', ['id' => $pickupTrip, 'status' => 'rejected', 'reject_reason' => 'المركبة ممتلئة']);
    }

    public function test_driver_earnings_use_the_configured_stop_rate(): void
    {
        HourlyRentalSettings::persist(['staff_app_driver_stop_rate' => 15]);
        $driver = $this->driver();
        $visit = $this->assign($this->order()->visits()->first(), $driver);
        $token = $this->loginDriver($driver);
        $this->withToken($token)->getJson('/api/driver/trips/today')->assertOk();
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/driver/earnings')->assertOk()->assertJsonPath('data.total_due', 0);
        auth()->forgetGuards();
        DB::table('hourly_route_stops')->where('hourly_visit_id', $visit->id)->update(['status' => 'completed']);
        $this->withToken($token)->getJson('/api/driver/earnings')->assertOk()
            ->assertJsonPath('data.total_due', 30)->assertJsonPath('data.today.stops_count', 2)->assertJsonPath('data.items.0.amount', 30);
    }

    public function test_booking_options_reach_worker_and_driver_tasks(): void
    {
        $driver = $this->driver();
        $visit = $this->assign($this->order()->visits()->first(), $driver);
        $snapshot=$visit->order->package_snapshot;
        $snapshot['booking_options']=['bring_materials'=>true,'service_type'=>'company'];
        $visit->order->update(['package_snapshot'=>$snapshot]);
        $trips=app(\App\Services\Hourly\DriverAppService::class)->today($driver);
        $this->assertSame($snapshot['booking_options'],$trips[0]['stops'][0]['booking_options']);
        $worker=\App\Models\ingaz\Maid::findOrFail(1);
        $visits=app(\App\Services\Hourly\WorkerAppService::class)->today($worker);
        $this->assertSame($snapshot['booking_options'],$visits[0]['booking_options']);
    }

    public function test_worker_sees_hours_and_finishing_creates_the_pickup_request(): void
    {
        HourlyRentalSettings::persist(['staff_app_worker_hour_rate' => 10]);
        $driver = $this->driver();
        $visit = $this->assign($this->order()->visits()->first(), $driver);
        $token = $this->loginWorker();

        $this->withToken($token)->getJson('/api/worker/hours')->assertOk()
            ->assertJsonPath('data.day.used', 4)->assertJsonPath('data.day.max', 8)->assertJsonPath('data.week.max', 48);
        auth()->forgetGuards();
        $rows = $this->withToken($token)->getJson('/api/worker/visits/today')->assertOk()
            ->assertJsonPath('data.0.status', 'assigned')->assertJsonPath('data.0.needs_transport', true)
            ->assertJsonPath('data.0.can_arrive', false)->assertJsonPath('data.0.can_finish', false)->json('data');
        // اسم العميل مختصر للحي حفاظًا على الخصوصية.
        $this->assertStringStartsWith('عميل — الملز', $rows[0]['client']['name']);
        $workerVisit = $rows[0]['id'];
        auth()->forgetGuards();

        // «وصلت» ليست لمن تحتاج توصيلًا، و«انتهيت» تحتاج زيارة جارية.
        $this->withToken($token)->postJson('/api/worker/visits/'.$workerVisit.'/arrived')->assertStatus(422)
            ->assertJsonPath('message', 'وصولك يُسجّل بالتسليم من السائق برمز العميل.');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/visits/'.$workerVisit.'/finish')->assertStatus(422)
            ->assertJsonPath('message', 'لا يمكن إعلان الانتهاء قبل بدء الزيارة.');
        $visit->fresh()->update(['status' => 'delivered']);
        auth()->forgetGuards();

        $this->withToken($token)->postJson('/api/worker/visits/'.$workerVisit.'/finish', ['note' => 'أنجزت المطلوب'])->assertOk()
            ->assertJsonPath('data.visit.status', 'finished');
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'worker_finished']);
        $this->assertDatabaseHas('hourly_visit_workers', ['hourly_visit_id' => $visit->id, 'maid_id' => 1, 'status' => 'finished']);
        auth()->forgetGuards();
        // إعادة الإرسال لا تغيّر شيئًا.
        $this->withToken($token)->postJson('/api/worker/visits/'.$workerVisit.'/finish')->assertOk()->assertJsonPath('data.visit.status', 'finished');
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/worker/earnings')->assertOk()
            ->assertJsonPath('data.total_due', 40)->assertJsonPath('data.month.visits_count', 1);
    }

    private function prepareServiceTime(): void
    {
        (require base_path('database/migrations/2026_10_18_000006_create_hourly_service_time_checks.php'))->up();
        $this->mock(\App\Services\Hourly\CustomerTrackingPushService::class,function($mock){$mock->shouldReceive('notify')->zeroOrMoreTimes();$mock->shouldReceive('orderStatus')->zeroOrMoreTimes();});
        $this->mock(\App\Services\Hourly\StaffPushService::class,function($mock){
            $mock->shouldReceive('sendToStaff')->zeroOrMoreTimes()->andReturn(\App\Services\Firebase\FirebasePushResult::skipped('test'));
            $mock->shouldReceive('extraHour')->zeroOrMoreTimes();$mock->shouldReceive('pickupRequested')->zeroOrMoreTimes();
        });
    }


    public function test_service_time_reminds_once_then_records_overrun_without_charging(): void
    {
        $order=$this->order();$visit=$order->visits()->first();$this->prepareServiceTime();
        Carbon::setTestNow('2026-09-22 08:30');$visit->update(['status'=>'delivered']);
        \App\Services\Hourly\ServiceTime::recordDelivery($visit);
        $worker=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->first();
        $service=app(\App\Services\Hourly\ServiceTime::class);
        $this->assertSame('12:30',\App\Services\Hourly\ServiceTime::expectedEnd($visit,$worker)->format('H:i'));
        $before=$order->only(['total_amount','paid_amount']);$price=$visit->visit_price;
        $this->mock(\App\Services\Hourly\CustomerTrackingPushService::class,function($mock)use($visit){$mock->shouldReceive('notify')->once()->with($visit->id,'service_ending');});
        Carbon::setTestNow('2026-09-22 12:00');$service->scan();$service->scan();
        $this->assertDatabaseCount('hourly_service_time_checks',1);
        $this->assertDatabaseHas('hourly_service_time_checks',['worker_visit_id'=>$worker->id,'status'=>'reminded']);
        Carbon::setTestNow('2026-09-22 12:31');$service->scan();$service->scan();
        $this->assertDatabaseCount('hourly_service_time_checks',1);
        $this->assertDatabaseHas('hourly_service_time_checks',['worker_visit_id'=>$worker->id,'status'=>'awaiting_reason']);
        $data=$service->workerData($visit,$worker);$this->assertSame('overrun',$data['phase']);$this->assertTrue($data['requires_reason']);
        $public=$service->publicData($visit);$this->assertTrue($public['under_review']);$this->assertFalse($public['automatic_charge']);
        $this->assertEquals($before,$order->fresh()->only(['total_amount','paid_amount']));$this->assertEquals($price,$visit->fresh()->visit_price);
    }


    public function test_service_time_reason_and_admin_review_preserve_execution_money_and_privacy(): void
    {
        $order=$this->order();$visit=$order->visits()->first();$this->prepareServiceTime();
        $visit->update(['status'=>'delivered']);Carbon::setTestNow('2026-09-22 08:00');\App\Services\Hourly\ServiceTime::recordDelivery($visit);
        Carbon::setTestNow('2026-09-22 12:10');$service=app(\App\Services\Hourly\ServiceTime::class);$service->scan();
        $worker=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->first();$token=$this->loginWorker();
        $url='/api/worker/visits/'.$worker->id.'/time-overrun';
        $this->withToken($token)->postJson($url,['reason'=>'forgot_finish'])->assertUnprocessable();auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/visits/99999/time-overrun',['reason'=>'other'])->assertNotFound();auth()->forgetGuards();
        $id=$this->withToken($token)->postJson($url,['reason'=>'forgot_finish','actual_finished_at'=>'2026-09-22T11:55','note'=>'PRIVATE OPERATIONAL DETAIL'])
            ->assertOk()->assertJsonPath('data.status','awaiting_admin')->json('data.id');auth()->forgetGuards();
        $this->assertNull(DB::table('hourly_visit_workers')->where('id',$worker->id)->value('finished_at'));
        $this->assertSame('delivered',$visit->fresh()->status);
        $public=app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit->fresh(),$order->fresh());
        $this->assertStringNotContainsString('PRIVATE OPERATIONAL DETAIL',json_encode($public));
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->name='مشرف';$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $this->get(route('admin.hourly-service-time.index'))->assertOk()->assertSee('PRIVATE OPERATIONAL DETAIL');
        $review=['decision'=>'no_charge','note'=>'Verified no additional work','verified'=>true];
        $this->post(route('admin.hourly-service-time.review',$id),$review)->assertRedirect()->assertSessionHasNoErrors();
        $this->post(route('admin.hourly-service-time.review',$id),$review)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hourly_service_time_checks',['id'=>$id,'status'=>'resolved','decision'=>'no_charge']);
        $this->assertEquals(1000,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $this->assertEquals($order->total_amount,$order->fresh()->total_amount);$this->assertSame('delivered',$visit->fresh()->status);
    }


    public function test_service_extension_requires_consent_and_uses_actual_arrival_deadline(): void
    {
        $order=$this->order();$visit=$order->visits()->first();$this->prepareServiceTime();
        Carbon::setTestNow('2026-09-22 08:30');$visit->update(['status'=>'delivered']);\App\Services\Hourly\ServiceTime::recordDelivery($visit);
        Carbon::setTestNow('2026-09-22 12:10');
        $this->assertNull(\App\Services\Hourly\OverdueWorkerReturn::reason(1));
        $actions=app(\App\Services\Hourly\CustomerVisitActions::class);$before=$visit->visit_price;
        $quote=$actions->extraHourQuote($visit,1);$this->assertSame('2026-09-22 13:30',$quote['ends_at']);
        $this->actingAs(\App\Models\ingaz\User::findOrFail(1),'api');
        $this->getJson('/api/app/hourly/orders/'.$order->id.'/visits/'.$visit->id.'/extra-hour?hours=1')->assertOk()->assertJsonPath('data.ends_at','2026-09-22 13:30');
        $this->assertEquals($before,$visit->fresh()->visit_price);
        $token=(string)Str::uuid();$actions->extraHour($visit,1,$token);$actions->extraHour($visit,1,$token);
        $visit->refresh();$this->assertEquals(5,$visit->planned_hours);$this->assertEquals((float)$before+$quote['amount'],$visit->visit_price);
        Carbon::setTestNow('2026-09-22 12:31');
        $row=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->first();
        $this->assertSame('in_service',app(\App\Services\Hourly\ServiceTime::class)->workerData($visit,$row)['phase']);
        Carbon::setTestNow('2026-09-22 13:31');
        try{$actions->extraHour($visit,1,(string)Str::uuid());$this->fail('No unapproved retrospective extension');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertArrayHasKey('hourly',$e->errors());}
        $this->assertEquals(5,$visit->fresh()->planned_hours);
    }


    public function test_late_finish_captures_review_without_cron_and_without_an_extra_charge(): void
    {
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>false]);$visit=$order->visits()->first();$this->prepareServiceTime();
        Carbon::setTestNow('2026-09-22 08:00');$visit->update(['status'=>'delivered']);\App\Services\Hourly\ServiceTime::recordDelivery($visit);
        $worker=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->first();Carbon::setTestNow('2026-09-22 12:10');
        $maid=\App\Models\ingaz\Maid::findOrFail(1);
        app(\App\Services\Hourly\WorkerAppService::class)->finish($maid,$worker->id,'Late finish',[]);
        $this->assertDatabaseHas('hourly_service_time_checks',['worker_visit_id'=>$worker->id,'status'=>'awaiting_reason']);
        $this->assertSame('completed',$visit->fresh()->status);$this->assertEquals($order->total_amount,$order->fresh()->total_amount);
        $service=app(\App\Services\Hourly\ServiceTime::class);$result=$service->reportReason($maid,$worker->id,['reason'=>'waiting_driver','note'=>'No additional service']);
        $this->assertSame('awaiting_admin',$result['status']);$this->assertSame('completed',$visit->fresh()->status);
    }

    public function test_early_arrival_deadline_does_not_allow_a_retrospective_extension(): void
    {
        $order=$this->order();$visit=$order->visits()->first();$this->prepareServiceTime();
        Carbon::setTestNow('2026-09-22 07:30');$visit->update(['status'=>'delivered']);\App\Services\Hourly\ServiceTime::recordDelivery($visit);
        $this->assertSame('11:30',\App\Services\Hourly\ServiceTime::deadline($visit)->format('H:i'));
        Carbon::setTestNow('2026-09-22 11:31');
        try{app(\App\Services\Hourly\CustomerVisitActions::class)->extraHour($visit,1,(string)Str::uuid());$this->fail('Actual service time ended');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertArrayHasKey('hourly',$e->errors());}
        Carbon::setTestNow('2026-09-22 12:00');
        $this->assertNotNull(\App\Services\Hourly\OverdueWorkerReturn::reason(1));
        $this->assertEquals(4,$visit->fresh()->planned_hours);$this->assertEquals($order->total_amount,$order->fresh()->total_amount);
    }

    public function test_worker_requiring_driver_cannot_self_arrive_from_web_or_api(): void
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>'1']);
        $visit=$this->assign($order->visits()->first(),$this->driver());$visit->update(['status'=>'en_route']);
        $token=$this->loginWorker();
        $task=$this->withToken($token)->getJson('/api/worker/visits/today')->assertOk()
            ->assertJsonPath('data.0.needs_transport',true)->assertJsonPath('data.0.can_arrive',false)->json('data.0');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/visits/'.$task['id'].'/arrived')->assertUnprocessable()
            ->assertJsonPath('message','وصولك يُسجّل بالتسليم من السائق برمز العميل.');
        auth()->forgetGuards();
        $account=\App\Models\Hourly\HourlyMaidAccount::findOrFail(1);
        $this->actingAs($account,'hourly_maid')->withSession(['hourly_maid_version'=>$account->hourly_auth_version]);
        $this->get(route('hourly-maid.home'))->assertOk()->assertSee('الزيارات والتحصيل النقدي')->assertDontSee('value="arrived"',false);
        $this->from(route('hourly-maid.home'))->post(route('hourly-maid.visits.action',$task['id']),['action'=>'arrived'])
            ->assertRedirect(route('hourly-maid.home'))->assertSessionHasErrors('hourly');
        $this->assertSame('en_route',$visit->fresh()->status);
        $this->assertNull(DB::table('hourly_visit_workers')->where('id',$task['id'])->value('arrived_at'));
        $maid=\App\Models\ingaz\Maid::findOrFail(1);
        $maid->hourly_settings=array_replace($maid->hourly_settings,['requires_driver'=>'0']);$maid->save();
        $ownTrip=app(\App\Services\Hourly\WorkerAppService::class)->today($maid)[0];
        $this->assertTrue($ownTrip['needs_transport']);$this->assertFalse($ownTrip['can_arrive']);
        $this->get(route('hourly-maid.home'))->assertOk()->assertDontSee('value="arrived"',false);
        $this->from(route('hourly-maid.home'))->post(route('hourly-maid.visits.action',$task['id']),['action'=>'arrived'])
            ->assertRedirect(route('hourly-maid.home'))->assertSessionHasErrors('hourly');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/visits/'.$task['id'].'/arrived')->assertUnprocessable()
            ->assertJsonPath('message','وصولك يُسجّل بالتسليم من السائق برمز العميل.');
        $this->assertNull(DB::table('hourly_visit_workers')->where('id',$task['id'])->value('arrived_at'));
        $this->assertSame('en_route',$visit->fresh()->status);
        $visit->update(['delivery_driver_id'=>null]);
        $ownTrip=app(\App\Services\Hourly\WorkerAppService::class)->today($maid)[0];
        $this->assertFalse($ownTrip['needs_transport']);$this->assertTrue($ownTrip['can_arrive']);
    }
    public function test_worker_cannot_execute_a_future_day_visit_even_with_stale_worker_status(): void
    {
        $order = $this->order(2, '2026-09-23 08:00', ['requires_driver' => false]);
        $visit = $order->visits()->first();
        $maid = \App\Models\ingaz\Maid::findOrFail(2);
        $row = DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->first();
        $service = app(\App\Services\Hourly\WorkerAppService::class);
        $task = $service->today($maid,'2026-09-23')[0];
        $this->assertFalse($task['can_arrive']);
        $this->assertFalse($task['can_finish']);
        try { $service->arrived($maid,$row->id,[]); $this->fail('Future arrival must be blocked.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('hourly',$e->errors()); }
        $this->assertNull(DB::table('hourly_visit_workers')->where('id',$row->id)->value('arrived_at'));
        DB::table('hourly_visit_workers')->where('id',$row->id)->update(['status'=>'delivered','arrived_at'=>now()]);
        $this->assertFalse($service->today($maid,'2026-09-23')[0]['can_finish']);
        try { $service->finish($maid,$row->id,null,[]); $this->fail('Future finish must be blocked.'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('hourly',$e->errors()); }
        $this->assertNull(DB::table('hourly_visit_workers')->where('id',$row->id)->value('finished_at'));
        $this->assertSame('scheduled',$visit->fresh()->status);
    }

    public function test_old_unreturned_visit_blocks_new_arrival_until_recorded_return(): void
    {
        if(!Schema::hasTable('worker_daily_movements'))(require base_path('database/migrations/2026_09_28_000001_create_worker_daily_movements_table.php'))->up();
        $old = $this->order(2, '2026-09-22 08:00', ['requires_driver'=>false])->visits()->first();
        $next = $this->order(2, '2026-09-23 08:00', ['requires_driver'=>false])->visits()->first();
        $driver = $this->driver();
        $old->update(['status'=>'in_progress','pickup_driver_id'=>$driver->id]);
        DB::table('hourly_visit_workers')->where('hourly_visit_id',$old->id)->update(['status'=>'delivered','arrived_at'=>'2026-09-22 08:00:00']);
        $maid = \App\Models\ingaz\Maid::findOrFail(2);
        $presence = app(\App\Services\Hourly\ShelterPresence::class);
        $presence->open($old,'driver',$driver->id);
        Carbon::setTestNow('2026-09-23 08:00:00');
        $service = app(\App\Services\Hourly\WorkerAppService::class);
        $task = $service->today($maid)[0];
        $this->assertSame($next->id,$task['visit_id']);
        $this->assertFalse($task['can_arrive']);
        $this->assertNotEmpty($task['arrival_blocked_reason']);
        $token = $this->loginWorker(2,'0500000002');
        $this->withToken($token)->postJson('/api/worker/visits/'.$task['id'].'/arrived')->assertStatus(422);
        $this->assertNull(DB::table('hourly_visit_workers')->where('id',$task['id'])->value('arrived_at'));
        auth()->forgetGuards();
        $account = \App\Models\Hourly\HourlyMaidAccount::findOrFail(2);
        $this->actingAs($account,'hourly_maid')->withSession(['hourly_maid_version'=>$account->hourly_auth_version]);
        $this->get(route('hourly-maid.home'))->assertOk()->assertDontSee('value="arrived"',false)->assertSee('data-arrival-blocked',false);
        $this->from(route('hourly-maid.home'))->post(route('hourly-maid.visits.action',$task['id']),['action'=>'arrived'])
            ->assertRedirect(route('hourly-maid.home'))->assertSessionHasErrors('hourly');
        $oldWorker = DB::table('hourly_visit_workers')->where('hourly_visit_id',$old->id)->first();
        $service->finish($maid,$oldWorker->id,null,[]);
        $this->assertSame('worker_finished',$old->fresh()->status);
        $this->assertFalse($service->today($maid)[0]['can_arrive']);
        $old->update(['status'=>'picked_up']);
        $this->assertFalse($service->today($maid)[0]['can_arrive']);
        $presence->close($old,'driver',$driver->id);
        $old->update(['status'=>'completed']);
        $task = $service->today($maid)[0];
        $this->assertTrue($task['can_arrive']);
        $this->assertNull($task['arrival_blocked_reason']);
        $service->arrived($maid,$task['id'],[],$next->fresh()->delivery_code);
        $this->assertSame('delivered',$next->fresh()->status);
        $this->assertNotNull(DB::table('hourly_visit_workers')->where('id',$task['id'])->value('arrived_at'));
    }

    public function test_worker_without_transport_starts_and_closes_her_own_visit(): void
    {
        $order = $this->order(2, '2026-09-22 08:00', ['requires_driver' => false]);
        $visit = $order->visits()->first();
        $token = $this->loginWorker(2, '0500000002');
        // 07:00 for an 08:00 visit: arrival would start the service clock an hour early, so it opens 30 minutes before.
        $early = $this->withToken($token)->getJson('/api/worker/visits/today')->assertOk()
            ->assertJsonPath('data.0.needs_transport', false)->assertJsonPath('data.0.can_arrive', false)->json('data.0');
        $this->assertStringContainsString('يتاح تسجيل الوصول من 07:30', $early['arrival_blocked_reason']);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/visits/'.$early['id'].'/arrived', ['latitude' => 24.71, 'longitude' => 46.7])->assertUnprocessable();
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'scheduled']);
        Carbon::setTestNow('2026-09-22 07:30:00');
        auth()->forgetGuards();
        $rows = $this->withToken($token)->getJson('/api/worker/visits/today')->assertOk()
            ->assertJsonPath('data.0.can_arrive', true)->assertJsonPath('data.0.requires_delivery_code', true)->json('data');
        // Like the driver's hand-over, the worker proves she reached the customer with the code shown in the customer's app.
        $code = $visit->fresh()->delivery_code;
        $this->assertNotEmpty($code);
        foreach ([[], ['otp_code' => $code === '0000' ? '1111' : '0000']] as $wrong) {
            auth()->forgetGuards();
            $this->withToken($token)->postJson('/api/worker/visits/'.$rows[0]['id'].'/arrived', $wrong + ['latitude' => 24.71, 'longitude' => 46.7])->assertUnprocessable();
        }
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'scheduled']);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/visits/'.$rows[0]['id'].'/arrived', ['otp_code' => $code, 'latitude' => 24.71, 'longitude' => 46.7])->assertOk()
            ->assertJsonPath('data.visit.status', 'delivered');
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'delivered']);
        auth()->forgetGuards();
        // After the full four hours (finishing at once would be an early finish awaiting the customer).
        Carbon::setTestNow('2026-09-22 11:30:00');
        // بلا سائق استلام: «انتهيت» تُغلق الزيارة والطلب.
        $this->withToken($token)->postJson('/api/worker/visits/'.$rows[0]['id'].'/finish')->assertOk();
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'completed']);
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_worker_leave_request_is_pending_until_the_admin_approves(): void
    {
        $this->order();
        $token = $this->loginWorker();
        $this->withToken($token)->postJson('/api/worker/leaves', ['type' => 'leave', 'starts_at' => '2026-09-22', 'ends_at' => '2026-09-23'])
            ->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.conflicting_visits', 1);
        $this->assertDatabaseHas('hourly_worker_leaves', ['maid_id' => 1, 'status' => 'pending', 'type' => 'leave', 'is_active' => false, 'source' => 'worker_app']);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/leaves', ['starts_at' => '2026-09-22', 'ends_at' => '2026-09-23'])->assertStatus(422)
            ->assertJsonPath('message', 'لديك طلب إجازة يتقاطع مع هذه المدة.');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/leaves', ['starts_at' => '2026-09-10', 'ends_at' => '2026-09-11'])->assertStatus(422)
            ->assertJsonPath('message', 'اختر تاريخًا من اليوم أو بعده.');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/leaves', ['type' => 'sick', 'starts_at' => '2026-09-19', 'ends_at' => '2026-09-19'])->assertCreated();
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/worker/leaves')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.type', 'sick');
    }

    public function test_worker_can_trigger_update_list_and_cancel_an_sos_alert(): void
    {
        $visit = $this->order()->visits()->first();
        $token = $this->loginWorker();
        $first = $this->withToken($token)->postJson('/api/worker/sos', [
            'latitude' => 24.7136, 'longitude' => 46.6753, 'accuracy' => 8, 'message' => 'أحتاج مساعدة',
        ])->assertCreated()->assertJsonPath('data.status', 'triggered')
            ->assertJsonPath('data.visit_id', $visit->id)->assertJsonPath('data.repeat_count', 1)->json('data');
        $this->assertDatabaseHas('hourly_worker_sos_alerts', [
            'id' => $first['id'], 'maid_id' => 1, 'status' => 'triggered', 'hourly_visit_id' => $visit->id,
        ]);

        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/sos', ['latitude' => 24.72, 'longitude' => 46.68])
            ->assertOk()->assertJsonPath('data.id', $first['id'])->assertJsonPath('data.repeat_count', 2);
        $this->assertDatabaseCount('hourly_worker_sos_alerts', 1);

        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/worker/sos')->assertOk()->assertJsonPath('data.0.id', $first['id']);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/sos/'.$first['id'].'/cancel', ['reason' => 'إنذار بالخطأ'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertDatabaseHas('hourly_worker_sos_alerts', ['id' => $first['id'], 'status' => 'cancelled', 'resolution_note' => 'إنذار بالخطأ']);
    }

    public function test_admin_can_acknowledge_and_resolve_an_sos_alert(): void
    {
        $alert = \App\Models\Hourly\HourlyWorkerSosAlert::create(['maid_id' => 1, 'status' => 'triggered']);
        $admin = \Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id = 7;
        $admin->shouldReceive('can')->andReturn(true);
        auth('admin')->setUser($admin);
        $controller = app(\App\Http\Controllers\admin\HourlySosController::class);

        $controller->update(new \Illuminate\Http\Request(['status' => 'acknowledged']), $alert);
        $this->assertDatabaseHas('hourly_worker_sos_alerts', ['id' => $alert->id, 'status' => 'acknowledged', 'acknowledged_by' => 7]);
        $controller->update(new \Illuminate\Http\Request(['status' => 'resolved', 'resolution_note' => 'تم التواصل مع العاملة']), $alert->fresh());
        $this->assertDatabaseHas('hourly_worker_sos_alerts', ['id' => $alert->id, 'status' => 'resolved', 'resolved_by' => 7, 'resolution_note' => 'تم التواصل مع العاملة']);
    }

    /** سطر إشعار بنفس شكل ما يكتبه StaffPushService. */
    private function pushLog(string $guard, int $staffId, string $title, string $event, ?string $when = null): int
    {
        return DB::table('communication_logs')->insertGetId([
            'batch_id' => (string) Str::uuid(), 'channel' => 'notification', 'recipient_type' => 'staff',
            'provider' => 'staff_app', 'recipient_name' => 'اختبار', 'title' => $title, 'message' => 'نص الإشعار',
            'status' => 'sent',
            'meta' => json_encode(['guard' => $guard, 'staff_id' => $staffId,
                'payload' => ['type' => $event, 'hourly_visit_id' => '35', 'hourly_order_id' => '12']], JSON_UNESCAPED_UNICODE),
            'created_at' => $when ?? now(), 'updated_at' => $when ?? now(),
        ]);
    }

    public function test_notification_inbox_lists_what_was_sent_and_clears_the_badge(): void
    {
        $driver = $this->driver();
        $this->order();
        $old = $this->pushLog('driver', $driver->id, 'مشوار قديم', 'hourly_trip_assigned', now()->subDays(2)->toDateTimeString());
        $new = $this->pushLog('driver', $driver->id, 'طلب استلام جاهز', 'hourly_pickup_request');
        $this->pushLog('worker', 1, 'زيارة جديدة', 'hourly_assignment');
        $token = $this->loginDriver($driver);

        // الأحدث أولًا، ومع كل إشعار بيانات الانتقال للشاشة الصحيحة.
        $feed = $this->withToken($token)->getJson('/api/driver/notifications')->assertOk()
            ->assertJsonPath('data.unread_count', 2)->assertJsonPath('data.last_read_at', null)
            ->assertJsonPath('data.items.0.id', $new)->assertJsonPath('data.items.0.event', 'hourly_pickup_request')
            ->assertJsonPath('data.items.0.visit_id', 35)->assertJsonPath('data.items.0.order_id', 12)
            ->assertJsonPath('data.items.0.delivered', true)->assertJsonPath('data.items.0.read', false)
            ->assertJsonPath('data.items.1.id', $old)->json('data');
        // إشعارات العاملة ليست ضمنها.
        $this->assertCount(2, $feed['items']);
        auth()->forgetGuards();

        // فتح الشاشة يصفّر الشارة، وإعادة الطلب لا تُرجعها.
        $this->withToken($token)->postJson('/api/driver/notifications/read')->assertOk()->assertJsonPath('data.unread_count', 0);
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/driver/notifications')->assertOk()
            ->assertJsonPath('data.unread_count', 0)->assertJsonPath('data.items.0.read', true);
        auth()->forgetGuards();

        // إشعار جديد بعد القراءة يعود غير مقروء (الوقت مجمَّد في الاختبار فنقدّمه دقيقة).
        Carbon::setTestNow(now()->addMinute());
        $this->pushLog('driver', $driver->id, 'إشعار لاحق', 'hourly_trip_assigned');
        $this->withToken($token)->getJson('/api/driver/notifications?limit=2')->assertOk()
            ->assertJsonPath('data.unread_count', 1)->assertJsonCount(2, 'data.items');
        auth()->forgetGuards();

        // العاملة ترى صندوقها المستقل. لا نثبّت العدد لأن إسناد الزيارة نفسه يولّد إشعارًا حقيقيًا لها.
        $workerToken = $this->loginWorker();
        $workerFeed = $this->withToken($workerToken)->getJson('/api/worker/notifications')->assertOk()->json('data');
        $this->assertNotEmpty($workerFeed['items']);
        $this->assertContains('hourly_assignment', array_column($workerFeed['items'], 'event'));
        $this->assertGreaterThanOrEqual(1, $workerFeed['unread_count']);
        // ولا يظهر لها شيء من إشعارات السائق.
        $this->assertNotContains('hourly_pickup_request', array_column($workerFeed['items'], 'event'));
        auth()->forgetGuards();
        $this->withToken($workerToken)->postJson('/api/worker/notifications/read')->assertOk()->assertJsonPath('data.unread_count', 0);
    }

    public function test_driver_sos_is_isolated_from_the_worker_and_closed_by_the_admin(): void
    {
        $driver = $this->driver();
        $visit = $this->assign($this->order()->visits()->first(), $driver);
        $token = $this->loginDriver($driver);

        // بلاغ السائق يُربط بزيارة يومه تلقائيًا.
        $alert = $this->withToken($token)->postJson('/api/driver/sos', [
            'latitude' => 24.70, 'longitude' => 46.68, 'accuracy' => 9, 'message' => 'عطل في المركبة',
        ])->assertCreated()->assertJsonPath('message', 'تم إرسال نداء النجدة إلى الإدارة.')
            ->assertJsonPath('data.guard', 'driver')->assertJsonPath('data.status', 'triggered')
            ->assertJsonPath('data.visit_id', $visit->id)->assertJsonPath('data.message', 'عطل في المركبة')
            ->assertJsonPath('data.repeat_count', 1)->json('data');
        $this->assertDatabaseHas('hourly_worker_sos_alerts', [
            'id' => $alert['id'], 'guard' => 'driver', 'hourly_driver_id' => $driver->id, 'maid_id' => null, 'status' => 'triggered',
        ]);
        auth()->forgetGuards();

        // الضغط مرة أخرى يقوّي نفس البلاغ ولا ينشئ ثانيًا.
        $this->withToken($token)->postJson('/api/driver/sos')->assertOk()
            ->assertJsonPath('data.id', $alert['id'])->assertJsonPath('data.repeat_count', 2);
        $this->assertDatabaseCount('hourly_worker_sos_alerts', 1);
        auth()->forgetGuards();

        // بعد إغلاق البلاغ لا يقبل الإلغاء (نغلقه مباشرة، وكل طلبات السائق تسبق أي تبديل حارس).
        \App\Models\Hourly\HourlyWorkerSosAlert::whereKey($alert['id'])->update(['status' => 'resolved', 'resolved_at' => now()]);
        $this->withToken($token)->postJson('/api/driver/sos/'.$alert['id'].'/cancel')->assertStatus(422)
            ->assertJsonPath('message', 'لا يمكن إلغاء هذا النداء بعد إغلاقه.');
        auth()->forgetGuards();

        // العاملة لا ترى بلاغ السائق ولا تستطيع إلغاءه.
        $workerToken = $this->loginWorker();
        $this->withToken($workerToken)->getJson('/api/worker/sos')->assertOk()->assertJsonPath('data', []);
        auth()->forgetGuards();
        $this->withToken($workerToken)->postJson('/api/worker/sos/'.$alert['id'].'/cancel')->assertNotFound();
        auth()->forgetGuards();

        // الإدارة تستلم وتعالج بلاغ سائق من نفس شاشة اللوحة.
        $second = \App\Models\Hourly\HourlyWorkerSosAlert::create([
            'guard' => 'driver', 'hourly_driver_id' => $driver->id, 'status' => 'triggered', 'message' => 'بلاغ ثانٍ',
        ]);
        $admin = \Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id = 7;
        $admin->shouldReceive('can')->andReturn(true);
        auth('admin')->setUser($admin);
        $controller = app(\App\Http\Controllers\admin\HourlySosController::class);
        $controller->update(new \Illuminate\Http\Request(['status' => 'acknowledged']), $second);
        $this->assertDatabaseHas('hourly_worker_sos_alerts', ['id' => $second->id, 'status' => 'acknowledged', 'acknowledged_by' => 7]);
        $controller->update(new \Illuminate\Http\Request(['status' => 'resolved', 'resolution_note' => 'تم إرسال مركبة بديلة']), $second->fresh());
        $this->assertDatabaseHas('hourly_worker_sos_alerts', [
            'id' => $second->id, 'status' => 'resolved', 'resolved_by' => 7, 'resolution_note' => 'تم إرسال مركبة بديلة',
        ]);
    }

    public function test_sos_confirmation_does_not_replace_the_alert_message(): void
    {
        $this->order();
        $token = $this->loginWorker();
        // الغلاف يحمل تأكيدًا للعاملة، وبيانات البلاغ تحمل نصها كما أرسلته.
        $this->withToken($token)->postJson('/api/worker/sos', ['message' => 'أحتاج مساعدة'])->assertCreated()
            ->assertJsonPath('message', 'تم إرسال نداء النجدة إلى الإدارة.')
            ->assertJsonPath('data.message', 'أحتاج مساعدة')
            ->assertJsonPath('data.created', true);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/sos', ['message' => 'ما زلت أحتاج'])->assertOk()
            ->assertJsonPath('message', 'تم تحديث نداء النجدة المفتوح وإعادة تنبيه الإدارة.')
            ->assertJsonPath('data.repeat_count', 2);
    }

    public function test_staff_routes_disappear_when_the_module_is_off(): void
    {
        $driver = $this->driver();
        $token = $this->loginDriver($driver);
        config(['superadmin_modules.hourly_rental.default_enabled' => false]);
        auth()->forgetGuards();
        foreach (['/api/driver/trips/today', '/api/driver/offers', '/api/worker/hours', '/api/app/hourly/driver/me'] as $path) {
            $this->withToken($token)->getJson($path)->assertNotFound();
            auth()->forgetGuards();
        }
    }


    public function test_manual_incident_records_return_without_completing_or_refunding_visit(): void
    {
        (require base_path('database/migrations/2026_10_18_000003_create_hourly_service_incidents.php'))->up();
        if(!Schema::hasTable('worker_daily_movements'))(require base_path('database/migrations/2026_09_28_000001_create_worker_daily_movements_table.php'))->up();
        $driver=$this->driver();$order=$this->order();$visit=$this->assign($order->visits()->first(),$driver);
        $this->mock(\App\Services\Hourly\CustomerTrackingPushService::class,function($mock){$mock->shouldReceive('notify')->zeroOrMoreTimes();$mock->shouldReceive('orderStatus')->zeroOrMoreTimes();});
        Carbon::setTestNow('2026-09-22 08:00:00');$board=app(\App\Services\Hourly\DriverAppService::class);$actions=app(\App\Services\Hourly\DriverStopActions::class);
        $trip=collect($board->sync($driver,'2026-09-22'))->firstWhere('type','delivery');
        $actions->shelterPickup($driver,$trip['stops'][0]['id'],[]);
        $report=app(\App\Services\Hourly\ServiceIncident::class)->report($visit->id,1,'driver',$driver->id,
            ['request_token'=>(string)Str::uuid(),'category'=>'accident','note'=>'Private transit incident']);
        $this->post(route('admin.hourly-incidents.review',$report['id']),['decision'=>'approve','verified'=>true,
            'unavailable_until'=>'2026-09-24T23:00','reason'=>'Verified incident'])->assertRedirect()->assertSessionHasNoErrors();
        $this->get(route('admin.hourly-incidents.index'))->assertOk()->assertSee('تسجيل العودة فقط');
        $held=collect($board->today($driver))->firstWhere('type','delivery');$this->assertFalse($held['stops'][0]['actionable']);
        $manager=app(\App\Services\Hourly\OrderManagementService::class);
        $cancel=['action'=>'cancel_visit','visit_ids'=>[$visit->id],'refund_method'=>'wallet','reason'=>'Company waives unperformed service'];
        try{$manager->apply($order,$cancel,7);$this->fail('Return must be checked before settlement');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertTrue(\App\Services\Hourly\ServiceIncident::hasOpenMovement($visit->id));}
        $returnUrl=route('admin.hourly-incidents.return',[$order->id,$visit->id]);
        $body=['confirmed_return'=>true,'reason'=>'Shelter confirmed physical return'];
        $this->post($returnUrl,$body)->assertRedirect()->assertSessionHasNoErrors();
        $this->post($returnUrl,$body)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('en_route',$visit->fresh()->status);$this->assertFalse(\App\Services\Hourly\ServiceIncident::hasOpenMovement($visit->id));
        $returned=collect($board->today($driver))->firstWhere('type','delivery');$this->assertStringContainsString('سُجلت عودة',$returned['stops'][0]['warning']);
        $this->assertSame(1,DB::table('hourly_order_audits')->where('action','incident_return_recorded')->count());
        $this->assertSame(0,$order->payments()->where('amount','<',0)->count());
        $this->postJson(route('admin.hourly-incidents.close',[$order->id,$visit->id]),['reason'=>'Cannot close before settlement'])->assertUnprocessable();
        $manager->apply($order,$cancel,7);
        $this->post(route('admin.hourly-incidents.close',[$order->id,$visit->id]),['reason'=>'Waived charge after reviewing non-delivery'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cancelled',$visit->fresh()->status);
        $this->assertSame('resolved',\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id)['status']);
    }
    public function test_admin_can_enter_worker_portal_and_return_without_losing_admin_session(): void
    {
        $this->order();
        config(['activitylog.enabled'=>false]);
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class,\App\Http\Middleware\EnforceRoutePermission::class,\App\Http\Middleware\EnforceCircularResponse::class]);
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->name='مشرف الاختبار';$admin->shouldReceive('can')->andReturn(true);
        auth('admin')->setUser($admin);
        $maid=\App\Models\ingaz\Maid::findOrFail(1);
        $this->post(route('admin.hourly-maids.login-as',$maid))->assertRedirect(route('hourly-maid.home'));
        $this->assertSame($maid->id,auth('hourly_maid')->id());$this->assertSame(7,auth('admin')->id());
        $this->assertSame(7,session('hourly_maid_impersonator.id'));
        $this->get(route('hourly-maid.home'))->assertOk()->assertSee('بصفتك المشرف مشرف الاختبار')->assertSee('إنهاء والعودة للوحة');
        $csrf=session()->token();
        $this->post(route('hourly-maid.logout'))->assertRedirect(route('admin.hourly-maids.index'));
        $this->assertNull(auth('hourly_maid')->user());$this->assertSame(7,auth('admin')->id());
        $this->assertSame($csrf,session()->token());$this->assertFalse(session()->has('hourly_maid_impersonator'));
        foreach([['account_enabled'=>false,'login_access'=>'both'],['account_enabled'=>true,'login_access'=>'mobile']] as $settings){
            $maid->hourly_settings=array_replace($maid->hourly_settings,$settings);$maid->save();
            $this->from(route('admin.hourly-maids.index'))->post(route('admin.hourly-maids.login-as',$maid))
                ->assertRedirect(route('admin.hourly-maids.index'))->assertSessionHasErrors('maid');
            $this->assertNull(auth('hourly_maid')->user());
        }
    }

    public function test_worker_impersonation_requires_its_own_permission(): void
    {
        $this->order();
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class,\App\Http\Middleware\EnforceRoutePermission::class,\App\Http\Middleware\EnforceCircularResponse::class]);
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(false);
        auth('admin')->setUser($admin);
        $this->post(route('admin.hourly-maids.login-as',1))->assertForbidden();
        $this->assertNull(auth('hourly_maid')->user());$this->assertFalse(session()->has('hourly_maid_impersonator'));
    }
    public function test_overdue_unreturned_worker_is_excluded_from_candidates_and_final_assignment(): void
    {
        $order=$this->order();$visit=$order->visits()->first();
        $maid=\App\Models\ingaz\Maid::findOrFail(1);
        $item=\App\Models\Hourly\HourlyPackage::findOrFail($order->hourly_package_id);
        $assignment=app(\App\Services\Hourly\HourlyWorkerAssignment::class);
        $visit->update(['status'=>'worker_finished','scheduled_start_at'=>'2026-09-18 08:00','scheduled_end_at'=>'2026-09-18 12:00']);
        $this->assertFalse($assignment->candidates($item,$this->team->id,false)->whereKey(1)->exists());
        $reason=$assignment->availabilityReason($maid,'2026-09-24 08:00','2026-09-24 12:00',$order->package_snapshot,$visit->id,true,true);
        $this->assertStringContainsString('عودة متأخرة',$reason);
        $future=$visit->replicate();$future->visit_number=2;$future->status='scheduled';$future->scheduled_start_at='2026-09-24 08:00';$future->scheduled_end_at='2026-09-24 12:00';$future->save();
        try{$assignment->assign($future,$item,1,false);$this->fail('Stale selected worker must be rejected');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertArrayHasKey('hourly',$e->errors());}
        $this->assertSame(0,$future->workers()->count());
        $this->assertSame('worker_finished',$visit->fresh()->status);
        $visit->update(['status'=>'completed']);
        $this->assertTrue($assignment->candidates($item,$this->team->id,false)->whereKey(1)->exists());
    }

    public function test_return_hold_starts_at_grace_boundary_and_allows_normal_future_bookings(): void
    {
        $order=$this->order();$visit=$order->visits()->first();$visit->update(['status'=>'delivered']);
        DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->update(['arrived_at'=>null]);
        $maid=\App\Models\ingaz\Maid::findOrFail(1);$assignment=app(\App\Services\Hourly\HourlyWorkerAssignment::class);
        HourlyRentalSettings::persist(['pickup_grace_minutes'=>30]);
        foreach(['2026-09-22 11:00','2026-09-22 12:29:59'] as $now){
            Carbon::setTestNow($now);
            $this->assertNull(\App\Services\Hourly\OverdueWorkerReturn::reason(1));
            $this->assertTrue($assignment->available($maid,'2026-09-24 08:00','2026-09-24 12:00',$order->package_snapshot));
        }
        Carbon::setTestNow('2026-09-22 12:30:00');
        $this->assertNotNull(\App\Services\Hourly\OverdueWorkerReturn::reason(1));
        $this->assertFalse($assignment->available($maid,'2026-09-24 08:00','2026-09-24 12:00',$order->package_snapshot));
    }

    public function test_open_movement_blocks_closed_or_unassigned_visit_until_actual_return(): void
    {
        if(!Schema::hasTable('worker_daily_movements'))(require base_path('database/migrations/2026_09_28_000001_create_worker_daily_movements_table.php'))->up();
        $order=$this->order();$visit=$order->visits()->first();
        $visit->update(['status'=>'completed','scheduled_start_at'=>'2026-09-18 08:00','scheduled_end_at'=>'2026-09-18 12:00']);
        $visit->workers()->detach();
        $movement=DB::table('worker_daily_movements')->insertGetId(['maid_id'=>1,'source'=>'hourly_visit','reference_type'=>'hourly_visit',
            'reference_id'=>$visit->id,'out_at'=>'2026-09-18 08:00','status'=>'out','created_at'=>now(),'updated_at'=>now()]);
        $this->assertNotNull(\App\Services\Hourly\OverdueWorkerReturn::reason(1));
        DB::table('worker_daily_movements')->where('id',$movement)->update(['in_at'=>now(),'status'=>'returned']);
        $this->assertNull(\App\Services\Hourly\OverdueWorkerReturn::reason(1));
        // Emergency return may leave execution/finance pending; it is still proof of physical return.
        $visit->workers()->attach(1);$visit->update(['status'=>'picked_up']);
        $this->assertNull(\App\Services\Hourly\OverdueWorkerReturn::reason(1));
        $this->assertSame('picked_up',$visit->fresh()->status);
        $this->assertEquals($order->paid_amount,$order->fresh()->paid_amount);
    }
    public function test_driver_and_worker_web_portals_can_submit_incidents_for_assigned_visits(): void
    {
        (require base_path('database/migrations/2026_10_18_000003_create_hourly_service_incidents.php'))->up();
        $driver=$this->driver();$visit=$this->assign($this->order()->visits()->first(),$driver);
        $driver->refresh();$this->actingAs($driver,'hourly_driver')->withSession(['hourly_driver_version'=>$driver->auth_version]);
        $this->get(route('hourly-driver.home'))->assertOk()->assertSee('الإبلاغ عن تعذر تنفيذ العاملة');
        $body=['request_token'=>(string)Str::uuid(),'maid_id'=>1,'category'=>'illness','note'=>'Private driver report'];
        $this->post(route('hourly-driver.visits.incidents',$visit->id),$body)->assertRedirect()->assertSessionHasNoErrors();
        $maid=\App\Models\Hourly\HourlyMaidAccount::findOrFail(1);
        $this->actingAs($maid,'hourly_maid')->withSession(['hourly_maid_version'=>$maid->hourly_auth_version]);
        $this->get(route('hourly-maid.home'))->assertOk()->assertSee('الإبلاغ عن ظرف يمنع تنفيذ زيارة');
        $this->get(route('hourly-maid.home'))->assertOk()->assertSee('بلاغ قائم #')->assertDontSee('إرسال البلاغ للتحقق');
        $this->post(route('hourly-maid.visits.incidents',$visit->id),array_replace($body,[
            'request_token'=>(string)Str::uuid(),'note'=>'Duplicate worker report']))->assertRedirect()->assertSessionHasErrors('hourly');
        $this->assertDatabaseCount('hourly_service_incidents',1);
        DB::table('hourly_service_incidents')->update(['status'=>'rejected']);
        session()->forget('errors');
        $this->post(route('hourly-maid.visits.incidents',$visit->id),array_replace($body,[
            'request_token'=>(string)Str::uuid(),'note'=>'Private worker report']))->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('hourly_service_incidents',2);$this->assertDatabaseCount('hourly_worker_leaves',0);
        $this->assertSame('scheduled',$visit->fresh()->status);
    }
    public function test_incident_for_a_later_day_does_not_disrupt_visits_before_it(): void
    {
        (require base_path('database/migrations/2026_10_18_000003_create_hourly_service_incidents.php'))->up();
        (require base_path('database/migrations/2026_10_22_000001_add_unavailable_from_to_hourly_service_incidents.php'))->up();
        $today=$this->order();$later=$this->order(1,'2026-09-25 08:00');$visit=$later->visits()->first();
        $this->mock(\App\Services\Hourly\CustomerTrackingPushService::class,function($mock){$mock->shouldReceive('notify')->once();});
        $report=app(\App\Services\Hourly\ServiceIncident::class)->report($visit->id,1,'worker',1,
            ['request_token'=>(string)Str::uuid(),'category'=>'illness','note'=>'Only on the 25th']);
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $url=route('admin.hourly-incidents.review',$report['id']);
        $this->post($url,['decision'=>'approve','verified'=>true,'unavailable_from'=>'2026-09-26T08:00',
            'unavailable_until'=>'2026-09-26T23:00','reason'=>'Outside the reported visit'])->assertSessionHasErrors('hourly');
        session()->forget('errors');
        $this->post($url,['decision'=>'approve','verified'=>true,'unavailable_until'=>'2026-09-25T23:00','reason'=>'Verified'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertNull(\App\Services\Hourly\ServiceIncident::state($today->fresh(),$today->visits()->first()->id));
        $this->assertSame('awaiting_admin',\App\Services\Hourly\ServiceIncident::state($later->fresh(),$visit->id)['status']);
        $this->assertSame('2026-09-25 08:00:00',(string)DB::table('hourly_worker_leaves')->value('starts_at'));
        $this->assertSame('2026-09-25 08:00:00',(string)DB::table('hourly_service_incidents')->where('id',$report['id'])->value('unavailable_from'));
    }
    public function test_staff_incident_reports_enforce_assignment_and_approval_covers_all_overlapping_visits(): void
    {
        (require base_path('database/migrations/2026_10_18_000003_create_hourly_service_incidents.php'))->up();
        $driver=$this->driver();$order=$this->order();$visit=$this->assign($order->visits()->first(),$driver);
        $later=$this->order(1,'2026-09-23 08:00');
        $this->mock(\App\Services\Hourly\CustomerTrackingPushService::class,function($mock){$mock->shouldReceive('notify')->twice();});
        $token=$this->loginDriver($driver);$body=['request_token'=>(string)Str::uuid(),'maid_id'=>1,'category'=>'illness','note'=>'PRIVATE STAFF DETAIL'];
        $url='/api/driver/visits/'.$visit->id.'/incidents';
        $first=$this->withToken($token)->postJson($url,$body)->assertOk()->assertJsonPath('data.status','pending')->json('data.id');auth()->forgetGuards();
        $this->withToken($token)->postJson($url,$body)->assertOk()->assertJsonPath('data.id',$first);auth()->forgetGuards();
        $this->assertDatabaseCount('hourly_service_incidents',1);$this->assertDatabaseCount('hourly_worker_leaves',0);
        $reports=$this->withToken($token)->getJson('/api/driver/service-incidents')->assertOk()->assertJsonCount(1,'data')->json('data');$this->assertArrayNotHasKey('note',$reports[0]);auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/driver/visits/'.$later->visits()->first()->id.'/incidents',array_replace($body,['request_token'=>(string)Str::uuid()]))->assertNotFound();auth()->forgetGuards();
        DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->update(['id'=>100]);
        $this->withToken($token)->postJson($url,array_replace($body,['request_token'=>(string)Str::uuid()]))->assertUnprocessable();auth()->forgetGuards();
        $workerToken=$this->loginWorker();
        $this->withToken($workerToken)->postJson('/api/worker/visits/100/incidents',array_replace($body,['request_token'=>(string)Str::uuid()]))->assertUnprocessable();auth()->forgetGuards();
        $this->assertDatabaseCount('hourly_service_incidents',1);
        DB::table('hourly_service_incidents')->where('id',$first)->update(['status'=>'rejected']);
        $this->withToken($workerToken)->postJson('/api/worker/visits/100/incidents',array_replace($body,['request_token'=>(string)Str::uuid(),'maid_id'=>999]))
            ->assertOk()->assertJsonPath('data.status','pending');auth()->forgetGuards();
        $this->assertDatabaseHas('hourly_service_incidents',['reporter_type'=>'worker','reporter_id'=>1,'maid_id'=>1,'visit_id'=>$visit->id]);
        $reports=$this->withToken($workerToken)->getJson('/api/worker/service-incidents')->assertOk()->assertJsonCount(1,'data')->json('data');$this->assertArrayNotHasKey('review_reason',$reports[0]);auth()->forgetGuards();
        $this->withToken($workerToken)->postJson('/api/worker/visits/9999/incidents',array_replace($body,['request_token'=>(string)Str::uuid()]))->assertNotFound();auth()->forgetGuards();
        $first=(int)DB::table('hourly_service_incidents')->where('reporter_type','worker')->value('id');
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $this->post(route('admin.hourly-incidents.review',$first),['decision'=>'approve','verified'=>true,'unavailable_until'=>'2026-09-24T23:00','reason'=>'Verified private reason'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('hourly_worker_leaves',1);
        $this->assertNotNull(\App\Services\Hourly\ServiceIncident::openReport($visit->id,1));
        try {
            app(\App\Services\Hourly\ServiceIncident::class)->report($visit->id,1,'driver',$driver->id,array_replace($body,['request_token'=>(string)Str::uuid()]));
            $this->fail('An approved unresolved report must block a new report.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('hourly',$e->errors());
        }
        $this->assertDatabaseCount('hourly_service_incidents',2);
        foreach([$order,$later] as $affected){
            $state=\App\Services\Hourly\ServiceIncident::state($affected->fresh(),$affected->visits()->first()->id);
            $this->assertSame('awaiting_admin',$state['status']);$this->assertSame([1],$state['failed_worker_ids']);
        }
    }
    public function test_today_keeps_old_running_delivery_and_pickup_with_stable_ids_until_return(): void
    {
        $driver=$this->driver();$visit=$this->assign($this->order()->visits()->first(),$driver);
        $board=app(\App\Services\Hourly\DriverAppService::class);
        $original=$board->sync($driver,'2026-09-22');
        $ids=collect($original)->pluck('id')->all();$stopIds=collect($original)->pluck('stops.0.id')->all();
        $visit->update(['status'=>'delivered']);Carbon::setTestNow('2026-09-25 08:00:00');
        $token=$this->loginDriver($driver);
        $trips=$this->withToken($token)->getJson('/api/driver/trips/today')->assertOk()->assertJsonCount(2,'data')->json('data');
        $this->assertSame($ids,collect($trips)->pluck('id')->all());
        $this->assertSame($stopIds,collect($trips)->pluck('stops.0.id')->all());
        foreach($trips as $trip){$this->assertTrue($trip['is_carryover']);$this->assertSame('2026-09-22',$trip['route_date']);$this->assertTrue($trip['stops'][0]['is_overdue']);$this->assertNotEmpty($trip['stops'][0]['warning']);}
        $this->assertSame('completed',$trips[0]['stops'][0]['status']);
        $this->assertFalse($trips[0]['stops'][0]['actionable']);
        $this->assertSame('2026-09-22 08:00',$visit->fresh()->scheduled_start_at->format('Y-m-d H:i'));
        $history=$board->history($driver,'2026-09-22','2026-09-25');
        $this->assertCount(2,$history['trips']);
        $visit->order->update(['status'=>'expired']);
        $held=$board->today($driver);$this->assertCount(2,$held);
        $this->assertFalse($held[1]['stops'][0]['actionable']);
        $visit->order->update(['status'=>'active']);
        $visit->update(['status'=>'worker_finished']);
        $this->assertSame($visit->id,$board->pickupRequests($driver)['requests'][0]['visit_id']);
        $actions=app(\App\Services\Hourly\DriverStopActions::class);
        $actions->pickUp($driver,$stopIds[1],[]);
        $this->assertSame($visit->id,$board->onboard($driver)->first()['visit_id']);
        $this->assertCount(2,$board->today($driver));
        $actions->shelterReturn($driver,$stopIds[1],[]);
        $this->assertSame([],$board->today($driver));
        $this->assertSame('completed',$visit->fresh()->status);
    }

    public function test_carryover_is_scoped_to_each_assigned_role_and_never_includes_unstarted_old_visits(): void
    {
        $driver=$this->driver();$other=$this->driver(['employee_number'=>'D-2','username'=>'HD-2','login_phone'=>'0512345679']);
        $visit=$this->assign($this->order()->visits()->first(),$driver);
        $visit->update(['pickup_driver_id'=>$other->id]);Carbon::setTestNow('2026-09-25 08:00:00');
        $board=app(\App\Services\Hourly\DriverAppService::class);
        $this->assertSame([],$board->today($driver));$this->assertSame([],$board->today($other));
        $visit->update(['status'=>'en_route']);
        $delivery=$board->today($driver);$pickup=$board->today($other);
        $this->assertCount(1,$delivery);$this->assertSame('delivery',$delivery[0]['type']);
        $this->assertCount(1,$pickup);$this->assertSame('pickup',$pickup[0]['type']);
        $this->assertSame('drop_off',$delivery[0]['stops'][0]['action']);
        $this->assertDatabaseHas('hourly_route_stops',['id'=>$pickup[0]['stops'][0]['id'],'hourly_driver_id'=>$other->id]);
    }
    public function test_delivery_rechecks_worker_days_off_and_approved_leave_before_any_departure(): void
    {
        if(!Schema::hasTable('worker_daily_movements'))(require base_path('database/migrations/2026_09_28_000001_create_worker_daily_movements_table.php'))->up();
        $driver=$this->driver();$visit=$this->assign($this->order()->visits()->first(),$driver);
        $board=app(\App\Services\Hourly\DriverAppService::class);$actions=app(\App\Services\Hourly\DriverStopActions::class);
        Carbon::setTestNow('2026-09-22 08:00:00');
        $worker=\App\Models\ingaz\Maid::findOrFail(1);$settings=$worker->hourly_settings;
        $off=$settings;$off['work_schedule']['tuesday']=['mode'=>'off'];$worker->update(['hourly_settings'=>$off]);
        foreach(['weekly','approved_leave'] as $case){
            if($case==='approved_leave'){
                $worker->update(['hourly_settings'=>$settings]);
                DB::table('hourly_worker_leaves')->insert(['maid_id'=>1,'starts_at'=>'2026-09-22 00:00:00','ends_at'=>'2026-09-23 00:00:00','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
            }
            $trip=collect($board->sync($driver,'2026-09-22'))->firstWhere('type','delivery');
            $this->assertFalse($trip['can_start']);$this->assertFalse($trip['stops'][0]['actionable']);
            $this->assertStringContainsString('إجازة',$trip['stops'][0]['blocked_reason']);
            foreach([fn()=>$board->start($driver,$trip['id']),fn()=>$actions->shelterPickup($driver,$trip['stops'][0]['id'],[]),
                fn()=>\App\Services\Hourly\DeliveryStartWindow::assertAllowed($visit)] as $attempt){
                try{$attempt();$this->fail('Worker leave must block departure');}
                catch(\Illuminate\Validation\ValidationException $e){$this->assertStringContainsString('إجازة',$e->errors()['hourly'][0]);}
            }
            $this->assertSame('scheduled',$visit->fresh()->status);
            $this->assertDatabaseCount('worker_daily_movements',0);
            $this->assertDatabaseMissing('hourly_order_audits',['visit_id'=>$visit->id,'action'=>'driver_shelter_pickup']);
        }
        DB::table('hourly_worker_leaves')->where('maid_id',1)->update(['is_active'=>false]);
        $trip=collect($board->sync($driver,'2026-09-22'))->firstWhere('type','delivery');
        $this->assertTrue($trip['can_start']);
        $actions->shelterPickup($driver,$trip['stops'][0]['id'],[]);
        $this->assertSame('en_route',$visit->fresh()->status);
        $this->assertDatabaseHas('worker_daily_movements',['maid_id'=>1,'reference_id'=>$visit->id,'status'=>'out']);
    }
    public function test_delivery_start_window_blocks_old_future_and_out_of_time_stops_but_allows_return(): void
    {
        $driver = $this->driver();
        $visit = $this->assign($this->order()->visits()->first(), $driver);
        $board = app(\App\Services\Hourly\DriverAppService::class);
        $actions = app(\App\Services\Hourly\DriverStopActions::class);
        foreach (['2026-09-21 08:00:00', '2026-09-22 07:59:59',
            $visit->scheduled_end_at->toDateTimeString(), '2026-09-24 08:00:00'] as $at) {
            Carbon::setTestNow($at);
            $trip = collect($board->sync($driver, '2026-09-22'))->firstWhere('type','delivery');
            $this->assertNull($trip['stops'][0]['action']);
            $this->assertFalse($trip['actionable']);
            $this->assertFalse($trip['can_start']);
            $this->assertNotNull($trip['stops'][0]['blocked_reason']);
            foreach ([fn()=>$board->start($driver, $trip['id']),
                fn()=>$actions->shelterPickup($driver, $trip['stops'][0]['id'], [])] as $attempt) {
                try { $attempt(); $this->fail('Out-of-window delivery must be rejected.'); }
                catch (\Illuminate\Validation\ValidationException $e) {
                    $this->assertStringContainsString('بدء التوصيل', $e->errors()['hourly'][0]);
                }
            }
            $this->assertSame('scheduled', $visit->fresh()->status);
            $this->assertDatabaseHas('hourly_driver_trips',['id'=>$trip['id'],'status'=>'accepted']);
        }
        Carbon::setTestNow('2026-09-22 08:00:00');
        $trip = collect($board->sync($driver, '2026-09-22'))->firstWhere('type','delivery');
        $this->assertSame('shelter_pickup', $trip['stops'][0]['action']);
        $this->assertTrue($trip['can_start']);
        $actions->shelterPickup($driver, $trip['stops'][0]['id'], []);
        Carbon::setTestNow('2026-09-24 08:00:00');
        $trip = collect($board->sync($driver, '2026-09-22'))->firstWhere('type','delivery');
        $this->assertSame('drop_off', $trip['stops'][0]['action']);
        $visit->update(['status'=>'worker_finished']);
        $trip = collect($board->sync($driver, '2026-09-22'))->firstWhere('type','pickup');
        $this->assertSame('pick_up', $trip['stops'][0]['action']);
        $actions->pickUp($driver, $trip['stops'][0]['id'], []);
        $actions->shelterReturn($driver, $trip['stops'][0]['id'], []);
        $this->assertSame('completed', $visit->fresh()->status);
    }
    public function test_partial_worker_arrival_locks_cancellation_and_schedule_and_assigns_cash(): void
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>false]);
        $other=$this->order(2,'2026-09-22 08:00',['requires_driver'=>false]);
        $other->visits()->first()->workers()->detach(2);
        $visit=$order->visits()->first();
        $visit->workers()->attach(2);
        $visit->update(['workers_count'=>2,'cash_collector_type'=>'driver']);
        $service=app(\App\Services\Hourly\WorkerAppService::class);
        $first=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->where('maid_id',1)->first();
        $service->arrived(\App\Models\ingaz\Maid::findOrFail(1),$first->id,[]);
        $visit->refresh();
        $this->assertSame('scheduled',$visit->status);
        $this->assertTrue($visit->has_started_execution);
        $this->assertSame('in_progress',$visit->display_status);
        $manager=app(\App\Services\Hourly\OrderManagementService::class);
        foreach(['cancel_visit','cancel_order','reschedule','start_time','duration'] as $action) {
            try {
                $manager->apply($order,['action'=>$action,'visit_ids'=>[$visit->id],'reason'=>'Started visit guard',
                    'shift_days'=>1,'start_time'=>'09:00','hours'=>5,'refund_method'=>'none'],7);
                $this->fail('Started visit accepted '.$action);
            } catch(\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('hourly',$e->errors()); }
        }
        try {
            app(\App\Services\Hourly\HourlyOrderService::class)->reschedule($visit,'2026-09-23 08:00',7);
            $this->fail('Direct reschedule accepted partial arrival.');
        } catch(\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('hourly',$e->errors()); }
        $customer=app(\App\Services\Hourly\CustomerVisitActions::class);
        $this->assertFalse($customer->policy($visit,$order)['can_cancel']);
        try { $customer->cancel($visit); $this->fail('Customer cancelled a started visit.'); }
        catch(\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('hourly',$e->errors()); }
        $this->assertSame('2026-09-22 08:00:00',$visit->fresh()->scheduled_start_at->toDateTimeString());
        $this->assertSame('active',$order->fresh()->status);
        $cash=app(\App\Services\Hourly\CashCollectionService::class);
        $this->assertSame(['type'=>'worker','id'=>1,'label'=>'العاملة'],$cash->collector($visit));
        $this->assertTrue($cash->info($visit,'worker',1)['can_collect']);
        $this->assertFalse($cash->info($visit,'worker',2)['can_collect']);
        try { $cash->collect($visit,'driver',1,10,(string)Str::uuid()); $this->fail('Driver collected self-arrival cash.'); }
        catch(\Illuminate\Validation\ValidationException $e) { $this->assertArrayHasKey('hourly',$e->errors()); }
        $collection=$cash->collect($visit,'worker',1,10,(string)Str::uuid());
        $this->assertDatabaseHas('hourly_cash_collections',['id'=>$collection['collection_id'],'holder_type'=>'worker','holder_id'=>1]);
        $second=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->where('maid_id',2)->first();
        $service->arrived(\App\Models\ingaz\Maid::findOrFail(2),$second->id,[]);
        $this->assertSame('delivered',$visit->fresh()->status);
        $this->assertSame(1,$cash->collector($visit->fresh())['id']);
        $this->assertDatabaseHas('hourly_cash_collections',['id'=>$collection['collection_id'],'holder_id'=>1,'amount'=>10]);
    }

    public function test_demo_single_worker_finish_http_flow(): void
    {
        $this->runDemoFinishHttpFlow(1);
    }

    public function test_demo_two_workers_finish_http_flow(): void
    {
        $this->runDemoFinishHttpFlow(2);
    }

    private function runDemoFinishHttpFlow(int $count): void
    {
        app('view')->getFinder()->prependLocation(base_path('tests/Fixtures/hourly-web-views'));
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class,\App\Http\Middleware\EnforceRoutePermission::class,\App\Http\Middleware\EnforceCircularResponse::class]);
        HourlyRentalSettings::persist(['show_on_website'=>true] + HourlyRentalSettings::all());
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>false]);
        $visit=$order->visits()->first();
        if ($count===2) {
            $extra=$this->order(2,'2026-09-22 08:00',['requires_driver'=>false]);
            $extra->visits()->first()->workers()->detach(2);
            $visit->workers()->attach(2);
            $visit->update(['workers_count'=>2]);
        }
        $driver=$this->driver();
        $visit->update(['pickup_driver_id'=>$driver->id]);
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id=7;$admin->name='Demo supervisor';$admin->shouldReceive('can')->andReturn(true);
        auth('admin')->setUser($admin);
        $user=\App\Models\ingaz\User::findOrFail(1);
        auth('web')->setUser($user);auth('api')->setUser($user);
        $account=\App\Models\Hourly\HourlyMaidAccount::findOrFail(1);
        $this->actingAs($account,'hourly_maid')->withSession(['hourly_maid_version'=>$account->hourly_auth_version]);
        $first=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->where('maid_id',1)->first();
        $workerUrl=route('hourly-maid.visits.action',$first->id);
        Carbon::setTestNow('2026-09-22 07:30:00'); // self-arrival opens 30 minutes before the 08:00 visit
        $this->postJson($workerUrl,['action'=>'arrived','delivery_code'=>$visit->fresh()->delivery_code])->assertRedirect();
        $this->assertNotNull(DB::table('hourly_visit_workers')->where('id',$first->id)->value('arrived_at'));
        $data=app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit->fresh(),$order->fresh());
        $this->assertSame('in_progress',$data['display_status']);
        $this->assertFalse($data['can_reschedule']);
        $this->assertTrue(app(\App\Services\Hourly\CashCollectionService::class)->info($visit->fresh(),'worker',1)['can_collect']);
        $this->postJson(route('hourly-maid.cash.collect',$first->id),['amount'=>10,'request_token'=>(string)Str::uuid()])->assertRedirect();
        $this->assertDatabaseHas('hourly_cash_collections',['visit_id'=>$visit->id,'holder_type'=>'worker','holder_id'=>1,'amount'=>10]);
        $this->postJson($workerUrl,['action'=>'finish','early_reason'=>'other','note'=>'Demo worker finished'])->assertRedirect();
        $this->assertDatabaseHas('hourly_visit_workers',['id'=>$first->id,'status'=>'finished']);
        $this->assertTrue($visit->fresh()->has_started_execution);
        $data=app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit->fresh(),$order->fresh());
        $this->assertFalse($data['can_reschedule']);
        $this->assertFalse($data['can_cancel']);
        $this->assertSame($count===1?'worker_finished':'scheduled',$visit->fresh()->status);
        $this->assertSame($count===1?'completed':'in_progress',$data['display_status']);
        $this->get(route('web.hourly.order',$order->id))->assertOk()->assertDontSee('data-visit-action="reschedule"',false);
        $this->get(route('hourly-maid.home'))->assertOk()->assertDontSee('value="finish"',false);
        if(!Schema::hasTable('hourly_worker_reviews')) Schema::create('hourly_worker_reviews',function(Blueprint $t){
            $t->id();$t->unsignedBigInteger('hourly_rental_order_id');$t->unsignedBigInteger('visit_id');$t->unsignedBigInteger('maid_id');$t->integer('rating');$t->text('comment')->nullable();$t->timestamps();
        });
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertDontSee('data-order-action="cancel_visit"',false);
        foreach(['cancel_visit','reschedule','start_time'] as $action) {
            $this->postJson(route('admin.hourly-orders.operations',$order),['action'=>$action,'visit_ids'=>[$visit->id],
                'reason'=>'Demo stale dashboard request','shift_days'=>1,'start_time'=>'09:00','refund_method'=>'none'])
                ->assertStatus(422)->assertJsonValidationErrors('hourly');
        }
        $this->putJson(route('admin.hourly-orders.reschedule',[$order,$visit->id]),['starts_at'=>'2026-09-23T08:00'])
            ->assertStatus(422)->assertJsonValidationErrors('hourly');
        $rescheduleRoute=\Illuminate\Support\Facades\Route::has('web.hourly.visit.reschedule')?'web.hourly.visit.reschedule':'api.client.hourly.visits.reschedule';
        $cancelRoute=\Illuminate\Support\Facades\Route::has('web.hourly.visit.cancel')?'web.hourly.visit.cancel':'api.client.hourly.visits.cancel';
        $this->postJson(route($rescheduleRoute,[$order->id,$visit->id]),['scheduled_start'=>'2026-09-23 08:00'])
            ->assertStatus(422)->assertJsonValidationErrors('hourly');
        $this->postJson(route($cancelRoute,[$order->id,$visit->id]))->assertStatus(422)->assertJsonValidationErrors('hourly');
        $this->assertSame('2026-09-22 08:00:00',$visit->fresh()->scheduled_start_at->toDateTimeString());
        if ($count===2) {
            $second=DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->where('maid_id',2)->first();
            $secondAccount=\App\Models\Hourly\HourlyMaidAccount::findOrFail(2);
            $this->actingAs($secondAccount,'hourly_maid')->withSession(['hourly_maid_version'=>$secondAccount->hourly_auth_version]);
            $secondUrl=route('hourly-maid.visits.action',$second->id);
            $this->postJson($secondUrl,['action'=>'arrived'])->assertRedirect();
            $this->postJson($secondUrl,['action'=>'finish','early_reason'=>'other','note'=>'Demo second worker finished'])->assertRedirect();
            $this->assertSame('worker_finished',$visit->fresh()->status);
            $data=app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit->fresh(),$order->fresh());
            $this->assertSame('completed',$data['display_status']);
            $this->assertFalse($data['can_reschedule']);
        }
        $this->assertSame('active',$order->fresh()->status);
        $this->assertSame(1,app(\App\Services\Hourly\CashCollectionService::class)->collector($visit->fresh())['id']);
    }

    public function test_worker_cash_is_paid_once_and_partial_remittances_only_settle_custody(): void
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>false]);
        $order->update(['total_amount'=>200]);$visit=$order->visits()->first();
        $cash=app(\App\Services\Hourly\CashCollectionService::class);
        $this->assertSame('worker',$cash->collector($visit)['type']);
        $workerVisit=(int)DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->value('id');
        $token=$this->loginWorker();$body=['amount'=>80,'request_token'=>(string)Str::uuid()];
        $this->withToken($token)->postJson("/api/worker/visits/$workerVisit/collect-cash",$body)->assertStatus(422);auth()->forgetGuards();
        Carbon::setTestNow('2026-09-22 07:30:00'); // self-arrival opens 30 minutes before the 08:00 visit
        $this->withToken($token)->postJson("/api/worker/visits/$workerVisit/arrived",['otp_code'=>$visit->fresh()->delivery_code])->assertOk();auth()->forgetGuards();
        $first=$this->withToken($token)->postJson("/api/worker/visits/$workerVisit/collect-cash",$body)->assertOk()->assertJsonPath('data.replayed',false)->json('data');auth()->forgetGuards();
        $this->withToken($token)->postJson("/api/worker/visits/$workerVisit/collect-cash",$body)->assertOk()->assertJsonPath('data.replayed',true);auth()->forgetGuards();
        $this->withToken($token)->postJson("/api/worker/visits/$workerVisit/collect-cash",['amount'=>121,'request_token'=>(string)Str::uuid()])->assertStatus(422);auth()->forgetGuards();
        $second=$this->withToken($token)->postJson("/api/worker/visits/$workerVisit/collect-cash",['amount'=>120,'request_token'=>(string)Str::uuid()])->assertOk()->json('data');auth()->forgetGuards();
        $this->assertSame(200.0,(float)$order->fresh()->paid_amount);$this->assertSame(2,$order->payments()->count());
        $this->assertDatabaseHas('hourly_order_payments',['id'=>$first['payment_id'],'collected_by_type'=>'maid','collected_by_id'=>1]);
        $this->withToken($token)->getJson('/api/worker/cash-custody')->assertOk()->assertJsonPath('data.outstanding',200);auth()->forgetGuards();
        $remitToken=(string)Str::uuid();$cash->remit($first['collection_id'],30,7,'Till-1',$remitToken);
        $this->assertTrue($cash->remit($first['collection_id'],30,7,'Till-1',$remitToken)['replayed']);
        $cash->remit($first['collection_id'],50,7,'Till-2',(string)Str::uuid());
        $this->assertSame(120.0,$cash->custody('worker',1)['outstanding']);
        $cash->remit($second['collection_id'],120,7,'Till-3',(string)Str::uuid());
        $this->assertSame(0.0,$cash->custody('worker',1)['outstanding']);
        $this->assertSame(2,$order->payments()->count());$this->assertSame(200.0,(float)$order->fresh()->paid_amount);
        $this->assertSame(3,DB::table('hourly_cash_remittances')->count());
    }

    public function test_cash_role_and_method_are_enforced_and_unpaid_completion_stays_unpaid(): void
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>false]);
        $visit=$order->visits()->first();$visit->update(['status'=>'delivered']);
        $cash=app(\App\Services\Hourly\CashCollectionService::class);
        foreach ([['cash_collector_type'=>'admin'],['cash_collector_type'=>'worker','cash_collector_worker_id'=>999]] as $policy) {
            $visit->update($policy);
            try {$cash->collect($visit,'worker',1,10,(string)Str::uuid());$this->fail('Wrong collector accepted');}
            catch(\Illuminate\Validation\ValidationException $e){$this->assertNotEmpty($e->errors());}
        }
        $visit->update(['cash_collector_type'=>'worker','cash_collector_worker_id'=>1,'status'=>'en_route']);
        $this->assertFalse($cash->info($visit->fresh(),'worker',1)['can_collect']);
        try {$cash->collect($visit,'worker',1,10,(string)Str::uuid());$this->fail('Worker collected before arrival');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertNotEmpty($e->errors());}
        $visit->update(['status'=>'delivered']);
        $visit->update(['cash_collector_type'=>'auto','cash_collector_worker_id'=>null]);$order->update(['payment_method'=>'moyasar']);
        try {$cash->collect($visit,'worker',1,10,(string)Str::uuid());$this->fail('Card order accepted cash');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertNotEmpty($e->errors());}
        $order->update(['payment_method'=>'cash']);$visit->update(['status'=>'scheduled']);
        $token=$this->loginWorker();$workerVisit=(int)DB::table('hourly_visit_workers')->where('hourly_visit_id',$visit->id)->value('id');
        $this->withToken($token)->postJson("/api/worker/visits/$workerVisit/arrived")->assertOk();auth()->forgetGuards();
        $this->withToken($token)->postJson("/api/worker/visits/$workerVisit/finish")->assertOk();auth()->forgetGuards();
        $this->assertSame('completed',$order->fresh()->status);$this->assertSame(0.0,(float)$order->fresh()->paid_amount);
        $this->assertSame(0,DB::table('hourly_cash_collections')->count());
    }

    public function test_refund_keeps_physical_cash_in_staff_custody_and_rejects_excess_remittance(): void
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>false]);
        $order->update(['total_amount'=>200]);$visit=$order->visits()->first();$visit->update(['status'=>'delivered']);
        $cash=app(\App\Services\Hourly\CashCollectionService::class);$row=$cash->collect($visit,'worker',1,200,(string)Str::uuid());
        $order->refresh()->update(['status'=>'cancelled','total_amount'=>0]);
        $this->assertSame(200.0,app(\App\Services\Hourly\OrderRefundService::class)->refund($order,'wallet','Cancellation',7));
        $this->assertSame(200.0,$cash->custody('worker',1)['outstanding']);$this->assertSame(0.0,(float)$order->fresh()->paid_amount);
        try {$cash->remit($row['collection_id'],201,7,'Too much',(string)Str::uuid());$this->fail('Excess remittance accepted');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertNotEmpty($e->errors());}
        $this->assertSame(0,DB::table('hourly_cash_remittances')->count());$this->assertNotEmpty(HourlyRentalSettings::disableBlockers());
    }
    public function test_driver_cash_has_custody_and_receipts_are_scoped_to_the_customer_and_staff(): void
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $driver=$this->driver();$order=$this->order();$visit=$this->assign($order->visits()->first(),$driver);
        $visit->update(['status'=>'delivered']);
        $token=$this->loginDriver($driver);
        $body=['amount'=>50,'request_token'=>(string)Str::uuid()];
        $result=$this->withToken($token)->postJson('/api/driver/visits/'.$visit->id.'/collect-cash',$body)->assertOk()->json('data');
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/driver/cash-custody')->assertOk()->assertJsonPath('data.outstanding',50);
        auth()->forgetGuards();
        $cash=app(\App\Services\Hourly\CashCollectionService::class);
        $this->assertSame('driver',$cash->collector($visit)['type']);
        $controller=app(\App\Http\Controllers\Hourly\CashCollectionController::class);
        auth('api')->setUser(\App\Models\ingaz\User::findOrFail(1));
        $this->assertStringContainsString('50.00',$controller->customerReceipt($result['collection_id'])->render());
        auth()->forgetGuards();
        DB::table('users')->insert(['id'=>2,'name'=>'Other','phone'=>'0500000099']);
        auth('api')->setUser(\App\Models\ingaz\User::findOrFail(2));
        try {$controller->customerReceipt($result['collection_id']);$this->fail('Another customer accessed receipt');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(404,$e->getStatusCode());}
        auth()->forgetGuards();
        auth('hourly_driver')->setUser($driver);
        $this->assertStringContainsString('50.00',$controller->driverReceipt($result['collection_id'])->render());
        view()->share('errors',new \Illuminate\Support\ViewErrorBag());
        $driverPage=app(\App\Http\Controllers\Hourly\DriverPortalController::class)->index(new \Illuminate\Http\Request(['date'=>'2026-09-22']));
        $this->assertStringContainsString('تحصيل المتبقي نقدًا',$driverPage->render());
        auth()->forgetGuards();
        $worker=\App\Models\ingaz\Maid::findOrFail(1);auth('hourly_maid')->setUser(\App\Models\Hourly\HourlyMaidAccount::findOrFail(1));
        try {$controller->staffReceipt($result['collection_id']);$this->fail('Worker accessed driver custody receipt');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(404,$e->getStatusCode());}
        $this->assertStringContainsString('مسؤول التحصيل',view('hourly_maid._cash-visits',['maid'=>$worker,'errors'=>new \Illuminate\Support\ViewErrorBag()])->render());
        $workerPage=app(\App\Http\Controllers\Hourly\MaidAuthController::class)->home(new \Illuminate\Http\Request(['date'=>'2026-09-22']));
        $this->assertStringContainsString('سندات التحصيل والعهدة',$workerPage->render());
    }
    public function test_admin_collector_policy_and_remittance_require_permission_and_actual_receipt(): void
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>false]);$visit=$order->visits()->first();
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $url=route('admin.hourly-cash.collector',[$order,$visit]);
        $this->postJson($url,['type'=>'worker','worker_id'=>999,'reason'=>'Wrong'])->assertStatus(422);
        $this->post($url,['type'=>'worker','worker_id'=>1,'reason'=>'Responsible worker'])->assertRedirect();
        $this->assertDatabaseHas('hourly_visits',['id'=>$visit->id,'cash_collector_type'=>'worker','cash_collector_worker_id'=>1]);
        $visit->refresh()->update(['status'=>'delivered']);
        $cash=app(\App\Services\Hourly\CashCollectionService::class);$entry=$cash->collect($visit,'worker',1,50,(string)Str::uuid());
        $this->postJson($url,['type'=>'admin','reason'=>'Too late'])->assertStatus(422);
        $remitUrl=route('admin.hourly-cash.remit',$entry['collection_id']);
        $body=['amount'=>20,'reference'=>'Cash desk','request_token'=>(string)Str::uuid()];
        $this->postJson($remitUrl,$body)->assertStatus(422);
        $this->post($remitUrl,$body+['confirmed'=>1])->assertRedirect();
        $this->get(route('admin.hourly-cash.index'))->assertOk()->assertSee('30.00');
        $id=DB::table('hourly_cash_remittances')->value('id');
        $this->get(route('admin.hourly-cash.remittance-receipt',$id))->assertOk()->assertSee('20.00');
        $this->assertSame(1,$order->payments()->count());
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(false);auth('admin')->setUser($admin);
        $this->postJson($remitUrl,$body+['confirmed'=>1])->assertForbidden();
        $this->get(route('admin.hourly-cash.index'))->assertForbidden();
    }
    private function paymentPlanOrder(string $policy): array
    {
        (require base_path('database/migrations/2026_10_14_000002_add_hourly_cash_custody.php'))->up();
        Carbon::setTestNow('2026-09-22 08:00:00');
        $order=$this->order(1,'2026-09-22 08:00',['requires_driver'=>false]);
        $order->update(['total_amount'=>800,'visits_total'=>4,'package_snapshot'=>array_merge($order->package_snapshot,[
            'payment_policy'=>$policy,'charge_now'=>$policy==='prepaid'?800:200,'deposit_amount'=>100,'monthly_amount'=>250])]);
        $first=$order->visits()->first();$first->update(['visit_price'=>200,'status'=>'delivered']);
        for($i=2;$i<=4;$i++){
            $v=$first->replicate();$v->visit_number=$i;$v->status='scheduled';
            $v->scheduled_start_at=$first->scheduled_start_at->copy()->addDays($i-1);$v->scheduled_end_at=$first->scheduled_end_at->copy()->addDays($i-1);$v->save();
            $v->workers()->attach(1);
        }
        return [$order->fresh(),$first->fresh()];
    }

    public function test_per_visit_collects_only_current_visit_and_keeps_future_visits_unpaid(): void
    {
        [$order,$visit]=$this->paymentPlanOrder('per_visit');$cash=app(\App\Services\Hourly\CashCollectionService::class);
        $schedule=app(\App\Services\Hourly\PaymentScheduleService::class);
        $this->assertSame(200.0,$schedule->forOrder($order)['due_now']);
        $this->assertSame(200.0,$cash->info($visit,'worker',1)['cash_due']);
        try{$cash->collect($visit,'worker',1,201,(string)Str::uuid());$this->fail('Collected future visits');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertNotEmpty($e->errors());}
        $cash->collect($visit,'worker',1,80,(string)Str::uuid());
        $financial=$schedule->forOrder($order->fresh());
        $this->assertSame('partial',$financial['visits'][$visit->id]['payment_status']);
        $this->assertSame(120.0,$financial['due_now']);
        $cash->collect($visit,'worker',1,120,(string)Str::uuid());
        $financial=$schedule->forOrder($order->fresh());
        $this->assertSame(0.0,$financial['due_now']);$this->assertSame(600.0,$financial['remaining']);
        $this->assertSame('paid',$financial['visits'][$visit->id]['payment_status']);
        $next=$order->visits()->where('visit_number',2)->first();
        $this->assertSame('unpaid',$financial['visits'][$next->id]['payment_status']);
        $this->assertFalse($financial['visits'][$next->id]['is_due']);
        $this->assertSame('partial',$order->fresh()->payment_status);
        Carbon::setTestNow($next->scheduled_start_at);
        $next->update(['status'=>'delivered']);
        $cash->collect($next,'worker',1,200,(string)Str::uuid());
        $this->assertSame(400.0,(float)$order->fresh()->paid_amount);
        $this->assertSame('paid',$schedule->forOrder($order->fresh())['visits'][$next->id]['payment_status']);
        $this->assertSame(400.0,$cash->custody('worker',1)['outstanding']);
        $payment=$order->payments()->latest('id')->first();
        $this->assertSame(20000,$payment->provider_payload['visit_allocations'][$next->id]);
    }

    public function test_completed_unpaid_visit_is_overdue_and_admin_cannot_charge_future_visits(): void
    {
        [$order,$visit]=$this->paymentPlanOrder('per_visit');$visit->update(['status'=>'completed']);
        $schedule=app(\App\Services\Hourly\PaymentScheduleService::class);
        $financial=$schedule->forOrder($order->fresh());
        $this->assertTrue($financial['visits'][$visit->id]['is_overdue']);
        $this->assertSame(200.0,$financial['overdue']);
        $this->assertSame(0,$order->payments()->count());
        try{app(\App\Services\Hourly\HourlyPaymentService::class)->collect($order,['amount'=>800],7);$this->fail('Admin charged all future visits');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertNotEmpty($e->errors());}
        app(\App\Services\Hourly\HourlyPaymentService::class)->collect($order,['amount'=>200],7);
        $this->assertSame(0.0,$schedule->forOrder($order->fresh())['due_now']);
        $this->assertSame('completed',$visit->fresh()->status);
    }

    public function test_installments_generate_exact_total_and_only_matured_amounts_are_collectible(): void
    {
        [$order,$visit]=$this->paymentPlanOrder('installments');
        $schedule=app(\App\Services\Hourly\PaymentScheduleService::class);$schedule->generateInstallments($order);
        $schedule->generateInstallments($order);
        $this->assertSame(4,DB::table('hourly_order_installments')->count());
        $this->assertSame(800.0,(float)DB::table('hourly_order_installments')->sum('amount'));
        $this->assertSame(100.0,$schedule->forOrder($order)['due_now']);
        app(\App\Services\Hourly\HourlyPaymentService::class)->collect($order,['amount'=>100],7);
        $this->assertSame(100.0,(float)DB::table('hourly_order_installments')->sum('paid_amount'));
        $visit->update(['status'=>'completed']);
        $this->assertFalse($schedule->forOrder($order->fresh())['visits'][$visit->id]['is_overdue']);
        $this->assertSame(0.0,$schedule->forOrder($order->fresh())['due_now']);
        Carbon::setTestNow('2026-10-22 08:00:00');
        $this->assertSame(250.0,$schedule->forOrder($order->fresh())['due_now']);
        app(\App\Services\Hourly\HourlyPaymentService::class)->collect($order,['amount'=>50],7);
        $this->assertSame(200.0,$schedule->forOrder($order->fresh())['due_now']);
        $this->assertSame(150.0,(float)DB::table('hourly_order_installments')->sum('paid_amount'));
        Carbon::setTestNow('2026-10-23 08:00:00');
        $this->assertSame(200.0,$schedule->forOrder($order->fresh())['overdue']);
        $order->refresh()->update(['status'=>'cancelled','total_amount'=>0]);
        app(\App\Services\Hourly\OrderRefundService::class)->refund($order,'wallet','Cancelled',7);
        $this->assertSame(0.0,$schedule->forOrder($order->fresh())['due_now']);
        $this->assertSame(0.0,(float)DB::table('hourly_order_installments')->sum('paid_amount'));
    }

    public function test_prepaid_covers_all_visits_and_historical_per_visit_is_inferred_without_rewriting_money(): void
    {
        [$order,$visit]=$this->paymentPlanOrder('prepaid');$schedule=app(\App\Services\Hourly\PaymentScheduleService::class);
        $this->assertSame(800.0,$schedule->forOrder($order)['due_now']);
        $order->update(['status'=>'pending_confirmation']);
        try{app(\App\Services\Hourly\HourlyOrderService::class)->confirm($order,7);$this->fail('Prepaid cash order activated without payment');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertNotEmpty($e->errors());}
        app(\App\Services\Hourly\HourlyPaymentService::class)->collect($order,['amount'=>800],7);
        $financial=$schedule->forOrder($order->fresh());
        foreach($financial['visits'] as $row){$this->assertSame('paid',$row['payment_status']);$this->assertSame(0.0,$row['remaining']);}
        $this->assertSame(0.0,$financial['due_now']);$this->assertSame('paid',$order->fresh()->payment_status);
        $snapshot=$order->package_snapshot;unset($snapshot['payment_policy']);$snapshot['price_display']='per_visit';
        $order->update(['package_snapshot'=>$snapshot]);
        $this->assertSame('per_visit',$schedule->policy($order));
        $this->assertSame(800.0,(float)$order->fresh()->paid_amount);
        $this->assertSame(1,$order->payments()->count());
    }
    public function test_booking_freezes_explicit_policy_and_creates_installments_once(): void
    {
        (require base_path('database/migrations/2026_10_14_000003_add_hourly_package_payment_policy.php'))->up();
        $order=$this->order(1,'2026-09-22 08:00',[],['payment_policy'=>'installments','total_price'=>800,'deposit_amount'=>100,'monthly_amount'=>250]);
        $schedule=app(\App\Services\Hourly\PaymentScheduleService::class);
        $this->assertSame('installments',$order->package_snapshot['payment_policy']);
        $this->assertSame(100.0,(float)$order->package_snapshot['charge_now']);
        $this->assertSame(4,DB::table('hourly_order_installments')->where('hourly_rental_order_id',$order->id)->count());
        $this->assertSame(800.0,(float)DB::table('hourly_order_installments')->where('hourly_rental_order_id',$order->id)->sum('amount'));
        \App\Models\Hourly\HourlyPackage::findOrFail($order->hourly_package_id)->update(['payment_policy'=>'prepaid','monthly_amount'=>1]);
        $this->assertSame('installments',$schedule->policy($order->fresh()));
        $this->assertSame(100.0,$schedule->forOrder($order->fresh())['due_now']);
        $this->assertStringContainsString('أقساط بمواعيد محددة',view('admin.hourly._payment-schedule',['order'=>$order->fresh()])->render());
    }
    /** الإنهاء المبكر: وصول 08:00 لزيارة 4 ساعات ثم «انتهيت» 09:00 ⇒ يُسجَّل ويُسأل العميل، ولا يُمنع الإنهاء. */
    private function earlyFinishedVisit(?string $reason = 'customer_request'): array
    {
        $visit = $this->assign($this->order()->visits()->first(), $this->driver());
        $token = $this->loginWorker();
        Carbon::setTestNow('2026-09-22 08:00');
        $visit->fresh()->update(['status' => 'delivered']);
        // بداية الخدمة تُختم على صف العاملة لحظة «تم التوصيل» من أي مسار.
        $this->assertSame('2026-09-22 08:00:00', (string) DB::table('hourly_visit_workers')->where('hourly_visit_id', $visit->id)->value('arrived_at'));
        $row = $this->withToken($token)->getJson('/api/worker/visits/today')->assertOk()->json('data.0');
        $this->assertSame('2026-09-22T12:00:00+03:00', $row['planned_end_at']);
        $this->assertArrayHasKey('customer_request', $row['early_finish_reasons']);
        auth()->forgetGuards();
        Carbon::setTestNow('2026-09-22 09:00');
        $this->withToken($token)->postJson('/api/worker/visits/'.$row['id'].'/finish', array_filter(['early_reason' => $reason, 'note' => 'طلبت العميلة الإنهاء']))
            ->assertOk()->assertJsonPath('data.visit.status', 'finished');
        auth()->forgetGuards();
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'worker_finished']);
        return [$visit->fresh(), DB::table('hourly_early_finishes')->where('visit_id', $visit->id)->first()];
    }

    public function test_early_finish_is_recorded_and_the_customer_confirms_it_without_admin_review(): void
    {
        [$visit, $early] = $this->earlyFinishedVisit();
        $this->assertNotNull($early);
        $this->assertSame([60, 240, 'customer_request', 'awaiting_customer'], [(int) $early->worked_minutes, (int) $early->planned_minutes, $early->reason, $early->status]);
        $service = app(\App\Services\Hourly\EarlyFinish::class);
        $order = $visit->order;
        $public = app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit, $order)['early_finishes'];
        $this->assertTrue($public[0]['can_respond']);
        $this->assertSame('1 س', $public[0]['worked_label']);

        $result = $service->respond($order, $visit->id, (int) $early->id, 'confirmed', null);
        $this->assertSame('confirmed', $result['status']);
        $this->assertSame(0, $service->reviewCount());
        // رد ثانٍ مرفوض، والقرار الإداري غير مطلوب لما أكده العميل.
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->respond($order, $visit->id, (int) $early->id, 'disputed', null);
    }

    public function test_disputed_or_unanswered_early_finish_waits_for_a_manual_admin_decision(): void
    {
        [$visit, $early] = $this->earlyFinishedVisit(null);
        $this->assertSame('unspecified', $early->reason);
        $service = app(\App\Services\Hourly\EarlyFinish::class);
        $this->assertSame(0, $service->reviewCount());
        // بلا رد حتى انقضاء المهلة (ساعتان) ⇒ يُحال للإدارة، ولا يُقبل رد العميل بعدها.
        Carbon::setTestNow('2026-09-22 11:01');
        $this->assertSame('no_response', $service->effectiveStatus(DB::table('hourly_early_finishes')->find($early->id)));
        $this->assertSame(1, $service->reviewCount());
        try { $service->respond($visit->order, $visit->id, (int) $early->id, 'disputed', null); $this->fail('late response accepted'); }
        catch (\Illuminate\Validation\ValidationException $e) { $this->assertStringContainsString('انتهت مهلة الرد', implode(' ', \Illuminate\Support\Arr::flatten($e->errors()))); }

        $service->review((int) $early->id, ['decision' => 'wallet_credit', 'decision_note' => 'تعويض 75% من قيمة الزيارة'], 7);
        $this->assertDatabaseHas('hourly_early_finishes', ['id' => $early->id, 'status' => 'resolved', 'decision' => 'wallet_credit', 'reviewed_by' => 7]);
        $this->assertSame(0, $service->reviewCount());
        // القرار يدوي: لا حركة مالية تلقائية على الطلب أو المحفظة.
        $this->assertSame(1000.0, (float) DB::table('users')->where('id', 1)->value('hourly_wallet_balance'));
    }

    public function test_finishing_after_the_full_service_time_is_not_an_early_finish(): void
    {
        $visit = $this->assign($this->order()->visits()->first(), $this->driver());
        $token = $this->loginWorker();
        Carbon::setTestNow('2026-09-22 08:00');
        $visit->fresh()->update(['status' => 'delivered']);
        $id = $this->withToken($token)->getJson('/api/worker/visits/today')->json('data.0.id');
        auth()->forgetGuards();
        Carbon::setTestNow('2026-09-22 12:00');
        $this->withToken($token)->postJson('/api/worker/visits/'.$id.'/finish')->assertOk();
        $this->assertDatabaseMissing('hourly_early_finishes', ['visit_id' => $visit->id]);
    }
}
