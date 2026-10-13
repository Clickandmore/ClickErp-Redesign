<?php
namespace Tests\Feature;

use App\Models\ingaz\Maid;
use App\Models\Hourly\{HourlyDriver, HourlyTeam, HourlyTeamMember};
use App\Services\Hourly\MaidSettingsService;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Schema, Hash};
use Illuminate\Validation\ValidationException;
use Tests\HourlyTestCase as TestCase;

class HourlyMaidSettingsTest extends TestCase
{
    public function test_email_is_normalized_and_duplicates_are_rejected_on_create_and_update(): void
    {
        $payload=$this->payload();$payload['hourly']['email']=' Worker@Example.COM ';
        $maid=$this->save($payload);
        $this->assertSame('worker@example.com',$maid->hourly_email);
        $this->assertSame('worker@example.com',$this->save($payload,$maid)->hourly_email);
        $other=Maid::create(['name'=>'Other worker','passport_num'=>'P456']);
        foreach([null,$other] as $target){
            $payload['hourly']['login_phone']='0507654321';
            try{$this->save($payload,$target);$this->fail('Duplicate email accepted');}
            catch(ValidationException $e){$this->assertArrayHasKey('hourly.email',$e->errors());}
        }
        DB::table('maids')->where('id',$maid->id)->update(['hourly_email'=>' Legacy@Example.COM ']);
        $payload['hourly']['email']='legacy@example.com';
        try{$this->save($payload,$other);$this->fail('Legacy email accepted');}
        catch(ValidationException $e){$this->assertArrayHasKey('hourly.email',$e->errors());}
        $payload['hourly']['email']='   ';
        $this->assertNull($this->save($payload,$other)->hourly_email);
    }
    public function test_admin_can_open_maid_portal_and_return_without_losing_admin_session(): void
    {
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class, \App\Http\Middleware\EnsureAdminRoutePermission::class, \App\Http\Middleware\Admin::class]);
        $admin = \Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id = 7;
        $admin->name = 'Test Supervisor';
        auth('admin')->setUser($admin);
        $maid = $this->save($this->payload());
        Schema::create('maid_jobs', function (Blueprint $table) { $table->unsignedBigInteger('maid_id'); $table->unsignedBigInteger('job_type_id'); });
        DB::table('maid_jobs')->insert(['maid_id' => $maid->id, 'job_type_id' => 2]);

        $this->post(route('admin.hourly-maids.login-as', $maid))->assertRedirect(route('hourly-maid.home'));
        $this->assertSame($maid->id, auth('hourly_maid')->id());
        $this->assertSame(7, auth('admin')->id());
        $this->get(route('hourly-maid.home'))->assertOk()->assertSee('Test Supervisor')->assertSee('إنهاء والعودة للوحة');
        $token = session()->token();
        $this->post(route('hourly-maid.logout'))->assertRedirect(route('admin.hourly-maids.index'));
        $this->assertNull(auth('hourly_maid')->user());
        $this->assertSame(7, auth('admin')->id());
        $this->assertSame($token, session()->token());
        $this->assertFalse(session()->has('hourly_maid_impersonator'));

        foreach ([['account_enabled' => false], ['login_access' => 'mobile'], ['suspended' => true]] as $change) {
            $maid->forceFill(['hourly_settings' => array_replace($maid->hourly_settings, ['account_enabled' => true, 'login_access' => 'both'], $change), 'hourly_is_suspended' => $change['suspended'] ?? false])->save();
            $this->from(route('admin.hourly-maids.index'))->post(route('admin.hourly-maids.login-as', $maid))
                ->assertRedirect(route('admin.hourly-maids.index'))->assertSessionHasErrors('maid');
            $this->assertNull(auth('hourly_maid')->user());
        }
    }

    protected function setUp(): void {
        parent::setUp();
        config(['database.connections.maid_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'database.default'=>'maid_test','superadmin_modules.hourly_rental.default_enabled'=>true,'activitylog.enabled'=>false,'jwt.secret'=>str_repeat('hourly-maid-tests',4)]);
        Schema::create('new_settings',function(Blueprint $t){$t->id();$t->string('key');$t->text('value');$t->timestamps();});
        Schema::create('maids',function(Blueprint $t){ $t->boolean('has_driving_license')->nullable(); $t->string('driving_license_file')->nullable();$t->id();$t->string('name');$t->string('passport_num')->nullable();$t->string('status')->default('available');$t->timestamps();$t->softDeletes();});
        Schema::create('maid_translations',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id');$t->string('locale');});
        Schema::create('job_types',function(Blueprint $t){$t->id();});
        Schema::create('job_types_translations',function(Blueprint $t){$t->id();$t->unsignedBigInteger('job_type_id');$t->string('title');$t->string('locale');});
        DB::table('job_types')->insert(['id'=>2]);
        DB::table('job_types_translations')->insert(['job_type_id'=>2,'title'=>'تأجير','locale'=>'ar']);
        Schema::create('hourly_teams',function(Blueprint $t){$t->id();$t->string('name');$t->boolean('is_active')->default(true);$t->timestamps();$t->softDeletes();});
        Schema::create('hourly_team_members',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_team_id');$t->string('member_type');$t->unsignedBigInteger('member_id');$t->string('role');$t->boolean('is_active')->default(true);$t->date('joined_at')->nullable();$t->date('left_at')->nullable();$t->timestamps();});
        (require base_path('database/migrations/2026_09_17_000006_add_hourly_settings_to_maids.php'))->up();
        (require base_path('database/migrations/2026_09_17_000007_add_hourly_suspension_to_maids.php'))->up();
        HourlyRentalSettings::flush(); HourlyRentalSettings::persist(['is_enabled'=>true]);
    }
    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }
    public function test_hourly_profile_saves_rates_and_preserves_them_when_existing_settings_are_edited(): void
    {
        Schema::table('maids',function(Blueprint $t){$t->string('phone_1')->nullable();$t->string('phone_2')->nullable();$t->date('birth_date')->nullable();$t->string('image')->nullable();});
        Schema::create('hourly_service_categories',function(Blueprint $t){$t->id();$t->string('name_ar');$t->integer('sort_order')->default(0);$t->boolean('is_active')->default(true);$t->softDeletes();});
        DB::table('hourly_service_categories')->insert(['id'=>1,'name_ar'=>'تنظيف']);
        $maid=$this->save($this->payload());
        $data=$this->payload()+['name'=>'اسم محدث','phone_1'=>'0505555555','profile'=>[
            'bank_name'=>'بنك الاختبار','contract_start_date'=>'2026-01-01','contract_exp_date'=>'2027-01-01',
            'rates'=>[['service_category_id'=>null,'payment_type'=>'hourly','payment_value'=>25],['service_category_id'=>1,'payment_type'=>'commission','payment_value'=>10]],
        ]];
        $photo=\Illuminate\Http\UploadedFile::fake()->image('worker.png',100,100);
        $storage=\Mockery::mock(\App\Services\ManagedFileStorage::class);
        $storage->shouldReceive('uploadFile')->once()->with($photo,'images/Maid')->andReturn('images/Maid/worker.png');
        $this->app->instance(\App\Services\ManagedFileStorage::class,$storage);
        app(\App\Services\Hourly\MaidProfileService::class)->save(new Request($data,[],[],[],['photo'=>$photo]),$maid);
        $maid->refresh();
        $this->assertSame('اسم محدث',$maid->name);
        $this->assertSame('P123',$maid->passport_num);
        $this->assertSame('images/Maid/worker.png',$maid->image);
        $this->assertCount(2,$maid->hourly_settings['profile']['rates']);
        $html=view('admin.hourly_maids._profile',[
            'maid'=>$maid,'canEdit'=>true,
            'services'=>\App\Models\Hourly\HourlyServiceCategory::all(),
        ])->render();
        $this->assertStringContainsString('بنك الاختبار',$html);
        $this->assertStringContainsString('profile[rates][1][payment_value]',$html);
        $this->assertStringContainsString('hourly[work_schedule][sunday][mode]',$html);
        $this->assertStringNotContainsString('MaidPass123',$html);
        $this->assertStringNotContainsString('<option value="regular">',$html);
        $this->save($this->payload(),$maid);
        $this->assertSame('بنك الاختبار',$maid->fresh()->hourly_settings['profile']['bank_name']);
        $this->assertSame('images/Maid/worker.png',$maid->fresh()->image);
        foreach ([\Illuminate\Http\UploadedFile::fake()->create('invalid.txt',10,'text/plain'),\Illuminate\Http\UploadedFile::fake()->image('large.png')->size(5121)] as $invalidPhoto) {
            try {app(\App\Services\Hourly\MaidProfileService::class)->save(new Request($data,[],[],[],['photo'=>$invalidPhoto]),$maid);$this->fail('Invalid photo accepted');}
            catch(ValidationException $e){$this->assertArrayHasKey('photo',$e->errors());}
            $this->assertSame('images/Maid/worker.png',$maid->fresh()->image);
        }
        foreach (['duplicate','commission','dates','missing_default'] as $invalid) {
            $bad=$data;
            if($invalid==='duplicate')$bad['profile']['rates'][]=$bad['profile']['rates'][1];
            if($invalid==='commission')$bad['profile']['rates'][1]['payment_value']=101;
            if($invalid==='dates')$bad['profile']['contract_exp_date']='2025-01-01';
            if($invalid==='missing_default')unset($bad['profile']['rates'][0]);
            $bad['name']='must not save';
            try { app(\App\Services\Hourly\MaidProfileService::class)->save(new Request($bad),$maid);$this->fail('Invalid profile accepted'); }
            catch(ValidationException $e){$this->assertNotEmpty($e->errors());}
            $this->assertSame('اسم محدث',$maid->fresh()->name);
        }
    }

    public function test_hourly_profile_stats_only_include_assigned_visits_and_reject_regular_workers(): void
    {
        Schema::create('maid_jobs',function(Blueprint $t){$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('job_type_id');});
        Schema::create('nationalities',function(Blueprint $t){$t->id();});
        Schema::create('hourly_service_categories',function(Blueprint $t){$t->id();$t->string('name_ar');$t->integer('sort_order')->default(0);$t->softDeletes();});
        Schema::create('hourly_visits',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_rental_order_id');$t->string('status');$t->decimal('planned_hours');});
        Schema::create('hourly_visit_workers',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_visit_id');$t->unsignedBigInteger('maid_id');});
        $maid=$this->save($this->payload());
        DB::table('maid_jobs')->insert(['maid_id'=>$maid->id,'job_type_id'=>2]);
        DB::table('hourly_visits')->insert([
            ['id'=>1,'hourly_rental_order_id'=>1,'status'=>'completed','planned_hours'=>4],
            ['id'=>2,'hourly_rental_order_id'=>1,'status'=>'completed','planned_hours'=>3],
            ['id'=>3,'hourly_rental_order_id'=>2,'status'=>'scheduled','planned_hours'=>2],
            ['id'=>4,'hourly_rental_order_id'=>3,'status'=>'completed','planned_hours'=>9],
        ]);
        foreach([1,2,3] as $id) DB::table('hourly_visit_workers')->insert(['hourly_visit_id'=>$id,'maid_id'=>$maid->id]);
        $controller=app(\App\Http\Controllers\admin\HourlyMaidController::class);
        $view=$controller->show(new Request(),$maid);
        $this->assertSame(['visits'=>2,'orders'=>1,'hours'=>7.0],$view->getData()['stats']['completed']);
        $this->assertSame(['visits'=>1,'orders'=>1,'hours'=>2.0],$view->getData()['stats']['pending']);
        $this->assertSame('admin.hourly_maids.show',$view->name());
        // Render the new page independently of the global dashboard chrome.
        $template=str_replace("@extends('admin.layout.indexs.index')",'',file_get_contents(resource_path('views/admin/hourly_maids/show.blade.php')));
        $html=\Illuminate\Support\Facades\Blade::render($template."\n@yield('content')",array_merge($view->getData(),['tab'=>'logs','errors'=>new \Illuminate\Support\ViewErrorBag()]));
        $this->assertStringContainsString('تفاصيل العاملة',$html);
        $this->assertStringContainsString('2 زيارات',$html);
        try {$controller->show(new Request(),$maid,'unknown');$this->fail('Unknown tab accepted');}
        catch(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e){$this->assertSame(404,$e->getStatusCode());}
        try {$controller->update(new Request(),$maid);$this->fail('Unauthorized update accepted');}
        catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(403,$e->getStatusCode());}
        $maid->forceFill(['rental_mode'=>'regular'])->save();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $controller->show(new Request(),$maid);
    }

    private function payload(): array {
        return ['type_id'=>2,'rental_mode'=>'hourly','hourly'=>[
            'requires_driver'=>1,'max_orders_per_hour'=>1,'account_enabled'=>1,'login_access'=>'both',
            'password'=>'MaidPass123','password_confirmation'=>'MaidPass123','login_phone'=>'0501234567',
            'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'hours','start'=>'09:00','end'=>'16:00']),
        ]];
    }
    private function save(array $payload, ?Maid $maid = null): Maid {
        if (!Schema::hasTable('rental_contracts')) Schema::create('rental_contracts',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id');$t->string('status');$t->softDeletes();});
        $service=app(MaidSettingsService::class);
        $data=$service->validate(new Request($payload),$maid);
        $maid ??= Maid::create(['name'=>'عاملة اختبار','passport_num'=>'P123']);
        DB::transaction(fn()=>$service->save($maid,$data));
        return $maid->fresh();
    }
    public function test_services_can_be_selected_replaced_cleared_and_preserved_on_legacy_updates():void {
        Schema::create('hourly_service_categories',function(Blueprint $t){$t->id();$t->string('name_ar');$t->string('name_en')->nullable();$t->boolean('is_active')->default(true);$t->integer('sort_order')->default(0);$t->softDeletes();});
        DB::table('hourly_service_categories')->insert([['id'=>1,'name_ar'=>'خدمة ساعات','is_active'=>true],['id'=>2,'name_ar'=>'تنظيف الفلل','is_active'=>true],['id'=>3,'name_ar'=>'خدمة متوقفة','is_active'=>false]]);
        $payload=$this->payload();$payload['hourly']['services_present']=1;$payload['hourly']['service_category_ids']=['1','2'];
        $maid=$this->save($payload);$this->assertSame([1,2],$maid->hourly_settings['service_category_ids']);
        $html=view('admin.ingaz.maids._hourly-fields',['obj'=>$maid])->render();$this->assertStringContainsString('خدمة ساعات',$html);$this->assertStringContainsString('تنظيف الفلل',$html);$this->assertStringNotContainsString('خدمة متوقفة',$html);
        $payload['hourly']['service_category_ids']=[2];$maid=$this->save($payload,$maid);$this->assertSame([2],$maid->hourly_settings['service_category_ids']);
        $maid=$this->save($this->payload(),$maid);$this->assertSame([2],$maid->hourly_settings['service_category_ids']);
        unset($payload['hourly']['service_category_ids']);$maid=$this->save($payload,$maid);$this->assertSame([],$maid->hourly_settings['service_category_ids']);
        foreach([[3],[999],[1,1]] as $ids){$payload['hourly']['service_category_ids']=$ids;try{$this->save($payload,$maid);$this->fail('Invalid services must be rejected');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}}
    }
    public function test_hourly_details_stay_on_existing_maid_and_team_changes_keep_history(): void {
        $a=HourlyTeam::create(['name'=>'A']);$b=HourlyTeam::create(['name'=>'B']);
        $payload=$this->payload();$payload['hourly']['primary_team_id']=$a->id;$payload['hourly']['secondary_team_ids']=[$b->id];
        $maid=$this->save($payload);
        $this->assertSame(1,Maid::count());$this->assertSame('available',$maid->status);$this->assertSame('P123',$maid->passport_num);
        $this->assertSame('hourly',$maid->rental_mode);$this->assertSame('HM-'.$maid->id,$maid->hourly_username);
        $this->assertTrue(Hash::check('MaidPass123',$maid->hourly_password));$this->assertArrayNotHasKey('hourly_password',$maid->toArray());
        $this->assertSame(2,HourlyTeamMember::whereNull('left_at')->count());
        $hash=$maid->hourly_password;
        $payload['hourly']['password']=null;$payload['hourly']['password_confirmation']=null;
        $payload['hourly']['primary_team_id']=$b->id;$payload['hourly']['secondary_team_ids']=[];
        $maid=$this->save($payload,$maid);
        $this->assertSame($hash,$maid->hourly_password);$this->assertSame(1,HourlyTeamMember::whereNull('left_at')->count());
        $this->assertSame(2,HourlyTeamMember::whereNotNull('left_at')->count());
        $this->save(['rental_mode'=>'regular'],$maid);
        $this->assertSame('regular',$maid->fresh()->rental_mode);$this->assertSame(0,HourlyTeamMember::whereNull('left_at')->count());
    }
    public function test_disabled_feature_hides_fields_without_reading_hourly_tables_and_rejects_payload(): void {
        config(['superadmin_modules.hourly_rental.default_enabled'=>false]);
        Schema::drop('hourly_team_members');Schema::drop('hourly_teams');
        $this->assertSame('',trim(view('admin.ingaz.maids._hourly-fields')->render()));
        $this->assertNull(app(MaidSettingsService::class)->validate(new Request(['type_id'=>2])));
        $this->expectException(ValidationException::class);
        app(MaidSettingsService::class)->validate(new Request($this->payload()));
    }
    public function test_fields_render_schedule_and_selected_mode(): void {
        $maid=$this->save($this->payload());
        $html=view('admin.ingaz.maids._hourly-fields',['obj'=>$maid])->render();
        $this->assertStringContainsString('إيجار ساعات',$html);
        $this->assertStringContainsString('value="hourly" selected',$html);
        $this->assertStringContainsString('hourly[work_schedule][sunday][mode]',$html);
        $this->assertStringNotContainsString('MaidPass123',$html);
    }
    public function test_invalid_hours_and_duplicate_teams_are_rejected(): void {
        $a=HourlyTeam::create(['name'=>'A']);
        foreach (['hours','teams','type'] as $case) {
            $payload=$this->payload();
            if ($case==='hours') $payload['hourly']['work_schedule']['sunday']['end']='08:00';
            if ($case==='teams') { $payload['hourly']['primary_team_id']=$a->id; $payload['hourly']['secondary_team_ids']=[$a->id]; }
            if ($case==='type') $payload['type_id']=1;
            try { app(MaidSettingsService::class)->validate(new Request($payload));$this->fail('Invalid settings accepted'); }
            catch (ValidationException $e) { $this->assertNotEmpty($e->errors()); }
        }
        $this->assertSame(0,Maid::count());
    }
    public function test_schedule_login_phone_and_password_errors_are_clear_and_keyed_to_their_fields(): void {
        $cases=[
            ['work_schedule', ['sunday'=>['mode'=>'hours','start'=>'09:00','end'=>'09:00']], 'hourly.work_schedule.sunday.end', 'يجب أن تكون بعد بدايته'],
            ['login_phone', '-12ab', 'hourly.login_phone', 'يقبل الأرقام فقط'],
            ['password_confirmation', 'OtherPass123', 'hourly.password', 'غير مطابق'],
        ];
        foreach ($cases as [$field,$value,$key,$message]) {
            $payload=$this->payload();
            $payload['hourly'][$field]=is_array($value) ? array_replace($payload['hourly'][$field],$value) : $value;
            try { app(MaidSettingsService::class)->validate(new Request($payload)); $this->fail("$field accepted"); }
            catch (ValidationException $e) { $this->assertArrayHasKey($key,$e->errors()); $this->assertStringContainsString($message,$e->errors()[$key][0]); }
        }
        // الأرقام العربية في هاتف الدخول تُقبل وتُحفظ لاتينية
        $payload=$this->payload(); $payload['hourly']['login_phone']='٠٥٠١٢٣٤٥٦٧';
        $this->assertSame('0501234567', app(MaidSettingsService::class)->validate(new Request($payload))['data']['login_phone']);
        $html=view('admin.ingaz.maids._hourly-fields',['obj'=>null])->render();
        $this->assertStringContainsString('data-toggle-password="maid-login-password"',$html);
        $this->assertStringContainsString('inputmode="numeric"',$html);
        $this->assertStringContainsString('data-time-error',$html);
    }

    public function test_login_channels_password_change_and_feature_gate(): void {
        $payload=$this->payload();$payload['hourly']['login_access']='web';
        $maid=$this->save($payload);
        $this->postJson('/api/app/hourly/maid/login',['login'=>$maid->hourly_username,'password'=>'MaidPass123'])->assertUnprocessable();
        $this->post(route('hourly-maid.authenticate'),['login'=>$maid->hourly_username,'password'=>'MaidPass123'])->assertRedirect(route('hourly-maid.home'));
        $this->get(route('hourly-maid.home'))->assertOk()->assertSee('عاملة اختبار');
        $this->save($payload,$maid);
        auth()->forgetGuards();
        $this->get(route('hourly-maid.home'))->assertRedirect(route('hourly-maid.login'));
        config(['superadmin_modules.hourly_rental.default_enabled'=>false]);
        $this->get(route('hourly-maid.login'))->assertNotFound();
    }

    public function test_mobile_login_and_account_disable_revoke_access(): void {
        $payload=$this->payload();$maid=$this->save($payload);
        // مسارات الفريق الميداني تردّ بالغلاف الموحد { code, status, message, data }.
        $response=$this->postJson('/api/app/hourly/maid/login',['login'=>'0501234567','password'=>'MaidPass123'])->assertOk();
        $token=$response->json('data.access_token');
        auth()->forgetGuards();
        $profile=$this->withToken($token)->getJson('/api/app/hourly/maid/me')->assertOk()->assertJsonPath('data.name','عاملة اختبار');
        $this->assertArrayNotHasKey('hourly_password',$profile->json('data'));
        $payload['hourly']['account_enabled']=0;
        $this->save($payload,$maid);
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/app/hourly/maid/me')->assertUnauthorized();
    }

    public function test_team_page_assigns_primary_secondary_and_scopes_removal(): void {
        Schema::create('maid_jobs',function(Blueprint $t){$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('job_type_id');});
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class,\App\Http\Middleware\EnsureAdminRoutePermission::class,\App\Http\Middleware\Admin::class]);
        $maid=$this->save($this->payload());
        DB::table('maid_jobs')->insert(['maid_id'=>$maid->id,'job_type_id'=>2]);
        $a=HourlyTeam::create(['name'=>'A','is_active'=>true]);$b=HourlyTeam::create(['name'=>'B','is_active'=>true]);
        $url=fn($team)=>route('admin.hourly-teams.maids',$team);
        $this->post($url($a),['maid_ids'=>[$maid->id],'role'=>'primary'])->assertRedirect();
        $this->post($url($b),['maid_ids'=>[$maid->id],'role'=>'secondary'])->assertRedirect();
        $this->assertSame(2,$maid->hourlyTeamMemberships()->count());
        $this->post($url($b),['maid_ids'=>[$maid->id],'role'=>'primary'])->assertRedirect();
        $member=$maid->hourlyTeamMemberships()->sole();
        $this->assertEquals($b->id,$member->hourly_team_id);$this->assertSame('primary',$member->role);
        $this->post($url($b),['maid_ids'=>[$maid->id],'role'=>'primary'])->assertRedirect();
        $this->assertSame(1,$maid->hourlyTeamMemberships()->count());
        $this->putJson(route('admin.hourly-teams.maid-member',[$a,$member]),['action'=>'remove'])->assertNotFound();
        $this->put(route('admin.hourly-teams.maid-member',[$b,$member]),['action'=>'deactivate'])->assertRedirect();
        $this->assertFalse($member->fresh()->is_active);
        $this->assertSame('available',$maid->fresh()->status);
        $this->put(route('admin.hourly-teams.maid-member',[$b,$member]),['action'=>'remove'])->assertRedirect();
        $this->assertNotNull($member->fresh()->left_at);
        $a->update(['is_active'=>false]);
        $this->postJson($url($a),['maid_ids'=>[$maid->id],'role'=>'primary'])->assertUnprocessable();
        $ordinary=Maid::create(['name'=>'Ordinary']);
        $this->postJson($url($b),['maid_ids'=>[$ordinary->id],'role'=>'primary'])->assertUnprocessable();
    }

    public function test_admin_can_review_leave_only_after_conflicting_visits_are_reassigned(): void
    {
        Schema::create('maid_jobs',function(Blueprint $t){$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('job_type_id');});
        Schema::create('nationalities',function(Blueprint $t){$t->id();});
        Schema::create('hourly_service_categories',function(Blueprint $t){$t->id();$t->string('name_ar');$t->integer('sort_order')->default(0);$t->softDeletes();});
        Schema::create('hourly_worker_leaves',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id');$t->dateTime('starts_at');$t->dateTime('ends_at');$t->string('reason')->nullable();$t->boolean('is_active')->default(false);$t->string('status')->default('pending');$t->string('type')->default('leave');$t->string('source')->nullable();$t->timestamps();});
        Schema::create('hourly_visits',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_rental_order_id');$t->integer('visit_number')->default(1);$t->string('status');$t->dateTime('scheduled_start_at')->nullable();$t->dateTime('scheduled_end_at')->nullable();$t->decimal('planned_hours')->default(4);$t->timestamps();});
        Schema::create('hourly_visit_workers',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_visit_id');$t->unsignedBigInteger('maid_id');});
        $maid=$this->save($this->payload());
        DB::table('maid_jobs')->insert(['maid_id'=>$maid->id,'job_type_id'=>2]);
        DB::table('hourly_worker_leaves')->insert([
            ['id'=>1,'maid_id'=>$maid->id,'starts_at'=>'2030-01-10 00:00:00','ends_at'=>'2030-01-12 23:59:59','status'=>'pending','type'=>'leave','is_active'=>false],
            ['id'=>2,'maid_id'=>$maid->id,'starts_at'=>'2030-02-10 00:00:00','ends_at'=>'2030-02-12 23:59:59','status'=>'pending','type'=>'sick','is_active'=>false],
        ]);
        DB::table('hourly_visits')->insert(['id'=>1,'hourly_rental_order_id'=>1,'status'=>'scheduled','scheduled_start_at'=>'2030-01-11 08:00:00','scheduled_end_at'=>'2030-01-11 12:00:00','planned_hours'=>4]);
        DB::table('hourly_visit_workers')->insert(['hourly_visit_id'=>1,'maid_id'=>$maid->id]);
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);
        auth('admin')->setUser($admin);
        $controller=app(\App\Http\Controllers\admin\HourlyMaidController::class);
        try {
            $controller->leaveStatus(new Request(['status'=>'approved']),$maid,1);
            $this->fail('Conflicting leave was approved.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status',$e->errors());
        }
        $this->assertDatabaseHas('hourly_worker_leaves',['id'=>1,'status'=>'pending','is_active'=>false]);
        $controller->leaveStatus(new Request(['status'=>'approved']),$maid,2);
        $this->assertDatabaseHas('hourly_worker_leaves',['id'=>2,'status'=>'approved','is_active'=>true]);
        $controller->leaveStatus(new Request(['status'=>'rejected']),$maid,1);
        $this->assertDatabaseHas('hourly_worker_leaves',['id'=>1,'status'=>'rejected','is_active'=>false]);
        $view=$controller->show(new Request(),$maid,'leaves');
        $this->assertCount(2,$view->getData()['leaves']);
    }

    public function test_hourly_list_includes_pending_arrivals_and_requires_actual_shelter_for_readiness(): void {
        Schema::table('maids',function(Blueprint $t){$t->string('rental_source')->nullable();$t->date('arrival_date')->nullable();$t->unsignedBigInteger('auto_recruitment_contract_id')->nullable();$t->unsignedBigInteger('nationality_id')->nullable();$t->string('phone_1')->nullable();$t->string('id_number')->nullable();});
        Schema::create('maid_jobs',function(Blueprint $t){$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('job_type_id');});
        Schema::create('nationalities',function(Blueprint $t){$t->id();});
        Schema::create('contracts',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('previous_contract_id')->nullable();$t->string('status');$t->softDeletes();});
        Schema::create('contract_infos',function(Blueprint $t){$t->id();$t->unsignedBigInteger('contract_id');$t->unsignedBigInteger('shelter_id')->nullable();$t->boolean('is_outside_shelter')->nullable();$t->string('wants_to_work')->nullable();});
        Schema::create('contract_status_logs',function(Blueprint $t){$t->id();$t->unsignedBigInteger('contract_id');$t->string('status');$t->timestamp('date');$t->text('notes');});
        Schema::create('rental_contracts',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id');$t->string('status');$t->string('closure_type')->nullable();$t->timestamp('returned_to_company_at')->nullable();$t->timestamp('closed_at')->nullable();$t->timestamps();$t->softDeletes();});
        Schema::create('sponsorship_transfers',function(Blueprint $t){$t->id();$t->unsignedBigInteger('contract_id');$t->string('status');$t->timestamp('maid_delivered_at')->nullable();$t->softDeletes();});
        Schema::create('deportation_procedures',function(Blueprint $t){$t->id();$t->unsignedBigInteger('contract_id');$t->string('status');$t->timestamp('completed_at')->nullable();});
        Schema::create('deportation_procedure_logs',function(Blueprint $t){$t->id();$t->unsignedBigInteger('deportation_procedure_id');$t->string('action_type');$t->timestamp('created_at');});
        foreach ([1=>'PendingWorker',2=>'ArrivedWorker',3=>'ReadyWorker',4=>'OrdinaryWorker',5=>'ReservedWorker'] as $id=>$name) {
            DB::table('maids')->insert(['id'=>$id,'name'=>$name,'rental_mode'=>$id===4 ? 'regular' : 'hourly','rental_source'=>'outside','arrival_date'=>$id===1 ? null : '2026-09-17','status'=>$id===5 ? 'reserved' : 'available']);
            DB::table('maid_jobs')->insert(['maid_id'=>$id,'job_type_id'=>2]);
        }
        foreach ([3,5] as $id) {
            DB::table('contracts')->insert(['id'=>$id,'maid_id'=>$id,'status'=>'shelter']);
            DB::table('contract_infos')->insert(['contract_id'=>$id,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
        }
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class,\App\Http\Middleware\EnsureAdminRoutePermission::class,\App\Http\Middleware\Admin::class]);
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        app('view')->getFinder()->prependLocation(base_path('tests/Fixtures/hourly-views'));
        $url=route('admin.hourly-maids.index');
        $this->get($url)->assertOk()->assertSee('PendingWorker')->assertSee('ArrivedWorker')->assertSee('ReadyWorker')->assertDontSee('OrdinaryWorker')
            ->assertViewHas('stats',fn($stats)=>$stats===['total'=>4,'waiting'=>1,'inside'=>2,'ready'=>1,'out_now'=>0,'available_now'=>1]);
        $this->get($url.'?state=ready')->assertOk()->assertSee('ReadyWorker')->assertDontSee('ArrivedWorker')->assertDontSee('ReservedWorker');
        $this->get($url.'?state=waiting')->assertOk()->assertSee('PendingWorker')->assertDontSee('ReadyWorker');
        $this->get($url.'?search=ArrivedWorker')->assertOk()->assertSee('ArrivedWorker')->assertDontSee('PendingWorker');
        $availability=route('admin.hourly-maids.availability',3);
        $this->putJson($availability,['is_suspended'=>true])->assertOk()->assertJsonPath('is_ready',false)->assertJsonPath('ready_count',0);
        $this->assertSame('available',Maid::find(3)->status);
        $this->get($url.'?state=ready')->assertOk()->assertDontSee('ReadyWorker');
        $this->putJson($availability,['is_suspended'=>false])->assertOk()->assertJsonPath('is_ready',true)->assertJsonPath('ready_count',1);
        $this->putJson(route('admin.hourly-maids.availability',1),['is_suspended'=>false])->assertOk()->assertJsonPath('is_ready',false);
        $this->putJson(route('admin.hourly-maids.availability',4),['is_suspended'=>true])->assertNotFound();
        $this->putJson($availability,[])->assertJsonValidationErrors('is_suspended');

        DB::table('maids')->insert(['id'=>6,'name'=>'ShelterRentalWorker','rental_mode'=>'regular','rental_source'=>'inside','arrival_date'=>'2026-09-17','status'=>'available']);
        DB::table('maid_jobs')->insert(['maid_id'=>6,'job_type_id'=>2]);
        DB::table('contracts')->insert(['id'=>6,'maid_id'=>6,'status'=>'shelter']);
        DB::table('contract_infos')->insert(['contract_id'=>6,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
        $this->get($url)->assertOk()->assertViewHas('allocatableMaids',fn($items)=>$items->pluck('id')->all()===[6]);
        $this->postJson(route('admin.hourly-maids.assign-from-shelter'),['maid_id'=>2])->assertJsonValidationErrors('maid_id');
        $this->post(route('admin.hourly-maids.assign-from-shelter'),['maid_id'=>6])
            ->assertRedirect(route('admin.hourly-maids.show',6));
        $assigned=Maid::findOrFail(6);
        $this->assertSame('hourly',$assigned->rental_mode);
        $this->assertSame('HM-6',$assigned->hourly_username);
        $this->assertFalse($assigned->hourly_settings['account_enabled']);
        $this->assertSame('all_day',$assigned->hourly_settings['work_schedule']['sunday']['mode']);
        $this->postJson(route('admin.hourly-maids.assign-from-shelter'),['maid_id'=>6])->assertJsonValidationErrors('maid_id');
        config(['superadmin_modules.hourly_rental.default_enabled'=>false]);
        $this->get($url)->assertNotFound();
    }
}
