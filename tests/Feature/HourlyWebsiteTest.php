<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyDriver, HourlyPackage, HourlyCategorySetting, HourlyRentalOrder};
use App\Models\ingaz\User;
use App\Services\Hourly\HourlyMoyasarGateway;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema, Storage};
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\HourlyTestCase as TestCase;

class HourlyWebsiteTest extends TestCase
{
    use BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2030-01-06 07:00:00'));
        $this->setUpHourlyCategorySchema(); $this->buildWorkers();
        app('view')->getFinder()->prependLocation(base_path('tests/Fixtures/hourly-web-views'));
        Schema::table('maids',fn(Blueprint $t)=>$t->boolean('is_show')->default(true));
        Schema::create('users',function(Blueprint $t){ $t->softDeletes();
            $t->id();$t->string('name');$t->string('phone');$t->string('email')->nullable();
            $t->boolean('is_blocked')->default(false);$t->timestamps();
        });
        Schema::table('user_addresses',function(Blueprint $t){foreach(['name','phone','title','notes'] as $key)$t->string($key)->nullable();});
        (require base_path('database/migrations/2026_09_20_000001_add_hourly_booking_wizard.php'))->up();
        (require base_path('database/migrations/2026_09_21_000001_extend_hourly_order_management.php'))->up();
        Schema::create('hourly_drivers',function(Blueprint $t){$t->id();$t->string('name');$t->boolean('is_active')->default(true);$t->softDeletes();});
        DB::table('users')->insert([['id'=>1,'name'=>'عميل','phone'=>'0500000000'],['id'=>2,'name'=>'عميل آخر','phone'=>'0500000001']]);
    }

    protected function tearDown(): void { Carbon::setTestNow(); HourlyRentalSettings::flush(); parent::tearDown(); }

    private function fixture(array $settings = [], int $visits = 1): array
    {
        $category=$this->category();$district=$this->district('الملز',24.7,46.7);$team=$this->team();
        $category->districts()->attach($district->id,['is_active'=>true,'team_id'=>$team->id]);
        HourlyCategorySetting::create(['service_category_id'=>$category->id,'settings'=>$settings+['customer_worker_selection'=>true]]);
        $item=$this->item($category,['visits_count'=>$visits]);
        DB::table('maids')->insert(['id'=>1,'name'=>'عاملة ساعات','rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>now()->toDateString(),
            'hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])])]);
        DB::table('maid_jobs')->insert(['maid_id'=>1,'job_type_id'=>2]);
        DB::table('contracts')->insert(['id'=>1,'maid_id'=>1,'status'=>'shelter']);
        DB::table('contract_infos')->insert(['contract_id'=>1,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>$team->id,'member_type'=>'maid','member_id'=>1,'role'=>'primary','is_active'=>true]);
        return ['booking_token'=>(string)Str::uuid(),'hourly_package_id'=>$item->id,'district_id'=>$district->id,
            'address_id'=>$this->address(24.7,46.7,'الملز'),'workers_per_visit'=>1,'payment_method'=>'cash','scheduled_starts'=>['2030-01-07 08:00']];
    }

    private function customer(int $id=1): void { $this->actingAs(User::findOrFail($id),'web'); }

    public function test_empty_package_cities_inherit_service_coverage_for_publication_and_booking(): void
    {
        $booking = $this->fixture();
        $item = HourlyPackage::findOrFail($booking['hourly_package_id']);
        $city = $item->cities->first()->id;
        $item->cities()->detach();
        $item->refresh();
        $catalog = app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        $this->assertTrue($item->effectiveCities()->whereKey($city)->exists());
        $this->assertTrue(HourlyPackage::forCity($city)->whereKey($item->id)->exists());
        $this->assertTrue($catalog->publicationStatus($item)['visible']);
        $this->assertCount(1, $catalog->districts($item));
        $this->customer();
        $this->postJson(route('web.hourly.store'), $booking)->assertCreated();
    }

    public function test_inherited_city_scope_respects_explicit_restrictions_and_disabled_coverage(): void
    {
        $booking = $this->fixture();
        $item = HourlyPackage::findOrFail($booking['hourly_package_id']);
        $city = $item->cities->first()->id;
        $item->cities()->detach();
        $item->refresh();
        $item->serviceCategory->districts()->updateExistingPivot($booking['district_id'], ['is_active'=>false]);
        $this->assertFalse($item->effectiveCities()->exists());
        $this->assertFalse(HourlyPackage::forCity($city)->whereKey($item->id)->exists());
        $item->serviceCategory->districts()->updateExistingPivot($booking['district_id'], ['is_active'=>true]);
        DB::table('cities')->insert(['id'=>999,'name_ar'=>'مدينة أخرى','is_active'=>false]);
        $item->cities()->attach(999);
        $item->refresh();
        $this->assertFalse($item->effectiveCities()->exists());
        $this->assertFalse(HourlyPackage::forCity($city)->whereKey($item->id)->exists());
    }

    private function secondWorker(): void
    {
        $worker=(array)DB::table('maids')->where('id',1)->first();$worker['id']=2;$worker['name']='Second worker';
        DB::table('maids')->insert($worker);
        DB::table('maid_jobs')->insert(['maid_id'=>2,'job_type_id'=>2]);
        DB::table('contracts')->insert(['id'=>2,'maid_id'=>2,'status'=>'shelter']);
        DB::table('contract_infos')->insert(['contract_id'=>2,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>DB::table('hourly_teams')->value('id'),'member_type'=>'maid','member_id'=>2,'role'=>'primary','is_active'=>true]);
    }

    public function test_optional_worker_choices_leave_remaining_for_operations_when_auto_assignment_is_off(): void
    {
        $booking=$this->fixture(['auto_assign'=>['web_app'=>false]]);$this->secondWorker();$this->customer();$booking['workers_per_visit']=2;
        $this->postJson(route('web.hourly.store'),$booking+['selected_maid_ids'=>[1]])->assertCreated();
        $visit=HourlyRentalOrder::first()->visits()->first();
        $this->assertEquals(2,$visit->workers_count);
        $this->assertEquals([1],$visit->workers()->pluck('maids.id')->all());
        $this->assertDatabaseHas('hourly_visit_reservations',['hourly_visit_id'=>$visit->id,'maid_id'=>2]);
    }

    public function test_empty_worker_choices_are_accepted_without_automatic_assignment(): void
    {
        $booking=$this->fixture(['auto_assign'=>['web_app'=>false]]);$this->customer();
        $this->postJson(route('web.hourly.store'),$booking+['selected_maid_ids'=>[]])->assertCreated();
        $this->assertSame(0,HourlyRentalOrder::first()->visits()->first()->workers()->count());
    }

    public function test_auto_assignment_completes_partial_choices_when_workers_are_available(): void
    {
        $booking=$this->fixture(['auto_assign'=>['web_app'=>true]]);$this->secondWorker();$this->customer();$booking['workers_per_visit']=2;
        $this->postJson(route('web.hourly.store'),$booking+['selected_maid_ids'=>[2]])->assertCreated();
        $this->assertEquals([1,2],HourlyRentalOrder::first()->visits()->first()->workers()->orderBy('maids.id')->pluck('maids.id')->all());
    }

    public function test_auto_assignment_keeps_partial_choices_when_remaining_workers_are_unavailable(): void
    {
        $booking=$this->fixture(['auto_assign'=>['web_app'=>true]]);$this->customer();$booking['workers_per_visit']=2;
        $this->postJson(route('web.hourly.store'),$booking+['selected_maid_ids'=>[1]])->assertCreated();
        $visit=HourlyRentalOrder::first()->visits()->first();
        $this->assertEquals(2,$visit->workers_count);
        $this->assertEquals([1],$visit->workers()->pluck('maids.id')->all());
    }

    public function test_date_only_api_can_assign_partial_choices_without_full_capacity(): void
    {
        $booking=$this->fixture(['show_time_options_app'=>false,'auto_assign'=>['web_app'=>true]]);$this->actingAs(User::findOrFail(1),'api');
        unset($booking['scheduled_starts']);$booking['scheduled_dates']=['2030-01-07'];$booking['workers_per_visit']=2;
        $this->postJson('/api/app/hourly/orders',$booking+['selected_maid_ids'=>[1]])->assertCreated();
        $this->assertEquals([1],HourlyRentalOrder::first()->visits()->first()->workers()->pluck('maids.id')->all());
    }

    public function test_customer_worker_selection_rejects_duplicates_excess_and_unavailable_workers(): void
    {
        $booking=$this->fixture();$this->secondWorker();$this->actingAs(User::findOrFail(1),'api');
        $booking['workers_per_visit']=2;
        foreach ([[1,1],[1,2,3]] as $ids) $this->postJson('/api/app/hourly/orders',$booking+['selected_maid_ids'=>$ids])->assertStatus(422);
        $this->postJson('/api/app/hourly/orders',$booking+['selected_maid_ids'=>[1,2],'preferred_maid_id'=>1])->assertStatus(422);
        DB::table('maids')->where('id',2)->update(['is_show'=>false]);
        $this->postJson('/api/app/hourly/orders',$booking+['selected_maid_ids'=>[1,2]])->assertStatus(422);
        $this->assertDatabaseCount('hourly_rental_orders',0);
        DB::table('maids')->where('id',2)->update(['is_show'=>true]);
        $this->postJson('/api/app/hourly/orders',$booking+['selected_maid_ids'=>[2,1]])->assertCreated();
        $this->assertEquals([1,2],HourlyRentalOrder::first()->visits()->first()->workers()->orderBy('maids.id')->pluck('maids.id')->all());
        $booking['booking_token']=(string)Str::uuid();
        $this->postJson('/api/app/hourly/orders',$booking+['selected_maid_ids'=>[1,2]])->assertStatus(422);
        $this->assertDatabaseCount('hourly_rental_orders',1);
    }

    public function test_website_full_worker_selection_and_disabled_setting(): void
    {
        $booking=$this->fixture(['customer_worker_selection'=>false]);$this->secondWorker();$this->customer();$booking['workers_per_visit']=2;
        $this->postJson(route('web.hourly.store'),$booking+['selected_maid_ids'=>[1,2]])->assertStatus(422);
        HourlyCategorySetting::query()->update(['settings'=>json_encode(['customer_worker_selection'=>true])]);
        $this->postJson(route('web.hourly.store'),$booking+['selected_maid_ids'=>[1,2]])->assertCreated();
        $this->assertEquals([1,2],HourlyRentalOrder::first()->visits()->first()->workers()->orderBy('maids.id')->pluck('maids.id')->all());
    }

    public function test_date_only_selection_requires_all_workers_in_the_same_slot(): void
    {
        $booking=$this->fixture(['show_time_options_app'=>false]);$this->secondWorker();$this->actingAs(User::findOrFail(1),'api');
        unset($booking['scheduled_starts']);$booking['scheduled_dates']=['2030-01-07'];$booking['workers_per_visit']=2;
        foreach([1=>['08:00','12:00'],2=>['13:00','17:00']] as $id=>$hours) DB::table('maids')->where('id',$id)->update(['hourly_settings'=>json_encode(['work_schedule'=>['monday'=>['mode'=>'hours','start'=>$hours[0],'end'=>$hours[1]]]])]);
        $this->postJson('/api/app/hourly/orders',$booking+['selected_maid_ids'=>[1,2]])->assertStatus(422);
        $this->assertDatabaseCount('hourly_rental_orders',0);
        DB::table('maids')->where('id',2)->update(['hourly_settings'=>DB::table('maids')->where('id',1)->value('hourly_settings')]);
        $this->postJson('/api/app/hourly/orders',$booking+['selected_maid_ids'=>[1,2]])->assertCreated();
    }

    public function test_customer_package_ready_filter_is_opt_in_and_config_explains_selection(): void
    {
        $booking=$this->fixture();$this->actingAs(User::findOrFail(1),'api');
        $this->getJson('/api/app/hourly/packages?city_id=1&ready_only=1')->assertOk()->assertJsonCount(1,'data');
        $this->getJson('/api/app/hourly/packages/'.$booking['hourly_package_id'].'/config?city_id=1')->assertOk()
            ->assertJsonPath('data.customer_worker_selection',true)->assertJsonPath('data.worker_selection.request_field','selected_maid_ids');
        DB::table('maids')->where('id',1)->update(['is_show'=>false]);
        $this->getJson('/api/app/hourly/packages?city_id=1&ready_only=1')->assertOk()->assertJsonCount(0,'data');
        $this->getJson('/api/app/hourly/packages?city_id=1')->assertOk()->assertJsonCount(1,'data');
    }

    private function driverLocations(): void
    {
        Schema::table('hourly_drivers',function(Blueprint $t){$t->json('work_schedule')->nullable();$t->timestamps();});
        Schema::create('location_pings',function(Blueprint $t){$t->id();$t->string('user_type');$t->integer('user_id');$t->double('lat');$t->double('lng');$t->dateTime('recorded_at');});
        foreach([1,2,3] as $id){
            HourlyDriver::create(['id'=>$id,'name'=>'Driver '.$id,'is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>$id===1?'off':'all_day'])]);
            DB::table('hourly_team_members')->insert(['hourly_team_id'=>DB::table('hourly_teams')->value('id'),'member_type'=>'driver','member_id'=>$id,'role'=>'primary','is_active'=>true]);
            DB::table('location_pings')->insert(['user_type'=>'driver','user_id'=>$id,'lat'=>24.7+$id/1000,'lng'=>46.7,'recorded_at'=>now()]);
        }
        $this->mock(\App\Services\Hourly\StaffPushService::class)->shouldReceive('assignmentChanged')->zeroOrMoreTimes();
    }

    public function test_automatic_driver_assignment_chooses_nearest_available_and_skips_busy_driver(): void
    {
        $booking=$this->fixture(['auto_assign_driver'=>['web_app'=>true]]);$this->driverLocations();$this->customer();
        $this->postJson(route('web.hourly.store'),$booking)->assertCreated();
        $visit=HourlyRentalOrder::first()->visits()->first();
        $this->assertEquals(2,$visit->delivery_driver_id);$this->assertEquals(2,$visit->pickup_driver_id);
        // Leave the driver's assignment in place but free worker capacity for a second booking.
        DB::table('hourly_visit_reservations')->delete();
        $booking['booking_token']=(string)Str::uuid();
        $this->postJson(route('web.hourly.store'),$booking)->assertCreated();
        $next=HourlyRentalOrder::latest('id')->first()->visits()->first();
        $this->assertEquals(3,$next->delivery_driver_id);$this->assertEquals(3,$next->pickup_driver_id);
    }

    public function test_automatic_driver_assignment_rejects_stale_locations_and_rolls_back_order(): void
    {
        $booking=$this->fixture(['auto_assign_driver'=>['web_app'=>true]]);$this->driverLocations();$this->customer();
        DB::table('location_pings')->update(['recorded_at'=>now()->subDays(2)]);
        $this->postJson(route('web.hourly.store'),$booking)->assertStatus(422);
        $this->assertDatabaseCount('hourly_rental_orders',0);
        $this->assertDatabaseCount('hourly_visit_reservations',0);
    }

    public function test_panel_only_automatic_driver_setting_does_not_change_customer_bookings(): void
    {
        $booking=$this->fixture(['auto_assign_driver'=>['panel'=>true,'web_app'=>false]]);$this->customer();
        $this->postJson(route('web.hourly.store'),$booking)->assertCreated();
        $this->assertNull(HourlyRentalOrder::first()->visits()->first()->delivery_driver_id);
    }

    public function test_driver_setting_saves_and_customer_api_assigns_separate_delivery_and_pickup_drivers(): void
    {
        $booking=$this->fixture();$this->driverLocations();
        $item=HourlyPackage::findOrFail($booking['hourly_package_id']);
        $this->putJson(route('admin.hourly-category-settings.update',$item->service_category_id),[
            'order_form_steppers_mode'=>'inherit',
            'auto_assign_driver'=>['web_app'=>'1','panel'=>'inherit'],
        ])->assertRedirect();
        $this->actingAs(User::findOrFail(1),'api');
        $this->getJson('/api/app/hourly/packages/'.$item->id.'/config?city_id=1')->assertOk()->assertJsonPath('data.auto_assign_driver',true);
        HourlyDriver::findOrFail(2)->update(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'hours','start'=>'07:00','end'=>'10:00'])]);
        $this->postJson('/api/app/hourly/orders',$booking)->assertCreated();
        $visit=HourlyRentalOrder::first()->visits()->first();
        $this->assertEquals(2,$visit->delivery_driver_id);$this->assertEquals(3,$visit->pickup_driver_id);
    }

    public function test_customer_api_can_list_available_workers_without_changing_legacy_slots(): void
    {
        $booking=$this->fixture();$this->actingAs(User::findOrFail(1),'api');
        $query=['city_id'=>1,'district_id'=>$booking['district_id'],'date'=>'2030-01-07','starts_at'=>'2030-01-07 08:00:00','workers_per_visit'=>1];
        $url='/api/app/hourly/packages/'.$booking['hourly_package_id'].'/workers';
        $this->getJson($url.'?'.http_build_query($query))->assertOk()->assertJsonPath('data.0.id',1)->assertJsonPath('selection_enabled',true);
        $legacy=$this->getJson('/api/app/hourly/slots?'.http_build_query($query+['hourly_package_id'=>$booking['hourly_package_id']]))->assertOk();
        $this->assertSame(['starts_at','ends_at'],array_keys($legacy->json('data.0')));
        $this->getJson($url.'?'.http_build_query(['starts_at'=>'2030-01-08 08:00:00']+$query))->assertStatus(422);
        $this->getJson($url.'?'.http_build_query(['workers_per_visit'=>2]+$query))->assertOk()->assertJsonCount(0,'data');
        DB::table('maids')->where('id',1)->update(['is_show'=>false]);
        $this->getJson($url.'?'.http_build_query($query))->assertOk()->assertJsonCount(0,'data');
        DB::table('maids')->where('id',1)->update(['is_show'=>true]);
        $this->postJson('/api/app/hourly/orders',$booking+['preferred_maid_id'=>1])->assertCreated();
        $this->assertEquals([1],HourlyRentalOrder::first()->visits()->first()->workers()->pluck('maids.id')->all());
    }

    public function test_customer_api_worker_selection_can_be_disabled_and_date_only_is_supported(): void
    {
        $booking=$this->fixture(['show_time_options_app'=>false]);$this->actingAs(User::findOrFail(1),'api');
        $query=['city_id'=>1,'district_id'=>$booking['district_id'],'date'=>'2030-01-07'];
        $url='/api/app/hourly/packages/'.$booking['hourly_package_id'].'/workers?'.http_build_query($query);
        $this->getJson($url)->assertOk()->assertJsonPath('time_selection','date')->assertJsonPath('data.0.id',1);
        HourlyCategorySetting::query()->update(['settings'=>json_encode(['show_time_options_app'=>false,'customer_worker_selection'=>false])]);
        $this->getJson($url)->assertOk()->assertJsonCount(0,'data')->assertJsonPath('selection_enabled',false);
    }

    public function test_checkout_worker_choices_use_public_available_workers_and_assign_the_choice(): void
    {
        $booking=$this->fixture();$this->customer();
        $this->get(route('web.hourly.checkout',$booking['hourly_package_id']))->assertOk()->assertSee('hr-worker-cards');
        $query=['hourly_package_id'=>$booking['hourly_package_id'],'district_id'=>$booking['district_id'],'date'=>'2030-01-07','workers_per_visit'=>1];
        $response=$this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonPath('data.0.workers.0.id',1);
        $this->assertSame(['id','name','photo','nationality'],array_keys($response->json('data.0.workers.0')));
        $this->postJson(route('web.hourly.store'),$booking+['preferred_maid_id'=>1])->assertCreated();
        $this->assertEquals([1],HourlyRentalOrder::first()->visits()->first()->workers()->pluck('maids.id')->all());
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonMissing(['starts_at'=>'2030-01-07 08:00:00']);
    }

    public function test_disabled_worker_selection_hides_cards_and_worker_data(): void
    {
        $booking=$this->fixture(['customer_worker_selection'=>false]);$this->customer();
        $this->get(route('web.hourly.checkout',$booking['hourly_package_id']))->assertOk()->assertDontSee('id="hr-worker-cards"',false);
        $this->getJson(route('web.hourly.slots',['hourly_package_id'=>$booking['hourly_package_id'],'district_id'=>$booking['district_id'],'date'=>'2030-01-07','workers_per_visit'=>1]))
            ->assertOk()->assertJsonCount(0,'data.0.workers')->assertJsonCount(0,'workers');
    }

    public function test_publication_badges_explain_hidden_workers_and_match_catalog_visibility(): void
    {
        $booking=$this->fixture();$item=HourlyPackage::findOrFail($booking['hourly_package_id']);
        $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        $this->assertTrue($catalog->publicationStatus($item)['visible']);
        $this->assertNotEmpty($catalog->contexts());
        DB::table('maids')->where('id',1)->update(['is_show'=>false]);
        $report=$catalog->publicationStatus($item);
        $this->assertFalse($report['visible']);
        $this->assertStringContainsString('مخفيات عن الموقع',implode(' ',$report['reasons']));
        $this->assertSame([],$catalog->contexts());
        $this->get(route('admin.hourly-service-items.index',['category'=>$item->service_category_id]))->assertOk()
            ->assertSee('لا يظهر في الموقع')->assertSee('مخفيات عن الموقع')->assertSee('role="tooltip"',false)
            ->assertSee('website-category-'.$item->service_category_id)->assertSee('website-package-'.$item->id);
    }

    public function test_publication_reports_all_disabled_package_flags_and_updates_when_status_changes(): void
    {
        $booking=$this->fixture();$item=HourlyPackage::findOrFail($booking['hourly_package_id']);
        $this->putJson(route('admin.hourly-service-items.status',$item),['is_active'=>false])->assertOk()
            ->assertJsonPath('publication.visible',false)->assertJsonPath('category_publication.visible',false)
            ->assertJsonFragment(['الباقة متوقفة.']);
        $this->putJson(route('admin.hourly-service-items.status',$item),['is_active'=>true])->assertOk()
            ->assertJsonPath('publication.visible',true)->assertJsonPath('category_publication.visible',true);
        $item->update(['is_active'=>false,'show_on_app'=>false,'is_private'=>true]);
        $report=app(\App\Services\Hourly\HourlyWebsiteCatalog::class)->publicationStatus($item->fresh());
        $this->assertFalse($report['visible']);$this->assertCount(3,$report['reasons']);
    }

    public function test_publication_distinguishes_missing_coverage_and_inactive_team(): void
    {
        $booking=$this->fixture();$item=HourlyPackage::findOrFail($booking['hourly_package_id']);
        $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        DB::table('hourly_teams')->update(['is_active'=>false]);
        $this->assertStringContainsString('فريق نشط',implode(' ',$catalog->publicationStatus($item)['reasons']));
        DB::table('hourly_category_district')->update(['is_active'=>false]);
        $this->assertStringContainsString('حي تغطية مفعّل',implode(' ',$catalog->publicationStatus($item)['reasons']));
    }

    public function test_archived_category_cannot_be_marked_visible_or_listed(): void
    {
        $booking=$this->fixture();$item=HourlyPackage::findOrFail($booking['hourly_package_id']);
        $item->serviceCategory->delete();
        $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        $this->assertFalse($catalog->publicationStatus($item->fresh())['visible']);
        $this->assertFalse($catalog->items()->contains('id',$item->id));
    }

    public function test_category_list_shows_publication_badge_and_toggle_returns_updated_reason(): void
    {
        $booking=$this->fixture();$item=HourlyPackage::findOrFail($booking['hourly_package_id']);
        $this->get(route('admin.hourly-service-categories.index'))->assertOk()
            ->assertSee('الظهور في الموقع')->assertSee('website-category-'.$item->service_category_id)
            ->assertSee('data-website-visible="1"',false)->assertSee('role="tooltip"',false);
        $this->putJson(route('admin.hourly-service-categories.status',$item->service_category_id),['is_active'=>false])
            ->assertOk()->assertJsonPath('publication.visible',false)
            ->assertJsonFragment(['فئة الخدمة غير موجودة أو متوقفة أو مؤرشفة.']);
        $this->get(route('admin.hourly-service-categories.index'))->assertOk()->assertSee('لا يظهر في الموقع')
            ->assertSee('فئة الخدمة غير موجودة أو متوقفة أو مؤرشفة.');
        $this->putJson(route('admin.hourly-service-categories.status',$item->service_category_id),['is_active'=>true])
            ->assertOk()->assertJsonPath('publication.visible',true);
    }

    public function test_worker_count_is_editable_without_a_custom_stepper_and_quote_accepts_it(): void
    {
        $booking=$this->fixture();$this->customer();
        $page=$this->get(route('web.hourly.checkout',$booking['hourly_package_id']))->assertOk();
        preg_match('/<input id="hr-workers"[^>]*>/',$page->getContent(),$input);
        $this->assertStringContainsString('min="1"',$input[0]);
        $this->assertStringContainsString('max="20"',$input[0]);
        $this->assertStringNotContainsString('readonly',$input[0]);
        $this->getJson(route('web.hourly.quote',['package'=>$booking['hourly_package_id'],'city_id'=>1,'workers'=>2]))
            ->assertOk()->assertJsonPath('data.pricing.workers',2)->assertJsonPath('data.pricing.total_amount',165);
        $slots=['hourly_package_id'=>$booking['hourly_package_id'],'district_id'=>$booking['district_id'],'date'=>'2030-01-07'];
        $this->getJson(route('web.hourly.slots',$slots+['workers_per_visit'=>1]))->assertOk()->assertJsonFragment(['starts_at'=>'2030-01-07 08:00:00']);
        // The count is editable, but times only appear where that many workers are actually free (the fixture has one).
        $this->getJson(route('web.hourly.slots',$slots+['workers_per_visit'=>2]))->assertOk()->assertJsonCount(0,'data');
    }

    public function test_times_require_real_worker_capacity_and_respect_notice_and_calendar(): void
    {
        $booking=$this->fixture();$this->customer();
        $item=HourlyPackage::findOrFail($booking['hourly_package_id']);
        $item->update(['start_times'=>['08:00'=>'all','10:00'=>'dispatch'],'min_notice_hours'=>24]);
        $query=['hourly_package_id'=>$item->id,'district_id'=>$booking['district_id'],
            'date'=>'2030-01-07','workers_per_visit'=>1];
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonCount(1,'data')
            ->assertJsonPath('data.0.starts_at','2030-01-07 08:00:00');
        // Only one worker exists, so asking for two must not offer a time the store step would reject.
        $this->getJson(route('web.hourly.slots',['workers_per_visit'=>2]+$query))->assertOk()->assertJsonCount(0,'data');
        $this->getJson(route('web.hourly.slots',['date'=>'2030-01-06']+$query))->assertOk()->assertJsonCount(0,'data');
        $this->getJson(route('web.hourly.slots',['date'=>'2030-03-01']+$query))->assertOk()->assertJsonCount(0,'data');
        DB::table('hourly_team_members')->delete();
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonCount(0,'data');
        $this->postJson(route('web.hourly.store'),$booking)->assertStatus(422);
        $this->assertDatabaseCount('hourly_rental_orders',0);
    }

    public function test_package_needing_more_workers_than_are_ready_is_hidden_with_a_clear_reason(): void
    {
        $booking=$this->fixture();$this->customer();
        $item=HourlyPackage::findOrFail($booking['hourly_package_id']);
        $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        $item->update(['workers_per_visit'=>2]);$item->refresh();
        $report=$catalog->publicationStatus($item);
        $this->assertFalse($report['visible']);
        $this->assertStringContainsString('عدد العاملات المطلوب لكل زيارة: 2، وأعلى عدد جاهز في أحياء التغطية: 1',implode(' ',$report['reasons']));
        $this->assertSame([],$catalog->contexts());
        $this->get(route('web.hourly.package',$item->id))->assertNotFound();
        $this->get(route('web.hourly.checkout',$item->id))->assertNotFound();
        // A worker whose whole week is "off" is not counted as ready either.
        $item->update(['workers_per_visit'=>1]);$item->refresh();
        DB::table('maids')->where('id',1)->update(['hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'off'])])]);
        $report=$catalog->publicationStatus($item);
        $this->assertFalse($report['visible']);
        $this->assertStringContainsString('جداول عملهن كلها إجازة',implode(' ',$report['reasons']));
        $this->assertSame([],$catalog->contexts());
    }

    public function test_fixed_worker_stepper_keeps_field_fixed_and_rejects_larger_count(): void
    {
        $booking=$this->fixture(['order_form_steppers'=>[
            ['key'=>'workers','label'=>'العاملات','channels'=>['web_app'],'min'=>1,'max'=>1,'scale_price'=>true],
        ]]);$this->customer();
        $page=$this->get(route('web.hourly.checkout',$booking['hourly_package_id']))->assertOk();
        preg_match('/<input id="hr-workers"[^>]*>/',$page->getContent(),$input);
        $this->assertStringContainsString('max="1"',$input[0]);
        $this->assertStringContainsString('readonly',$input[0]);
        $this->getJson(route('web.hourly.quote',['package'=>$booking['hourly_package_id'],'city_id'=>1,'workers'=>2]))->assertStatus(422);
        $booking['workers_per_visit']=2;
        $this->postJson(route('web.hourly.store'),$booking)->assertStatus(422);
    }

    public function test_public_workers_and_authenticated_package_details_show_eligible_options(): void
    {
        $data=$this->fixture();
        $this->get(route('web.hourly.index'))->assertOk()->assertSee('عاملة ساعات')->assertSee('إيجار ساعات');
        $this->customer();
        $this->get(route('web.hourly.catalog',['maid'=>1]))->assertOk()->assertSee('الرياض')->assertSee('تفاصيل الباقة');
        $this->get(route('web.hourly.package',$data['hourly_package_id']))->assertOk()->assertSee('شراء الباقة');
        DB::table('maids')->where('id',1)->update(['is_show'=>false]);
        $this->get(route('web.hourly.index'))->assertOk()->assertDontSee('عاملة ساعات');
        $this->get(route('web.hourly.package',['package'=>$data['hourly_package_id'],'maid'=>1]))->assertNotFound();
        HourlyPackage::find($data['hourly_package_id'])->update(['is_private'=>true]);
        $this->get(route('web.hourly.package',$data['hourly_package_id']))->assertNotFound();
    }

    public function test_disabled_module_is_hidden_and_guest_checkout_requires_session_login(): void
    {
        $data=$this->fixture();$url=route('web.hourly.checkout',$data['hourly_package_id']);
        $this->get($url)->assertRedirect(route('site.login.index'))->assertSessionHas('url.intended',$url);
        $this->postJson(route('web.hourly.store'),$data+['user_id'=>1])->assertUnauthorized();
        config(['superadmin_modules.hourly_rental.default_enabled'=>false]);
        $this->get(route('web.hourly.index'))->assertNotFound();
        $this->postJson(route('web.hourly.store'),$data)->assertNotFound();
    }

    public function test_guests_cannot_view_packages_even_by_direct_url_or_json_request(): void
    {
        $data=$this->fixture();
        $urls=[route('web.hourly.catalog',['city'=>1,'maid'=>1]),
            route('web.hourly.package',['package'=>$data['hourly_package_id'],'city'=>1,'maid'=>1])];
        foreach ($urls as $url) {
            $this->get($url)->assertRedirect(route('site.login.index'))->assertSessionHas('url.intended',$url);
            $response=$this->getJson($url)->assertUnauthorized();
            $this->assertArrayNotHasKey('data',$response->json());
        }
        $this->getJson(route('web.hourly.quote',['package'=>$data['hourly_package_id'],'city_id'=>1]))->assertUnauthorized();
        $this->assertDatabaseCount('hourly_rental_orders',0);
    }

    public function test_blocked_customers_cannot_browse_packages_or_create_orders(): void
    {
        $data=$this->fixture();
        DB::table('users')->where('id',1)->update(['is_blocked'=>true]);
        $this->customer();
        $this->get(route('web.hourly.catalog'))->assertForbidden();
        $this->get(route('web.hourly.package',$data['hourly_package_id']))->assertForbidden();
        $this->postJson(route('web.hourly.store'),$data)->assertForbidden();
        $this->assertDatabaseCount('hourly_rental_orders',0);
    }

    public function test_checkout_has_three_steps_and_creates_one_owned_order_on_retry(): void
    {
        $data=$this->fixture([],3);$this->customer();
        $this->get(route('web.hourly.checkout',$data['hourly_package_id']))->assertOk()
            ->assertSee('hr-panel-0')->assertSee('hr-panel-1')->assertSee('hr-panel-2')->assertSee('hourly-checkout-config');
        $response=$this->postJson(route('web.hourly.store'),$data+['user_id'=>2])->assertCreated();
        $this->postJson(route('web.hourly.store'),$data+['user_id'=>2])->assertCreated()->assertJsonPath('id',$response->json('id'));
        $this->assertDatabaseCount('hourly_rental_orders',1);
        $this->assertDatabaseCount('hourly_visit_reservations',1);
        $order=HourlyRentalOrder::first();
        $this->assertEquals(1,$order->user_id);
        $this->assertSame('website',$order->booking_metadata['booking_channel']);
        $this->assertSame(3,$order->visits()->count());
        $this->assertSame(1,$order->visits()->whereNotNull('scheduled_start_at')->count());
        $this->get($response->json('redirect'))->assertOk()->assertSee($order->order_number);
        $this->customer(2);
        $this->get(route('web.hourly.order',$order->id))->assertNotFound();
        $this->post(route('web.hourly.pay',$order->id))->assertNotFound();
    }

    public function test_reserved_time_disappears_and_a_racing_second_booking_rolls_back_with_slot_error(): void
    {
        $data=$this->fixture();$this->customer();
        $query=['hourly_package_id'=>$data['hourly_package_id'],'district_id'=>$data['district_id'],'date'=>'2030-01-07','workers_per_visit'=>1];
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonFragment(['starts_at'=>'2030-01-07 08:00:00']);
        $this->postJson(route('web.hourly.store'),$data)->assertCreated();
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonMissing(['starts_at'=>'2030-01-07 08:00:00']);
        // A customer who loaded the time before it was taken gets the "slot" key, which sends the page back to step 2.
        $data['booking_token']=(string)Str::uuid();
        $this->postJson(route('web.hourly.store'),$data)->assertStatus(422)->assertJsonValidationErrors('slot');
        $this->assertDatabaseCount('hourly_rental_orders',1);
    }

    public function test_selection_and_payment_settings_are_enforced_on_direct_requests(): void
    {
        $data=$this->fixture(['customer_worker_selection'=>false]);$this->customer();
        DB::table('hourly_category_payment_methods')->updateOrInsert(['service_category_id'=>HourlyPackage::find($data['hourly_package_id'])->service_category_id,'payment_method'=>'moyasar'],['web_app'=>false,'employee'=>true,'branch'=>true]);
        $this->get(route('web.hourly.package',['package'=>$data['hourly_package_id'],'maid'=>1]))->assertNotFound();
        $this->postJson(route('web.hourly.store'),$data+['preferred_maid_id'=>1])->assertStatus(422);
        $data['payment_method']='moyasar';
        $this->postJson(route('web.hourly.store'),$data)->assertStatus(422);
        $this->assertDatabaseCount('hourly_rental_orders',0);
    }

    public function test_date_only_mode_finds_real_capacity_and_reserves_it_without_assigning(): void
    {
        $data=$this->fixture(['show_time_options_app'=>false]);$this->customer();
        DB::table('maids')->where('id',1)->update(['hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'hours','start'=>'12:00','end'=>'20:00'])])]);
        unset($data['scheduled_starts']);$data['scheduled_dates']=['2030-01-07'];
        $this->postJson(route('web.hourly.store'),$data)->assertCreated();
        $this->assertSame('12:00',HourlyRentalOrder::first()->visits()->first()->scheduled_start_at->format('H:i'));
        $this->assertDatabaseCount('hourly_visit_reservations',1);$this->assertDatabaseCount('hourly_visit_workers',0);
    }

    public function test_address_is_owned_and_city_and_district_must_match(): void
    {
        $data=$this->fixture();$this->customer();
        $address=['city_id'=>1,'district_id'=>$data['district_id'],'title'=>'المنزل','address'=>'شارع تجريبي','latitude'=>24.7,'longitude'=>46.7,'contact_phone'=>'0500000000','user_id'=>2];
        $response=$this->postJson(route('web.hourly.address'),$address)->assertCreated()->assertJsonPath('data.user_id',1);
        $this->assertDatabaseHas('user_addresses',['id'=>$response->json('data.id'),'hourly_district_id'=>$data['district_id']]);
        $address['city_id']=2;$this->postJson(route('web.hourly.address'),$address)->assertNotFound();
        $this->customer(2);$this->postJson(route('web.hourly.store'),$data)->assertNotFound();
    }

    public function test_new_address_resolves_its_district_from_coordinates_without_manual_selection(): void
    {
        $booking=$this->fixture();$this->customer();
        $this->postJson(route('web.hourly.address'),[
            'city_id'=>1,'hourly_package_id'=>$booking['hourly_package_id'],'title'=>'المنزل','address'=>'عنوان من الخريطة',
            'latitude'=>24.7,'longitude'=>46.7,'contact_phone'=>'0500000000',
        ])->assertCreated()->assertJsonPath('data.hourly_district_id',$booking['district_id'])->assertJsonPath('data.district','الملز');
    }

    public function test_address_matches_district_name_in_full_address_when_boundaries_are_missing(): void
    {
        $booking=$this->fixture();$this->customer();
        DB::table('hourly_service_districts')->where('id',$booking['district_id'])->update(['name'=>'حي السويدي','polygon'=>null]);
        $this->postJson(route('web.hourly.address'),[
            'city_id'=>1,'hourly_package_id'=>$booking['hourly_package_id'],'title'=>'المكتب','address'=>'الرياض - حي السويدي - منتزه السويدي العام',
            'latitude'=>24.5,'longitude'=>46.5,'contact_phone'=>'0500000000','district_id'=>null,
        ])->assertCreated()->assertJsonPath('data.hourly_district_id',$booking['district_id']);
    }

    public function test_unresolved_address_requests_manual_district_and_does_not_save_incorrect_data(): void
    {
        $booking=$this->fixture();$this->customer();
        $address=['city_id'=>1,'hourly_package_id'=>$booking['hourly_package_id'],'title'=>'المنزل','address'=>'عنوان غير محدد',
            'latitude'=>24.5,'longitude'=>46.5,'contact_phone'=>'0500000000'];
        $this->postJson(route('web.hourly.address'),$address)->assertStatus(422)->assertJsonValidationErrors('district_id');
        $this->assertDatabaseCount('user_addresses',1);
        $this->postJson(route('web.hourly.address'),$address+['district_id'=>$booking['district_id']])->assertCreated();
    }

    public function test_detected_district_outside_package_coverage_cannot_be_overridden_by_manual_selection(): void
    {
        $booking=$this->fixture();$this->customer();$this->district('حي خارج التغطية',24.5,46.5);
        $this->postJson(route('web.hourly.address'),[
            'city_id'=>1,'hourly_package_id'=>$booking['hourly_package_id'],'district_id'=>$booking['district_id'],
            'title'=>'المنزل','address'=>'عنوان آخر','latitude'=>24.5,'longitude'=>46.5,'contact_phone'=>'0500000000',
        ])->assertStatus(422)->assertJsonValidationErrors('district_id');
        $this->assertDatabaseCount('user_addresses',1);
    }

    public function test_saved_address_without_district_link_is_resolved_when_loading_checkout(): void
    {
        $booking=$this->fixture();$this->customer();
        DB::table('user_addresses')->where('id',$booking['address_id'])->update(['district'=>null]);
        $this->get(route('web.hourly.checkout',$booking['hourly_package_id']))->assertOk()
            ->assertViewHas('addresses',fn($addresses)=>(int)$addresses->first()->hourly_district_id===$booking['district_id']);
    }

    public function test_moyasar_uses_website_return_and_does_not_trust_callback_status(): void
    {
        $data=$this->fixture();$this->customer();$data['payment_method']='moyasar';
        $gateway=\Mockery::mock(HourlyMoyasarGateway::class);
        $gateway->shouldReceive('createPaymentLink')->once()->withArgs(fn($amount,$description,$callback,$metadata,$success)=>$amount>0 && str_contains($callback,'payments/moyasar/webhook') && str_contains($success,'hourly-rental/payment-return') && isset($metadata['hourly_payment_id']))->andReturn(['id'=>'invoice-1','url'=>'https://pay.example.test/invoice-1']);
        $gateway->shouldReceive('getInvoice')->once()->with('invoice-1')->andReturn(['id'=>'invoice-1','status'=>'pending']);
        $this->app->instance(HourlyMoyasarGateway::class,$gateway);
        $this->postJson(route('web.hourly.store'),$data)->assertCreated()->assertJsonPath('redirect','https://pay.example.test/invoice-1');
        $this->postJson(route('web.hourly.store'),$data)->assertCreated();
        $order=HourlyRentalOrder::first();$payment=$order->payments()->first();
        $this->get(route('web.hourly.payment-return',['payment'=>$payment->public_id,'status'=>'paid']))->assertRedirect(route('web.hourly.order',$order->id));
        $this->assertSame('pending',$payment->fresh()->status);$this->assertEquals(0,$order->fresh()->paid_amount);
    }

    public function test_gateway_failure_preserves_order_and_offers_payment_retry(): void
    {
        $data=$this->fixture();$this->customer();$data['payment_method']='moyasar';
        $gateway=\Mockery::mock(HourlyMoyasarGateway::class);$gateway->shouldReceive('createPaymentLink')->once()->andThrow(new \RuntimeException('Gateway unavailable'));
        $this->app->instance(HourlyMoyasarGateway::class,$gateway);
        $response=$this->postJson(route('web.hourly.store'),$data)->assertCreated();
        $this->assertDatabaseCount('hourly_rental_orders',1);$this->assertDatabaseCount('hourly_order_payments',0);
        $this->get($response->json('redirect'))->assertOk()->assertSee('المتابعة إلى الدفع الإلكتروني');
    }

    public function test_bank_receipt_is_private_and_pending_review(): void
    {
        Storage::fake('local');$data=$this->fixture();$this->customer();$data['payment_method']='bank_transfer';
        $response=$this->postJson(route('web.hourly.store'),$data)->assertCreated();
        $this->postJson(route('web.hourly.bank-transfer',$response->json('id')),['receipt'=>UploadedFile::fake()->create('receipt.pdf',10,'application/pdf')])->assertSuccessful();
        $payment=HourlyRentalOrder::first()->payments()->first();
        $this->assertSame('pending_review',$payment->status);Storage::disk('local')->assertExists($payment->attachment_path);
        $this->assertEquals(0,HourlyRentalOrder::first()->paid_amount);
    }

    public function test_order_page_labels_missed_visit_and_directs_customer_to_operations(): void
    {
        $data=$this->fixture();$this->customer();
        $order=HourlyRentalOrder::findOrFail($this->postJson(route('web.hourly.store'),$data)->assertCreated()->json('id'));
        $visit=$order->visits()->firstOrFail();
        $visit->update(['scheduled_start_at'=>now()->subDays(2),'scheduled_end_at'=>now()->subDay()]);
        $this->get(route('web.hourly.order',$order->id))->assertOk()
            ->assertSee('فات موعدها — لم تُنفّذ')->assertSee('يرجى التواصل مع الإدارة لإعادة الجدولة أو إلغاء الزيارة.')
            ->assertDontSee('data-visit-action="reschedule"',false)->assertDontSee('class="hr-delivery-code"',false);
        $this->assertSame('scheduled',$visit->fresh()->status);
    }


    public function test_cancelled_paid_order_page_explains_wallet_refund_and_keeps_original_receipt(): void
    {
        $data=$this->fixture();$this->customer();
        $order=HourlyRentalOrder::findOrFail($this->postJson(route('web.hourly.store'),$data)->assertCreated()->json('id'));
        $visit=$order->visits()->firstOrFail();$visit->update(['visit_price'=>200]);
        $order->update(['status'=>'active','total_amount'=>200,'paid_amount'=>200]);
        $order->payments()->create(['user_id'=>$order->user_id,'public_id'=>(string)Str::uuid(),'method'=>'cash','amount'=>200,'status'=>'paid','paid_at'=>now()]);
        $before=(float)DB::table('users')->where('id',$order->user_id)->value('hourly_wallet_balance');
        app(\App\Services\Hourly\CustomerVisitActions::class)->cancel($visit);
        $this->get(route('web.hourly.order',$order->id))->assertOk()->assertSee('المبلغ المسترد: 200.00')->assertSee('مستردة');
        $this->assertEquals($before+200,DB::table('users')->where('id',$order->user_id)->value('hourly_wallet_balance'));
        $this->assertSame(1,$order->payments()->where('amount',200)->count());
        $this->assertSame(1,$order->payments()->where('amount',-200)->count());
    }

    public function test_customer_can_preview_and_confirm_extra_hours_once_from_website(): void
    {
        $booking=$this->fixture(); $this->customer();
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $order=HourlyRentalOrder::findOrFail($id);
        $order->update(['status'=>'active','extra_hour_price'=>60]);
        $visit=$order->visits()->firstOrFail();
        $before=(float)$visit->planned_hours;
        $price=(float)$visit->visit_price;
        $params=['id'=>$id,'visit'=>$visit->id];
        $this->get(route('web.hourly.order',$id))->assertOk()->assertSee('data-extra-hour="'.$visit->id.'"',false);
        $this->getJson(route('web.hourly.visit.extra-hour.quote',$params).'?hours=2')->assertOk()
            ->assertJsonPath('data.amount',120)->assertJsonPath('data.ends_at',$visit->scheduled_end_at->copy()->addHours(2)->format('Y-m-d H:i'));
        $this->assertEquals($before,$visit->fresh()->planned_hours);
        $this->assertEquals($price,$visit->fresh()->visit_price);
        $body=['hours'=>2,'request_token'=>(string)Str::uuid()];
        $url=route('web.hourly.visit.extra-hour',$params);
        $this->postJson($url,$body)->assertOk()->assertJsonPath('data.extra_hour.amount',120);
        $this->postJson($url,$body)->assertOk();
        $this->assertEquals($before+2,$visit->fresh()->planned_hours);
        $this->assertEquals($price+120,$visit->fresh()->visit_price);
        $this->customer(2);
        $this->getJson(route('web.hourly.visit.extra-hour.quote',$params).'?hours=1')->assertNotFound();
        $this->postJson($url,['hours'=>1])->assertNotFound();
    }

    public function test_website_extra_hours_rejects_closed_visits_and_invalid_quantities(): void
    {
        $booking=$this->fixture(); $this->customer();
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $order=HourlyRentalOrder::findOrFail($id); $order->update(['status'=>'active']);
        $visit=$order->visits()->firstOrFail();
        $params=['id'=>$id,'visit'=>$visit->id];
        $this->getJson(route('web.hourly.visit.extra-hour.quote',$params).'?hours=7')->assertUnprocessable();
        $this->postJson(route('web.hourly.visit.extra-hour',$params),['hours'=>0])->assertUnprocessable();
        $visit->update(['status'=>'completed']);
        $this->getJson(route('web.hourly.visit.extra-hour.quote',$params).'?hours=1')->assertUnprocessable();
        $this->postJson(route('web.hourly.visit.extra-hour',$params),['hours'=>1])->assertUnprocessable();
    }
    public function test_website_additional_worker_uses_saved_package_price_and_updates_only_selected_visit(): void
    {
        $booking=$this->fixture([],2); $this->secondWorker(); $this->customer();
        HourlyPackage::whereKey($booking['hourly_package_id'])->update(['extra_worker_price'=>65]);
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $order=HourlyRentalOrder::findOrFail($id); $order->update(['status'=>'active']);
        $visits=$order->visits()->orderBy('visit_number')->get(); $visit=$visits[0];
        $price=(float)$visit->visit_price; $total=(float)$order->total_amount;
        HourlyPackage::whereKey($booking['hourly_package_id'])->update(['extra_worker_price'=>99]);
        $params=['id'=>$id,'visit'=>$visit->id];
        $page=route('web.hourly.order',['id'=>$id,'add_worker_visit'=>$visit->id]);
        $this->get(route('web.hourly.order',$id))->assertOk()->assertSee($page.'#hourly-customer-add-worker',false);
        $this->get($page)->assertOk()->assertSee('hourly-customer-add-worker');
        $quote=$this->getJson(route('web.hourly.visit.additional-worker.preview',$params))->assertOk()
            ->assertJsonPath('data.additional_fee',65)->json('data');
        $body=['request_token'=>(string)Str::uuid(),'confirmed'=>1,'worker_id'=>$quote['workers'][0]['id'],
            'expected_workers'=>$quote['current_workers'],'expected_price'=>$quote['current_price'],'expected_total'=>$quote['order_total']];
        $url=route('web.hourly.visit.additional-worker.store',$params);
        $this->postJson($url,$body)->assertOk()->assertJsonPath('data.additional_fee',65);
        $this->postJson($url,$body)->assertOk()->assertJsonPath('data.replayed',true);
        $this->assertEquals($price+65,$visit->fresh()->visit_price);
        $this->assertEquals($total+65,$order->fresh()->total_amount);
        $this->assertEquals(2,$visit->fresh()->workers_count);
        $this->assertEquals(1,$visits[1]->fresh()->workers_count);
        $this->customer(2);
        $this->getJson(route('web.hourly.visit.additional-worker.preview',$params))->assertNotFound();
        $this->postJson($url,$body)->assertNotFound();
        $this->customer(); $visit->update(['status'=>'completed']);
        $this->get(route('web.hourly.order',$id))->assertOk()->assertDontSee($page.'#hourly-customer-add-worker',false);
    }

    public function test_website_additional_worker_respects_zero_package_surcharge(): void
    {
        $booking=$this->fixture(); $this->secondWorker(); $this->customer();
        HourlyPackage::whereKey($booking['hourly_package_id'])->update(['extra_worker_price'=>0]);
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $order=HourlyRentalOrder::findOrFail($id); $order->update(['status'=>'active']);
        $visit=$order->visits()->firstOrFail();
        $this->getJson(route('web.hourly.visit.additional-worker.preview',['id'=>$id,'visit'=>$visit->id]))
            ->assertOk()->assertJsonPath('data.additional_fee',0);
    }
    public function test_external_website_api_supports_extra_hours_and_additional_workers(): void
    {
        $booking=$this->fixture(); $this->secondWorker(); $this->customer();
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $order=HourlyRentalOrder::findOrFail($id); $order->update(['status'=>'active','extra_worker_price'=>65,'extra_hour_price'=>40]);
        $visit=$order->visits()->firstOrFail();
        $this->actingAs(User::findOrFail(1),'api');
        $base='/api/client/hourly/orders/'.$id;
        $this->getJson($base)->assertOk()->assertJsonPath('data.visits.0.can_request_additional_worker',true);
        $url=$base.'/visits/'.$visit->id;
        $this->getJson($url.'/extra-hour?hours=1')->assertOk()->assertJsonPath('data.amount',40);
        $this->postJson($url.'/extra-hour',['hours'=>1,'request_token'=>(string)Str::uuid()])->assertOk();
        $quote=$this->getJson($url.'/additional-worker')->assertOk()->assertJsonPath('data.additional_fee',65)->json('data');
        $this->postJson($url.'/additional-worker',['request_token'=>(string)Str::uuid(),'confirmed'=>true,
            'worker_id'=>$quote['workers'][0]['id'],'expected_workers'=>$quote['current_workers'],
            'expected_price'=>$quote['current_price'],'expected_total'=>$quote['order_total']])->assertOk();
        $this->assertEquals(2,$visit->fresh()->workers_count);
        $this->actingAs(User::findOrFail(2),'api');
        $this->getJson($url.'/extra-hour?hours=1')->assertNotFound();
        $this->getJson($url.'/additional-worker')->assertNotFound();
    }
    public function test_unassigned_team_capacity_matches_panel_and_returns_after_cancellation(): void
    {
        $booking=$this->fixture(['auto_assign'=>['web_app'=>false]]); $this->customer();
        HourlyPackage::whereKey($booking['hourly_package_id'])->update(['start_times'=>['08:00'=>'all']]);
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $visit=HourlyRentalOrder::findOrFail($id)->visits()->firstOrFail();
        $this->assertSame(0,$visit->workers()->count());
        // Older or manually scheduled visits may hold team capacity without a named reservation.
        DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->delete();
        $query=['hourly_package_id'=>$booking['hourly_package_id'],'district_id'=>$booking['district_id'],'date'=>'2030-01-07','workers_per_visit'=>1];
        $panelRequest=\Illuminate\Http\Request::create('/panel/add-order/availability','POST',$query+['address_mode'=>'existing','user_id'=>1,'address_id'=>$booking['address_id']]);
        $panel=app(\App\Http\Controllers\Hourly\PanelBookingController::class)->availability($panelRequest,app(\App\Services\Hourly\PanelBookingService::class))->getData(true);
        $this->assertFalse($panel[0]['available']);
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonCount(0,'data');
        $visit->update(['status'=>'cancelled_by_client']);
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonCount(1,'data');
    }

    public function test_named_reservations_are_not_subtracted_twice_and_unassigned_demand_keeps_remaining_capacity(): void
    {
        $booking=$this->fixture(['auto_assign'=>['web_app'=>false]]); $this->secondWorker(); $this->customer();
        HourlyPackage::whereKey($booking['hourly_package_id'])->update(['start_times'=>['08:00'=>'all']]);
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $visit=HourlyRentalOrder::findOrFail($id)->visits()->firstOrFail();
        $this->assertSame(1,DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->count());
        $query=['hourly_package_id'=>$booking['hourly_package_id'],'district_id'=>$booking['district_id'],'date'=>'2030-01-07','workers_per_visit'=>1];
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonCount(1,'data');
        $this->getJson(route('web.hourly.slots',['workers_per_visit'=>2]+$query))->assertOk()->assertJsonCount(0,'data');
        DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->delete();
        $this->getJson(route('web.hourly.slots',$query))->assertOk()->assertJsonCount(1,'data');
        $this->getJson(route('web.hourly.slots',['workers_per_visit'=>2]+$query))->assertOk()->assertJsonCount(0,'data');
    }
    public function test_automatic_driver_assignment_excludes_residency_expiring_before_the_task_and_explains_failure(): void
    {
        $booking=$this->fixture(['auto_assign_driver'=>['web_app'=>true]]);$this->driverLocations();$this->customer();
        Schema::table('hourly_drivers',fn(Blueprint $t)=>$t->date('residency_expiry_date')->nullable());
        // Driver 2 is nearest and valid today, but not on tomorrow's visit date.
        DB::table('hourly_drivers')->where('id',2)->update(['residency_expiry_date'=>today()->toDateString()]);
        DB::table('hourly_drivers')->where('id',3)->update(['residency_expiry_date'=>'2030-01-07']);
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $visit=HourlyRentalOrder::findOrFail($id)->visits()->firstOrFail();
        $this->assertEquals(3,$visit->delivery_driver_id);$this->assertEquals(3,$visit->pickup_driver_id);
        // Reassigning an unassigned task must explain why all available GPS candidates are excluded.
        $visit->update(['delivery_driver_id'=>null,'pickup_driver_id'=>null]);
        DB::table('hourly_drivers')->where('id',3)->update(['residency_expiry_date'=>today()->toDateString()]);
        try {
            app(\App\Services\Hourly\NearestDriverAssignment::class)->assign($visit,false);
            $this->fail('Expired residency at task date must prevent automatic assignment.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('تنتهي إقامته',implode(' ',\Illuminate\Support\Arr::flatten($e->errors())));
        }
        $this->assertNull($visit->fresh()->delivery_driver_id);
    }

    public function test_driver_badge_distinguishes_expired_residency_from_upcoming_expiry(): void
    {
        $driver=(new HourlyDriver)->forceFill(['name'=>'Driver','residency_expiry_date'=>'2030-01-05']);
        $this->assertNotNull($driver->residencyAssignmentReason());
        $html=view('admin.hourly_drivers._assignment-eligibility',compact('driver'))->render();
        $this->assertStringContainsString('غير مؤهل للإسناد — الإقامة منتهية',$html);
        $this->assertStringContainsString('2030-01-05',$html);
        $driver->residency_expiry_date='2030-01-08';
        $this->assertNull($driver->residencyAssignmentReason());
        $this->assertNull($driver->residencyAssignmentReason(Carbon::parse('2030-01-08 23:59:59')));
        $this->assertNotNull($driver->residencyAssignmentReason(Carbon::parse('2030-01-09 00:00:00')));
        $html=view('admin.hourly_drivers._assignment-eligibility',compact('driver'))->render();
        $this->assertStringContainsString('data-driver-residency="expiring"',$html);
        $this->assertStringContainsString('2030-01-08',$html);
    }
    private function coverageStoppedOrder(): array
    {
        $booking=$this->fixture([],4);$this->customer();
        $order=HourlyRentalOrder::findOrFail($this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id'));
        $order->visits()->update(['visit_price'=>100]);
        $first=$order->visits()->orderBy('visit_number')->firstOrFail();
        $first->update(['status'=>'completed']);
        $order->update(['status'=>'active','total_amount'=>400,'paid_amount'=>400,'payment_status'=>'paid']);
        $order->payments()->create(['user_id'=>$order->user_id,'public_id'=>(string)Str::uuid(),'method'=>'cash','amount'=>400,'status'=>'paid','paid_at'=>now()]);
        DB::table('hourly_category_district')->where('service_category_id',$order->service_category_id)->update(['is_active'=>false]);
        return [$order,$first];
    }

    public function test_coverage_failure_refunds_three_unused_visits_without_touching_completed_visit(): void
    {
        [$order,$first]=$this->coverageStoppedOrder();
        $this->get(route('web.hourly.order',$order->id))->assertOk()->assertSee('توقف التغطية');
        $service=app(\App\Services\Hourly\MissedVisitRecovery::class);
        $before=(float)User::findOrFail(1)->hourly_wallet_balance;
        foreach($order->visits()->where('status','unscheduled')->get() as $visit){
            $token=(string)Str::uuid();
            $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),['type'=>'refund','request_token'=>$token])
                ->assertOk()->assertJsonPath('data.reason_code','coverage_unavailable');
            $review=['request_id'=>$token,'decision'=>'approve','verified_non_execution'=>true,'reason'=>'Coverage withdrawn by company'];
            $this->assertEquals(100,$service->review($order,$visit->id,$review,7)['refunded_amount']);
            $service->review($order,$visit->id,$review,7);
            $this->assertSame('cancelled',$visit->fresh()->status);
            $this->assertEquals(0,$visit->fresh()->cancellation_fee);
        }
        $this->assertSame('completed',$first->fresh()->status);
        $this->assertEquals(100,$order->fresh()->total_amount);
        $this->assertEquals(100,$order->fresh()->paid_amount);
        $this->assertEquals($before+300,User::findOrFail(1)->hourly_wallet_balance);
        $this->assertSame(3,$order->payments()->where('amount',-100)->count());
    }

    public function test_coverage_failure_preserves_unused_visits_past_expiry_and_reschedules_only_after_service_returns(): void
    {
        [$order,$first]=$this->coverageStoppedOrder();
        $order->update(['expires_at'=>'2030-01-05 23:59:59']);
        $this->artisan('hourly:expire-orders')->assertExitCode(0);
        $this->assertSame('active',$order->fresh()->status);
        $this->assertSame(3,$order->visits()->where('status','unscheduled')->count());
        $visit=$order->visits()->where('status','unscheduled')->firstOrFail();
        $token=(string)Str::uuid();
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),[
            'type'=>'reschedule','request_token'=>$token,'requested_start'=>'2030-01-10T08:00'])->assertOk();
        $review=['request_id'=>$token,'decision'=>'approve','verified_non_execution'=>true,'reason'=>'Coverage restored'];
        $service=app(\App\Services\Hourly\MissedVisitRecovery::class);
        try{$service->review($order,$visit->id,$review,7);$this->fail('Uncovered address must not be approved');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertSame('unscheduled',$visit->fresh()->status);}
        DB::table('hourly_category_district')->where('service_category_id',$order->service_category_id)->update(['is_active'=>true]);
        $service->review($order,$visit->id,$review,7);
        $this->assertSame('scheduled',$visit->fresh()->status);
        $this->assertTrue($order->fresh()->expires_at->gte($visit->fresh()->scheduled_end_at));
        $this->assertEquals(400,$order->fresh()->paid_amount);
        $this->assertEquals(400,$order->fresh()->total_amount);
        $this->assertSame('completed',$first->fresh()->status);
    }

    public function test_already_expired_unused_visit_can_be_refunded_for_coverage_failure_but_covered_visit_cannot(): void
    {
        [$order,$first]=$this->coverageStoppedOrder();
        $visit=$order->visits()->where('status','unscheduled')->firstOrFail();
        $order->visits()->where('status','unscheduled')->update(['status'=>'expired']);$order->update(['status'=>'expired']);
        $token=(string)Str::uuid();
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),['type'=>'refund','request_token'=>$token])->assertOk();
        $result=app(\App\Services\Hourly\MissedVisitRecovery::class)->review($order,$visit->id,[
            'request_id'=>$token,'decision'=>'approve','verified_non_execution'=>true,'reason'=>'Company coverage failure'],7);
        $this->assertEquals(100,$result['refunded_amount']);
        $this->assertSame('completed',$first->fresh()->status);
        $this->assertSame('expired',$order->fresh()->status);
        DB::table('hourly_category_district')->where('service_category_id',$order->service_category_id)->update(['is_active'=>true]);
        $other=$order->visits()->where('status','expired')->firstOrFail();
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$other->id]),[
            'type'=>'refund','request_token'=>(string)Str::uuid()])->assertUnprocessable();
    }

    public function test_admin_replaces_worker_directly_only_when_customer_selection_is_disabled_and_notifies_once(): void
    {
        $booking=$this->fixture(['customer_worker_selection'=>false],2);$this->secondWorker();$this->customer();
        $order=HourlyRentalOrder::findOrFail($this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id'));
        $order->update(['status'=>'active']);$visit=$order->visits()->orderBy('visit_number')->firstOrFail();
        $visit->workers()->sync([1]);$before=$visit->visit_price;$total=$order->total_amount;$paid=$order->paid_amount;
        $this->mock(\App\Services\Hourly\CustomerTrackingPushService::class,function($mock)use($visit){
            $mock->shouldReceive('notify')->once()->with($visit->id,'worker_replaced');
        });
        $body=['worker_id'=>1,'replacement_worker_id'=>2,'reason'=>'Verified emergency - private reason'];
        $visit->workers()->sync([1]);$url=route('admin.hourly-workers.replace',[$order->id,$visit->id]);
        $this->post($url,$body)->assertRedirect()->assertSessionHasNoErrors();
        $this->post($url,$body)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame([2],$visit->workers()->pluck('maids.id')->all());
        $this->assertEquals($before,$visit->fresh()->visit_price);$this->assertEquals($total,$order->fresh()->total_amount);
        $this->assertEquals($paid,$order->fresh()->paid_amount);$this->assertSame(1,(int)$visit->fresh()->workers_count);
        $this->assertSame(1,DB::table('hourly_order_audits')->where('action','worker_replaced')->count());
        $this->assertSame('scheduled',$visit->fresh()->status);
        $this->assertSame('unscheduled',$order->visits()->where('id','!=',$visit->id)->first()->status);
        $public=app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit->fresh(),$order->fresh());
        $this->assertNotNull($public['worker_replaced_at']);
        $this->assertStringNotContainsString('Verified emergency',json_encode($public));
        $this->get(route('web.hourly.order',$order->id))->assertOk()->assertSee('تم توفير عاملة بديلة لهذه الزيارة');
    }

    public function test_admin_replacement_rejects_customer_selection_unavailable_workers_and_started_visits(): void
    {
        $booking=$this->fixture(['customer_worker_selection'=>true]);$this->secondWorker();$this->customer();
        $order=HourlyRentalOrder::findOrFail($this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id'));
        $order->update(['status'=>'active']);$visit=$order->visits()->firstOrFail();
        $visit->workers()->sync([1]);$url=route('admin.hourly-workers.replace',[$order->id,$visit->id]);
        $body=['worker_id'=>1,'replacement_worker_id'=>2,'reason'=>'Private operational reason'];
        $this->postJson($url,$body)->assertUnprocessable();
        HourlyCategorySetting::where('service_category_id',$order->service_category_id)->update(['settings'=>['customer_worker_selection'=>false]]);
        app(\App\Services\Hourly\HourlyCategorySettings::class)->forget();
        DB::table('hourly_worker_leaves')->insert(['maid_id'=>2,'starts_at'=>'2030-01-07 00:00:00','ends_at'=>'2030-01-08 00:00:00','is_active'=>true]);
        $this->postJson($url,$body)->assertUnprocessable();
        $this->assertSame([1],$visit->workers()->pluck('maids.id')->all());
        DB::table('hourly_worker_leaves')->delete();$visit->update(['status'=>'en_route']);
        $this->postJson($url,$body)->assertUnprocessable();
        $this->assertSame([1],$visit->workers()->pluck('maids.id')->all());
        $this->assertSame(0,DB::table('hourly_order_audits')->where('action','worker_replaced')->count());
    }
    private function incidentFixture(bool $selection=true): array
    {
        (require base_path('database/migrations/2026_10_18_000003_create_hourly_service_incidents.php'))->up();
        $booking=$this->fixture(['customer_worker_selection'=>$selection],2);$this->secondWorker();$this->customer();
        $order=HourlyRentalOrder::findOrFail($this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id'));
        $order->update(['status'=>'active','paid_amount'=>200,'total_amount'=>200,'payment_status'=>'paid']);$order->visits()->update(['visit_price'=>100]);
        $visit=$order->visits()->orderBy('visit_number')->firstOrFail();$visit->workers()->sync([1]);
        $order->payments()->create(['user_id'=>1,'public_id'=>(string)Str::uuid(),'method'=>'cash','amount'=>200,'status'=>'paid','paid_at'=>now(),
            'provider_payload'=>['visit_allocations'=>[$visit->id=>10000]]]);
        $this->mock(\App\Services\Hourly\CustomerTrackingPushService::class,function($mock){$mock->shouldReceive('notify')->zeroOrMoreTimes();});
        $service=app(\App\Services\Hourly\ServiceIncident::class);
        $body=['request_token'=>(string)Str::uuid(),'category'=>'illness','note'=>'PRIVATE HEALTH DETAIL'];
        $report=$service->report($visit->id,1,'worker',1,$body);
        return [$order,$visit,$service,$report,$body];
    }

    private function approveIncident(int $id): void
    {
        $this->post(route('admin.hourly-incidents.review',$id),['decision'=>'approve','verified'=>true,
            'unavailable_until'=>'2030-01-09T23:00','reason'=>'PRIVATE ADMIN VERIFICATION'])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_incident_report_review_customer_replacement_and_privacy_are_end_to_end(): void
    {
        [$order,$visit,$service,$report,$body]=$this->incidentFixture();
        $this->assertSame($report,$service->report($visit->id,1,'worker',1,$body));
        $this->assertDatabaseCount('hourly_service_incidents',1);$this->assertDatabaseCount('hourly_worker_leaves',0);
        $this->get(route('admin.hourly-incidents.index'))->assertOk()->assertSee('PRIVATE HEALTH DETAIL');
        $this->postJson(route('admin.hourly-incidents.review',$report['id']),['decision'=>'approve','unavailable_until'=>'2030-01-09T23:00','reason'=>'test'])->assertUnprocessable();
        $this->approveIncident($report['id']);$this->approveIncident($report['id']);
        $this->assertDatabaseCount('hourly_worker_leaves',1);
        $this->assertDatabaseHas('hourly_worker_leaves',['maid_id'=>1,'is_active'=>true]);
        $this->get(route('web.hourly.order',$order->id))->assertOk()->assertSee('اختر بديلة متاحة')->assertDontSee('PRIVATE HEALTH DETAIL')->assertDontSee('PRIVATE ADMIN VERIFICATION');
        $public=app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit->fresh(),$order->fresh());
        $this->assertSame('awaiting_customer',$public['service_disruption']['status']);
        $this->assertSame([2],array_column($public['service_disruption']['replacement_options'],'id'));
        $this->assertFalse($public['can_cancel']);$this->assertFalse($public['can_reschedule']);
        try{\App\Services\Hourly\DeliveryStartWindow::assertAllowed($visit->fresh());$this->fail('Execution held');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertStringContainsString('بلاغ',$e->errors()['hourly'][0]);}
        $url=route('web.hourly.visit.disruption.choose',[$order->id,$visit->id]);
        $choice=['disruption_id'=>$public['service_disruption']['id'],'replacement_worker_id'=>2];
        $this->customer(2);$this->postJson($url,$choice)->assertNotFound();$this->customer();
        $this->postJson($url,$choice)->assertOk();$this->postJson($url,$choice)->assertOk();
        $this->assertSame([2],$visit->workers()->pluck('maids.id')->all());
        $this->assertSame('resolved',\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id)['status']);
        $this->assertEquals(200,$order->fresh()->total_amount);$this->assertEquals(200,$order->fresh()->paid_amount);
    }

    public function test_incident_refund_preserves_other_visits_and_is_not_ordinary_cancellation(): void
    {
        [$order,$visit,$service,$report]=$this->incidentFixture();$this->approveIncident($report['id']);
        $token=(string)Str::uuid();
        try{app(\App\Services\Hourly\CustomerVisitActions::class)->cancel($visit);$this->fail('Incident cancellation must use recovery');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertStringContainsString('بلاغ',$e->errors()['hourly'][0]);}
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),['type'=>'refund','request_token'=>$token])->assertOk()->assertJsonPath('data.reason_code','service_incident');
        $this->postJson(route('web.hourly.visit.disruption.choose',[$order->id,$visit->id]),[
            'disruption_id'=>\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id)['id'],'replacement_worker_id'=>2])->assertUnprocessable();
        $review=['request_id'=>$token,'decision'=>'approve','verified_non_execution'=>true,'reason'=>'Company could not fulfil visit'];
        $before=(float)User::findOrFail(1)->hourly_wallet_balance;
        $url=route('admin.hourly-recovery.review',[$order->id,$visit->id]);
        $this->post($url,$review)->assertRedirect()->assertSessionHasNoErrors();$this->post($url,$review)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cancelled',$visit->fresh()->status);$this->assertEquals(0,$visit->fresh()->cancellation_fee);
        $this->assertEquals($before+100,User::findOrFail(1)->hourly_wallet_balance);
        $this->assertEquals(100,$order->fresh()->paid_amount);$this->assertEquals(100,$order->fresh()->total_amount);
        $this->assertSame('unscheduled',$order->visits()->where('id','!=',$visit->id)->first()->status);
    }

    public function test_incident_reschedule_preserves_expired_credit_and_requires_available_workers(): void
    {
        [$order,$visit,$service,$report]=$this->incidentFixture();$this->approveIncident($report['id']);
        $order->update(['expires_at'=>'2030-01-06 08:00:00']);Carbon::setTestNow('2030-01-06 09:00:00');
        $this->artisan('hourly:expire-orders')->assertExitCode(0);$this->assertSame('active',$order->fresh()->status);
        $token=(string)Str::uuid();
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),[
            'type'=>'reschedule','requested_start'=>'2030-01-08T08:00','request_token'=>$token])->assertOk();
        $this->post(route('admin.hourly-recovery.review',[$order->id,$visit->id]),[
            'request_id'=>$token,'decision'=>'approve','verified_non_execution'=>true,'reason'=>'New available visit'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('2030-01-08 08:00',$visit->fresh()->scheduled_start_at->format('Y-m-d H:i'));
        $this->assertSame([2],$visit->workers()->pluck('maids.id')->all());
        $this->assertEquals(200,$order->fresh()->paid_amount);$this->assertEquals(200,$order->fresh()->total_amount);
        $this->assertTrue($order->fresh()->expires_at->gte($visit->fresh()->scheduled_end_at));
        $this->assertSame('resolved',\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id)['status']);
    }

    public function test_incident_disabled_selection_uses_admin_replacement_and_running_incident_requires_manual_review(): void
    {
        [$order,$visit,$service,$report]=$this->incidentFixture(false);$this->approveIncident($report['id']);
        $state=\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id);
        $this->assertSame('awaiting_admin',$state['status']);
        $this->postJson(route('web.hourly.visit.disruption.choose',[$order->id,$visit->id]),['disruption_id'=>$state['id'],'replacement_worker_id'=>2])->assertUnprocessable();
        $this->post(route('admin.hourly-workers.replace',[$order->id,$visit->id]),[
            'worker_id'=>1,'replacement_worker_id'=>2,'reason'=>'Verified operational replacement'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('resolved',\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id)['status']);
        $visit->update(['status'=>'en_route']);
        $report=$service->report($visit->id,2,'worker',2,['request_token'=>(string)Str::uuid(),'category'=>'accident','note'=>'PRIVATE EVENT']);
        $this->approveIncident($report['id']);
        $this->assertSame('manual_review',\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id)['status']);
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),['type'=>'refund','request_token'=>(string)Str::uuid()])->assertUnprocessable();
        $this->assertSame('en_route',$visit->fresh()->status);$this->assertEquals(200,$order->fresh()->paid_amount);
    }
    public function test_incident_rejection_does_not_hold_visit_and_lost_alternative_is_revalidated(): void
    {
        [$order,$visit,$service,$report]=$this->incidentFixture();
        $this->post(route('admin.hourly-incidents.review',$report['id']),['decision'=>'reject','reason'=>'Not confirmed'])->assertRedirect()->assertSessionHasNoErrors();
        $this->approveIncident($report['id']);
        $this->assertDatabaseCount('hourly_worker_leaves',0);
        $this->assertNull(\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id));
        $report=$service->report($visit->id,1,'worker',1,['request_token'=>(string)Str::uuid(),'category'=>'illness','note'=>'Another verified report']);
        $this->approveIncident($report['id']);
        $state=\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id);
        DB::table('hourly_worker_leaves')->insert(['maid_id'=>2,'starts_at'=>'2030-01-07 00:00:00','ends_at'=>'2030-01-08 00:00:00','is_active'=>true]);
        $this->postJson(route('web.hourly.visit.disruption.choose',[$order->id,$visit->id]),[
            'disruption_id'=>$state['id'],'replacement_worker_id'=>2])->assertUnprocessable();
        $this->assertSame([1],$visit->workers()->pluck('maids.id')->all());$this->assertEquals(200,$order->fresh()->paid_amount);
    }

    public function test_incident_unpaid_cancellation_has_no_fee_or_wallet_credit(): void
    {
        [$order,$visit,$service,$report]=$this->incidentFixture();$order->payments()->delete();$order->update(['paid_amount'=>0,'payment_status'=>'unpaid']);
        $this->approveIncident($report['id']);$token=(string)Str::uuid();$before=(float)User::findOrFail(1)->hourly_wallet_balance;
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),['type'=>'refund','request_token'=>$token])->assertOk();
        $this->post(route('admin.hourly-recovery.review',[$order->id,$visit->id]),[
            'request_id'=>$token,'decision'=>'approve','verified_non_execution'=>true,'reason'=>'Confirmed company failure'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cancelled',$visit->fresh()->status);$this->assertEquals(0,$visit->fresh()->cancellation_fee);
        $this->assertEquals($before,User::findOrFail(1)->hourly_wallet_balance);$this->assertSame(0,$order->payments()->count());
    }
    public function test_incident_pending_payment_blocks_refund_and_rolls_back_entire_resolution(): void
    {
        [$order,$visit,$service,$report]=$this->incidentFixture();$this->approveIncident($report['id']);
        $pending=$order->payments()->create(['user_id'=>1,'public_id'=>(string)Str::uuid(),'method'=>'moyasar','amount'=>100,'status'=>'pending']);
        $token=(string)Str::uuid();$before=(float)User::findOrFail(1)->hourly_wallet_balance;
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),['type'=>'refund','request_token'=>$token])->assertOk();
        $review=['request_id'=>$token,'decision'=>'approve','verified_non_execution'=>true,'reason'=>'Confirmed company failure'];
        $url=route('admin.hourly-recovery.review',[$order->id,$visit->id]);
        $this->postJson($url,$review)->assertUnprocessable();
        $this->assertSame('scheduled',$visit->fresh()->status);$this->assertEquals(200,$order->fresh()->paid_amount);
        $this->assertEquals($before,User::findOrFail(1)->hourly_wallet_balance);
        $this->assertSame('awaiting_customer',\App\Services\Hourly\ServiceIncident::state($order->fresh(),$visit->id)['status']);
        $this->assertSame('pending',$order->fresh()->booking_metadata['missed_visit_requests'][$visit->id]['status']);
        $pending->update(['status'=>'failed']);$this->post($url,$review)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('cancelled',$visit->fresh()->status);$this->assertEquals($before+100,User::findOrFail(1)->hourly_wallet_balance);
    }
    private function paidMissedVisit(int $paid=100): array
    {
        $booking=$this->fixture([],2);$this->customer();
        $id=$this->postJson(route('web.hourly.store'),$booking)->assertCreated()->json('id');
        $order=HourlyRentalOrder::findOrFail($id);$order->visits()->update(['visit_price'=>100]);
        $order->update(['status'=>'active','total_amount'=>200,'paid_amount'=>$paid]);
        $visit=$order->visits()->orderBy('visit_number')->firstOrFail();
        $order->payments()->create(['user_id'=>$order->user_id,'public_id'=>(string)Str::uuid(),'method'=>'cash','amount'=>$paid,'status'=>'paid',
            'provider_payload'=>['visit_allocations'=>[$visit->id=>10000]],'paid_at'=>now()]);
        Carbon::setTestNow('2030-01-08 07:00:00');
        return [$order,$visit];
    }

    public function test_missed_visit_refund_returns_only_allocated_payment_once_and_keeps_other_visits(): void
    {
        [$order,$visit]=$this->paidMissedVisit();
        $body=['type'=>'refund','request_token'=>(string)Str::uuid()];
        $url=route('web.hourly.visit.recovery',[$order->id,$visit->id]);
        $this->postJson($url,$body)->assertOk()->assertJsonPath('data.status','pending');
        $this->postJson($url,$body)->assertOk();
        $this->get(route('web.hourly.order',$order->id))->assertOk()->assertSee('قيد مراجعة الإدارة');
        $service=app(\App\Services\Hourly\MissedVisitRecovery::class);
        $review=['request_id'=>$body['request_token'],'decision'=>'approve','verified_non_execution'=>true,'reason'=>'تحققنا من عدم تنفيذ الخدمة'];
        $before=(float)User::findOrFail(1)->hourly_wallet_balance;
        $this->get(route('admin.hourly-recovery.index'))->assertOk()->assertSee($order->order_number);
        $this->post(route('admin.hourly-recovery.review',[$order->id,$visit->id]),$review)->assertRedirect()->assertSessionHasNoErrors();
        $result=$order->fresh()->booking_metadata['missed_visit_requests'][$visit->id];
        $this->assertEquals(100,$result['refunded_amount']);
        $this->assertSame('approved',$service->review($order,$visit->id,$review,7)['status']);
        $this->assertEquals($before+100,User::findOrFail(1)->hourly_wallet_balance);
        $this->assertSame('cancelled',$visit->fresh()->status);$this->assertEquals(0,$visit->fresh()->cancellation_fee);
        $this->assertEquals(100,$order->fresh()->total_amount);$this->assertEquals(0,$order->fresh()->paid_amount);
        $this->assertSame('active',$order->fresh()->status);
        $this->assertSame('unscheduled',$order->visits()->where('id','!=',$visit->id)->first()->status);
        $this->assertSame(1,$order->payments()->where('amount',-100)->count());
    }

    public function test_missed_visit_reschedule_preserves_price_and_extends_expired_validity_after_review(): void
    {
        [$order,$visit]=$this->paidMissedVisit(200);
        $order->update(['status'=>'expired','expires_at'=>'2030-01-07 23:59:59']);
        $body=['type'=>'reschedule','requested_start'=>'2030-01-10T08:00','request_token'=>(string)Str::uuid()];
        $this->postJson(route('web.hourly.visit.recovery',[$order->id,$visit->id]),$body)->assertOk();
        $service=app(\App\Services\Hourly\MissedVisitRecovery::class);
        $result=$service->review($order,$visit->id,['request_id'=>$body['request_token'],'decision'=>'approve',
            'verified_non_execution'=>true,'reason'=>'إعادة جدولة لتعذر تقديم الخدمة'],7);
        $this->assertSame('approved',$result['status']);
        $this->assertSame('2030-01-10 08:00',$visit->fresh()->scheduled_start_at->format('Y-m-d H:i'));
        $this->assertEquals(100,$visit->fresh()->visit_price);$this->assertEquals(200,$order->fresh()->total_amount);
        $this->assertEquals(200,$order->fresh()->paid_amount);$this->assertTrue($order->fresh()->expires_at->gte($visit->fresh()->scheduled_end_at));
    }

    public function test_missed_visit_request_ownership_validation_and_review_verification_are_enforced(): void
    {
        [$order,$visit]=$this->paidMissedVisit();$url=route('web.hourly.visit.recovery',[$order->id,$visit->id]);
        $body=['type'=>'refund','request_token'=>(string)Str::uuid()];
        $this->customer(2);$this->postJson($url,$body)->assertNotFound();$this->customer();
        $this->postJson($url,['type'=>'reschedule','request_token'=>(string)Str::uuid()])->assertUnprocessable();
        $this->postJson($url,$body)->assertOk();
        $service=app(\App\Services\Hourly\MissedVisitRecovery::class);
        try{$service->review($order,$visit->id,['request_id'=>$body['request_token'],'decision'=>'approve','reason'=>'test'],7);$this->fail('Verification required');}
        catch(\Illuminate\Validation\ValidationException $e){$this->assertSame('scheduled',$visit->fresh()->status);}
        $service->review($order,$visit->id,['request_id'=>$body['request_token'],'decision'=>'reject','reason'=>'الطلب يحتاج تحققًا إضافيًا'],7);
        $this->assertEquals(100,$order->fresh()->paid_amount);$this->assertSame('scheduled',$visit->fresh()->status);
        $this->get(route('web.hourly.order',$order->id))->assertOk()->assertSee('الطلب يحتاج تحققًا إضافيًا');
    }
}
