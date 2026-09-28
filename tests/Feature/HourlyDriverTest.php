<?php

namespace Tests\Feature;

use App\Models\Hourly\HourlyDriver;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\HourlyTestCase as TestCase;

class HourlyDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.driver_test' => ['driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'database.default'=>'driver_test', 'jwt.secret'=>str_repeat('hourly-driver-test-secret', 3), 'superadmin_modules.hourly_rental.default_enabled'=>true,'activitylog.enabled'=>false]);
        Schema::create('new_settings', function (Blueprint $table) { $table->id(); $table->string('key'); $table->text('value'); $table->timestamps(); });
        (require base_path('database/migrations/2026_09_17_000001_create_hourly_drivers_table.php'))->up();
        Schema::create('users', function (Blueprint $table) { $table->softDeletes(); $table->id(); $table->string('name')->nullable(); $table->string('phone')->nullable(); });
        Schema::create('hourly_service_categories', function (Blueprint $table) { $table->id(); $table->string('name_ar')->nullable(); $table->string('name_en')->nullable(); $table->string('icon_type')->nullable(); $table->string('icon')->nullable(); $table->softDeletes(); });
        Schema::create('hourly_rental_orders', function (Blueprint $table) { $table->id(); $table->string('order_number')->nullable(); $table->unsignedBigInteger('user_id')->nullable(); $table->unsignedBigInteger('service_category_id')->nullable(); $table->string('status'); $table->timestamps(); });
        Schema::create('hourly_visits', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('hourly_rental_order_id'); $table->unsignedInteger('visit_number')->default(1);
            $table->unsignedBigInteger('delivery_driver_id')->nullable(); $table->unsignedBigInteger('pickup_driver_id')->nullable();
            $table->date('scheduled_date')->nullable(); $table->dateTime('scheduled_start_at')->nullable(); $table->dateTime('scheduled_end_at')->nullable();
            $table->dateTime('delivery_at')->nullable(); $table->dateTime('pickup_at')->nullable(); $table->string('status')->default('scheduled');
            $table->text('address_text')->nullable(); $table->decimal('latitude',10,7)->nullable(); $table->decimal('longitude',10,7)->nullable(); $table->string('contact_phone')->nullable(); $table->boolean('is_free')->default(false); $table->timestamps();
        });
        Schema::create('maids', function (Blueprint $table) { $table->boolean('has_driving_license')->nullable(); $table->string('driving_license_file')->nullable(); $table->id(); $table->string('name')->nullable(); $table->softDeletes(); });
        Schema::create('hourly_visit_workers', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('hourly_visit_id'); $table->unsignedBigInteger('maid_id'); $table->timestamps(); });
        Schema::create('hourly_order_audits', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('hourly_rental_order_id'); $table->unsignedBigInteger('visit_id')->nullable(); $table->unsignedBigInteger('admin_id')->nullable(); $table->string('action'); $table->text('reason')->nullable(); $table->text('before')->nullable(); $table->text('after')->nullable(); $table->timestamps(); });
        Schema::create('hourly_order_status_logs', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('hourly_rental_order_id'); $table->string('from_status')->nullable(); $table->string('to_status'); $table->string('actor_type'); $table->unsignedBigInteger('actor_id')->nullable(); $table->text('reason')->nullable(); $table->timestamps(); });
        HourlyRentalSettings::flush();
        HourlyRentalSettings::persist(['is_enabled'=>true]);
    }

    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    private function payload(): array
    {
        return ['name'=>'سائق اختبار', 'employee_number'=>'0012', 'gender'=>'male', 'phone'=>'0500000000', 'vehicle_capacity'=>12, 'is_active'=>1, 'account_enabled'=>1, 'login_access'=>'both', 'password'=>'DriverPass123', 'password_confirmation'=>'DriverPass123', 'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS), ['mode'=>'hours','start'=>'09:00','end'=>'16:00'])];
    }

    private function driver(array $overrides = []): HourlyDriver
    {
        $data = $this->payload(); unset($data['password_confirmation']);
        return HourlyDriver::create(array_replace($data, ['username'=>'DR-TEST', 'password'=>Hash::make('DriverPass123')], $overrides));
    }

    private function bypassAdmin(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class, \App\Http\Middleware\EnsureAdminRoutePermission::class, \App\Http\Middleware\Admin::class]);
    }

    public function test_create_saves_schedule_and_hashes_password_without_team(): void
    {
        $this->bypassAdmin();
        $data = $this->payload();
        $data['work_schedule']['friday'] = ['mode'=>'off', 'start'=>'09:00','end'=>'16:00'];
        $data['work_schedule']['saturday'] = ['mode'=>'all_day'];
        $this->postJson(route('admin.hourly-drivers.store'), $data)->assertRedirect();
        $driver = HourlyDriver::firstOrFail();
        $this->assertTrue(Hash::check('DriverPass123', $driver->password));
        $this->assertStringStartsWith('DR-', $driver->username);
        $this->assertSame('0012', $driver->employee_number);
        $this->assertNull($driver->work_schedule['friday']['start']);
        $this->assertSame('all_day', $driver->work_schedule['saturday']['mode']);
    }

    /** @dataProvider invalidData */
    public function test_invalid_driver_data_is_rejected(string $field, $value): void
    {
        $this->bypassAdmin(); $data = $this->payload(); data_set($data, $field, $value);
        $this->postJson(route('admin.hourly-drivers.store'), $data)->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertSame(0, HourlyDriver::count());
    }

    public function invalidData(): array
    {
        return [['vehicle_capacity',2], ['vehicle_capacity',51], ['vehicle_capacity',3.5], ['work_schedule.sunday.end','08:00'], ['work_schedule.monday.start',null], ['work_schedule.tuesday.mode','unknown'], ['password','short'], ['login_access','admin'], ['login_phone','+966500000000'], ['birth_date','2099-01-01']];
    }

    public function test_update_preserves_password_and_rejects_duplicate_employee_number(): void
    {
        $this->bypassAdmin(); $driver = $this->driver(); $hash = $driver->password;
        $data=$this->payload(); $data['password']=null; $data['password_confirmation']=null;
        $this->putJson(route('admin.hourly-drivers.update', $driver),$data)->assertRedirect();
        $this->assertSame($hash,$driver->fresh()->password);
        $this->postJson(route('admin.hourly-drivers.store'),$this->payload())->assertJsonValidationErrors('employee_number');
        $data['contract_start_date']='2026-10-01'; $data['contract_end_date']='2026-09-01';
        $this->putJson(route('admin.hourly-drivers.update',$driver),$data)->assertJsonValidationErrors('contract_end_date');
    }

    public function test_web_session_is_revoked_on_account_change(): void
    {
        $driver=$this->driver();
        $this->post(route('hourly-driver.authenticate'), ['login'=>$driver->username,'password'=>'DriverPass123'])->assertRedirect(route('hourly-driver.home'));
        $this->get(route('hourly-driver.home'))->assertOk()->assertSee('جدول العمل الأسبوعي');
        $driver->update(['auth_version'=>2]); auth()->forgetGuards();
        $this->get(route('hourly-driver.home'))->assertRedirect(route('hourly-driver.login'));
    }

    public function test_portal_shows_calendar_trip_details_and_admin_login_link(): void
    {
        $driver=$this->driver();
        DB::table('users')->insert(['id'=>7,'name'=>'عميل الاختبار','phone'=>'0501111111']);
        DB::table('hourly_service_categories')->insert(['id'=>3,'name_ar'=>'تنظيف']);
        DB::table('hourly_rental_orders')->insert(['id'=>11,'order_number'=>'HR-11','user_id'=>7,'service_category_id'=>3,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        DB::table('hourly_visits')->insert(['id'=>21,'hourly_rental_order_id'=>11,'visit_number'=>2,'delivery_driver_id'=>$driver->id,'scheduled_date'=>today(),'scheduled_start_at'=>today()->setTime(9,0),'scheduled_end_at'=>today()->setTime(12,0),'status'=>'scheduled','address_text'=>'حي الياسمين','latitude'=>24.1,'longitude'=>46.1,'contact_phone'=>'0501111111','created_at'=>now(),'updated_at'=>now()]);

        $this->post(route('hourly-driver.authenticate'),['login'=>$driver->username,'password'=>'DriverPass123']);
        $this->get(route('hourly-driver.home',['date'=>today()->toDateString()]))->assertOk()
            ->assertSee('مهامي ومشاويري')->assertSee('HR-11')->assertSee('حي الياسمين')->assertSee('بدء مشوار التوصيل')->assertSee('دخول لوحة الإدارة');
        $this->get(route('hourly-driver.login'))->assertOk()->assertSee(route('admin.form.login'),false);
    }

    public function test_driver_can_only_advance_an_assigned_task_in_order(): void
    {
        $driver=$this->driver(); $other=$this->driver(['username'=>'DR-OTHER','employee_number'=>'0099','phone'=>'0500000099']);
        DB::table('hourly_rental_orders')->insert(['id'=>11,'order_number'=>'HR-11','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        foreach([[21,$driver->id],[22,$other->id]] as [$id,$assigned]) DB::table('hourly_visits')->insert(['id'=>$id,'hourly_rental_order_id'=>11,'visit_number'=>$id,'delivery_driver_id'=>$assigned,'pickup_driver_id'=>$assigned,'scheduled_date'=>today(),'scheduled_start_at'=>today()->setTime(9,0),'scheduled_end_at'=>today()->setTime(12,0),'status'=>'scheduled','created_at'=>now(),'updated_at'=>now()]);
        $this->post(route('hourly-driver.authenticate'),['login'=>$driver->username,'password'=>'DriverPass123']);

        $this->post(route('hourly-driver.visits.action',21),['action'=>'start_delivery'])->assertRedirect();
        $this->assertDatabaseHas('hourly_visits',['id'=>21,'status'=>'en_route']);
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>21,'action'=>'driver_start_delivery']);
        $this->post(route('hourly-driver.visits.action',21),['action'=>'complete'])->assertSessionHasErrors('hourly');
        $this->post(route('hourly-driver.visits.action',22),['action'=>'start_delivery'])->assertForbidden();
        $this->assertDatabaseHas('hourly_visits',['id'=>22,'status'=>'scheduled']);
    }

    public function test_mobile_token_is_revoked_by_admin_status_change(): void
    {
        $driver=$this->driver(['login_phone'=>'966500000000']);
        // مسارات الفريق الميداني تردّ بالغلاف الموحد { code, status, message, data }.
        $token=$this->postJson('/api/app/hourly/driver/login',['login'=>'966500000000','password'=>'DriverPass123'])->assertOk()->json('data.access_token');
        auth()->forgetGuards();
        $response=$this->withToken($token)->getJson('/api/app/hourly/driver/me')->assertOk()->assertJsonPath('data.username',$driver->username);
        $this->assertArrayNotHasKey('password',$response->json('data'));
        $this->bypassAdmin();
        $this->putJson(route('admin.hourly-drivers.status',$driver),['is_active'=>0])
            ->assertOk()->assertJsonPath('is_active', false)->assertJsonPath('counts.active', 0)->assertJsonPath('counts.inactive', 1)->assertJsonPath('counts.canLogin', 0);
        $this->putJson(route('admin.hourly-drivers.status',$driver),['is_active'=>1])
            ->assertOk()->assertJsonPath('is_active', true)->assertJsonPath('counts.active', 1)->assertJsonPath('counts.canLogin', 1);
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/app/hourly/driver/me')->assertUnauthorized();
    }

    public function test_platform_limits_disabled_accounts_and_archived_drivers_cannot_login(): void
    {
        $driver=$this->driver(['login_access'=>'mobile']);
        $credentials=['login'=>$driver->username,'password'=>'DriverPass123'];
        $this->postJson(route('hourly-driver.authenticate'),$credentials)->assertUnprocessable();
        $driver->update(['login_access'=>'web']);
        $this->postJson('/api/app/hourly/driver/login',$credentials)->assertUnprocessable();
        $driver->update(['login_access'=>'both','account_enabled'=>false]);
        $this->postJson('/api/app/hourly/driver/login',$credentials)->assertUnprocessable();
        $driver->update(['account_enabled'=>true]); $driver->delete();
        $this->postJson('/api/app/hourly/driver/login',$credentials)->assertUnprocessable();
    }

    public function test_driver_token_cannot_authenticate_as_customer(): void
    {
        $driver=$this->driver(); $token=auth('hourly_driver_api')->login($driver);
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/app/hourly/orders')->assertUnauthorized();
    }

    public function test_feature_flag_hides_driver_routes_and_admin_routes_have_permissions(): void
    {
        config(['superadmin_modules.hourly_rental.default_enabled'=>false]);
        $this->get(route('hourly-driver.login'))->assertNotFound();
        $this->postJson('/api/app/hourly/driver/login',[])->assertNotFound();
        $this->getJson(route('admin.hourly-drivers.index'))->assertNotFound();
        $count=0;
        foreach (app('router')->getRoutes() as $route) {
            if (!str_starts_with($route->getName() ?? '', 'admin.hourly-drivers.')) continue;
            $count++;
            foreach (['admin','admin.route.permission','feature.hourly_rental'] as $middleware) $this->assertContains($middleware,$route->gatherMiddleware());
            $this->assertArrayHasKey($route->getName(),config('hourly_permissions'));
        }
        $this->assertSame(10,$count);
        $this->assertSame(['hourly_drivers_impersonate'],config('hourly_permissions')['admin.hourly-drivers.login-as']);
    }

    public function test_admin_can_open_the_driver_portal_as_a_driver_and_return_to_the_panel(): void
    {
        $this->bypassAdmin();
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->name='مشرف الاختبار';
        auth('admin')->setUser($admin);
        $driver=$this->driver();
        $this->post(route('admin.hourly-drivers.login-as',$driver))->assertRedirect(route('hourly-driver.home'));
        $this->assertSame($driver->id,auth('hourly_driver')->id());
        $this->assertSame(7,auth('admin')->id());
        $this->get(route('hourly-driver.home'))->assertOk()->assertSee('بصفتك المشرف مشرف الاختبار')->assertSee('إنهاء والعودة للوحة');
        $token=session()->token();
        $this->post(route('hourly-driver.logout'))->assertRedirect(route('admin.hourly-drivers.index'));
        $this->assertNull(auth('hourly_driver')->user());
        $this->assertSame($token,session()->token());
        $this->assertFalse(session()->has('hourly_driver_impersonator'));
        // A driver who cannot use the website is refused instead of being bounced by the portal.
        $mobileOnly=$this->driver(['username'=>'DR-MOB','employee_number'=>'0099','phone'=>'0500000099','login_access'=>'mobile']);
        $this->from(route('admin.hourly-drivers.index'))->post(route('admin.hourly-drivers.login-as',$mobileOnly))
            ->assertRedirect(route('admin.hourly-drivers.index'))->assertSessionHasErrors('driver');
        $this->assertNull(auth('hourly_driver')->user());
    }

    public function test_soft_delete_restore_and_permanent_delete_lifecycle(): void
    {
        $this->bypassAdmin();
        $driver = $this->driver();
        $token = auth('hourly_driver_api')->login($driver);
        $this->delete(route('admin.hourly-drivers.destroy', $driver))->assertRedirect();
        $this->assertNull(HourlyDriver::find($driver->id));
        $this->assertTrue(HourlyDriver::onlyTrashed()->findOrFail($driver->id)->trashed());
        $this->post(route('admin.hourly-drivers.restore', $driver->id))->assertRedirect();
        $this->assertNotNull(HourlyDriver::find($driver->id));
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/app/hourly/driver/me')->assertUnauthorized();
        $this->deleteJson(route('admin.hourly-drivers.force-destroy', $driver->id))->assertNotFound();
        $this->postJson(route('admin.hourly-drivers.restore', $driver->id))->assertNotFound();
        $this->delete(route('admin.hourly-drivers.destroy', $driver))->assertRedirect();
        $this->delete(route('admin.hourly-drivers.force-destroy', $driver->id))->assertRedirect();
        $this->assertNull(HourlyDriver::withTrashed()->find($driver->id));
    }

    public function test_permanent_delete_preserves_driver_with_team_history(): void
    {
        $this->bypassAdmin();
        Schema::create('hourly_team_members', function (Blueprint $table) {
            $table->id(); $table->string('member_type'); $table->unsignedBigInteger('member_id');
        });
        $driver = $this->driver();
        \Illuminate\Support\Facades\DB::table('hourly_team_members')->insert(['member_type'=>'driver', 'member_id'=>$driver->id]);
        $driver->delete();
        $this->deleteJson(route('admin.hourly-drivers.force-destroy', $driver->id))->assertUnprocessable()->assertJsonValidationErrors('driver');
        $this->assertNotNull(HourlyDriver::onlyTrashed()->find($driver->id));
    }
}
