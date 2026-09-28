<?php
namespace Tests\Feature;

use Tests\HourlyTestCase as TestCase;
use App\Models\Hourly\{HourlyPackage,HourlyRentalOrder};
use App\Services\Hourly\{HourlyOrderService,HourlyPaymentService};
use App\Services\Hourly\HourlyMoyasarGateway;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Validation\ValidationException;

class HourlyCommerceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.hourly_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'database.default'=>'hourly_test']);
        config(['jwt.secret'=>str_repeat('test-hourly-secret',4), 'superadmin_modules.hourly_rental.default_enabled'=>true,'activitylog.enabled'=>false]);
        HourlyRentalSettings::flush();
        Schema::create('new_settings',function(Blueprint $t){$t->id();$t->string('key');$t->text('value');$t->timestamps();});
        Schema::create('cities',function(Blueprint $t){$t->id();$t->string('name_ar');$t->boolean('is_active')->default(true);});
        Schema::create('user_addresses',function(Blueprint $t){$t->id();$t->unsignedBigInteger('user_id');$t->unsignedBigInteger('city_id');$t->text('address');$t->decimal('latitude',10,7)->nullable();$t->decimal('longitude',10,7)->nullable();$t->string('district')->nullable();$t->timestamps();});
        (require base_path('database/migrations/2026_09_16_000006_create_hourly_commerce_tables.php'))->up();
        (require base_path('database/migrations/2026_09_17_000008_extend_hourly_service_items.php'))->up();
        DB::table('cities')->insert(['id'=>1,'name_ar'=>'الرياض']);
        DB::table('user_addresses')->insert(['id'=>1,'user_id'=>1,'city_id'=>1,'address'=>'العنوان الأول','latitude'=>24.7,'longitude'=>46.7]);
        HourlyRentalSettings::persist(['is_enabled'=>true]);
    }
    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }
    private function package(): HourlyPackage
    {
        $p=HourlyPackage::create(['code'=>'P1','category'=>'cleaning','hours_per_visit'=>4,'workers_per_visit'=>1,'visits_count'=>3,'validity_days'=>30,'price_per_visit'=>115,'extra_worker_price'=>57.5,'tax_rate'=>15,'is_active'=>true,'transport_included'=>true]);
        $p->translations()->create(['locale'=>'ar','title'=>'باقة']);$p->cities()->attach(1);return $p;
    }
    private function order(string $method='cash'): HourlyRentalOrder
    {
        return app(HourlyOrderService::class)->create(1,['hourly_package_id'=>$this->package()->id,'address_id'=>1,'payment_method'=>$method]);
    }
    public function test_purchase_snapshots_price_and_address_and_generates_credit_rows(): void
    {
        $o=$this->order();$this->assertSame(3,$o->balance['available']);$this->assertEquals(345,$o->total_amount);$this->assertEquals(45,$o->tax_amount);
        HourlyPackage::find($o->hourly_package_id)->update(['price_per_visit'=>999]);DB::table('user_addresses')->where('id',1)->update(['address'=>'تغيير']);
        $this->assertEquals(345,$o->fresh()->total_amount);$this->assertSame('العنوان الأول',$o->visits()->first()->address_text);
    }
    public function test_cash_confirmation_does_not_mark_order_paid_and_collection_is_bounded(): void
    {
        $o=$this->order();app(HourlyOrderService::class)->confirm($o,7);$this->assertSame('active',$o->fresh()->status);$this->assertSame('unpaid',$o->fresh()->payment_status);
        $service=app(HourlyPaymentService::class);$service->collect($o,['amount'=>100],7);$this->assertSame('partial',$o->fresh()->payment_status);
        $service->collect($o,['amount'=>245],7);$this->assertSame('paid',$o->fresh()->payment_status);
        $this->expectException(ValidationException::class);$service->collect($o,['amount'=>1],7);
    }
    public function test_other_clients_address_is_rejected(): void
    {
        $p=$this->package();$this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(HourlyOrderService::class)->create(2,['hourly_package_id'=>$p->id,'address_id'=>1,'payment_method'=>'cash']);
    }
    public function test_disabled_module_hides_api_and_expiry_does_nothing(): void
    {
        $o=$this->order();$o->update(['expires_at'=>now()->subDay()]);HourlyRentalSettings::persist(['is_enabled'=>false]);
        $this->getJson('/api/app/hourly/packages?city_id=1')->assertNotFound();
        $this->artisan('hourly:expire-orders')->assertExitCode(0);$this->assertSame('pending_confirmation',$o->fresh()->status);
    }
    public function test_expiry_only_expires_unused_visits_and_is_repeatable(): void
    {
        $o=$this->order();$o->visits()->first()->update(['status'=>'completed']);$o->update(['expires_at'=>now()->subDay()]);
        $this->artisan('hourly:expire-orders')->assertExitCode(0);$this->artisan('hourly:expire-orders')->assertExitCode(0);
        $this->assertSame(['available'=>0,'reserved'=>0,'consumed'=>1,'expired'=>2],$o->fresh()->balance);
        $this->assertSame(2,$o->statusLogs()->count());
    }
    public function test_payment_verifies_stored_invoice_and_is_idempotent(): void
    {
        $o=$this->order('moyasar');$gateway=\Mockery::mock(HourlyMoyasarGateway::class);
        $gateway->shouldReceive('createPaymentLink')->once()->andReturn(['id'=>'invoice-1','url'=>'https://example.test/pay']);
        $gateway->shouldReceive('getInvoice')->with('invoice-1')->once()->andReturn(['id'=>'invoice-1','status'=>'paid','amount'=>34500,'currency'=>'SAR']);
        $service=new HourlyPaymentService($gateway,app(HourlyOrderService::class));$p=$service->initiate($o);$this->assertSame($p->id,$service->initiate($o)->id);
        $p=$service->synchronize($p);$service->synchronize($p);$this->assertSame('active',$o->fresh()->status);$this->assertEquals(345,$o->fresh()->paid_amount);$this->assertSame(1,$o->payments()->count());
    }
    public function test_wrong_provider_amount_cannot_activate_order(): void
    {
        $o=$this->order('moyasar');$p=$o->payments()->create(['user_id'=>1,'public_id'=>(string)\Illuminate\Support\Str::uuid(),'method'=>'moyasar','provider'=>'moyasar','provider_reference'=>'i1','amount'=>345,'status'=>'pending']);
        $gateway=\Mockery::mock(HourlyMoyasarGateway::class);$gateway->shouldReceive('getInvoice')->andReturn(['id'=>'i1','status'=>'paid','amount'=>1,'currency'=>'SAR']);
        try {(new HourlyPaymentService($gateway,app(HourlyOrderService::class)))->synchronize($p);$this->fail('Must reject amount mismatch');}catch(ValidationException $e){$this->assertSame('unpaid',$o->fresh()->payment_status);}
    }
    public function test_all_admin_commerce_routes_require_feature_auth_and_permission(): void
    {
        foreach(app('router')->getRoutes() as $route){
            if(!preg_match('/^admin\.hourly-(packages|orders)\./',$route->getName()??''))continue;
            $this->assertContains('feature.hourly_rental',$route->gatherMiddleware());$this->assertContains('admin',$route->gatherMiddleware());
            $this->assertContains('admin.route.permission',$route->gatherMiddleware());$this->assertArrayHasKey($route->getName(),config('hourly_permissions'));
        }
    }
    public function test_slots_obey_notice_window_and_configured_interval(): void
    {
        HourlyRentalSettings::persist(['working_start'=>'09:00','working_end'=>'17:00','slot_minutes'=>30,'min_notice_hours'=>24]);
        $slots=app(\App\Services\Hourly\HourlySlotService::class)->forDate(now()->addDays(2)->toDateString(),4);
        $this->assertCount(9,$slots);
        $this->assertStringEndsWith('09:00:00',$slots[0]['starts_at']);
        $this->assertStringEndsWith('17:00:00',$slots[8]['ends_at']);
        $this->assertSame([],app(\App\Services\Hourly\HourlySlotService::class)->forDate(now()->toDateString(),4));
    }
    public function test_purchase_accepts_optional_slots_and_rejects_overlaps(): void
    {
        $p=$this->package(); $date=now()->addDays(2)->format('Y-m-d');
        $data=['hourly_package_id'=>$p->id,'address_id'=>1,'payment_method'=>'cash','scheduled_starts'=>[$date.' 09:00']];
        $o=app(HourlyOrderService::class)->create(1,$data);
        $this->assertSame(2,$o->balance['available']);$this->assertSame(1,$o->balance['reserved']);
        $data['scheduled_starts'][]=$date.' 09:30';
        try {app(HourlyOrderService::class)->create(1,$data);$this->fail('Overlap must be rejected');}
        catch(ValidationException $e){$this->assertSame(1,HourlyRentalOrder::count());}
    }
    public function test_disable_blockers_detect_an_outbound_visit(): void
    {
        $o=$this->order();$this->assertSame([],HourlyRentalSettings::disableBlockers());
        $o->visits()->first()->update(['status'=>'en_route']);
        $this->assertCount(1,HourlyRentalSettings::disableBlockers());
    }
    public function test_migration_can_resume_after_partial_mysql_table_creation(): void
    {
        Schema::drop('hourly_package_nationality');
        Schema::create('hourly_package_nationality',function(Blueprint $t){$t->unsignedBigInteger('hourly_package_id');$t->unsignedBigInteger('nationality_id');});
        $migration=require base_path('database/migrations/2026_09_16_000006_create_hourly_commerce_tables.php');
        $migration->up();$migration->up();
        $indexes=Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes('hourly_package_nationality');
        $this->assertArrayHasKey('hp_nationality_unique',$indexes);
    }

    public function test_invoice_generates_a_pdf_from_order_snapshot(): void
    {
        Schema::create('users',function(Blueprint $t){ $t->softDeletes();$t->id();$t->string('name');});
        DB::table('users')->insert(['id'=>1,'name'=>'عميل الاختبار']);
        $order=$this->order();
        (require base_path('database/migrations/2026_09_18_000001_create_hourly_service_categories.php'))->up();
        $response=app(\App\Http\Controllers\Hourly\OrderController::class)->invoice($order);
        $this->assertSame('application/pdf',$response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF',$response->getContent());
    }

}
