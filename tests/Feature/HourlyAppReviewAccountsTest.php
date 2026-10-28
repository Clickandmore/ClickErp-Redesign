<?php

namespace Tests\Feature;

use App\Http\Actions\APPAPI\PhoneVerificationAction;
use App\Models\ingaz\PhoneVerification;
use App\Support\{HourlyAppReviewAccounts as Review, HourlyStaffOtp};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash, Schema};
use Tests\HourlyTestCase;

class HourlyAppReviewAccountsTest extends HourlyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.connections.review_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'review_test', 'activitylog.enabled' => false,
        ]);
        Schema::create('new_settings', function (Blueprint $t) { $t->id(); $t->string('key')->unique(); $t->text('value'); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('phone'); $t->string('phone_code')->default('966');
            $t->boolean('is_blocked')->default(false); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('maids', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('rental_mode')->default('hourly'); $t->string('hourly_login_phone');
            $t->json('hourly_settings'); $t->boolean('hourly_is_suspended')->default(false); $t->softDeletes();
        });
        (require base_path('database/migrations/2026_09_17_000001_create_hourly_drivers_table.php'))->up();
        Schema::create('phone_verifications', function (Blueprint $t) {
            $t->id(); $t->string('phone'); $t->string('phone_code'); $t->string('code'); $t->timestamps();
        });
        Schema::create('hourly_staff_otps', function (Blueprint $t) {
            $t->id(); $t->string('guard'); $t->string('phone'); $t->unsignedBigInteger('staff_id')->nullable();
            $t->string('code_hash'); $t->dateTime('expires_at'); $t->integer('attempts'); $t->timestamp('consumed_at')->nullable(); $t->timestamps();
        });
        DB::table('users')->insert(['id'=>1, 'name'=>'Review customer', 'phone'=>'500000001']);
        DB::table('maids')->insert(['id'=>1, 'name'=>'Review worker', 'hourly_login_phone'=>'0500000002', 'hourly_settings'=>json_encode(['account_enabled'=>true, 'login_access'=>'mobile'])]);
        DB::table('hourly_drivers')->insert(['id'=>1, 'name'=>'Review driver', 'employee_number'=>'TEST-1', 'gender'=>'male', 'phone'=>'0500000003', 'login_phone'=>'500000003', 'username'=>'HD-TEST', 'vehicle_capacity'=>6, 'work_schedule'=>'{}']);
    }

    private function payload(): array
    {
        return ['enabled'=>1, 'accounts'=>array_fill_keys(array_keys(Review::ROLES), ['account_id'=>1, 'enabled'=>1, 'code'=>'73916'])];
    }

    private function save(array $payload): void
    {
        $this->withSession(['dev_superadmin_authenticated'=>true])->put(route('dev-superadmin.hourly-app-review-accounts.update'), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('dev-superadmin.hourly-app-review-accounts.index'));
    }

    public function test_guests_cannot_manage_review_accounts(): void
    {
        $this->get(route('dev-superadmin.hourly-app-review-accounts.index'))->assertRedirect(route('superadmin.login'));
        $this->put(route('dev-superadmin.hourly-app-review-accounts.update'), $this->payload())->assertRedirect(route('superadmin.login'));
        $this->assertDatabaseCount('new_settings', 0);
    }

    public function test_page_selects_existing_accounts_and_links_standard_creation_forms(): void
    {
        $this->withSession(['dev_superadmin_authenticated'=>true])->get(route('dev-superadmin.hourly-app-review-accounts.index'))
            ->assertOk()->assertSee('Review worker')->assertSee('Review driver')->assertSee('Review customer')
            ->assertSee(route('admin.hourly-customers.create'), false)
            ->assertSee(route('admin.hourly-drivers.create'), false);
    }

    public function test_codes_are_encrypted_and_scoped_by_role_identity_phone_and_country(): void
    {
        $this->save($this->payload());
        $this->assertStringNotContainsString('73916', DB::table('new_settings')->value('value'));
        $this->assertSame('73916', Review::code('customer', '500000001', null, '966'));
        $this->assertNull(Review::code('customer', '500000001', null, '20'));
        $this->assertSame('73916', Review::code('worker', '+966500000002', 1));
        $this->assertSame('73916', Review::code('driver', '0500000003', 1));
        $this->assertNull(Review::code('driver', '0500000003', 2));
        $this->assertNull(Review::code('worker', '0500000003', 1));
        $this->assertNull(Review::code('customer', '500000009', null, '966'));
        DB::table('maids')->where('id',1)->update(['hourly_is_suspended'=>true]);
        $this->assertNull(Review::code('worker', '0500000002', 1));
        DB::table('users')->where('id',1)->update(['phone'=>'500000009']);
        $this->assertNull(Review::code('customer', '500000001', null, '966'));
    }

    public function test_disabling_clears_pending_codes_but_preserves_accounts_and_other_phones(): void
    {
        $this->save($this->payload());
        HourlyStaffOtp::issue('driver', '0500000003', 1);
        DB::table('phone_verifications')->insert([
            ['phone'=>'500000001','phone_code'=>'966','code'=>'73916'],
            ['phone'=>'500000009','phone_code'=>'966','code'=>'48291'],
        ]);
        $payload=$this->payload(); $payload['enabled']=0;
        $this->save($payload);
        $this->assertNull(Review::code('driver', '0500000003', 1));
        $this->assertDatabaseCount('hourly_staff_otps',0);
        $this->assertDatabaseHas('phone_verifications',['phone'=>'500000009','code'=>'48291']);
        $this->assertDatabaseHas('phone_verifications',['phone'=>'500000001','code'=>'73916']);
        $this->assertDatabaseHas('hourly_drivers',['id'=>1,'is_active'=>1]);
        $this->assertDatabaseHas('users',['id'=>1,'is_blocked'=>0]);
    }

    public function test_staff_uses_fixed_code_and_requires_issuance_and_correct_code(): void
    {
        $this->save($this->payload());
        $result=HourlyStaffOtp::issue('worker','0500000002',1);
        $this->assertSame('73916',$result['debug_code']);
        $this->assertFalse($result['sent']);
        $row=DB::table('hourly_staff_otps')->first();
        $this->assertTrue(Hash::check('73916',$row->code_hash));
        HourlyStaffOtp::consume('worker','0500000002','73916');
        $this->assertNotNull(DB::table('hourly_staff_otps')->value('consumed_at'));
    }

    public function test_customer_issues_fixed_code_without_sending_and_rejects_wrong_code(): void
    {
        $this->save($this->payload());
        $action=new PhoneVerificationAction(new PhoneVerification());
        $request=new Request(['phone'=>'500000001','phone_code'=>'966','code'=>'00000']);
        $this->assertFalse($action->confirmCode($request));
        $result=$action->storeSendCode(['phone'=>'500000001','phone_code'=>'966'],$request);
        $this->assertSame('73916',$result['code']);
        $this->assertFalse($action->confirmCode($request));
        $this->assertDatabaseCount('phone_verifications',0);
        $request->merge(['code'=>'73916']);
        $this->assertNotFalse($action->confirmCode($request));
    }

    public function test_per_account_disable_and_invalid_accounts(): void
    {
        $payload=$this->payload(); $payload['accounts']['worker']['enabled']=0;
        $this->save($payload);
        $this->assertNull(Review::code('worker','0500000002',1));
        $this->assertSame('73916',Review::code('driver','0500000003',1));
        DB::table('hourly_drivers')->where('id',1)->update(['residency_expiry_date'=>'2020-01-01']);
        $this->assertNull(Review::code('driver','0500000003',1));
        $this->withSession(['dev_superadmin_authenticated'=>true])->put(route('dev-superadmin.hourly-app-review-accounts.update'),$this->payload())
            ->assertSessionHasErrors('accounts.driver.account_id');
        $payload['enabled']=0;
        $this->save($payload);
        $this->assertFalse(Review::all()['enabled']);
    }

    public function test_rotating_code_invalidates_pending_codes_and_leaves_other_accounts_unchanged(): void
    {
        $this->save($this->payload());
        HourlyStaffOtp::issue('worker','0500000002',1);
        DB::table('phone_verifications')->insert(['phone'=>'500000001','phone_code'=>'966','code'=>'73916']);
        $payload=$this->payload(); $payload['accounts']['worker']['code']='38291';
        $this->save($payload);
        $this->assertDatabaseCount('hourly_staff_otps',0);
        $this->assertDatabaseHas('phone_verifications',['phone'=>'500000001','code'=>'73916']);
        $this->assertSame('38291',HourlyStaffOtp::issue('worker','0500000002',1)['debug_code']);
        $this->assertSame('73916',Review::code('customer','500000001',null,'966'));
        $this->assertNull(Review::code('worker','0500000009',1));
        DB::table('maids')->where('id',1)->update(['deleted_at'=>now()]);
        $payload['enabled']=0;
        $this->save($payload);
        $this->assertFalse(Review::all()['enabled']);
    }

    public function test_customer_with_plus_country_code_can_be_selected_and_use_review_code(): void
    {
        DB::table('users')->where('id',1)->update(['phone_code'=>'+966']);
        $this->save($this->payload());
        $this->assertSame('73916', Review::code('customer', '500000001', null, '+966'));
        $this->assertSame('73916', Review::code('customer', '500000001', null, '966'));
        DB::table('users')->where('id',1)->update(['is_blocked'=>true]);
        $this->assertNull(Review::code('customer', '500000001', null, '+966'));
    }

    public function test_customer_must_match_existing_mobile_login_phone_format(): void
    {
        DB::table('users')->where('id',1)->update(['phone'=>'0500000001']);
        $this->withSession(['dev_superadmin_authenticated'=>true])->put(route('dev-superadmin.hourly-app-review-accounts.update'),$this->payload())
            ->assertSessionHasErrors('accounts.customer.account_id');
        $this->assertDatabaseCount('new_settings',0);
    }

    public function test_saving_disabled_or_unchanged_settings_preserves_existing_codes(): void
    {
        DB::table('phone_verifications')->insert(['phone'=>'500000001','phone_code'=>'966','code'=>'48291']);
        $payload=$this->payload(); $payload['enabled']=0;
        $this->save($payload);
        $this->assertDatabaseHas('phone_verifications',['phone'=>'500000001','code'=>'48291']);
        $payload['enabled']=1;
        $this->save($payload);
        $this->assertDatabaseHas('phone_verifications',['phone'=>'500000001','code'=>'48291']);
        HourlyStaffOtp::issue('worker','0500000002',1);
        $before=DB::table('hourly_staff_otps')->first();
        $this->save($payload);
        $this->assertSame($before->code_hash,DB::table('hourly_staff_otps')->value('code_hash'));
        $payload['accounts']['driver']['code']='38291';
        $this->save($payload);
        $this->assertSame($before->code_hash,DB::table('hourly_staff_otps')->value('code_hash'));
        $this->assertDatabaseHas('phone_verifications',['phone'=>'500000001','code'=>'48291']);
    }

    public function test_broken_review_settings_do_not_break_normal_authentication(): void
    {
        DB::table('new_settings')->insert(['key'=>Review::KEY,'value'=>'invalid-encrypted-settings']);
        $this->assertNull(Review::code('customer','500000001',null,'966'));
        $this->assertNull(Review::code('worker','0500000002',1));
        $this->assertNull(Review::code('driver','0500000003',1));
    }

    public function test_customer_review_code_works_without_overwriting_external_site_code(): void
    {
        $payload=$this->payload(); $payload['accounts']['customer']['code']='11111';
        $this->save($payload);
        $action=new PhoneVerificationAction(new PhoneVerification());
        $request=new Request(['phone'=>'500000001','phone_code'=>'966','code'=>'11111']);
        $this->assertNotFalse($action->confirmCode($request));
        DB::table('phone_verifications')->insert(['phone'=>'500000001','phone_code'=>'966','code'=>'48291']);
        $this->assertSame('11111',$action->storeSendCode(['phone'=>'500000001','phone_code'=>'966'],$request)['code']);
        $this->assertDatabaseHas('phone_verifications',['phone'=>'500000001','code'=>'48291']);
        $this->assertNotFalse($action->confirmCode($request));
        $request->merge(['code'=>'48291']);
        $this->assertFalse($action->confirmCode($request));
        $payload['accounts']['customer']['code']='22222';
        $this->save($payload);
        $request->merge(['code'=>'11111']);
        $this->assertFalse($action->confirmCode($request));
        $request->merge(['code'=>'22222']);
        $this->assertNotFalse($action->confirmCode($request));
        $payload['enabled']=0;
        $this->save($payload);
        $this->assertFalse($action->confirmCode($request));
        $request->merge(['code'=>'48291']);
        $this->assertNotFalse($action->confirmCode($request));
        $this->assertDatabaseHas('phone_verifications',['phone'=>'500000001','code'=>'48291']);
    }
}
