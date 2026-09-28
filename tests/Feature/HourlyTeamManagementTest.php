<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyTeam, HourlyDriver, HourlyTeamMember, ServiceDistrict};
use App\Services\Hourly\DriverTeamService;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};
use Tests\HourlyTestCase as TestCase;

class HourlyTeamManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.team_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'database.default'=>'team_test', 'superadmin_modules.hourly_rental.default_enabled'=>true,'activitylog.enabled'=>false]);
        Schema::create('new_settings', function(Blueprint $t){$t->id();$t->string('key');$t->text('value');$t->timestamps();});
        Schema::create('cities', function(Blueprint $t){$t->id();$t->string('name_ar');$t->string('name_en')->nullable();$t->boolean('is_active')->default(true);$t->integer('sort_order')->default(0);$t->decimal('latitude')->nullable();$t->decimal('longitude')->nullable();});
        Schema::create('admins', function(Blueprint $t){ $t->softDeletes();$t->id();$t->string('name');$t->string('email');});
        Schema::create('maids',function(Blueprint $t){ $t->boolean('has_driving_license')->nullable(); $t->string('driving_license_file')->nullable();$t->id();$t->string('name');$t->string('rental_mode')->default('regular');$t->softDeletes();});
        Schema::create('maid_translations',function(Blueprint $t){$t->id();$t->unsignedBigInteger('maid_id');$t->string('locale');});
        Schema::create('job_types',function(Blueprint $t){$t->id();});
        Schema::create('job_types_translations',function(Blueprint $t){$t->id();$t->unsignedBigInteger('job_type_id');$t->string('locale');$t->string('title');});
        Schema::create('maid_jobs',function(Blueprint $t){$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('job_type_id');});
        Schema::create('countries', function(Blueprint $t){$t->id();$t->string('code');});
        Schema::table('cities', function(Blueprint $t){$t->unsignedBigInteger('country_id')->default(1);});
        Schema::table('cities', function(Blueprint $t){$t->timestamps();});
        DB::table('countries')->insert([['id'=>1,'code'=>'SA'],['id'=>2,'code'=>'AE']]);
        require_once base_path('database/migrations/2026_09_16_000001_create_hourly_teams_tables.php');
        (new \CreateHourlyTeamsTables)->up();
        Schema::create('shelter_places', function(Blueprint $t){$t->id();$t->string('city')->nullable();$t->unsignedInteger('sort_order')->default(0);$t->timestamps();});
        Schema::create('shelter_place_translations', function(Blueprint $t){$t->id();$t->unsignedBigInteger('shelter_place_id');$t->string('locale');$t->string('title');$t->unique(['shelter_place_id','locale']);});
        (require base_path('database/migrations/2026_09_25_000002_add_start_locations_to_hourly_teams_and_shelters.php'))->up();
        (require base_path('database/migrations/2026_09_17_000001_create_hourly_drivers_table.php'))->up();
        (require base_path('database/migrations/2026_09_17_000002_extend_hourly_team_management.php'))->up();
        (require base_path('database/migrations/2026_09_17_000003_add_hourly_district_geometry.php'))->up();
        (require base_path('database/migrations/2026_09_17_000004_extend_hourly_district_details.php'))->up();
        (require base_path('database/migrations/2026_09_17_000005_create_hourly_team_driver_leaders.php'))->up();
        DB::table('cities')->insert([['id'=>1,'name_ar'=>'الرياض'],['id'=>2,'name_ar'=>'جدة']]);
        DB::table('admins')->insert(['id'=>1,'name'=>'مشرف','email'=>'leader@example.test']);
        HourlyRentalSettings::flush(); HourlyRentalSettings::persist(['is_enabled'=>true]);
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class,\App\Http\Middleware\EnsureAdminRoutePermission::class,\App\Http\Middleware\Admin::class]);
        app('view')->getFinder()->prependLocation(base_path('tests/Fixtures/hourly-views'));
    }

    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }
    private function team(string $name='الفريق'): HourlyTeam { return HourlyTeam::create(['name'=>$name,'is_active'=>true]); }
    private function driver(): HourlyDriver { return HourlyDriver::create(['name'=>'سائق','employee_number'=>'001','username'=>'DR-TEST','gender'=>'male','phone'=>'0500000000','vehicle_capacity'=>12,'is_active'=>true,'work_schedule'=>[]]); }

    public function test_create_with_name_only_and_edit_preserves_existing_members_and_geometry(): void
    {
        $this->post(route('admin.hourly-teams.store'),['name'=>'الرياض'])->assertRedirect();
        $team=HourlyTeam::firstOrFail(); $this->assertNull($team->shelter_place_id);
        $member=$team->members()->create(['member_type'=>'maid','member_id'=>100,'role'=>'primary']);
        $this->put(route('admin.hourly-teams.update',$team),['name'=>'الاسم الجديد','description'=>'الوصف'])->assertRedirect();
        $this->assertNull($member->fresh()->left_at);
        $this->assertSame('الاسم الجديد',$team->fresh()->name);
        $this->get(route('admin.hourly-teams.create'))->assertOk()->assertSee('اسم الفريق');
    }

    public function test_team_map_lists_only_cities_with_visible_teams_and_makes_them_searchable(): void
    {
        DB::table('cities')->insert(['id'=>3,'name_ar'=>'المدينة بلا فرق','is_active'=>true]);
        $this->team('فريق الرياض')->update(['city_id'=>1]);
        $this->team('فريق جدة المتوقف')->update(['city_id'=>2,'is_active'=>false]);

        $this->get(route('admin.hourly-teams.map'))
            ->assertOk()
            ->assertViewHas('cities',fn ($cities)=>$cities->modelKeys()===[1])
            ->assertSee('data-placeholder="ابحث عن مدينة…"',false)
            ->assertSee('minimumResultsForSearch: 0',false);

        $this->get(route('admin.hourly-teams.map',['include_inactive'=>1]))
            ->assertOk()
            ->assertViewHas('cities',fn ($cities)=>$cities->modelKeys()===[1,2]);
    }

    public function test_teams_list_adds_and_edits_teams_through_modal(): void
    {
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $index=route('admin.hourly-teams.index');
        $this->get($index)->assertOk()->assertSee('id="teamModal"',false)->assertSee('data-bs-target="#teamModal"',false)->assertSee('reopen: false',false);

        // إضافة فاشلة تعيد فتح المودال بالبيانات المدخلة، والناجحة تنتقل إلى إدارة الفريق
        $this->from($index)->post(route('admin.hourly-teams.store'),['_form'=>'team-create','name'=>'','description'=>'وصف مؤقت','is_active'=>1])->assertRedirect($index)->assertSessionHasErrors('name');
        $this->get($index)->assertSee('reopen: true',false)->assertSee('وصف مؤقت');
        $this->post(route('admin.hourly-teams.store'),['_form'=>'team-create','name'=>'فريق المودال','is_active'=>0])->assertRedirect();
        $team=HourlyTeam::where('name','فريق المودال')->firstOrFail();
        $this->assertFalse((bool)$team->is_active);

        // زر التعديل يحمل بيانات الفريق، والتعديل من المودال يعود إلى القائمة
        $this->get($index)->assertSee(e(json_encode(route('admin.hourly-teams.update',$team))),false);
        $this->from($index)->put(route('admin.hourly-teams.update',$team),['_form'=>'team-edit','_record_id'=>$team->id,'name'=>'','is_active'=>1])->assertSessionHasErrors('name');
        $this->get($index)->assertSee('action="'.route('admin.hourly-teams.update',$team).'"',false)->assertSee('تعديل الفريق');
        $this->put(route('admin.hourly-teams.update',$team),['_form'=>'team-edit','_record_id'=>$team->id,'name'=>'فريق معدّل','is_active'=>1])->assertRedirect($index);
        $this->assertSame('فريق معدّل',$team->fresh()->name);
        // صفحة التعديل الكاملة تبقى كما هي
        $this->put(route('admin.hourly-teams.update',$team),['name'=>'فريق معدّل','is_active'=>1])->assertRedirect(route('admin.hourly-teams.edit',$team));
    }

    public function test_driver_primary_transfer_secondary_and_history(): void
    {
        $driver=$this->driver(); $a=$this->team('A');$b=$this->team('B');$c=$this->team('C');
        $service=app(DriverTeamService::class);
        $service->sync($driver,$a->id,[$b->id]);
        $this->post(route('admin.hourly-teams.drivers',$c),['driver_ids'=>[$driver->id],'role'=>'primary'])->assertRedirect();
        $this->assertSame($c->id,(int)$driver->teamMemberships()->where('role','primary')->value('hourly_team_id'));
        $this->assertTrue($driver->teamMemberships()->where('hourly_team_id',$b->id)->exists());
        $this->assertNotNull(HourlyTeamMember::where('hourly_team_id',$a->id)->first()->left_at);
        $service->sync($driver,$c->id,[$b->id]);
        $this->assertSame(2,$driver->teamMemberships()->count());
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->sync($driver,$c->id,[$c->id]);
    }

    public function test_reject_inactive_team_and_scope_membership_actions(): void
    {
        $driver=$this->driver();$a=$this->team();$b=$this->team('other');
        app(DriverTeamService::class)->sync($driver,$a->id,[]);
        $member=$driver->teamMemberships()->first();
        $this->putJson(route('admin.hourly-teams.member',[$b,$member->id]),['action'=>'remove'])->assertNotFound();
        $this->put(route('admin.hourly-teams.member',[$a,$member->id]),['action'=>'deactivate'])->assertRedirect();
        $this->assertFalse($member->fresh()->is_active);$this->assertTrue($driver->fresh()->is_active);
        $b->update(['is_active'=>false]);
        $this->postJson(route('admin.hourly-teams.drivers',$b),['driver_ids'=>[$driver->id],'role'=>'primary'])->assertUnprocessable();
    }

    public function test_district_city_validation_and_team_scoped_bulk_actions(): void
    {
        $a=$this->team();$b=$this->team('other');
        $d=ServiceDistrict::create(['name'=>'النرجس','city_id'=>1]);
        $this->postJson(route('admin.hourly-teams.districts',$a),['city_id'=>2,'district_ids'=>[$d->id]])->assertUnprocessable();
        foreach([$a,$b] as $team)$this->post(route('admin.hourly-teams.districts',$team),['city_id'=>1,'district_ids'=>[$d->id]])->assertRedirect();
        $this->put(route('admin.hourly-teams.district-action',$a),['action'=>'deactivate'])->assertRedirect();
        $this->assertEquals(0,$a->districts()->first()->pivot->is_active);
        $this->assertEquals(1,$b->districts()->first()->pivot->is_active);
        $this->put(route('admin.hourly-teams.district-action',$a),['action'=>'remove'])->assertRedirect();
        $this->assertSame(0,$a->districts()->count());$this->assertSame(1,ServiceDistrict::count());
        $this->assertSame(1,$b->districts()->count());
    }

    public function test_management_saves_return_json_without_redirecting_and_validation_keeps_data(): void
    {
        $team=$this->team();
        $this->putJson(route('admin.hourly-teams.location',$team),['base_lat'=>24.7,'base_lng'=>46.7])
            ->assertOk()->assertJsonPath('tab','location')->assertJsonPath('message','تم حفظ التغييرات.');
        $this->putJson(route('admin.hourly-teams.location',$team),['base_lat'=>95,'base_lng'=>46.7])
            ->assertUnprocessable()->assertJsonValidationErrors('base_lat');
        $this->assertSame(24.7,$team->fresh()->base_lat);
        $district=ServiceDistrict::create(['name'=>'النرجس','city_id'=>1]);
        $this->postJson(route('admin.hourly-teams.districts',$team),['city_id'=>1,'district_ids'=>[$district->id]])
            ->assertOk()->assertJsonPath('tab','areas');
        $this->assertSame(1,$team->districts()->count());
        $this->putJson(route('admin.hourly-teams.district-action',$team),['action'=>'remove','district_id'=>$district->id])
            ->assertOk()->assertJsonPath('tab','areas');
        $this->assertSame(0,$team->districts()->count());
    }

    public function test_start_location_can_use_a_custom_point_or_a_shelter_headquarters(): void
    {
        $team=$this->team();
        $shelterId=DB::table('shelter_places')->insertGetId([
            'city'=>'الرياض','latitude'=>24.8123456,'longitude'=>46.7123456,'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('shelter_place_translations')->insert(['shelter_place_id'=>$shelterId,'locale'=>'ar','title'=>'مقر الرياض']);

        $this->putJson(route('admin.hourly-teams.location',$team),[
            'base_location_type'=>'shelter','base_shelter_place_id'=>$shelterId,
        ])->assertOk()->assertJsonPath('tab','location');

        $team->refresh();
        $this->assertSame('shelter',$team->base_location_type);
        $this->assertSame($shelterId,(int)$team->base_shelter_place_id);
        $this->assertSame(24.8123456,$team->base_lat);
        $this->assertSame(46.7123456,$team->base_lng);

        $withoutLocation=DB::table('shelter_places')->insertGetId(['city'=>'جدة','created_at'=>now(),'updated_at'=>now()]);
        $this->putJson(route('admin.hourly-teams.location',$team),[
            'base_location_type'=>'shelter','base_shelter_place_id'=>$withoutLocation,
        ])->assertUnprocessable()->assertJsonValidationErrors('base_shelter_place_id');
        $this->assertSame($shelterId,(int)$team->fresh()->base_shelter_place_id);

        $this->putJson(route('admin.hourly-teams.location',$team),[
            'base_location_type'=>'custom','base_lat'=>24.7,'base_lng'=>46.7,
        ])->assertOk();
        $team->refresh();
        $this->assertSame('custom',$team->base_location_type);
        $this->assertNull($team->base_shelter_place_id);
        $this->assertSame(24.7,$team->base_lat);
        $this->assertSame(46.7,$team->base_lng);
    }

    public function test_team_district_picker_only_accepts_active_saudi_cities(): void
    {
        $team=$this->team();
        DB::table('countries')->insert(['id'=>99,'code'=>'AE']);
        DB::table('cities')->insert([
            ['id'=>98,'country_id'=>99,'name_ar'=>'Dubai','is_active'=>true],
            ['id'=>99,'country_id'=>1,'name_ar'=>'Inactive city','is_active'=>false],
        ]);
        $district=ServiceDistrict::create(['name'=>'Foreign district','city_id'=>98]);
        $this->get(route('admin.hourly-teams.manage',$team))->assertOk()
            ->assertViewHas('cities',fn ($cities)=>!$cities->contains('id',98) && !$cities->contains('id',99) && $cities->contains('id',1))
            ->assertViewHas('districts',fn ($districts)=>!$districts->contains('id',$district->id));
        $this->postJson(route('admin.hourly-teams.districts',$team),['city_id'=>98,'district_ids'=>[$district->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('city_id');
        $this->assertSame(0,$team->districts()->count());
    }

    public function test_location_leaders_and_render_management_pages(): void
    {
        $team=$this->team();
        $driver=$this->driver();
        $this->putJson(route('admin.hourly-teams.location',$team),['base_lat'=>91,'base_lng'=>46])->assertUnprocessable();
        $this->put(route('admin.hourly-teams.location',$team),['base_lat'=>24.7,'base_lng'=>46.7])->assertRedirect();
        $this->assertSame(24.7,$team->fresh()->base_lat);
        $this->put(route('admin.hourly-teams.leaders',$team),['leader_ids'=>[$driver->id]])->assertRedirect();
        $this->assertSame(1,$team->leaders()->count());
        $this->assertInstanceOf(HourlyDriver::class,$team->leaders()->first());
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true); auth('admin')->setUser($admin);
        $this->get(route('admin.hourly-teams.manage',$team))->assertOk()->assertSee('تعيين المناطق')->assertSee('حفظ القادة');
        $this->get(route('admin.hourly-teams.index'))->assertOk()->assertSee('إدارة الفريق');
        $this->put(route('admin.hourly-teams.leaders',$team),[])->assertRedirect();
        $this->assertSame(0,$team->leaders()->count());
    }

    public function test_maid_picker_excludes_current_members_even_when_inactive(): void
    {
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $team=$this->team();$other=$this->team('Other');
        DB::table('job_types')->insert(['id'=>2]);
        DB::table('job_types_translations')->insert(['job_type_id'=>2,'locale'=>'ar','title'=>'تأجير']);
        foreach ([1,2,3,4] as $id) {
            DB::table('maids')->insert(['id'=>$id,'name'=>'Maid '.$id,'rental_mode'=>'hourly']);
            DB::table('maid_jobs')->insert(['maid_id'=>$id,'job_type_id'=>2]);
        }
        $active=$team->members()->create(['member_type'=>'maid','member_id'=>1,'role'=>'primary','is_active'=>true]);
        $team->members()->create(['member_type'=>'maid','member_id'=>2,'role'=>'secondary','is_active'=>false]);
        $team->members()->create(['member_type'=>'maid','member_id'=>3,'role'=>'primary','left_at'=>now()->toDateString()]);
        $other->members()->create(['member_type'=>'maid','member_id'=>4,'role'=>'primary']);
        $this->get(route('admin.hourly-teams.manage',$team))->assertOk()
            ->assertViewHas('maids',fn($maids)=>$maids->pluck('id')->all()===[3,4]);
        $active->update(['left_at'=>now()->toDateString()]);
        $this->get(route('admin.hourly-teams.manage',$team))->assertOk()
            ->assertViewHas('maids',fn($maids)=>$maids->pluck('id')->all()===[1,3,4]);
    }

    public function test_leaders_reject_admin_only_ids_and_deleted_drivers(): void
    {
        $team=$this->team();
        DB::table('admins')->insert(['id'=>999,'name'=>'Admin only','email'=>'admin999@example.test']);
        $this->putJson(route('admin.hourly-teams.leaders',$team),['leader_ids'=>[999]])->assertJsonValidationErrors('leader_ids.0');
        $driver=$this->driver();
        $this->put(route('admin.hourly-teams.leaders',$team),['leader_ids'=>[$driver->id]])->assertRedirect();
        $this->assertSame($driver->id,$team->leaders()->first()->id);
        $driver->delete();
        $this->assertSame(0,$team->leaders()->count());
        $this->putJson(route('admin.hourly-teams.leaders',$team),['leader_ids'=>[$driver->id]])->assertJsonValidationErrors('leader_ids.0');
        $driver->restore();
        $this->assertSame($driver->id,$team->leaders()->first()->id);
        Schema::enableForeignKeyConstraints();
        $driver->forceDelete();
        $this->assertDatabaseMissing('hourly_team_driver_leaders',['driver_id'=>$driver->id]);
    }

    public function test_team_location_search_accepts_addresses_and_only_saves_on_confirmation(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        $team=$this->team();
        \Illuminate\Support\Facades\Http::fake([
            '*'=>\Illuminate\Support\Facades\Http::response([
                ['lat'=>'24.83','lon'=>'46.64','display_name'=>'حي النرجس، الرياض','address'=>['country_code'=>'sa']],
                ['lat'=>'25','lon'=>'55','display_name'=>'نتيجة خارج المملكة','address'=>['country_code'=>'ae']],
            ]),
        ]);
        $url=route('admin.hourly-teams.locate',$team);
        $this->postJson($url,['name'=>'حي النرجس، الرياض'])->assertOk()->assertJsonCount(1,'results')->assertJsonPath('results.0.latitude',24.83);
        \Illuminate\Support\Facades\Http::assertSent(fn($request)=>$request['q']==='حي النرجس، الرياض, Saudi Arabia' && $request['countrycodes']==='sa' && !isset($request['city']));
        $this->assertNull($team->fresh()->base_lat);
        $this->postJson($url,['name'=>''])->assertJsonValidationErrors('name');
        $this->put(route('admin.hourly-teams.location',$team),['base_lat'=>24.83,'base_lng'=>46.64])->assertRedirect();
        $this->assertSame(24.83,$team->fresh()->base_lat);
        $this->assertSame(['hourly_teams_edit'],config('hourly_permissions')['admin.hourly-teams.locate']);
    }

    public function test_driver_form_saves_teams_and_rolls_back_invalid_assignment(): void
    {
        $driver=$this->driver();$team=$this->team();
        $data=['name'=>'سائق معدل','employee_number'=>'001','gender'=>'male','phone'=>'0500000000','vehicle_capacity'=>12,'is_active'=>1,'account_enabled'=>0,'login_access'=>'both','work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'off']),'primary_team_id'=>$team->id,'secondary_team_ids'=>[]];
        $this->put(route('admin.hourly-drivers.update',$driver),$data)->assertRedirect();
        $this->assertSame($team->id,(int)$driver->teamMemberships()->value('hourly_team_id'));
        $data['primary_team_id']=9999;$data['name']='يجب عدم حفظ الاسم';
        $this->putJson(route('admin.hourly-drivers.update',$driver),$data)->assertUnprocessable();
        $this->assertSame('سائق معدل',$driver->fresh()->name);
        $data['primary_team_id']=null;
        $this->put(route('admin.hourly-drivers.update',$driver),$data)->assertRedirect();
        $this->assertSame(0,$driver->teamMemberships()->count());
    }

    public function test_management_permissions_and_feature_gate(): void
    {
        foreach(app('router')->getRoutes() as $route) {
            if(!str_starts_with($route->getName() ?? '', 'admin.hourly-teams.')) continue;
            foreach(['admin','admin.route.permission','feature.hourly_rental'] as $middleware) $this->assertContains($middleware,$route->gatherMiddleware());
            $this->assertArrayHasKey($route->getName(), config('hourly_permissions'));
        }
        $team=$this->team(); HourlyRentalSettings::persist(['is_enabled'=>false]);
        $this->get(route('admin.hourly-teams.manage',$team))->assertNotFound();
        $this->postJson(route('admin.hourly-teams.drivers',$team),[])->assertNotFound();
    }

    public function test_standalone_district_catalog_and_linked_region_protection(): void
    {
        $this->post(route('admin.hourly-districts.store'), ['city_id'=>1,'name'=>'النرجس'])->assertRedirect();
        $district=ServiceDistrict::firstOrFail();
        $this->postJson(route('admin.hourly-districts.store'), ['city_id'=>1,'name'=>'النرجس'])->assertJsonValidationErrors('name');
        $this->put(route('admin.hourly-districts.update',$district),['city_id'=>1,'name'=>'الياسمين'])->assertRedirect();
        $team=$this->team();$team->districts()->attach($district->id);
        $this->putJson(route('admin.hourly-districts.update',$district),['city_id'=>2,'name'=>'الياسمين'])->assertJsonValidationErrors('city_id');
        $this->delete(route('admin.hourly-districts.destroy',$district))->assertSessionHasErrors('district');
        $this->assertNotNull($district->fresh());
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $this->get(route('admin.hourly-districts.index',['city_id'=>1]))->assertOk()->assertSee('الياسمين')->assertSee('إضافة منطقة جديدة');
        $this->get(route('admin.hourly-districts.index',['city_id'=>2]))->assertOk()->assertDontSee('الياسمين');
        $team->districts()->detach();
        $this->delete(route('admin.hourly-districts.destroy',$district))->assertRedirect();
        $this->assertNull($district->fresh());
        foreach(app('router')->getRoutes() as $route) {
            if(!str_starts_with($route->getName() ?? '', 'admin.hourly-districts.')) continue;
            foreach(['admin','admin.route.permission','feature.hourly_rental'] as $middleware) $this->assertContains($middleware,$route->gatherMiddleware());
            $this->assertArrayHasKey($route->getName(),config('hourly_permissions'));
        }
        HourlyRentalSettings::persist(['is_enabled'=>false]);
        $this->get(route('admin.hourly-districts.index'))->assertNotFound();
    }

    public function test_hourly_saudi_cities_and_city_district_page(): void
    {
        DB::table('cities')->insert(['id'=>3,'name_ar'=>'دبي','country_id'=>2]);
        $riyadh=ServiceDistrict::create(['city_id'=>1,'name'=>'حي الرياض']);
        ServiceDistrict::create(['city_id'=>2,'name'=>'حي جدة']);
        $this->get(route('admin.hourly-cities.index'))->assertOk()->assertSee('الرياض')->assertSee('جدة')->assertDontSee('دبي');
        $this->get(route('admin.hourly-cities.districts',1))->assertOk()->assertSee('حي الرياض')->assertDontSee('حي جدة')->assertSee('مناطق وأحياء الرياض');
        $this->get(route('admin.hourly-cities.districts',3))->assertNotFound();
        $this->postJson(route('admin.hourly-districts.store'),['city_id'=>3,'name'=>'منطقة خارج المملكة'])->assertJsonValidationErrors('city_id');
        $this->from(route('admin.hourly-cities.districts',1))->post(route('admin.hourly-districts.store'),['city_id'=>1,'name'=>'حي جديد'])->assertRedirect(route('admin.hourly-cities.districts',1));
        $this->assertSame(2,ServiceDistrict::where('city_id',1)->count());
        HourlyRentalSettings::persist(['is_enabled'=>false]);
        $this->get(route('admin.hourly-cities.index'))->assertNotFound();
    }

    public function test_city_create_edit_status_sort_and_validation(): void
    {
        $data=['name_ar'=>'الخبر','name_en'=>'Khobar','sort_order'=>3,'is_active'=>1,'latitude'=>26.2,'longitude'=>50.2,'country_id'=>2];
        $this->post(route('admin.hourly-cities.store'),$data)->assertRedirect(route('admin.hourly-cities.index'));
        $city=\App\Models\City::where('name_en','Khobar')->firstOrFail();$this->assertSame(1,(int)$city->country_id);
        $this->postJson(route('admin.hourly-cities.store'),$data)->assertJsonValidationErrors(['name_ar','name_en']);
        $data['latitude']=100;
        $this->putJson(route('admin.hourly-cities.update',$city),$data)->assertJsonValidationErrors('latitude');
        $data['latitude']=null;
        $this->putJson(route('admin.hourly-cities.update',$city),$data)->assertJsonValidationErrors('latitude');
        $data['latitude']=26.1;$data['name_ar']='مدينة الخبر';
        $this->put(route('admin.hourly-cities.update',$city),$data)->assertRedirect();
        $this->putJson(route('admin.hourly-cities.status',$city),['is_active'=>false])->assertOk()->assertJsonPath('is_active',false);
        $this->assertSame('مدينة الخبر',$city->fresh()->name_ar);
        $this->getJson(route('admin.hourly-cities.index',['sort'=>'password']))->assertUnprocessable();
        $this->get(route('admin.hourly-cities.index',['sort'=>'name_en','direction'=>'desc','per_page'=>10]))->assertOk()->assertSee('Khobar');
        $this->get(route('admin.hourly-cities.create'))->assertOk()->assertSee('Translate All');
        $this->get(route('admin.hourly-cities.edit',$city))->assertOk()->assertSee('مدينة الخبر');
    }

    public function test_city_list_uses_bootstrap_pagination_and_keeps_filters(): void
    {
        foreach (range(3, 13) as $id) {
            DB::table('cities')->insert([
                'id'=>$id,
                'name_ar'=>'مدينة '.$id,
                'name_en'=>'City '.$id,
                'country_id'=>1,
            ]);
        }

        $response=$this->get(route('admin.hourly-cities.index',[
            'search'=>'مدينة',
            'sort'=>'name_ar',
            'direction'=>'asc',
            'per_page'=>10,
        ]));

        $response->assertOk()
            ->assertSee('class="page-item active"',false)
            ->assertSee('class="page-link"',false)
            ->assertSee('sort=name_ar&amp;direction=asc&amp;per_page=10&amp;page=2',false);
    }

    public function test_city_list_filters_and_bulk_status_are_scoped_to_saudi_cities(): void
    {
        DB::table('cities')->where('id',1)->update(['latitude'=>24.7,'longitude'=>46.6]);
        DB::table('cities')->where('id',2)->update(['is_active'=>false]);
        DB::table('cities')->insert(['id'=>3,'name_ar'=>'دبي','name_en'=>'Dubai','country_id'=>2,'is_active'=>true]);
        ServiceDistrict::create(['city_id'=>1,'name'=>'مغطاة','polygon'=>[[24,46],[25,46],[25,47],[24,47]]]);
        ServiceDistrict::create(['city_id'=>1,'name'=>'غير مغطاة']);

        $this->get(route('admin.hourly-cities.index',['status'=>'active']))
            ->assertOk()->assertSee('الرياض')->assertDontSee('جدة')->assertDontSee('دبي');
        $this->get(route('admin.hourly-cities.index',['districts'=>'without']))
            ->assertOk()->assertSee('جدة')->assertDontSee('الرياض');
        $this->get(route('admin.hourly-cities.index',['coverage'=>'partial']))
            ->assertOk()->assertSee('الرياض')->assertDontSee('جدة');
        $this->get(route('admin.hourly-cities.index',['location'=>'missing']))
            ->assertOk()->assertSee('جدة')->assertDontSee('الرياض');

        $index=route('admin.hourly-cities.index',['status'=>'inactive']);
        $this->from($index)->put(route('admin.hourly-cities.bulk-status'),['city_ids'=>[1,2],'is_active'=>1])
            ->assertRedirect($index);
        $this->assertTrue((bool)\App\Models\City::findOrFail(1)->is_active);
        $this->assertTrue((bool)\App\Models\City::findOrFail(2)->is_active);

        $this->putJson(route('admin.hourly-cities.bulk-status'),['city_ids'=>[1,3],'is_active'=>0])
            ->assertJsonValidationErrors('city_ids');
        $this->assertTrue((bool)\App\Models\City::findOrFail(1)->is_active);

        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $this->get(route('admin.hourly-cities.index'))->assertOk()
            ->assertSee('id="city-bulk-form"',false)->assertSee('id="city-select-all"',false)
            ->assertSee('تفعيل المدن المحددة')->assertSee('تغطية الحدود');
    }

    public function test_district_polygon_validation_coverage_and_scoping(): void
    {
        $city=\App\Models\City::findOrFail(1);
        $a=ServiceDistrict::create(['city_id'=>1,'name'=>'أول']);
        ServiceDistrict::create(['city_id'=>1,'name'=>'ثان','is_active'=>false]);
        $url=route('admin.hourly-districts.polygon',[$city,$a]);
        $ring=[[24,46],[25,46],[25,47],[24,47]];
        $this->putJson($url,['polygon'=>$ring])->assertOk();
        $this->get(route('admin.hourly-cities.index'))->assertOk()->assertSee('50%');
        $this->get(route('admin.hourly-cities.polygons',$city))->assertOk()->assertSee('حدود مناطق');
        foreach([[[24,46],[25,47],[24,47],[25,46]],[[24,46],[24,46],[25,47]],[[24,46],[25,47],[26,48]],[[91,46],[25,46],[25,47]]] as $invalid) {
            $this->putJson($url,['polygon'=>$invalid])->assertJsonValidationErrors('polygon');
        }
        $this->putJson(route('admin.hourly-districts.polygon',[2,$a]),['polygon'=>$ring])->assertNotFound();
        $this->assertEquals($ring,$a->fresh()->polygon);
        $this->putJson($url,[])->assertJsonValidationErrors('polygon');
        $this->putJson($url,['polygon'=>null])->assertOk()->assertJsonPath('polygon',null);
        $this->put(route('admin.hourly-districts.status',$a),['is_active'=>false])->assertRedirect();
        $this->assertFalse($a->fresh()->is_active);
    }

    public function test_translation_and_geocoding_success_cache_and_failure(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        \Illuminate\Support\Facades\Http::fake([
            'api.mymemory.translated.net/*'=>\Illuminate\Support\Facades\Http::response(['responseStatus'=>200,'responseData'=>['translatedText'=>'الخبر']],200),
            'nominatim.openstreetmap.org/*'=>\Illuminate\Support\Facades\Http::response([['lat'=>'26.2','lon'=>'50.2','display_name'=>'الخبر، السعودية','address'=>['country_code'=>'sa']]],200),
        ]);
        for($i=0;$i<2;$i++)$this->postJson(route('admin.hourly-cities.translate'),['text'=>'Khobar','source'=>'en','target'=>'ar'])->assertOk()->assertJsonPath('text','الخبر');
        $this->postJson(route('admin.hourly-cities.locate'),['name'=>'Khobar'])->assertOk()->assertJsonPath('results.0.latitude',26.2);
        \Illuminate\Support\Facades\Http::assertSentCount(2);
        \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory());
        \Illuminate\Support\Facades\Http::fake(['*'=>\Illuminate\Support\Facades\Http::response([],503)]);
        $this->postJson(route('admin.hourly-cities.translate'),['text'=>'Unknown','source'=>'en','target'=>'ar'])->assertJsonValidationErrors('text');
        \Illuminate\Support\Facades\Cache::forget('hourly-nominatim-request');
        $this->postJson(route('admin.hourly-cities.locate'),['name'=>'Unknown'])->assertJsonValidationErrors('name');
    }

    public function test_district_details_team_assignment_and_edit_preserve_pivot_state(): void
    {
        $a=$this->team('A');$b=$this->team('B');
        $data=['city_id'=>1,'name'=>'حي النرجس','name_en'=>'Al Narjis','latitude'=>24.8,'longitude'=>46.7,'is_active'=>1,'teams_submitted'=>1,'team_ids'=>[$a->id,$b->id]];
        $this->post(route('admin.hourly-districts.store'),$data)->assertRedirect(route('admin.hourly-cities.districts',1));
        $district=ServiceDistrict::firstOrFail();
        $this->assertSame('Al Narjis',$district->name_en);$this->assertStringStartsWith('al-narjis-',$district->slug);
        $this->assertSame(2,$district->teams()->count());
        $district->teams()->updateExistingPivot($a->id,['is_active'=>false]);
        $data['name']='النرجس الجديد';
        $this->put(route('admin.hourly-districts.update',$district),$data)->assertRedirect();
        $this->assertEquals(0,$district->teams()->find($a->id)->pivot->is_active);
        $this->assertEquals(24.8,$district->fresh()->latitude);
        $data['team_ids']=[];
        $this->put(route('admin.hourly-districts.update',$district),$data)->assertRedirect();
        $this->assertSame(0,$district->teams()->count());
        $this->putJson(route('admin.hourly-districts.status',$district),['is_active'=>false])->assertOk()->assertJsonPath('is_active',false);
        $this->get(route('admin.hourly-districts.create',['city_id'=>1]))->assertOk()->assertSee('ربط الفرق')->assertDontSee('Service &amp; Team');
        $this->get(route('admin.hourly-districts.edit',$district))->assertOk()->assertSee('Al Narjis');
    }

    public function test_district_rejects_invalid_coordinates_and_teams_without_partial_save(): void
    {
        $inactive=$this->team();$inactive->update(['is_active'=>false]);
        $data=['city_id'=>1,'name'=>'حي','name_en'=>'District','teams_submitted'=>1,'team_ids'=>[$inactive->id]];
        $this->postJson(route('admin.hourly-districts.store'),$data)->assertJsonValidationErrors('team_ids');
        $this->assertSame(0,ServiceDistrict::count());
        $data['team_ids']=[];$data['latitude']=24.5;
        $this->postJson(route('admin.hourly-districts.store'),$data)->assertJsonValidationErrors('longitude');
        $data['longitude']=190;
        $this->postJson(route('admin.hourly-districts.store'),$data)->assertJsonValidationErrors('longitude');
        $data['longitude']=46.7;
        $this->postJson(route('admin.hourly-districts.store'),$data)->assertRedirect();
        $data['name']='اسم مختلف';
        $this->postJson(route('admin.hourly-districts.store'),$data)->assertJsonValidationErrors('name_en');
        $this->assertSame(1,ServiceDistrict::count());
    }

    public function test_city_and_district_lists_add_through_modal_and_reopen_it_on_errors(): void
    {
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $this->team('فريق المودال');

        $this->get(route('admin.hourly-districts.index',['city_id'=>1]))->assertOk()
            ->assertSee('id="districtModal"',false)->assertSee('name="_form" value="district-create"',false)->assertSee('فريق المودال')
            ->assertSee('reopen: false',false);
        $this->from(route('admin.hourly-cities.districts',1))
            ->post(route('admin.hourly-districts.store'),['_form'=>'district-create','city_id'=>1,'name'=>'','is_active'=>1,'teams_submitted'=>1])
            ->assertRedirect(route('admin.hourly-cities.districts',1))->assertSessionHasErrors('name');
        $this->get(route('admin.hourly-cities.districts',1))->assertSee('reopen: true',false);
        $this->post(route('admin.hourly-districts.store'),['_form'=>'district-create','city_id'=>1,'name'=>'حي من المودال','is_active'=>1,'teams_submitted'=>1])
            ->assertRedirect(route('admin.hourly-cities.districts',1));
        $this->assertTrue(ServiceDistrict::where('name','حي من المودال')->exists());

        $this->get(route('admin.hourly-cities.index'))->assertOk()
            ->assertSee('id="cityModal"',false)->assertSee('Translate All')
            ->assertSee('reopen: false',false);
        $this->from(route('admin.hourly-cities.index'))
            ->post(route('admin.hourly-cities.store'),['_form'=>'city-create','name_ar'=>'','name_en'=>'Abha','sort_order'=>1,'is_active'=>1])
            ->assertRedirect(route('admin.hourly-cities.index'))->assertSessionHasErrors('name_ar');
        $this->get(route('admin.hourly-cities.index'))->assertSee('reopen: true',false)->assertSee('value="Abha"',false);
        $this->post(route('admin.hourly-cities.store'),['_form'=>'city-create','name_ar'=>'أبها','name_en'=>'Abha','sort_order'=>1,'is_active'=>0])
            ->assertRedirect(route('admin.hourly-cities.index'));
        $this->assertFalse((bool)\App\Models\City::where('name_en','Abha')->value('is_active'));
    }

    public function test_city_and_district_lists_edit_through_the_same_modal(): void
    {
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $stopped=$this->team('فريق متوقف');
        $district=ServiceDistrict::create(['city_id'=>1,'name'=>'الملقا','name_en'=>'Al Malqa','is_active'=>true]);
        $district->teams()->attach($stopped->id);
        $stopped->update(['is_active'=>false]);

        // زر التعديل يحمل بيانات الصف ورابط التحديث، والفريق المتوقف المرتبط يبقى ضمن الخيارات.
        $this->get(route('admin.hourly-cities.districts',1))->assertOk()
            ->assertSee('data-record=',false)->assertSee(e(json_encode(route('admin.hourly-districts.update',$district))),false)
            ->assertSee('فريق متوقف (متوقف)');
        $data=['_form'=>'district-edit','_record_id'=>$district->id,'city_id'=>1,'name'=>'','name_en'=>'Al Malqa','is_active'=>1,'teams_submitted'=>1,'team_ids'=>[$stopped->id]];
        $this->from(route('admin.hourly-cities.districts',1))->put(route('admin.hourly-districts.update',$district),$data)->assertSessionHasErrors('name');
        $this->get(route('admin.hourly-cities.districts',1))
            ->assertSee('action="'.route('admin.hourly-districts.update',$district).'"',false)
            ->assertSee('name="_method" value="PUT" >',false)->assertSee('تعديل المنطقة')->assertSee("reopen: true",false);
        $data['name']='الملقا الجديد';
        $this->put(route('admin.hourly-districts.update',$district),$data)->assertRedirect(route('admin.hourly-cities.districts',1));
        $this->assertSame('الملقا الجديد',$district->fresh()->name);
        $this->assertSame(1,$district->teams()->count());

        $city=\App\Models\City::findOrFail(2);
        $this->get(route('admin.hourly-cities.index'))->assertOk()->assertSee(e(json_encode(route('admin.hourly-cities.update',$city))),false)->assertSee('name="_method" value="PUT" disabled',false);
        $cityData=['_form'=>'city-edit','_record_id'=>2,'name_ar'=>'جدة','name_en'=>'','sort_order'=>4,'is_active'=>1];
        $this->from(route('admin.hourly-cities.index'))->put(route('admin.hourly-cities.update',$city),$cityData)->assertSessionHasErrors('name_en');
        $this->get(route('admin.hourly-cities.index'))->assertSee('action="'.route('admin.hourly-cities.update',$city).'"',false)->assertSee('تعديل المدينة');
        $cityData['name_en']='Jeddah';
        $this->put(route('admin.hourly-cities.update',$city),$cityData)->assertRedirect(route('admin.hourly-cities.index'));
        $this->assertSame('Jeddah',$city->fresh()->name_en);
    }

    public function test_manage_page_status_switches_toggle_membership_and_district_coverage(): void
    {
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $team=$this->team();$driver=$this->driver();
        app(DriverTeamService::class)->sync($driver,$team->id,[]);
        $member=$driver->teamMemberships()->first();
        $a=ServiceDistrict::create(['city_id'=>1,'name'=>'عرفات']);$b=ServiceDistrict::create(['city_id'=>1,'name'=>'منى']);
        $team->districts()->attach([$a->id,$b->id]);

        $this->get(route('admin.hourly-teams.manage',$team))->assertOk()
            ->assertSee('data-status-url="'.route('admin.hourly-teams.member',[$team,$member->id]).'"',false)
            ->assertSee('data-district-id="'.$a->id.'"',false)->assertDontSee('إيقاف العضوية');

        $this->putJson(route('admin.hourly-teams.member',[$team,$member->id]),['action'=>'deactivate'])->assertOk()->assertJsonPath('is_active',false);
        $this->assertFalse($member->fresh()->is_active);
        $this->putJson(route('admin.hourly-teams.member',[$team,$member->id]),['action'=>'activate'])->assertOk()->assertJsonPath('is_active',true);

        $url=route('admin.hourly-teams.district-action',$team);
        $this->putJson($url,['action'=>'deactivate','district_id'=>$a->id])->assertOk()->assertJsonPath('is_active',false);
        $this->assertEquals(0,$team->districts()->find($a->id)->pivot->is_active);
        $this->assertEquals(1,$team->districts()->find($b->id)->pivot->is_active,'التبديل يخص المنطقة المختارة فقط');
        $this->putJson($url,['action'=>'activate','district_id'=>999])->assertNotFound();
        // النماذج العادية والإجراءات الجماعية تبقى بإعادة توجيه
        $this->put($url,['action'=>'activate'])->assertRedirect();
    }

    public function test_district_list_teams_modal_lists_and_saves_district_teams(): void
    {
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $mecca=$this->team('Mecca Team');$other=$this->team('فريق آخر');$stopped=$this->team('فريق متوقف');
        $district=ServiceDistrict::create(['city_id'=>1,'name'=>'جبل النور']);
        $district->teams()->attach($mecca->id,['is_active'=>false]);
        $stopped->update(['is_active'=>false]);

        $this->get(route('admin.hourly-cities.districts',1))->assertOk()
            ->assertSee('id="teamsModal"',false)->assertSee('data-teams-count>1<',false)
            ->assertSee(e(json_encode(route('admin.hourly-districts.teams',$district))),false)
            ->assertSee(e('"name":"Mecca Team","active":true,"coverage":false'),false);

        $url=route('admin.hourly-districts.teams',$district);
        $this->putJson($url,['team_ids'=>[$mecca->id,$stopped->id]])->assertUnprocessable()->assertJsonValidationErrors('team_ids');
        $this->assertSame(1,$district->teams()->count());

        $this->putJson($url,['team_ids'=>[$mecca->id,$other->id]])->assertOk()
            ->assertJsonCount(2,'teams')->assertJsonPath('teams.0.name','Mecca Team')->assertJsonPath('teams.0.coverage',false)->assertJsonPath('teams.1.coverage',true);
        $this->putJson($url,['team_ids'=>[]])->assertOk()->assertJsonCount(0,'teams');
        $this->assertSame(0,$district->teams()->count());
        $this->putJson($url,[])->assertJsonValidationErrors('team_ids');
    }

    public function test_district_list_draw_boundary_modal_and_osm_polygon_fetch(): void
    {
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        \Illuminate\Support\Facades\Cache::flush();
        DB::table('cities')->where('id',1)->update(['name_en'=>'Riyadh']);
        $narjis=ServiceDistrict::create(['city_id'=>1,'name'=>'النرجس','name_en'=>'Al Narjis']);
        $unknown=ServiceDistrict::create(['city_id'=>1,'name'=>'حي غير معروف']);

        $this->get(route('admin.hourly-cities.districts',1))->assertOk()
            ->assertSee('id="polygonModal"',false)->assertSee('رسم الحدود')
            ->assertSee(e(json_encode(route('admin.hourly-districts.osm-polygon',$narjis))),false)
            ->assertSee(e(json_encode(route('admin.hourly-districts.polygon',[1,$narjis]))),false)
            ->assertSee('data-polygon-chip="'.$narjis->id.'"',false);

        $ring=[[46.60,24.83],[46.64,24.83],[46.64,24.86],[46.60,24.86],[46.60,24.83]];
        \Illuminate\Support\Facades\Http::fake(function ($request) use ($ring) {
            \Illuminate\Support\Facades\Cache::forget('hourly-nominatim-request');
            if (str_starts_with($request['q'],'Al Narjis')) return \Illuminate\Support\Facades\Http::response([['category'=>'boundary','type'=>'administrative','place_rank'=>20,'display_name'=>'النرجس، الرياض','geojson'=>['type'=>'Polygon','coordinates'=>[$ring]]]]);
            // نتيجة على مستوى المدينة فقط: لا تُقبل كحدود حي.
            return \Illuminate\Support\Facades\Http::response([['place_rank'=>16,'category'=>'boundary','type'=>'administrative','display_name'=>'الرياض','geojson'=>['type'=>'Polygon','coordinates'=>[$ring]]]]);
        });
        $this->getJson(route('admin.hourly-districts.osm-polygon',$narjis))->assertOk()
            ->assertJsonPath('polygon',[[24.83,46.6],[24.83,46.64],[24.86,46.64],[24.86,46.6]])->assertJsonPath('label','النرجس، الرياض');
        $this->assertNull($narjis->fresh()->polygon, 'الجلب للمراجعة فقط ولا يحفظ');
        $sent=count(\Illuminate\Support\Facades\Http::recorded());
        $this->getJson(route('admin.hourly-districts.osm-polygon',$narjis))->assertOk();
        $this->assertCount($sent,\Illuminate\Support\Facades\Http::recorded());
        $this->getJson(route('admin.hourly-districts.osm-polygon',$unknown))->assertUnprocessable()->assertJsonValidationErrors('polygon');

        $this->assertArrayHasKey('admin.hourly-districts.osm-polygon',config('hourly_permissions'));
    }

    public function test_district_boundary_normalizes_city_and_skips_landmarks(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        DB::table('cities')->where('id',1)->update(['name_ar'=>'مكة المكرمة3','name_en'=>'Mecca3']);
        $district=ServiceDistrict::create(['city_id'=>1,'name'=>'جبل النور','name_en'=>'Jabal Al Noor']);
        $ring=[[39.8,21.4],[39.9,21.4],[39.9,21.5],[39.8,21.5],[39.8,21.4]];
        \Illuminate\Support\Facades\Http::fake(function ($request) use ($ring) {
            \Illuminate\Support\Facades\Cache::forget('hourly-nominatim-request');
            $this->assertSame('جبل النور, مكة المكرمة, Saudi Arabia',$request['q']);
            return \Illuminate\Support\Facades\Http::response([
                ['place_rank'=>24,'category'=>'leisure','type'=>'park','display_name'=>'حديقة جبل النور','geojson'=>['type'=>'Polygon','coordinates'=>[$ring]]],
                ['place_rank'=>18,'category'=>'natural','type'=>'peak','geojson'=>['type'=>'Point','coordinates'=>[39.85,21.45]]],
                ['place_rank'=>20,'category'=>'boundary','type'=>'administrative','display_name'=>'حي جبل النور','geojson'=>['type'=>'Polygon','coordinates'=>[$ring]]],
            ]);
        });
        $this->getJson(route('admin.hourly-districts.osm-polygon',$district))->assertOk()->assertJsonPath('label','حي جبل النور');
        $this->assertNull($district->fresh()->polygon);
    }

    public function test_polygons_page_shows_the_city_itself_not_its_governorate_or_region(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        DB::table('cities')->where('id',1)->update(['name_en'=>'Unknown City']);
        DB::table('cities')->where('id',2)->update(['name_ar'=>'جدة2']);
        DB::table('cities')->insert(['id'=>5,'name_ar'=>'مكة المكرمة','country_id'=>1]);
        $square=fn ($x) => ['type'=>'Polygon','coordinates'=>[[[$x,20],[$x+1,20],[$x+1,21],[$x,20]]]];
        $answers=[
            'الرياض'=>[['place_rank'=>8,'lat'=>'23','lon'=>'45','geojson'=>$square(40)], ['place_rank'=>16,'lat'=>'24.71','lon'=>'46.67','geojson'=>$square(46),'boundingbox'=>['24.6','24.9','46.5','46.9']]],
            'جدة'=>[['place_rank'=>12,'lat'=>'21.5','lon'=>'39.2','geojson'=>$square(39)], ['place_rank'=>16,'lat'=>'21.55','lon'=>'39.17','geojson'=>['type'=>'Point','coordinates'=>[39.17,21.55]],'boundingbox'=>['21.39','21.71','39.01','39.33']]],
            'مكة المكرمة'=>[['place_rank'=>8,'lat'=>'21','lon'=>'41','geojson'=>$square(38)], ['place_rank'=>12,'lat'=>'21.42','lon'=>'39.82','geojson'=>$square(39)]],
        ];
        \Illuminate\Support\Facades\Http::fake(function ($request) use ($answers) {
            \Illuminate\Support\Facades\Cache::forget('hourly-nominatim-request');
            return \Illuminate\Support\Facades\Http::response($answers[$request['q']] ?? []);
        });
        $this->get(route('admin.hourly-cities.polygons',1))->assertOk()->assertSee('city-boundary-status',false)->assertSee('cities\/1\/boundary',false);

        // مضلع المدينة نفسها (لا المنطقة الإدارية)، بعد فشل الاسم الإنجليزي.
        $this->getJson(route('admin.hourly-cities.boundary',1))->assertOk()
            ->assertJsonPath('boundary.coordinates.0.0',[46,20])->assertJsonPath('center',[24.71,46.67])->assertJsonPath('bbox',[24.6,46.5,24.9,46.9]);
        // المدينة نقطة فقط: لا يُرسم مضلع المحافظة، ويُعاد نطاق المدينة؛ والرقم اللاحق "جدة2" يُتجاهل.
        $this->getJson(route('admin.hourly-cities.boundary',2))->assertOk()
            ->assertJsonPath('boundary',null)->assertJsonPath('center',[21.55,39.17])->assertJsonPath('bbox',[21.39,39.01,21.71,39.33]);
        // لا توجد المدينة نفسها: مركز المحافظة فقط، دون حدودها أو نطاقها.
        $this->getJson(route('admin.hourly-cities.boundary',5))->assertOk()
            ->assertJsonPath('boundary',null)->assertJsonPath('center',[21.42,39.82])->assertJsonPath('bbox',null);

        $sent=count(\Illuminate\Support\Facades\Http::recorded());
        $this->getJson(route('admin.hourly-cities.boundary',1))->assertOk()->assertJsonPath('center',[24.71,46.67]);
        $this->assertCount($sent,\Illuminate\Support\Facades\Http::recorded(),'النتائج تُقرأ من الذاكرة المؤقتة');

        DB::table('cities')->insert(['id'=>4,'name_ar'=>'دبي','country_id'=>2]);
        $this->getJson(route('admin.hourly-cities.boundary',4))->assertNotFound();
        $this->assertArrayHasKey('admin.hourly-cities.boundary',config('hourly_permissions'));
    }

    public function test_district_location_search_includes_selected_city(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        \Illuminate\Support\Facades\Http::fake(['*'=>\Illuminate\Support\Facades\Http::response([['lat'=>'24.8','lon'=>'46.7','display_name'=>'حي النرجس، الرياض','address'=>['country_code'=>'sa']]],200)]);
        $this->postJson(route('admin.hourly-districts.locate'),['city_id'=>1,'name'=>'النرجس'])->assertOk()->assertJsonPath('results.0.latitude',24.8);
        \Illuminate\Support\Facades\Http::assertSent(fn($request)=>$request['q']==='النرجس, الرياض, Saudi Arabia' && $request['countrycodes']==='sa');
    }
}
