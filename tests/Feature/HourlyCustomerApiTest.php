<?php

namespace Tests\Feature;

use App\Models\Hourly\HourlyRentalOrder;
use App\Models\ingaz\User;
use App\Services\Hourly\HourlyMoyasarGateway;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Queue, Schema, Storage};
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

class HourlyCustomerApiTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHourlyCategorySchema();
        Carbon::setTestNow(Carbon::parse('2030-01-06 07:00:00'));
        Schema::create('users', function (Blueprint $t) { $t->softDeletes(); $t->id(); $t->string('name'); $t->decimal('hourly_wallet_balance',12,2)->default(0); });
        DB::table('users')->insert([['id'=>1,'name'=>'عميل','hourly_wallet_balance'=>1000],['id'=>2,'name'=>'عميل آخر','hourly_wallet_balance'=>0]]);
        Schema::create('hourly_wallet_transactions', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id'); $t->string('type')->nullable();
            $t->unsignedBigInteger('hourly_rental_order_id')->nullable()->unique(); $t->unsignedBigInteger('linked_order_id')->nullable();
            $t->decimal('amount',12,2); $t->decimal('balance_after',12,2); $t->string('payment_method')->nullable();
            $t->string('collected_by_type')->nullable(); $t->unsignedBigInteger('collected_by_id')->nullable(); $t->decimal('vat_rate')->nullable();
            $t->string('description')->nullable(); $t->dateTime('paid_at')->nullable(); $t->unsignedBigInteger('admin_id')->nullable(); $t->timestamps();
        });
        Schema::table('user_addresses', function (Blueprint $t) { foreach (['title','name','phone','notes'] as $field) $t->string($field)->default(''); });
        Schema::table('hourly_rental_orders', function (Blueprint $t) { $t->uuid('booking_token')->nullable()->unique(); $t->json('booking_metadata')->nullable(); });
        auth('api')->setUser(new User(['id'=>1]));
        $this->withHeader('lang','ar');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    public function test_cities_only_returns_active_saudi_cities_within_hourly_scope(): void
    {
        DB::table('cities')->insert([
            ['id'=>3,'country_id'=>2,'name_ar'=>'دبي','is_active'=>true],
            ['id'=>4,'country_id'=>1,'name_ar'=>'مدينة متوقفة','is_active'=>false],
        ]);
        $this->getJson('/api/app/hourly/cities')->assertOk()->assertJsonCount(2,'data')
            ->assertJsonPath('data.0.id',1)->assertJsonPath('data.1.id',2);
        HourlyRentalSettings::persist(['city_ids'=>[2,3,4]]);
        $this->getJson('/api/app/hourly/cities')->assertOk()->assertJsonCount(1,'data')
            ->assertJsonPath('data.0.id',2);
    }

    private function checkout(): array
    {
        $category=$this->category(['name_ar'=>'تنظيف عام']);
        $district=$this->district('الصحافة',24.7,46.7);
        $category->districts()->attach($district->id,['is_active'=>true]);
        $item=$this->item($category,['visits_count'=>4,'total_price'=>720,'extra_worker_price'=>50,'start_times'=>['08:00'=>'all','14:00'=>'all']]);
        return [$item,$district,['hourly_package_id'=>$item->id,'address_id'=>$this->address(24.7,46.7),'payment_method'=>'moyasar','workers_per_visit'=>1,'scheduled_starts'=>['2030-01-07 08:00']]];
    }

    public function test_catalog_detail_and_checkout_share_prices_and_first_visit_only(): void
    {
        [$item,$district,$payload]=$this->checkout();
        $this->getJson('/api/app/hourly/packages?city_id=1')->assertOk()
            ->assertJsonPath('data.0.service.name','تنظيف عام')
            ->assertJsonPath('data.0.category_id',$item->service_category_id)
            ->assertJsonPath('data.0.price',720)
            ->assertJsonPath('data.0.total_price',720)
            ->assertJsonPath('data.0.total_amount',720)
            ->assertJsonPath('data.0.price_per_visit',180)
            ->assertJsonPath('data.0.currency','SAR')
            ->assertJsonPath('data.0.pricing.total_amount',720);
        $detail=$this->getJson('/api/app/hourly/packages/'.$item->id.'?city_id=1&workers=2')->assertOk()->assertJsonPath('data.total_hours',16)->assertJsonPath('data.pricing.total_amount',920)->json('data');
        $payload['workers_per_visit']=2;
        $created=$this->postJson('/api/app/hourly/orders',$payload+['total_amount'=>1,'status'=>'active'])->assertCreated()
            ->assertJsonPath('data.status','pending_confirmation')->assertJsonPath('data.remaining_visits',4)->assertJsonPath('data.balance.available',3)->assertJsonPath('data.balance.reserved',1)->assertJsonCount(4,'data.visits')->json('data');
        $this->assertSame($created['id'],$created['order_id']);
        $this->assertEquals($detail['pricing']['total_amount'],$created['total_amount']);
        $this->assertSame('scheduled',$created['visits'][0]['status']);
        $this->assertSame('مجدولة',$created['visits'][0]['status_label']);
        $this->assertSame('unscheduled',$created['visits'][1]['status']);
        $this->assertArrayNotHasKey('booking_metadata',$created);
        $this->getJson('/api/app/hourly/orders')->assertOk()->assertJsonPath('data.0.remaining_visits',4);
    }

    public function test_visit_keeps_coverage_team_when_immediate_auto_assignment_is_disabled(): void
    {
        [$item,$district,$payload]=$this->checkout();
        $team=$this->team('فريق التغطية');
        $item->serviceCategory->districts()->updateExistingPivot($district->id,['team_id'=>$team->id]);
        $item->update(['settings_overrides'=>['auto_assign'=>['web_app'=>false]]]);

        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');

        $this->assertDatabaseHas('hourly_visits',[
            'id'=>$order['visits'][0]['id'],
            'hourly_team_id'=>$team->id,
        ]);
        $this->assertDatabaseMissing('hourly_visit_workers',['hourly_visit_id'=>$order['visits'][0]['id']]);
    }

    public function test_one_off_package_exposes_its_configured_price_to_flat_app_models(): void
    {
        $item=$this->item($this->category(),[
            'code'=>'SI-SFBNFNHZ', 'visits_count'=>1, 'total_price'=>200, 'price_per_visit'=>200,
        ]);

        $this->getJson('/api/app/hourly/packages/'.$item->id.'?city_id=1')
            ->assertOk()
            ->assertJsonPath('data.price',200)
            ->assertJsonPath('data.total_price',200)
            ->assertJsonPath('data.total_amount',200)
            ->assertJsonPath('data.price_per_visit',200)
            ->assertJsonPath('data.pricing.display_amount',200);
    }

    public function test_visit_status_includes_the_localized_customer_label(): void
    {
        [$item,$district,$payload]=$this->checkout();
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        DB::table('hourly_visits')->where('id',$order['visits'][0]['id'])->update(['status'=>'en_route']);

        $this->getJson('/api/app/hourly/orders/'.$order['id'])
            ->assertOk()
            ->assertJsonPath('data.visits.0.status','en_route')
            ->assertJsonPath('data.visits.0.status_label','في الطريق');
    }

    public function test_private_inactive_wrong_city_and_other_users_resources_are_hidden(): void
    {
        [$item,$district,$payload]=$this->checkout();
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        $item->update(['is_private'=>true]);
        $this->getJson('/api/app/hourly/packages?city_id=1')->assertOk()->assertJsonPath('data',[]);
        $this->getJson('/api/app/hourly/packages/'.$item->id.'?city_id=1')->assertNotFound();
        $item->update(['is_private'=>false]);
        $this->getJson('/api/app/hourly/packages/'.$item->id.'?city_id=2')->assertNotFound();
        auth('api')->setUser(new User(['id'=>2]));
        $this->getJson('/api/app/hourly/orders')->assertOk()->assertJsonPath('data',[]);
        foreach (['','/visits/'.$order['visits'][0]['id'],'/payments/moyasar'] as $suffix) $this->getJson('/api/app/hourly/orders/'.$order['id'].$suffix)->assertNotFound();
        $this->postJson('/api/app/hourly/orders/'.$order['id'].'/visits/'.$order['visits'][1]['id'].'/schedule',['scheduled_start'=>'2030-01-08 08:00'])->assertNotFound();
        $this->postJson('/api/app/hourly/orders',$payload)->assertNotFound();
    }

    public function test_address_is_owned_and_supports_shared_address_schema(): void
    {
        $payload=['title'=>'المنزل','name'=>'عميل','contact_phone'=>'0500000000','city_id'=>1,'address'=>'الصحافة','latitude'=>24.7,'longitude'=>46.7];
        $id=$this->postJson('/api/app/hourly/addresses',$payload+['user_id'=>2])->assertCreated()->json('data.id');
        $this->assertDatabaseHas('user_addresses',['id'=>$id,'user_id'=>1,'phone'=>'0500000000','notes'=>'']);
        $this->getJson('/api/app/hourly/addresses')->assertOk()->assertJsonPath('data.0.id',$id);
        $this->getJson('/api/app/hourly/addresses')->assertOk()->assertJsonPath('data.0.title','المنزل')->assertJsonPath('data.0.name','عميل');
        auth('api')->setUser(new User(['id'=>2]));
        $this->putJson('/api/app/hourly/addresses/'.$id.'/location',$payload)->assertNotFound();
        $this->getJson('/api/app/hourly/addresses')->assertOk()->assertJsonPath('data',[]);
    }

    public function test_checkout_retry_reuses_order_and_rejects_changed_input(): void
    {
        [$item,$district,$payload]=$this->checkout();
        $payload['booking_token']=(string) \Illuminate\Support\Str::uuid();
        $id=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data.id');
        $this->postJson('/api/app/hourly/orders',array_reverse($payload,true))->assertCreated()->assertJsonPath('data.id',$id);
        $payload['workers_per_visit']=2;
        $this->postJson('/api/app/hourly/orders',$payload)->assertStatus(409);
        $this->assertSame(1,HourlyRentalOrder::count());
        $this->assertSame(4,HourlyRentalOrder::first()->visits()->count());
    }

    public function test_unscheduled_credit_can_be_scheduled_once_without_using_reschedule_allowance(): void
    {
        [$item,$district,$payload]=$this->checkout();
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        $visit=$order['visits'][1]['id'];
        $url='/api/app/hourly/orders/'.$order['id'].'/visits/'.$visit.'/schedule';
        $this->postJson($url,['scheduled_start'=>'2030-01-07 14:00'])->assertStatus(422);
        $this->postJson($url,['scheduled_start'=>'2030-01-08 08:00'])->assertOk()->assertJsonPath('data.status','scheduled');
        $this->postJson($url,['scheduled_start'=>'2030-01-09 08:00'])->assertStatus(422);
        $this->assertDatabaseHas('hourly_visits',['id'=>$visit,'reschedule_count'=>0,'scheduled_start_at'=>'2030-01-08 08:00:00']);
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()->assertJsonPath('data.balance.available',2)->assertJsonPath('data.remaining_visits',4);
    }

    public function test_expiry_and_operating_slots_are_enforced(): void
    {
        [$item,$district,$payload]=$this->checkout();
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        $url='/api/app/hourly/orders/'.$order['id'].'/visits/'.$order['visits'][1]['id'].'/schedule';
        $this->postJson($url,['scheduled_start'=>'2030-01-08 09:00'])->assertStatus(422);
        $this->postJson($url,['scheduled_start'=>'2030-02-10 08:00'])->assertStatus(422);
        HourlyRentalOrder::find($order['id'])->update(['expires_at'=>now()->subMinute()]);
        $this->postJson($url,['scheduled_start'=>'2030-01-08 08:00'])->assertStatus(422);
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()->assertJsonPath('data.display_status','expired')->assertJsonPath('data.visits.1.can_schedule',false);
    }

    public function test_moyasar_link_is_reused_and_only_verified_invoice_activates_order(): void
    {
        [$item,$district,$payload]=$this->checkout();
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        $gateway=\Mockery::mock(HourlyMoyasarGateway::class);
        $gateway->shouldReceive('createPaymentLink')->once()->withArgs(fn($amount,$description,$callback,$metadata,$success)=>$amount==720 && str_contains($callback,'payments/moyasar/webhook') && str_contains($success,'payment=') && isset($metadata['hourly_payment_id']))->andReturn(['id'=>'invoice-test','url'=>'https://example.test/pay']);
        $gateway->shouldReceive('getInvoice')->with('invoice-test')->once()->andReturn(['id'=>'invoice-test','status'=>'paid','amount'=>72000,'currency'=>'SAR']);
        $this->app->instance(HourlyMoyasarGateway::class,$gateway);
        $url='/api/app/hourly/orders/'.$order['id'].'/payments/moyasar';
        $payment=$this->postJson($url)->assertOk()->assertJsonPath('data.status','pending')->json('data');
        $this->postJson($url)->assertOk()->assertJsonPath('data.public_id',$payment['public_id']);
        $this->assertDatabaseHas('hourly_rental_orders',['id'=>$order['id'],'payment_status'=>'unpaid']);
        $this->getJson($url)->assertOk()->assertJsonPath('data.status','paid');
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()->assertJsonPath('data.status','active')->assertJsonPath('data.payment_status','paid');
    }

    public function test_invoice_callback_and_payment_webhook_verify_once_and_activate_order(): void
    {
        [,,$payload]=$this->checkout();
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        $model=HourlyRentalOrder::findOrFail($order['id']);
        $payment=$model->payments()->create(['user_id'=>1,'public_id'=>(string)\Illuminate\Support\Str::uuid(),'method'=>'moyasar','provider'=>'moyasar','provider_reference'=>'invoice-callback','amount'=>720,'status'=>'pending']);
        $gateway=\Mockery::mock(HourlyMoyasarGateway::class);
        $gateway->shouldReceive('getInvoice')->with('invoice-callback')->once()->andReturn(['id'=>'invoice-callback','status'=>'paid','amount'=>72000,'currency'=>'SAR']);
        $this->app->instance(HourlyMoyasarGateway::class,$gateway);
        $this->postJson('/api/app/hourly/payments/moyasar/webhook',['id'=>'invoice-callback','status'=>'paid'])->assertOk();
        $this->postJson('/api/app/hourly/payments/moyasar/webhook',['data'=>['invoice_id'=>'invoice-callback','id'=>'payment-id']])->assertOk();
        $this->assertDatabaseHas('hourly_rental_orders',['id'=>$model->id,'payment_status'=>'paid','paid_amount'=>720,'status'=>'active']);
        $this->assertEquals('paid',$payment->fresh()->status);
    }

    public function test_webhook_verification_outage_is_retryable_and_does_not_mark_paid(): void
    {
        [,,$payload]=$this->checkout();
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        HourlyRentalOrder::findOrFail($order['id'])->payments()->create(['user_id'=>1,'public_id'=>(string)\Illuminate\Support\Str::uuid(),'method'=>'moyasar','provider'=>'moyasar','provider_reference'=>'invoice-retry','amount'=>720,'status'=>'pending']);
        $gateway=\Mockery::mock(HourlyMoyasarGateway::class);
        $gateway->shouldReceive('getInvoice')->with('invoice-retry')->once()->andReturn(null);
        $this->app->instance(HourlyMoyasarGateway::class,$gateway);
        $this->postJson('/api/app/hourly/payments/moyasar/webhook',['id'=>'invoice-retry','status'=>'paid'])->assertStatus(503);
        $this->assertDatabaseHas('hourly_rental_orders',['id'=>$order['id'],'payment_status'=>'unpaid','paid_amount'=>0]);
    }

    public function test_bank_transfer_receipt_can_be_reviewed_and_replaced_after_rejection(): void
    {
        Queue::fake();
        Storage::fake('local');
        [,,$payload]=$this->checkout();
        $payload['payment_method']='bank_transfer';
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        $url='/api/app/hourly/orders/'.$order['id'].'/payments/bank-transfer';

        $this->post($url,['receipt'=>UploadedFile::fake()->create('receipt.pdf',100,'application/pdf'),'reference_number'=>'TRX-100'])
            ->assertOk()->assertExactJson(['status'=>true,'data'=>['payment_status'=>'pending_review']]);
        $payment=\App\Models\Hourly\HourlyOrderPayment::firstOrFail();
        Storage::disk('local')->assertExists($payment->attachment_path);
        $this->assertSame('TRX-100',$payment->provider_payload['reference_number']);
        $this->assertDatabaseHas('hourly_rental_orders',['id'=>$order['id'],'payment_status'=>'pending_review']);
        $this->post($url,['receipt'=>UploadedFile::fake()->image('duplicate.jpg')])->assertUnprocessable();

        $review=route('admin.hourly-orders.bank-transfer-review',[$order['id'],$payment->id]);
        $this->post($review,['decision'=>'reject'])->assertSessionHasErrors('rejection_reason');
        $this->post($review,['decision'=>'reject','rejection_reason'=>'الصورة غير واضحة'])->assertRedirect();
        $this->assertDatabaseHas('hourly_order_payments',['id'=>$payment->id,'status'=>'rejected']);
        $this->assertDatabaseHas('hourly_rental_orders',['id'=>$order['id'],'payment_status'=>'rejected']);

        $this->post($url,['receipt'=>UploadedFile::fake()->image('replacement.png'),'reference_number'=>'TRX-101'])
            ->assertOk()->assertJsonPath('data.payment_status','pending_review');
        $replacement=\App\Models\Hourly\HourlyOrderPayment::latest('id')->firstOrFail();
        $this->post(route('admin.hourly-orders.bank-transfer-review',[$order['id'],$replacement->id]),['decision'=>'accept'])->assertRedirect();
        $this->assertDatabaseHas('hourly_order_payments',['id'=>$replacement->id,'status'=>'paid']);
        $this->assertDatabaseHas('hourly_rental_orders',['id'=>$order['id'],'payment_status'=>'paid','status'=>'active']);
        Queue::assertPushed(\App\Jobs\SendHourlyBankTransferReviewNotification::class,2);
    }

    public function test_api_requires_customer_authentication_and_respects_language(): void
    {
        [$item]=$this->checkout();
        $item->translations()->create(['locale'=>'en','title'=>'Four visits']);
        $this->withHeader('lang','en')->getJson('/api/app/hourly/packages/'.$item->id.'?city_id=1')->assertOk()->assertJsonPath('data.title','Four visits');
        $this->withMiddleware(\App\Http\Middleware\Authenticate::class);
        auth()->forgetGuards();
        foreach (['cities','packages?city_id=1','orders','addresses'] as $path) $this->getJson('/api/app/hourly/'.$path)->assertUnauthorized();
    }

    public function test_errors_are_readable_translated_and_hide_the_trace(): void
    {
        [$item,,$payload]=$this->checkout();
        config(['app.debug'=>true]);
        $missing=$this->getJson('/api/app/hourly/packages/999999/config')->assertNotFound()
            ->assertExactJson(['message'=>'الباقة غير موجودة أو غير متاحة للحجز.','error'=>'not_found']);
        $this->assertArrayNotHasKey('trace',$missing->json());
        $this->withHeader('lang','en')->getJson('/api/app/hourly/orders/999999')->assertNotFound()
            ->assertJsonPath('message','The order does not exist or does not belong to you.');
        $this->withHeader('lang','ar')->getJson('/api/app/hourly/orders/abc')->assertNotFound()->assertJsonPath('message','المسار غير موجود. تأكد من الرابط وطريقة الطلب.');
        $this->deleteJson('/api/app/hourly/cities')->assertStatus(405)->assertJsonPath('error','method_not_allowed');
        $this->getJson('/api/app/hourly/packages/'.$item->id.'?city_id=1&workers=30')->assertUnprocessable()
            ->assertJsonPath('message','يجب أن تكون قيمة عدد العاملات بين 1 و 20.')->assertJsonPath('error','validation_error')
            ->assertJsonPath('errors.workers.0','يجب أن تكون قيمة عدد العاملات بين 1 و 20.');
        // package_id مقبول كاسم بديل لـ hourly_package_id.
        $payload['package_id']=$payload['hourly_package_id'];unset($payload['hourly_package_id']);
        $this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->assertJsonPath('data.hourly_package_id',$item->id);
    }

    public function test_slots_use_requested_worker_count_and_active_city(): void
    {
        [$item,$district]=$this->checkout();
        $team=$this->team();
        $item->serviceCategory->districts()->updateExistingPivot($district->id,['team_id'=>$team->id]);
        $item->update(['settings_overrides'=>['auto_assign'=>['web_app'=>true]]]);
        $query=\Mockery::mock();
        $query->shouldReceive('get')->andReturn(collect([new \App\Models\ingaz\Maid(['id'=>1])]));
        $assignment=\Mockery::mock(\App\Services\Hourly\HourlyWorkerAssignment::class);
        $assignment->shouldReceive('candidates')->andReturn($query);
        $assignment->shouldReceive('available')->andReturn(true);
        $this->app->instance(\App\Services\Hourly\HourlyWorkerAssignment::class,$assignment);
        $url='/api/app/hourly/slots?city_id=1&hourly_package_id='.$item->id.'&district_id='.$district->id.'&date=2030-01-07';
        $this->getJson($url.'&workers_per_visit=1')->assertOk()->assertJsonCount(2,'data');
        $this->getJson($url.'&workers_per_visit=2')->assertOk()->assertJsonPath('data',[]);
        DB::table('cities')->where('id',1)->update(['is_active'=>false]);
        $this->getJson($url)->assertOk()->assertJsonPath('data',[]);
        $this->getJson('/api/app/hourly/packages?city_id=1')->assertOk()->assertJsonPath('data',[]);
    }

    public function test_app_config_uses_only_supported_payment_keys_and_filters_districts_by_city(): void
    {
        [$item,$district]=$this->checkout();
        $other=$this->district('حي جدة',21.5,39.2,2);
        $item->serviceCategory->districts()->attach($other->id,['is_active'=>true]);
        DB::table('hourly_category_payment_methods')->updateOrInsert(
            ['service_category_id'=>$item->service_category_id,'payment_method'=>'wallet'],
            ['web_app'=>true,'employee'=>false,'branch'=>false,'created_at'=>now(),'updated_at'=>now()]
        );

        $this->getJson('/api/app/hourly/packages/'.$item->id.'/config?city_id=1')->assertOk()
            ->assertJsonPath('data.payment_methods',['moyasar','wallet','bank_transfer','cash'])
            ->assertJsonCount(1,'data.districts')->assertJsonPath('data.districts.0.id',$district->id);
    }

    public function test_wallet_checkout_debits_once_and_returns_the_actual_paid_order_state(): void
    {
        [$item,,$payload]=$this->checkout();
        DB::table('hourly_category_payment_methods')->updateOrInsert(
            ['service_category_id'=>$item->service_category_id,'payment_method'=>'wallet'],
            ['web_app'=>true,'employee'=>false,'branch'=>false,'created_at'=>now(),'updated_at'=>now()]
        );
        $payload['payment_method']='wallet';
        $payload['booking_token']=(string) \Illuminate\Support\Str::uuid();

        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()
            ->assertJsonPath('data.payment_method','wallet')->assertJsonPath('data.payment_status','paid')
            ->assertJsonPath('data.status','active')->json('data');
        $this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->assertJsonPath('data.order_id',$order['id']);
        $this->assertEquals(280,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $this->assertDatabaseHas('hourly_wallet_transactions',['hourly_rental_order_id'=>$order['id'],'amount'=>-720,'payment_method'=>'wallet']);
        $this->assertDatabaseHas('hourly_order_payments',['hourly_rental_order_id'=>$order['id'],'method'=>'wallet','status'=>'paid','amount'=>720]);
        $this->assertSame(1,DB::table('hourly_wallet_transactions')->count());
    }

    public function test_cancelled_by_admin_is_exposed_as_cancelled_to_the_app(): void
    {
        [,,$payload]=$this->checkout();
        $order=$this->postJson('/api/app/hourly/orders',$payload)->assertCreated()->json('data');
        DB::table('hourly_visits')->where('id',$order['visits'][0]['id'])->update(['status'=>'cancelled_by_admin']);
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()
            ->assertJsonPath('data.visits.0.status','cancelled_by_admin')->assertJsonPath('data.visits.0.display_status','cancelled');
    }
}
