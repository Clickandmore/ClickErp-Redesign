<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyDriver, HourlyCategorySetting, HourlyRentalOrder};
use App\Models\ingaz\User;
use App\Services\Hourly\HourlyMoyasarGateway;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\HourlyTestCase as TestCase;

/** واجهات حجز الساعات للموقع الخارجي (api/client/hourly/*) وخيار إظهارها في الموقع. */
class HourlyExternalWebsiteApiTest extends TestCase
{
    use BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2030-01-06 07:00:00'));
        $this->setUpHourlyCategorySchema(); $this->buildWorkers();
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

    private function fixture(): array
    {
        $category=$this->category();$district=$this->district('الملز',24.7,46.7);$team=$this->team();
        $category->districts()->attach($district->id,['is_active'=>true,'team_id'=>$team->id]);
        HourlyCategorySetting::create(['service_category_id'=>$category->id,'settings'=>['customer_worker_selection'=>true]]);
        $item=$this->item($category,['visits_count'=>1]);
        DB::table('maids')->insert(['id'=>1,'name'=>'عاملة ساعات','rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>now()->toDateString(),
            'hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])])]);
        DB::table('maid_jobs')->insert(['maid_id'=>1,'job_type_id'=>2]);
        DB::table('contracts')->insert(['id'=>1,'maid_id'=>1,'status'=>'shelter']);
        DB::table('contract_infos')->insert(['contract_id'=>1,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>$team->id,'member_type'=>'maid','member_id'=>1,'role'=>'primary','is_active'=>true]);
        return ['booking_token'=>(string)Str::uuid(),'hourly_package_id'=>$item->id,'district_id'=>$district->id,
            'address_id'=>$this->address(24.7,46.7,'الملز'),'workers_per_visit'=>1,'payment_method'=>'cash','scheduled_starts'=>['2030-01-07 08:00']];
    }

    private function customer(int $id=1): void { $this->actingAs(User::findOrFail($id),'api'); }

    public function test_existing_booking_is_reported_even_when_no_worker_slots_remain(): void
    {
        $booking=$this->fixture();$this->customer();
        $id=$this->postJson('/api/client/hourly/orders',$booking)->assertCreated()->json('data.id');
        $catalog=\Mockery::mock(\App\Services\Hourly\HourlyWebsiteCatalog::class)->makePartial();
        $catalog->shouldReceive('slots')->andReturn([]);
        $this->app->instance(\App\Services\Hourly\HourlyWebsiteCatalog::class,$catalog);
        $query=['hourly_package_id'=>$booking['hourly_package_id'],'district_id'=>$booking['district_id'],
            'workers_per_visit'=>1,'date'=>'2030-01-07','address_id'=>$booking['address_id']];
        $this->getJson('/api/client/hourly/slots?'.http_build_query($query))->assertOk()
            ->assertJsonCount(0,'data')->assertJsonPath('existing_bookings.visits.0.order_id',$id);
        $query['date']='2030-01-08';
        $this->getJson('/api/client/hourly/slots?'.http_build_query($query))->assertOk()->assertJsonCount(0,'existing_bookings.visits');
        $this->customer(2);$query['date']='2030-01-07';
        $this->getJson('/api/client/hourly/slots?'.http_build_query($query))->assertNotFound();
        $query['address_id']=$this->address(24.7,46.7,'الملز');
        DB::table('user_addresses')->where('id',$query['address_id'])->update(['user_id'=>2]);
        $this->getJson('/api/client/hourly/slots?'.http_build_query($query))->assertOk()->assertJsonCount(0,'existing_bookings.visits');
    }

    public function test_catalog_package_and_location_follow_the_website_sequence(): void
    {
        $booking=$this->fixture();
        $this->getJson('/api/client/hourly/catalog')->assertOk()
            ->assertJsonPath('data.city',1)
            ->assertJsonPath('data.packages.0.id',$booking['hourly_package_id']);
        $this->getJson('/api/client/hourly/packages/'.$booking['hourly_package_id'].'?city=1')->assertOk()
            ->assertJsonPath('data.package.id',$booking['hourly_package_id'])
            ->assertJsonPath('data.districts.0.id',$booking['district_id']);
        $this->postJson('/api/client/hourly/location',['district_id'=>$booking['district_id']])->assertOk()
            ->assertJsonPath('data.available',true)->assertJsonPath('data.city_id',1);
        $this->getJson('/api/client/hourly/location/options')->assertOk()
            ->assertJsonPath('data.0.districts.0.available',true);
    }

    public function test_website_toggle_hides_only_the_website_api_and_keeps_the_app_api(): void
    {
        $this->fixture();
        HourlyRentalSettings::persist(['show_on_website'=>false]);
        $this->assertFalse(HourlyRentalSettings::websiteEnabled());
        $this->assertTrue(HourlyRentalSettings::isEnabled());
        $this->getJson('/api/client/hourly/catalog')->assertNotFound();
        $this->customer();
        $this->getJson('/api/app/hourly/cities')->assertOk();
        HourlyRentalSettings::persist(['show_on_website'=>true]);
        $this->getJson('/api/client/hourly/catalog')->assertOk();
    }

    public function test_customer_books_and_reads_order_with_website_details(): void
    {
        $booking=$this->fixture();$this->customer();
        $this->getJson('/api/client/hourly/checkout/'.$booking['hourly_package_id'].'?city=1')->assertOk()
            ->assertJsonPath('data.addresses.0.id',$booking['address_id'])
            ->assertJsonPath('data.coverage.0.id',$booking['district_id']);
        $this->getJson('/api/client/hourly/slots?'.http_build_query(['hourly_package_id'=>$booking['hourly_package_id'],
            'district_id'=>$booking['district_id'],'date'=>'2030-01-07','workers_per_visit'=>1]))->assertOk()->assertJsonPath('time_selection','time');
        $id=$this->postJson('/api/client/hourly/orders',$booking)->assertCreated()->assertJsonPath('data.payment_url',null)->json('data.id');
        $order=HourlyRentalOrder::findOrFail($id);
        $this->assertSame('website',$order->booking_metadata['booking_channel']);
        $this->getJson('/api/client/hourly/orders/'.$id)->assertOk()
            ->assertJsonPath('data.order_number',$order->order_number)
            ->assertJsonPath('data.website.slot_query.district_id',$booking['district_id']);
        $this->customer(2);
        $this->getJson('/api/client/hourly/orders/'.$id)->assertNotFound();
    }

    public function test_moyasar_returns_to_the_external_website_page(): void
    {
        $booking=$this->fixture();$this->customer();$booking['payment_method']='moyasar';
        $gateway=\Mockery::mock(HourlyMoyasarGateway::class);
        $gateway->shouldReceive('createPaymentLink')->once()->withArgs(fn($amount,$description,$callback,$metadata,$success)=>str_starts_with($success,'https://site.example/hourly/payment-return?payment='))
            ->andReturn(['id'=>'invoice-1','url'=>'https://pay.example.test/invoice-1']);
        $gateway->shouldReceive('getInvoice')->once()->with('invoice-1')->andReturn(['id'=>'invoice-1','status'=>'pending']);
        $this->app->instance(HourlyMoyasarGateway::class,$gateway);
        $id=$this->postJson('/api/client/hourly/orders',$booking+['return_url'=>'https://site.example/hourly/payment-return'])
            ->assertCreated()->assertJsonPath('data.payment_url','https://pay.example.test/invoice-1')->json('data.id');
        $payment=HourlyRentalOrder::findOrFail($id)->payments()->first();
        $this->getJson('/api/client/hourly/payments/moyasar/return?payment='.$payment->public_id)->assertOk()->assertJsonPath('data.order_id',$id);
        $this->assertSame('pending',$payment->fresh()->status);
    }

    public function test_return_url_must_match_configured_hosts(): void
    {
        config(['services.hourly_website.return_hosts'=>['site.example']]);
        $booking=$this->fixture();$this->customer();$booking['payment_method']='moyasar';
        $this->postJson('/api/client/hourly/orders',$booking+['return_url'=>'https://evil.example/back'])
            ->assertStatus(422)->assertJsonValidationErrors('return_url');
        $this->assertDatabaseCount('hourly_rental_orders',0);
    }

    public function test_settings_resource_exposes_the_website_flag(): void
    {
        $this->assertTrue(HourlyRentalSettings::websiteEnabled());
        HourlyRentalSettings::persist(['show_on_website'=>false]);
        $this->assertFalse(HourlyRentalSettings::all()['show_on_website']);
        $this->assertFalse(HourlyRentalSettings::websiteEnabled());
    }
}
