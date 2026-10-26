<?php
namespace Tests\Feature;

use Tests\HourlyTestCase as TestCase;
use App\Models\Hourly\{HourlyPackage,HourlyPackageLog};
use App\Services\Hourly\{HourlySlotService,HourlyOrderService};
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;

class HourlyServiceItemsTest extends TestCase
{
    public function test_service_item_can_be_saved_without_optional_details_and_scope(): void
    {
        $data = ['service_item_form'=>1,'category'=>'تنظيف','title_ar'=>'باقة دون بيانات إضافية',
            'total_price'=>200,'hours_per_visit'=>4,'workers_per_visit'=>1,'visits_count'=>1,
            'validity_days'=>30,'extra_worker_price'=>0,'extra_hour_price'=>0,'transport_fee'=>0,'sort_order'=>0];
        $this->postJson(route('admin.hourly-packages.store'), $data)->assertOk();
        $item = HourlyPackage::latest('id')->firstOrFail();
        $this->assertStringStartsWith('SI-', $item->code);
        $this->assertCount(0, $item->cities);
        $this->assertCount(0, $item->nationalities);
        $code = $item->code;
        $item->cities()->attach(1);
        $this->putJson(route('admin.hourly-packages.update', $item), $data + ['city_ids'=>[]])->assertOk();
        $this->assertCount(0, $item->fresh()->cities);
        $this->assertSame($code, $item->fresh()->code);
        $this->putJson(route('admin.hourly-packages.update', $item), $data + ['city_ids'=>[999999]])
            ->assertUnprocessable()->assertJsonValidationErrors('city_ids.0');
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.items_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'database.default'=>'items_test','superadmin_modules.hourly_rental.default_enabled'=>true,'activitylog.enabled'=>false]);
        Schema::create('new_settings',function(Blueprint $t){$t->id();$t->string('key');$t->text('value');$t->timestamps();});
        Schema::create('countries',function(Blueprint $t){$t->id();$t->string('code');});
        DB::table('countries')->insert([['id'=>1,'code'=>'SA'],['id'=>2,'code'=>'EG']]);
        Schema::create('cities',function(Blueprint $t){$t->id();$t->unsignedBigInteger('country_id')->default(1);$t->string('name_ar');$t->boolean('is_active')->default(true);});
        Schema::create('nationalities',function(Blueprint $t){$t->id();});
        Schema::create('user_addresses',function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id');$t->unsignedBigInteger('city_id');$t->string('address');$t->decimal('latitude');$t->decimal('longitude');$t->timestamps();});
        (require base_path('database/migrations/2026_09_16_000006_create_hourly_commerce_tables.php'))->up();
        (require base_path('database/migrations/2026_09_17_000008_extend_hourly_service_items.php'))->up();
        (require base_path('database/migrations/2026_09_18_000001_create_hourly_service_categories.php'))->up();
        DB::table('cities')->insert(['id'=>1,'name_ar'=>'الرياض']);
        DB::table('user_addresses')->insert(['id'=>1,'user_id'=>1,'city_id'=>1,'address'=>'الرياض','latitude'=>24.7,'longitude'=>46.7]);
        HourlyRentalSettings::flush();HourlyRentalSettings::persist(['is_enabled'=>true]);
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class,\App\Http\Middleware\EnsureAdminRoutePermission::class,\App\Http\Middleware\Admin::class]);
        app('view')->getFinder()->prependLocation(base_path('tests/Fixtures/hourly-views'));
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->name='مشرف الاختبار';$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
    }
    protected function tearDown(): void {HourlyRentalSettings::flush();parent::tearDown();}
    private function item(array $attributes=[]): HourlyPackage
    {
        $item=HourlyPackage::create($attributes+['code'=>uniqid('ITEM'),'category'=>'تنظيف','hours_per_visit'=>4,'workers_per_visit'=>1,'visits_count'=>3,'validity_days'=>30,'price_per_visit'=>100,'is_active'=>true,'tax_rate'=>15,'transport_included'=>true]);
        $item->translations()->create(['locale'=>'ar','title'=>'عنصر تجريبي']);$item->cities()->attach(1);return $item->fresh();
    }
    private function preview(array $data) {return $this->postJson(route('admin.hourly-service-items.preview'),$data);}
    public function test_bulk_prices_preview_then_apply_once_with_audit(): void
    {
        $item=$this->item();$other=$this->item(['category'=>'مكاتب']);
        $preview=$this->preview(['category'=>'تنظيف','type'=>'prices','prices'=>[$item->id=>300],'percentage'=>-10])->assertOk();
        $this->assertNull($item->fresh()->total_price);$this->assertSame(0,HourlyPackageLog::count());
        $token=$preview->json('token');$this->postJson(route('admin.hourly-service-items.apply'),['token'=>$token])->assertOk();
        $this->assertEquals(270,$item->fresh()->total_price);$this->assertNull($other->fresh()->total_price);
        $log=HourlyPackageLog::firstOrFail();$this->assertEquals(7,$log->admin_id);$this->assertSame('bulk_prices',$log->type);
        $this->postJson(route('admin.hourly-service-items.apply'),['token'=>$token])->assertStatus(422);
    }
    public function test_stale_preview_rolls_back_every_item(): void
    {
        $first=$this->item();$second=$this->item();
        $token=$this->preview(['category'=>'تنظيف','type'=>'prices','prices'=>[$first->id=>200,$second->id=>250]])->assertOk()->json('token');
        $second->increment('revision');
        $this->postJson(route('admin.hourly-service-items.apply'),['token'=>$token])->assertStatus(422);
        $this->assertNull($first->fresh()->total_price);$this->assertSame(0,HourlyPackageLog::count());
    }
    public function test_shifts_apply_only_to_active_items_and_public_slots_win_overlap(): void
    {
        $item=$this->item();$inactive=$this->item(['is_active'=>false]);
        $token=$this->preview(['category'=>'تنظيف','type'=>'shifts','shifts'=>['mode'=>'grid','first'=>'08:00','last'=>'09:00','step'=>30,'access'=>'all','dispatcher_enabled'=>1,'dispatcher_first'=>'08:00','dispatcher_last'=>'10:00','dispatcher_step'=>30]])->assertOk()->json('token');
        $this->postJson(route('admin.hourly-service-items.apply'),['token'=>$token])->assertOk();
        $this->assertSame('all',$item->fresh()->start_times['08:00']);$this->assertSame('dispatch',$item->fresh()->start_times['10:00']);$this->assertNull($inactive->fresh()->start_times);
    }
    public function test_invalid_grid_and_negative_price_are_rejected(): void
    {
        $item=$this->item();
        $this->preview(['category'=>'تنظيف','type'=>'shifts','shifts'=>['mode'=>'grid','first'=>'10:00','last'=>'08:00','step'=>0,'access'=>'all']])->assertStatus(422);
        $this->preview(['category'=>'تنظيف','type'=>'prices','prices'=>[$item->id=>-10]])->assertStatus(422);
    }
    public function test_package_total_is_exact_and_visit_rounding_preserves_total(): void
    {
        $item=$this->item(['total_price'=>100,'price_per_visit'=>33.33]);
        $order=app(HourlyOrderService::class)->create(1,['hourly_package_id'=>$item->id,'address_id'=>1,'payment_method'=>'cash']);
        $this->assertEquals(100,$order->total_amount);$this->assertEquals(100,$order->visits->sum('visit_price'));
        $item->update(['total_price'=>200]);$this->assertEquals(100,$order->fresh()->total_amount);
    }
    public function test_public_dispatch_and_closed_day_rules(): void
    {
        $date=now()->addDays(3);$service=app(HourlySlotService::class);$item=['start_times'=>['09:00'=>'all','10:00'=>'dispatch']];
        $this->assertCount(1,$service->forDate($date->toDateString(),4,0,$item));$this->assertCount(2,$service->forDate($date->toDateString(),4,0,$item,true));
        HourlyRentalSettings::persist(['closed_weekdays'=>[$date->dayOfWeek]]);
        $this->assertSame([],$service->forDate($date->toDateString(),4,0,$item));
        $this->assertCount(1,$service->forDate($date->toDateString(),4,0,$item+['ignore_closed_days'=>true]));
    }
    public function test_buffer_rejects_adjacent_visits(): void
    {
        $date=now()->addDays(3)->toDateString();$this->expectException(ValidationException::class);
        app(HourlySlotService::class)->validate([$date.' 08:00',$date.' 12:00'],4,0,now()->addDays(5),['start_times'=>['08:00'=>'all','12:00'=>'all'],'buffer_minutes'=>30]);
    }
    public function test_views_render_and_form_can_create_service_item(): void
    {
        (require base_path('database/migrations/2026_10_14_000003_add_hourly_package_payment_policy.php'))->up();
        $this->item();$this->get(route('admin.hourly-service-items.index'))->assertOk()->assertSee('عنصر تجريبي');
        $this->get(route('admin.hourly-packages.create'))->assertOk()->assertSee('إجمالي سعر الباقة')->assertSee('سياسة استحقاق الدفع');
        $this->postJson(route('admin.hourly-packages.store'),['service_item_form'=>1,'code'=>'NEW','payment_policy'=>'installments','deposit_amount'=>50,'monthly_amount'=>75,'category'=>'مكاتب','title_ar'=>'تنظيف المكاتب','package_type'=>'one_off','total_price'=>200,'duration_minutes'=>240,'hours_per_visit'=>4,'workers_per_visit'=>1,'visits_count'=>1,'validity_days'=>30,'extra_worker_price'=>0,'extra_hour_price'=>0,'transport_fee'=>0,'tax_rate'=>15,'sort_order'=>0,'city_ids'=>[1],'is_active'=>1,'show_on_app'=>1,'use_general_schedule'=>1])->assertOk();
        $this->assertEquals(200,HourlyPackage::where('code','NEW')->firstOrFail()->total_price);
        $this->assertSame('installments',HourlyPackage::where('code','NEW')->firstOrFail()->payment_policy);
        $this->get(route('admin.hourly-service-items.logs'))->assertOk()->assertSee('تنظيف المكاتب');
    }
    public function test_service_item_routes_keep_feature_and_permission_gates(): void
    {
        foreach(app('router')->getRoutes() as $route){if(!str_starts_with($route->getName()??'','admin.hourly-service-items.'))continue;
            $this->assertContains('feature.hourly_rental',$route->gatherMiddleware());$this->assertContains('admin',$route->gatherMiddleware());$this->assertContains('admin.route.permission',$route->gatherMiddleware());$this->assertArrayHasKey($route->getName(),config('hourly_permissions'));
        }
        config(['superadmin_modules.hourly_rental.default_enabled'=>false]);$this->get(route('admin.hourly-service-items.index'))->assertNotFound();
    }
    public function test_quick_price_status_and_break_threshold_are_logged(): void
    {
        $short=$this->item();$long=$this->item(['hours_per_visit'=>6]);
        $this->putJson(route('admin.hourly-service-items.price',$short),['total_price'=>333])->assertOk();
        $this->assertEquals(111,$short->fresh()->price_per_visit);
        $token=$this->preview(['category'=>'تنظيف','type'=>'breaks','minimum_duration'=>360,'break_minutes'=>30])->assertOk()->json('token');
        $this->postJson(route('admin.hourly-service-items.apply'),['token'=>$token])->assertOk();
        $this->assertEquals(0,$short->fresh()->break_minutes);$this->assertEquals(30,$long->fresh()->break_minutes);
        $this->putJson(route('admin.hourly-service-items.status',$short),['is_active'=>false])->assertOk();
        $this->assertFalse($short->fresh()->is_active);$this->assertSame('status',HourlyPackageLog::latest('id')->first()->type);
    }
    public function test_private_items_cannot_be_purchased_by_client(): void
    {
        $item=$this->item(['is_private'=>true]);$this->expectException(ValidationException::class);
        app(HourlyOrderService::class)->create(1,['hourly_package_id'=>$item->id,'address_id'=>1,'payment_method'=>'cash']);
    }
    public function test_dispatch_time_cannot_be_booked_by_client_but_admin_can(): void
    {
        $item=$this->item(['start_times'=>['09:00'=>'dispatch']]);$data=['hourly_package_id'=>$item->id,'address_id'=>1,'payment_method'=>'cash','scheduled_starts'=>[now()->addDays(3)->format('Y-m-d').' 09:00']];
        $order=app(HourlyOrderService::class)->create(1,$data,7);$this->assertSame('scheduled',$order->visits()->first()->status);
        $this->expectException(ValidationException::class);app(HourlyOrderService::class)->create(1,$data);
    }

    public function test_package_city_picker_and_saving_allow_only_active_saudi_cities(): void
    {
        DB::table('cities')->insert([
            ['id'=>2,'country_id'=>2,'name_ar'=>'القاهرة','is_active'=>true],
            ['id'=>3,'country_id'=>1,'name_ar'=>'مدينة سعودية موقوفة','is_active'=>false],
        ]);
        $this->get(route('admin.hourly-packages.create'))->assertOk()
            ->assertViewHas('cities',fn($cities)=>$cities->pluck('id')->all()===[1]);
        $this->withHeader('X-Requested-With','XMLHttpRequest')->get(route('admin.hourly-packages.create'))->assertOk()
            ->assertViewHas('cities',fn($cities)=>$cities->pluck('id')->all()===[1]);
        $payload=['service_item_form'=>1,'code'=>'SA-ONLY','category'=>'مكاتب','title_ar'=>'باقة سعودية','package_type'=>'one_off',
            'total_price'=>200,'duration_minutes'=>240,'hours_per_visit'=>4,'workers_per_visit'=>1,'visits_count'=>1,'validity_days'=>30,
            'extra_worker_price'=>0,'extra_hour_price'=>0,'transport_fee'=>0,'tax_rate'=>15,'sort_order'=>0,'city_ids'=>[2],
            'is_active'=>1,'show_on_app'=>1,'use_general_schedule'=>1];
        $this->postJson(route('admin.hourly-packages.store'),$payload)->assertUnprocessable()->assertJsonValidationErrors('city_ids');
        $payload['city_ids']=[3];
        $this->postJson(route('admin.hourly-packages.store'),$payload)->assertUnprocessable()->assertJsonValidationErrors('city_ids');
        $this->assertFalse(HourlyPackage::where('code','SA-ONLY')->exists());
        $payload['city_ids']=[1];$this->postJson(route('admin.hourly-packages.store'),$payload)->assertOk();
        $item=HourlyPackage::where('code','SA-ONLY')->firstOrFail();
        $payload['city_ids']=[1,2];
        $this->putJson(route('admin.hourly-packages.update',$item),$payload)->assertUnprocessable()->assertJsonValidationErrors('city_ids');
        $this->assertEquals([1],$item->fresh()->cities->pluck('id')->all());
    }

}
