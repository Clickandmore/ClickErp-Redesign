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
            $table->string('delivery_code')->nullable();
            $table->dateTime('delivery_at')->nullable(); $table->dateTime('pickup_at')->nullable(); $table->string('status')->default('scheduled');
            $table->text('address_text')->nullable(); $table->decimal('latitude',10,7)->nullable(); $table->decimal('longitude',10,7)->nullable(); $table->string('contact_phone')->nullable(); $table->boolean('is_free')->default(false); $table->timestamps();
        });
        Schema::create('maids', function (Blueprint $table) { $table->boolean('has_driving_license')->nullable(); $table->string('driving_license_file')->nullable(); $table->id(); $table->string('name')->nullable(); $table->softDeletes(); });
        Schema::create('hourly_order_payments', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('hourly_rental_order_id'); $table->string('status'); $table->string('method')->nullable(); $table->decimal('amount',12,2)->default(0); $table->json('provider_payload')->nullable(); $table->timestamps(); });
        Schema::create('hourly_visit_workers', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('hourly_visit_id'); $table->unsignedBigInteger('maid_id'); $table->timestamps(); });
        Schema::create('hourly_order_audits', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('hourly_rental_order_id'); $table->unsignedBigInteger('visit_id')->nullable(); $table->unsignedBigInteger('admin_id')->nullable(); $table->string('action'); $table->text('reason')->nullable(); $table->text('before')->nullable(); $table->text('after')->nullable(); $table->timestamps(); });
        Schema::create('hourly_order_status_logs', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('hourly_rental_order_id'); $table->string('from_status')->nullable(); $table->string('to_status'); $table->string('actor_type'); $table->unsignedBigInteger('actor_id')->nullable(); $table->text('reason')->nullable(); $table->timestamps(); });
        HourlyRentalSettings::flush();
        HourlyRentalSettings::persist(['is_enabled'=>true]);
    }

    protected function tearDown(): void { \Carbon\Carbon::setTestNow(); HourlyRentalSettings::flush(); parent::tearDown(); }

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
    public function test_invalid_driver_data_is_rejected(string $field, $value, ?string $errorKey = null): void
    {
        $this->bypassAdmin(); $data = $this->payload(); data_set($data, $field, $value);
        $this->postJson(route('admin.hourly-drivers.store'), $data)->assertUnprocessable()->assertJsonValidationErrors($errorKey ?? $field);
        $this->assertSame(0, HourlyDriver::count());
    }

    public function invalidData(): array
    {
        return [['vehicle_capacity',2], ['vehicle_capacity',51], ['vehicle_capacity',3.5], ['work_schedule.sunday.end','08:00'], ['work_schedule.monday.start',null], ['work_schedule.tuesday.mode','unknown'], ['password','short'], ['login_access','admin'], ['login_phone','+966500000000'], ['birth_date','2099-01-01'],
            ['name','سائق 2'], ['name','سائق@'], ['name',str_repeat('ب',101)], ['employee_number','EMP-1'], ['phone','05000abc00'], ['phone','0400000000'], ['phone','050000000'],
            ['email','سائق@example.com'], ['email','driver@'], ['birth_date',now()->subYears(10)->toDateString()], ['birth_date',now()->subYears(80)->toDateString()],
            ['residency_number','1234567890'], ['residency_number','212345678'], ['passport_number','A12-345'], ['border_number','1234567890'],
            ['residency_expiry_date','2027-01-01','residency_number'], ['entry_date',now()->addDay()->toDateString()], ['password_confirmation','OtherPass123','password']];
    }

    public function test_phones_and_documents_are_normalized_and_not_duplicated(): void
    {
        $this->bypassAdmin(); $this->driver(['phone2'=>'0555555555','residency_number'=>'2123456789','passport_number'=>'A1234567','border_number'=>'3123456789','email'=>'one@example.com']);
        $data=array_replace($this->payload(),['employee_number'=>'0099','phone'=>'+966 55 555 5555']);
        $this->postJson(route('admin.hourly-drivers.store'),$data)->assertJsonValidationErrors(['phone'=>'مسجّل لسائق آخر']);
        $data=array_replace($this->payload(),['employee_number'=>'0099','phone'=>'0511111111','phone2'=>'0500000000','residency_number'=>'٢١٢٣٤٥٦٧٨٩','residency_expiry_date'=>'2027-01-01','passport_number'=>'a1234567','passport_expiry_date'=>'2030-01-01','border_number'=>'3123456789','email'=>'ONE@example.com']);
        $this->postJson(route('admin.hourly-drivers.store'),$data)->assertJsonValidationErrors(['phone2','residency_number','passport_number','border_number','email']);
        $this->assertSame(1, HourlyDriver::count());

        // حفظ النموذج من الصفحة (fetch) يُرجع رابط صفحة التعديل، والأرقام تُخزَّن بصيغة موحدة
        $data=array_replace($this->payload(),['employee_number'=>'0099','name'=>'  Ahmed   Ali ','phone'=>'+966511111111','passport_number'=>'b7654321','passport_expiry_date'=>'2030-01-01']);
        $this->postJson(route('admin.hourly-drivers.store'),$data,['X-Requested-With'=>'XMLHttpRequest'])->assertOk()->assertJsonStructure(['redirect','message']);
        $driver=HourlyDriver::where('employee_number','0099')->firstOrFail();
        $this->assertSame(['Ahmed Ali','0511111111','B7654321'],[$driver->name,$driver->phone,$driver->passport_number]);
        $this->assertSame(['residency_expiry_date'=>'الإقامة'],$driver->missingDocuments());
    }

    public function test_work_and_contract_dates_follow_the_birth_date(): void
    {
        $this->bypassAdmin();
        $birth=now()->subYears(30)->toDateString(); $age18=now()->subYears(12)->toDateString();
        $data=array_replace($this->payload(),['birth_date'=>$birth,'work_start_date'=>now()->subYears(15)->toDateString(),'contract_start_date'=>now()->subYears(13)->toDateString(),'entry_date'=>now()->subYears(31)->toDateString()]);
        $this->postJson(route('admin.hourly-drivers.store'),$data)->assertJsonValidationErrors(['work_start_date','contract_start_date','entry_date']);
        $data=array_replace($data,['work_start_date'=>$age18,'contract_start_date'=>$age18,'entry_date'=>$age18]);
        $this->postJson(route('admin.hourly-drivers.store'),$data)->assertRedirect();
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
        \Carbon\Carbon::setTestNow('2026-10-14 09:00:00');
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

    public function test_portal_requires_customer_delivery_code_before_confirmation(): void
    {
        \Carbon\Carbon::setTestNow('2026-10-14 10:00:00');$driver=$this->driver();
        DB::table('hourly_rental_orders')->insert(['id'=>11,'order_number'=>'HR-11','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        DB::table('hourly_visits')->insert(['id'=>21,'hourly_rental_order_id'=>11,'visit_number'=>1,'delivery_driver_id'=>$driver->id,
            'scheduled_date'=>'2026-10-14','scheduled_start_at'=>'2026-10-14 09:00:00','scheduled_end_at'=>'2026-10-14 12:00:00',
            'status'=>'en_route','delivery_code'=>'057391','created_at'=>now(),'updated_at'=>now()]);
        $this->post(route('hourly-driver.authenticate'),['login'=>$driver->username,'password'=>'DriverPass123']);
        $this->get(route('hourly-driver.home'))->assertOk()->assertSee('name="delivery_code"',false)
            ->assertSee('كود استلام العميل')->assertDontSee('057391');
        foreach([[],['delivery_code'=>'999999']] as $code){
            $this->post(route('hourly-driver.visits.action',21),['action'=>'confirm_delivery']+$code)->assertSessionHasErrors('hourly');
            $this->assertDatabaseHas('hourly_visits',['id'=>21,'status'=>'en_route']);
            $this->assertDatabaseMissing('hourly_order_audits',['visit_id'=>21,'action'=>'driver_confirm_delivery']);
        }
        $this->post(route('hourly-driver.visits.action',21),['action'=>'confirm_delivery','delivery_code'=>'057391'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hourly_visits',['id'=>21,'status'=>'delivered']);
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>21,'action'=>'driver_confirm_delivery']);
        DB::table('hourly_visits')->where('id',21)->update(['status'=>'en_route']);
        HourlyRentalSettings::persist(['staff_app_require_delivery_code'=>false]);
        $this->get(route('hourly-driver.home'))->assertOk()->assertDontSee('name="delivery_code"',false);
        $this->post(route('hourly-driver.visits.action',21),['action'=>'confirm_delivery'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hourly_visits',['id'=>21,'status'=>'delivered']);
        HourlyRentalSettings::persist(['staff_app_require_delivery_code'=>true]);
        DB::table('hourly_visits')->where('id',21)->update(['status'=>'en_route','delivery_code'=>null]);
        $this->get(route('hourly-driver.home'))->assertOk()->assertDontSee('name="delivery_code"',false);
        $this->post(route('hourly-driver.visits.action',21),['action'=>'confirm_delivery'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('hourly_visits',['id'=>21,'status'=>'delivered']);
    }
    public function test_driver_can_only_advance_an_assigned_task_in_order(): void
    {
        \Carbon\Carbon::setTestNow('2026-10-14 09:00:00');
        $driver=$this->driver(); $other=$this->driver(['username'=>'DR-OTHER','employee_number'=>'0099','phone'=>'0500000099']);
        DB::table('hourly_rental_orders')->insert(['id'=>11,'order_number'=>'HR-11','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        foreach([[21,$driver->id],[22,$other->id]] as [$id,$assigned]) DB::table('hourly_visits')->insert(['id'=>$id,'hourly_rental_order_id'=>11,'visit_number'=>$id,'delivery_driver_id'=>$assigned,'pickup_driver_id'=>$assigned,'scheduled_date'=>today(),'scheduled_start_at'=>today()->setTime(9,0),'scheduled_end_at'=>today()->setTime(12,0),'status'=>'scheduled','created_at'=>now(),'updated_at'=>now()]);
        $this->post(route('hourly-driver.authenticate'),['login'=>$driver->username,'password'=>'DriverPass123']);

        foreach (['2026-10-13 09:00:00','2026-10-14 08:59:59','2026-10-14 12:00:00','2026-10-16 09:00:00'] as $at) {
            \Carbon\Carbon::setTestNow($at);
            $this->get(route('hourly-driver.home',['date'=>'2026-10-14']))->assertOk()->assertDontSee('بدء مشوار التوصيل');
            $this->post(route('hourly-driver.visits.action',21),['action'=>'start_delivery'])->assertSessionHasErrors('hourly');
            $this->assertDatabaseHas('hourly_visits',['id'=>21,'status'=>'scheduled']);
        }
        \Carbon\Carbon::setTestNow('2026-10-14 09:00:00');
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
