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
    private function order(int $worker = 1, string $at = '2026-09-22 08:00', array $workerSettings = []): HourlyRentalOrder
    {
        // أحياء متباعدة 0.1 درجة: مضلعات الأحياء (±0.01) لا تتقاطع فيُحلّ حي العنوان بلا لبس.
        static $n = 0; $n++;
        $category = $this->category(['name_ar' => 'تنظيف '.$n]);
        $district = $this->district('الملز '.$n, 24.7 + $n / 10, 46.7);
        $category->districts()->attach($district->id, ['is_active' => true, 'team_id' => $this->team->id]);
        $item = $this->item($category);
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
        $driver = $this->driver();
        $order = $this->order();
        $visit = $this->assign($order->visits()->first(), $driver);
        $token = $this->loginDriver($driver);

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
            ->assertJsonPath('data.trip.stops.0.requires_code', true);
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

    public function test_trips_history_covers_other_days_and_explains_an_empty_list(): void
    {
        $driver = $this->driver();
        // زيارة اليوم، وأخرى بعد ثلاثة أيام على طلب لم يُؤكَّد بعد.
        $today = $this->assign($this->order(1, '2026-09-22 08:00')->visits()->first(), $driver);
        $later = $this->order(2, '2026-09-25 08:00');
        $later->update(['status' => 'pending_confirmation']);
        $future = $this->assign($later->visits()->first(), $driver);
        $token = $this->loginDriver($driver);

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

    public function test_worker_without_transport_starts_and_closes_her_own_visit(): void
    {
        $order = $this->order(2, '2026-09-22 08:00', ['requires_driver' => false]);
        $visit = $order->visits()->first();
        $token = $this->loginWorker(2, '0500000002');
        $rows = $this->withToken($token)->getJson('/api/worker/visits/today')->assertOk()
            ->assertJsonPath('data.0.needs_transport', false)->assertJsonPath('data.0.can_arrive', true)->json('data');
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/api/worker/visits/'.$rows[0]['id'].'/arrived', ['latitude' => 24.71, 'longitude' => 46.7])->assertOk()
            ->assertJsonPath('data.visit.status', 'delivered');
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit->id, 'status' => 'delivered']);
        auth()->forgetGuards();
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

}
