<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyDriver, HourlyOrderPayment, HourlyRentalOrder};
use App\Services\Hourly\{PanelBookingService, HourlyWorkerAssignment};
use App\Services\Hourly\HourlyMoyasarGateway;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\HourlyTestCase as TestCase;

class HourlyPanelBookingTest extends TestCase
{
    use BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema;

    protected function setUp(): void
    {
        parent::setUp(); $this->setUpHourlyCategorySchema(); $this->buildWorkers();
        Schema::create('admins',fn (Blueprint $t)=>[$t->id(),$t->softDeletes()]); DB::table('admins')->insert(['id'=>7]);
        Schema::create('users',function (Blueprint $t) { $t->softDeletes();
            $t->id(); $t->string('name'); $t->string('email')->nullable()->unique(); $t->string('phone')->unique();
            $t->string('phone_code')->default('966'); $t->string('password')->nullable(); $t->string('register_from')->nullable();
            $t->boolean('is_blocked')->default(false); $t->timestamps();
        });
        Schema::table('user_addresses',function (Blueprint $t) {foreach (['name','phone','title'] as $column) $t->string($column)->nullable();});
        (require base_path('database/migrations/2026_09_20_000001_add_hourly_booking_wizard.php'))->up();
        (require base_path('database/migrations/2026_09_21_000001_extend_hourly_order_management.php'))->up();
        (require base_path('database/migrations/2026_09_23_000001_link_hourly_order_renewals.php'))->up();
        DB::table('users')->insert(['id'=>1,'name'=>'عميل','email'=>'client@example.test','phone'=>'0500000000','hourly_wallet_balance'=>1000]);
        Schema::create('hourly_drivers',function (Blueprint $t) {$t->id();$t->string('name');$t->boolean('is_active')->default(true);$t->softDeletes();});
    }

    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    private function booking(int $visits = 1): array
    {
        $category=$this->category(); $district=$this->district('الملز',24.7,46.7); $team=$this->team();
        $category->districts()->attach($district->id,['is_active'=>true,'team_id'=>$team->id]);
        $item=$this->item($category,['visits_count'=>$visits]);
        for ($id=1;$id<=2;$id++) {
            DB::table('maids')->insert(['id'=>$id,'name'=>'عاملة '.$id,'rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>now()->toDateString(),
                'hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])])]);
            DB::table('maid_jobs')->insert(['maid_id'=>$id,'job_type_id'=>2]);
            DB::table('contracts')->insert(['id'=>$id,'maid_id'=>$id,'status'=>'shelter']);
            DB::table('contract_infos')->insert(['contract_id'=>$id,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
            DB::table('hourly_team_members')->insert(['hourly_team_id'=>$team->id,'member_type'=>'maid','member_id'=>$id,'role'=>'primary','is_active'=>true]);
        }
        return ['booking_token'=>(string)Str::uuid(),'customer_mode'=>'existing','user_id'=>1,'address_mode'=>'existing',
            'address_id'=>$this->address(24.7,46.7,'الملز'),'hourly_package_id'=>$item->id,'workers_per_visit'=>1,
            'payment_method'=>'cash','collect_now'=>false,'all_workers'=>false,'notify_email'=>false,'notify_app'=>false,
            'visits'=>array_map(fn ($i)=>['starts_at'=>now()->addDays(3+$i)->format('Y-m-d').' 08:00','worker_ids'=>[1]],range(0,$visits-1))];
    }

    public function test_wizard_only_lists_active_saudi_cities_and_their_districts(): void
    {
        DB::table('cities')->insert([
            ['id'=>3,'country_id'=>2,'name_ar'=>'Dubai','is_active'=>true],
            ['id'=>4,'country_id'=>1,'name_ar'=>'Inactive Saudi city','is_active'=>false],
        ]);
        $district=$this->district('Saudi district',24.7,46.7);
        $this->district('Foreign district',25.2,55.3,3);
        $this->district('Inactive city district',24.7,46.7,4);
        $this->get('/admin/hourly/orders/create')->assertOk()
            ->assertViewHas('cities',fn ($cities)=>$cities->pluck('id')->all()===[1,2])
            ->assertViewHas('districts',fn ($districts)=>$districts->pluck('id')->all()===[$district->id]);
    }

    public function test_panel_booking_automatically_assigns_nearest_driver_when_enabled(): void
    {
        $data=$this->booking();
        $item=\App\Models\Hourly\HourlyPackage::findOrFail($data['hourly_package_id']);
        \App\Models\Hourly\HourlyCategorySetting::create(['service_category_id'=>$item->service_category_id,'settings'=>['auto_assign_driver'=>['panel'=>true]]]);
        Schema::table('hourly_drivers',fn(Blueprint $t)=>$t->json('work_schedule')->nullable());
        Schema::create('location_pings',function(Blueprint $t){$t->id();$t->string('user_type');$t->integer('user_id');$t->double('lat');$t->double('lng');$t->dateTime('recorded_at');});
        DB::table('hourly_drivers')->insert(['id'=>1,'name'=>'Nearest driver','is_active'=>true,'work_schedule'=>json_encode(array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day']))]);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>DB::table('hourly_teams')->value('id'),'member_type'=>'driver','member_id'=>1,'role'=>'primary','is_active'=>true]);
        DB::table('location_pings')->insert(['user_type'=>'driver','user_id'=>1,'lat'=>24.701,'lng'=>46.7,'recorded_at'=>now()]);
        $this->mock(\App\Services\Hourly\StaffPushService::class)->shouldReceive('assignmentChanged')->zeroOrMoreTimes();
        $order=app(PanelBookingService::class)->create($data,7);
        $this->assertEquals(1,$order->visits()->first()->delivery_driver_id);
        $this->assertEquals(1,$order->visits()->first()->pickup_driver_id);
    }

    public function test_panel_settings_control_quantity_options_pricing_and_automatic_assignment(): void
    {
        $data=$this->booking();
        $item=\App\Models\Hourly\HourlyPackage::findOrFail($data['hourly_package_id']);
        $item->update(['for_home'=>true,'for_company'=>true,'transport_included'=>false,'transport_fee'=>10]);
        \App\Models\Hourly\HourlyCategorySetting::create(['service_category_id'=>$item->service_category_id,'settings'=>[
            'show_material_option'=>true,'show_service_type_field'=>true,
            'show_tax_separately'=>['panel'=>false],'show_custom_fee_separately'=>true,
            'auto_assign'=>['panel'=>true],
            'order_form_steppers'=>[['key'=>'quantity','label'=>'الغرف','channels'=>['panel'],'min'=>2,'max'=>4,'scale_price'=>true]],
        ]]);
        $this->postJson('/panel/add-order/catalog',$data)->assertOk()
            ->assertJsonPath('0.items.0.quantity',2)->assertJsonPath('0.items.0.booking_config.auto_assign',true);
        $data['quantity']=3;$data['bring_materials']=true;$data['service_type']='company';
        $data['visits'][0]['worker_ids']=[null];
        $quote=$this->postJson('/panel/add-order/quote',$data)->assertOk()
            ->assertJsonPath('quantity',3)->assertJsonPath('presentation.tax_line',null)
            ->assertJsonPath('presentation.lines.1.code','transport');
        $response=$this->postJson('/panel/add-order/store',$data)->assertOk();
        $order=HourlyRentalOrder::findOrFail($response->json('id'));
        $this->assertEquals($quote->json('total')/100,$order->total_amount);
        $this->assertSame(['bring_materials'=>true,'service_type'=>'company'],$order->package_snapshot['booking_options']);
        $this->assertSame(3,$order->package_snapshot['quantity']);
        $this->assertSame(1,$order->visits()->first()->workers()->count());
        $this->assertDatabaseCount('hourly_visit_reservations',0);
        $data['quantity']=5;
        $this->postJson('/panel/add-order/quote',$data)->assertStatus(422);
    }

    public function test_panel_keeps_workers_unassigned_when_automatic_assignment_is_disabled(): void
    {
        $data=$this->booking();
        $data['visits'][0]['worker_ids']=[null];
        $order=app(PanelBookingService::class)->create($data,7);
        $this->assertSame(0,$order->visits()->first()->workers()->count());
        $this->assertDatabaseCount('hourly_visit_reservations',1);
        $this->postJson('/panel/add-order/quote',$data+['bring_materials'=>true])->assertStatus(422);
    }

    public function test_foreign_city_is_rejected_even_if_the_service_covers_it(): void
    {
        $data=$this->booking();
        // Make the otherwise valid booking's city foreign to isolate the country restriction.
        DB::table('cities')->where('id',1)->update(['country_id'=>2]);
        $this->postJson('/panel/add-order/quote',$data)->assertStatus(422);
        $this->postJson('/panel/add-order/store',$data)->assertStatus(422);
        $this->assertSame(0,HourlyRentalOrder::count());
    }

    public function test_covered_category_without_items_reports_missing_items(): void
    {
        $category=$this->category();
        $district=$this->district('Nargis',24.7,46.7);
        $category->districts()->attach($district->id,['is_active'=>true]);
        $data=['address_mode'=>'existing','user_id'=>1,'address_id'=>$this->address(24.7,46.7,'Nargis')];
        $this->postJson('/panel/add-order/catalog',$data)->assertOk()
            ->assertJsonPath('0.available',false)
            ->assertJsonPath('0.unavailable_message',__('لا توجد عناصر خدمة مفعّلة لهذه الفئة - أضف عنصر خدمة وفعّله من الخدمات والباقات'));
        $this->item($category);
        $this->postJson('/panel/add-order/catalog',$data)->assertOk()->assertJsonPath('0.available',true);
    }

    public function test_availability_explains_missing_team_and_insufficient_workers(): void
    {
        $data=$this->booking();
        $data['date']=substr($data['visits'][0]['starts_at'],0,10);
        $data['workers_per_visit']=3;
        $this->postJson('/panel/add-order/availability',$data)->assertOk()
            ->assertJsonPath('0.available',false)
            ->assertJsonPath('0.unavailable_reason',__('المتاح :available من أصل :required عاملات مطلوبات. راجع جدول العمل والإجازات والحجوزات أو اختر موعدًا آخر.',['available'=>2,'required'=>3]));
        DB::table('hourly_category_district')->update(['team_id'=>null]);
        $this->postJson('/panel/add-order/availability',$data)->assertOk()
            ->assertJsonPath('0.available',false)
            ->assertJsonPath('0.unavailable_reason',__('الحي غير مربوط بفريق خدمة. اربط فريقًا مفعّلًا بالحي من مناطق الخدمة.'));
    }

    public function test_booking_uses_district_team_when_category_has_no_specific_team(): void
    {
        $data=$this->booking();
        $link=DB::table('hourly_category_district')->first();
        DB::table('hourly_team_district')->insert(['district_id'=>$link->district_id,'hourly_team_id'=>$link->team_id,'is_active'=>true]);
        DB::table('hourly_category_district')->update(['team_id'=>null]);
        $data['date']=substr($data['visits'][0]['starts_at'],0,10);
        $this->postJson('/panel/add-order/availability',$data)->assertOk()->assertJsonPath('0.available',true);
        $order=app(PanelBookingService::class)->create($data,7);
        $this->assertEquals($link->team_id,$order->visits()->first()->hourly_team_id);
        $this->assertNull(DB::table('hourly_category_district')->value('team_id'));
        DB::table('hourly_team_district')->update(['is_active'=>false]);
        $this->postJson('/panel/add-order/availability',$data)->assertOk()->assertJsonPath('0.available',false)
            ->assertJsonPath('0.unavailable_reason',__('الحي غير مربوط بفريق خدمة. اربط فريقًا مفعّلًا بالحي من مناطق الخدمة.'));
    }

    public function test_specific_category_team_takes_precedence_over_district_team(): void
    {
        $data=$this->booking();
        $link=DB::table('hourly_category_district')->first();
        $other=$this->team('Other team');
        DB::table('hourly_team_district')->insert(['district_id'=>$link->district_id,'hourly_team_id'=>$other->id,'is_active'=>true]);
        $item=\App\Models\Hourly\HourlyPackage::findOrFail($data['hourly_package_id']);
        [, $resolved]=app(PanelBookingService::class)->coverage($item,['city_id'=>1,'hourly_district_id'=>$link->district_id]);
        $this->assertEquals($link->team_id,$resolved->team_id);
    }

    public function test_booking_creates_all_visits_and_retry_is_idempotent(): void
    {
        $data=$this->booking(2);$service=app(PanelBookingService::class);
        $order=$service->create($data,7);
        $this->assertEquals(230,$order->total_amount);
        $this->assertSame('HR-'.str_pad((string) $order->id,8,'0',STR_PAD_LEFT),$order->order_number);
        $this->assertSame(2,$order->visits()->count());
        $this->assertSame(2,DB::table('hourly_visit_workers')->count());
        $this->assertSame($order->id,$service->create($data,7)->id);
        $this->assertSame(1,HourlyRentalOrder::count());
    }

    public function test_order_details_notes_and_branded_invoice(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);
        $this->get(route('admin.hourly-orders.summary',$order))->assertOk()->assertSee('ملخص الطلب')->assertSee('تاريخ الزيارات')->assertSee('نسخ تفاصيل الطلب')->assertSee(route('admin.hourly-orders.show',$order));
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertSee('بطاقة الطلب')->assertSee('data-visit-card',false)->assertSee('الطاقم')->assertSee('سجل الدفعات')->assertSee('إجمالي ساعات العمل');
        $url=route('admin.hourly-orders.note',$order);
        $this->post($url,['note'=>'Order instructions'])->assertRedirect();
        $this->post($url,['note'=>'Visit instructions','visit_id'=>$order->visits()->first()->id])->assertRedirect();
        $this->postJson($url,['note'=>'Wrong visit','visit_id'=>999999])->assertNotFound();
        $this->assertCount(2,$order->fresh()->booking_metadata['notes']);
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertSee('Order instructions')->assertSee('Visit instructions');
        $pdf=$this->get(route('admin.hourly-orders.invoice',$order))->assertOk()->assertHeader('Content-Type','application/pdf');
        $this->assertStringStartsWith('%PDF-',$pdf->getContent());
    }

    public function test_online_payment_can_be_selected_and_paid_payments_have_receipts(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()
            ->assertSee('value="moyasar"',false)->assertSee('دفع إلكتروني أونلاين (ميسر)');
        $this->postJson(route('admin.hourly-orders.operations',$order),[
            'action'=>'payment_method','payment_method'=>'moyasar','reason'=>'تحويل العميل للدفع الإلكتروني',
        ])->assertOk();
        $this->assertSame('moyasar',$order->fresh()->payment_method);

        $order->refresh()->update(['payment_method'=>'cash']);
        $this->post(route('admin.hourly-orders.collect',$order),['amount'=>25])->assertRedirect()->assertSessionHas('receipt_url');
        $payment=$order->payments()->firstOrFail();
        $this->get(route('admin.hourly-orders.receipt',[$order,$payment]))->assertOk()
            ->assertSee('سند قبض')->assertSee('25.00')->assertSee($order->order_number);
        $pending=$order->payments()->create(['user_id'=>$order->user_id,'public_id'=>(string)Str::uuid(),'method'=>'moyasar','amount'=>10,'status'=>'pending']);
        $this->get(route('admin.hourly-orders.receipt',[$order,$pending]))->assertNotFound();

        $order->update(['payment_method'=>'moyasar']);
        $pending->update(['provider'=>'moyasar','provider_reference'=>'invoice-pending']);
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()
            ->assertSee('الطلب بانتظار إتمام العميل للدفع عبر رابط ميسر')
            ->assertDontSee('تأكيد الصرّاف')
            ->assertDontSee('payment_method ===');
        $gateway=\Mockery::mock(HourlyMoyasarGateway::class);
        $gateway->shouldReceive('cancelInvoice')->once()->with('invoice-pending')->andReturn(['id'=>'invoice-pending','status'=>'canceled']);
        $this->app->instance(HourlyMoyasarGateway::class,$gateway);
        $this->postJson(route('admin.hourly-orders.operations',$order),[
            'action'=>'payment_method','payment_method'=>'cash','reason'=>'طلب العميل الدفع نقدًا',
        ])->assertOk();
        $this->assertSame('cash',$order->fresh()->payment_method);
        $this->assertSame('cancelled',$pending->fresh()->status);
        $this->assertSame(7,$pending->fresh()->provider_payload['cancelled_by']);
    }

    public function test_pending_bank_transfer_is_visible_and_reviewable_on_order_page(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);
        $order->update(['payment_method'=>'bank_transfer','payment_status'=>'pending_review']);
        $payment=HourlyOrderPayment::create([
            'hourly_rental_order_id'=>$order->id,'user_id'=>$order->user_id,'public_id'=>(string)Str::uuid(),
            'method'=>'bank_transfer','amount'=>$order->total_amount,'status'=>'pending_review',
            'attachment_path'=>'hourly/payments/bank-transfers/test.pdf',
            'provider_payload'=>['reference_number'=>'TRX-PANEL'],
        ]);

        $this->get(route('admin.hourly-orders.show',$order))->assertOk()
            ->assertSee($order->user->name)->assertSee(number_format((float)$order->total_amount,2))
            ->assertSee('TRX-PANEL')->assertSee('عرض الإيصال')->assertSee('قبول التحويل')->assertSee('سبب الرفض')
            ->assertDontSee("payment_method === 'bank_transfer'")
            ->assertSee(route('admin.hourly-orders.bank-transfer-review',[$order,$payment]),false);
    }

    public function test_operational_notes_have_audience_scope_and_idempotent_saving(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(2),7);
        [$first,$second]=$order->visits()->get()->all();
        $first->update(['delivery_driver_id'=>77]);
        $url=route('admin.hourly-orders.note',$order);
        foreach(['admin','driver','worker','all','address'] as $audience){
            $body=['note'=>$audience.' text','audience'=>$audience,'note_token'=>(string)Str::uuid()];
            if($audience==='worker')$body['visit_id']=$first->id;
            $this->postJson($url,$body)->assertOk();$this->postJson($url,$body)->assertOk();
        }
        $this->assertCount(5,$order->fresh()->booking_metadata['notes']);
        $this->assertSame(5,DB::table('hourly_order_audits')->where('action','note')->count());
        $notes=app(\App\Services\Hourly\OperationalNotes::class);
        $this->assertSame(['worker','all','address'],$notes->forStaff($first->fresh(),'worker',1)->pluck('audience')->all());
        $this->assertSame(['all','address'],$notes->forStaff($second,'worker',1)->pluck('audience')->all());
        $this->assertSame(['driver','all','address'],$notes->forStaff($first->fresh(),'driver',77)->pluck('audience')->all());
        $this->assertArrayNotHasKey('booking_metadata',$order->fresh()->toArray());
        $this->postJson($url,['note'=>'  ','audience'=>'admin'])->assertUnprocessable();
        $this->postJson($url,['note'=>'Invalid','audience'=>'unknown'])->assertUnprocessable();
        $this->postJson($url,['note'=>'Invalid','audience'=>'address','visit_id'=>$first->id])->assertUnprocessable();
        $this->postJson($url,['note'=>'Invalid','audience'=>'worker','visit_id'=>999999])->assertNotFound();
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $notes->forStaff($first,'worker',2);
    }

    public function test_address_notes_are_shared_only_for_same_customer_address_and_unit(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);
        $this->postJson(route('admin.hourly-orders.note',$order),['note'=>'Gate code','audience'=>'address'])->assertOk();
        $this->postJson(route('admin.hourly-orders.note',$order),['note'=>'Internal only','audience'=>'admin'])->assertOk();
        $other=$order->replicate();$other->order_number=(string)Str::uuid();$other->invoice_number=(string)Str::uuid();$other->booking_token=(string)Str::uuid();$other->booking_metadata=[];$other->save();
        $notes=app(\App\Services\Hourly\OperationalNotes::class);
        $this->assertSame(['Gate code'],$notes->forOrder($other)->pluck('text')->all());
        $other->unit='different';$other->save();$this->assertCount(0,$notes->forOrder($other));
        $other->unit=$order->unit;$other->user_id=999;$other->save();$this->assertCount(0,$notes->forOrder($other));
        $response=$this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertSee('id="operational-note-modal"',false)->assertSee('name="audience"',false)->assertDontSee('@checked',false);
        $this->assertMatchesRegularExpression('/<input[^>]+name="audience"[^>]+value="admin"[^>]+\schecked\s/', $response->getContent());
    }

    public function test_order_visit_reschedule_is_scoped_and_validates_the_time(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);
        $visit=$order->visits()->first();
        $url=route('admin.hourly-orders.reschedule',[$order,$visit->id]);
        $this->putJson($url,['starts_at'=>'invalid'])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->putJson(route('admin.hourly-orders.reschedule',[$order,999999]),['starts_at'=>now()->addDays(4)->format('Y-m-d').'T08:00'])->assertNotFound();
        $this->put($url,['starts_at'=>now()->addDays(4)->format('Y-m-d').'T08:00'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(now()->addDays(4)->format('Y-m-d').' 08:00',$visit->fresh()->scheduled_start_at->format('Y-m-d H:i'));
    }

    public function test_schedule_modal_saves_json_and_rejects_stale_or_unavailable_dates(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();
        $url=route('admin.hourly-orders.reschedule',[$order,$visit->id]);
        $data=['starts_at'=>now()->addDays(4)->format('Y-m-d').'T08:00','expected_start_at'=>$visit->scheduled_start_at->format('Y-m-d\TH:i')];
        $this->putJson($url,array_replace($data,['starts_at'=>now()->subDay()->format('Y-m-d').'T08:00']))->assertUnprocessable();
        $this->assertSame($data['expected_start_at'],$visit->fresh()->scheduled_start_at->format('Y-m-d\TH:i'));
        $this->putJson($url,$data)->assertOk()->assertJsonStructure(['message']);
        $this->assertSame($data['starts_at'],$visit->fresh()->scheduled_start_at->format('Y-m-d\TH:i'));
        $this->putJson($url,$data)->assertUnprocessable();
        $this->assertSame(1,DB::table('hourly_order_audits')->where('visit_id',$visit->id)->where('action','reschedule')->count());
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertSee('id="visit-schedule-modal"',false)->assertSee('id="vs-days"',false);
    }

    public function test_operations_preserve_initial_and_collected_amounts_and_audit_each_visit(): void
    {
        $data=$this->booking(2);$data['collect_now']=true;
        $order=app(PanelBookingService::class)->create($data,7);$ids=$order->visits()->pluck('id')->all();
        $this->postJson(route('admin.hourly-orders.operations',$order),['action'=>'price','reason'=>'Customer adjustment','visit_ids'=>[$ids[0]],'price'=>80])->assertOk();
        $this->assertEquals(195,$order->fresh()->total_amount);$this->assertEquals(230,$order->fresh()->initial_amount);$this->assertEquals(230,$order->fresh()->paid_amount);
        $this->assertSame(2,DB::table('hourly_order_audits')->count());
        $this->postJson(route('admin.hourly-orders.operations',$order),['action'=>'cancel_visit','reason'=>'Cancelled visit','visit_ids'=>[$ids[1]]])->assertOk();
        $this->assertEquals(80,$order->fresh()->total_amount);$this->assertEquals(80,$order->fresh()->paid_amount);
        $this->assertDatabaseHas('hourly_order_payments',['hourly_rental_order_id'=>$order->id,'amount'=>-150,'status'=>'paid']);
        $this->assertEquals(round(80/1.15,2),$order->fresh()->subtotal);
        $this->postJson(route('admin.hourly-orders.operations',$order),['action'=>'price','reason'=>'Invalid visit','visit_ids'=>[$ids[1]],'price'=>70])->assertUnprocessable();
        $this->assertEquals(80,$order->fresh()->total_amount);
    }

    public function test_bulk_rescheduling_is_atomic_and_rejects_foreign_visits(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(2),7);$ids=$order->visits()->pluck('id')->all();
        $url=route('admin.hourly-orders.operations',$order);
        $this->postJson($url,['action'=>'reschedule','reason'=>'Move together','visit_ids'=>$ids,'shift_days'=>1])->assertOk();
        $this->assertSame(now()->addDays(4)->toDateString(),$order->visits()->first()->scheduled_start_at->toDateString());
        $this->postJson($url,['action'=>'price','reason'=>'Wrong scope','visit_ids'=>[$ids[0],999999],'price'=>10])->assertUnprocessable();
        $this->assertEquals(230,$order->fresh()->total_amount);
        $auditCount=DB::table('hourly_order_audits')->count();
        $this->postJson($url,['action'=>'reschedule','reason'=>'Past dates','visit_ids'=>$ids,'shift_days'=>-365])->assertUnprocessable();
        $this->assertSame($auditCount,DB::table('hourly_order_audits')->count());
        $this->assertSame(now()->addDays(4)->toDateString(),$order->visits()->first()->scheduled_start_at->toDateString());
    }

    public function test_worker_assignment_unassignment_and_free_visit_reserve_capacity(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();$url=route('admin.hourly-orders.operations',$order);
        $this->postJson($url,['action'=>'unassign_workers','reason'=>'Reassign later','visit_ids'=>[$visit->id]])->assertOk();
        $this->assertSame(0,$visit->workers()->count());$this->assertSame(1,DB::table('hourly_visit_reservations')->count());
        $this->postJson($url,['action'=>'assign_all','reason'=>'Assign','visit_ids'=>[$visit->id]])->assertOk();
        $this->assertSame(1,$visit->workers()->count());
        $this->postJson($url,['action'=>'add_worker','reason'=>'Extra worker','visit_ids'=>[$visit->id],'worker_id'=>2])->assertOk();
        $this->assertEquals(230,$order->fresh()->total_amount);
        $this->postJson($url,['action'=>'free_visit','reason'=>'Complimentary visit','starts_at'=>now()->addDays(5)->format('Y-m-d').'T08:00'])->assertOk();
        $this->assertEquals(230,$order->fresh()->total_amount);$this->assertSame(2,$order->visits()->count());
        $this->assertEquals(0,$order->visits()->orderByDesc('id')->first()->visit_price);
        $this->postJson($url,['action'=>'remove_worker','reason'=>'Reduce headcount','visit_ids'=>[$visit->id],'worker_id'=>2])->assertOk();
        $this->assertEquals(115,$order->fresh()->total_amount);
        $this->postJson($url,['action'=>'complete','reason'=>'Too early','visit_ids'=>[$visit->id]])->assertUnprocessable();
    }

    public function test_add_worker_modal_lists_available_workers_and_saves_zero_fee(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();
        $url=route('admin.hourly-orders.available-workers',[$order,$visit->id]);
        $this->getJson($url)->assertOk()->assertJsonCount(1,'workers')->assertJsonPath('workers.0.id',2)->assertJsonPath('workers.0.teams.0.id',$visit->hourly_team_id)->assertJsonPath('default_fee',115);
        $this->getJson(route('admin.hourly-orders.available-workers',[$order,999999]))->assertNotFound();
        $this->postJson(route('admin.hourly-orders.operations',$order),['action'=>'add_worker','worker_id'=>2,'additional_fee'=>0,'visit_ids'=>[$visit->id],'expected_price'=>115,'expected_workers'=>1,'reason'=>'Complimentary worker'])->assertOk();
        $this->assertEquals(115,$order->fresh()->total_amount);$this->assertEquals(2,$visit->fresh()->workers_count);$this->assertSame(2,$visit->workers()->count());
        $this->getJson($url)->assertOk()->assertJsonCount(0,'workers');
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertSee('id="add-worker-modal"',false);
    }

    public function test_add_worker_fee_and_late_availability_change_are_validated(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();
        $url=route('admin.hourly-orders.operations',$order);
        $data=['action'=>'add_worker','worker_id'=>2,'additional_fee'=>40.25,'visit_ids'=>[$visit->id],'expected_price'=>115,'expected_workers'=>1,'reason'=>'Extra worker'];
        DB::table('hourly_worker_leaves')->insert(['maid_id'=>2,'starts_at'=>$visit->scheduled_start_at,'ends_at'=>$visit->scheduled_end_at,'is_active'=>true]);
        $this->getJson(route('admin.hourly-orders.available-workers',[$order,$visit->id]))->assertOk()->assertJsonCount(0,'workers');
        $this->postJson($url,$data)->assertUnprocessable();$this->assertEquals(115,$order->fresh()->total_amount);
        DB::table('hourly_worker_leaves')->delete();
        $this->postJson($url,array_replace($data,['additional_fee'=>-1]))->assertUnprocessable();
        $this->postJson($url,$data)->assertOk();$this->assertEquals(155.25,$order->fresh()->total_amount);$this->assertEquals(115,$order->fresh()->initial_amount);
        $audit=DB::table('hourly_order_audits')->where('visit_id',$visit->id)->latest('id')->first();$this->assertEquals(40.25,json_decode($audit->after,true)['additional_fee']);
        $this->postJson($url,$data)->assertUnprocessable();$this->assertSame(2,$visit->workers()->count());
    }

    public function test_filters_aliases_and_selected_excel_columns(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);
        $this->get('/panel/orders/index/act/all?short_id='.$order->id)->assertOk()->assertSee($order->order_number)->assertSee('#'.str_pad((string)$order->id,8,'0',STR_PAD_LEFT));
        $this->get('/panel/orders/index/act/new')->assertOk()->assertSee($order->order_number);
        $this->get('/panel/orders/details/id/'.$order->id)->assertOk()->assertSee('سجل التعديلات')->assertSee('تقييمات العاملات');
        DB::table('hourly_order_installments')->insert(['hourly_rental_order_id'=>$order->id,'due_date'=>now()->subDay()->toDateString(),'amount'=>115,'paid_amount'=>0]);
        $search=app(\App\Services\Hourly\OrderSearch::class);
        $this->assertSame(1,$search->query(['installment'=>'overdue'])->count());$this->assertSame(0,$search->query(['installment'=>'none'])->count());
        $this->assertSame(0,$search->query(['coupon_code'=>'NOT-USED'])->count());
        $this->assertSame(1,$search->query(['source'=>'admin'])->count());$this->assertSame(0,$search->query(['source'=>'customer'])->count());
        $order->update(['source'=>'web']);
        $this->assertSame(0,$search->query(['source'=>'admin'])->count());$this->assertSame(1,$search->query(['source'=>'customer'])->count());
        $response=$this->get(route('admin.hourly-orders.export',['columns'=>['order_number','total_amount'],'short_id'=>$order->id]))->assertOk();
        $this->assertStringStartsWith('PK',$response->streamedContent());
        $this->get(route('admin.hourly-orders.export',['columns'=>['password']]))->assertSessionHasErrors('columns.0');
    }

    public function test_driver_roles_times_and_team_leader_are_validated_and_audited(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);
        $visit=$order->visits()->first();$url=route('admin.hourly-orders.operations',$order);
        Schema::table('hourly_drivers',fn(Blueprint $t)=>$t->json('work_schedule')->nullable());
        (require base_path('database/migrations/2026_09_17_000005_create_hourly_team_driver_leaders.php'))->up();
        DB::table('hourly_drivers')->insert(['id'=>1,'name'=>'Driver','is_active'=>true,'work_schedule'=>json_encode(array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day']))]);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>$visit->hourly_team_id,'member_type'=>'driver','member_id'=>1,'role'=>'primary','is_active'=>true]);
        $data=['action'=>'assign_driver','reason'=>'Transport assignment','visit_ids'=>[$visit->id],'driver_id'=>1,'driver_role'=>'delivery','driver_time'=>'08:30'];
        $this->postJson($url,$data)->assertUnprocessable();
        $this->assertNull($visit->fresh()->delivery_driver_id);
        $data['driver_time']='08:00';$this->postJson($url,$data)->assertOk();
        $data['driver_role']='pickup';$data['driver_time']=$visit->scheduled_end_at->format('H:i');$this->postJson($url,$data)->assertOk();
        $this->assertEquals(1,$visit->fresh()->delivery_driver_id);$this->assertEquals(1,$visit->fresh()->pickup_driver_id);
        $leader=['action'=>'team_leader','reason'=>'Team leader','visit_ids'=>[$visit->id],'leader_id'=>1];
        $this->postJson($url,$leader)->assertUnprocessable();
        DB::table('hourly_team_driver_leaders')->insert(['hourly_team_id'=>$visit->hourly_team_id,'driver_id'=>1]);
        $this->postJson($url,$leader)->assertOk();
        $this->assertEquals(1,$visit->fresh()->team_leader_id);
        $this->postJson($url,['action'=>'unassign_drivers','reason'=>'Reset transport','visit_ids'=>[$visit->id]])->assertOk();
        $this->assertNull($visit->fresh()->pickup_driver_id);$this->assertNull($visit->fresh()->delivery_at);
        $this->assertSame(8,DB::table('hourly_order_audits')->count());
    }

    public function test_team_leader_modal_filters_pages_and_rechecks_all_teams_on_save(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();
        (require base_path('database/migrations/2026_09_17_000005_create_hourly_team_driver_leaders.php'))->up();
        $other=$this->team('Another team');
        for($i=1;$i<=12;$i++){
            DB::table('hourly_drivers')->insert(['id'=>$i,'name'=>'Leader '.$i,'is_active'=>true]);
            DB::table('hourly_team_driver_leaders')->insert(['hourly_team_id'=>$i===1?$visit->hourly_team_id:$other->id,'driver_id'=>$i]);
        }
        $url=route('admin.hourly-orders.leaders',[$order,$visit->id]);
        $this->getJson($url)->assertOk()->assertJsonPath('leaders.total',1)->assertJsonPath('leaders.data.0.id',1);
        $this->getJson($url.'?all_teams=1&per_page=10&page=2&sort=id')->assertOk()->assertJsonPath('leaders.total',12)->assertJsonCount(2,'leaders.data');
        $this->getJson($url.'?all_teams=1&search=Leader%2012')->assertOk()->assertJsonPath('leaders.total',1)->assertJsonPath('leaders.data.0.id',12);
        $this->getJson(route('admin.hourly-orders.leaders',[$order,999999]))->assertNotFound();
        $body=['action'=>'team_leader','visit_ids'=>[$visit->id],'leader_id'=>12,'reason'=>'Choose another team leader'];
        $save=route('admin.hourly-orders.operations',$order);
        $this->postJson($save,$body)->assertUnprocessable();
        $this->postJson($save,$body+['all_teams'=>true])->assertOk();
        $this->assertEquals(12,$visit->fresh()->team_leader_id);
        $this->assertNull($visit->fresh()->delivery_driver_id);$this->assertNull($visit->fresh()->pickup_driver_id);
        DB::table('hourly_drivers')->where('id',12)->update(['is_active'=>false]);
        $this->postJson($save,$body+['all_teams'=>true])->assertUnprocessable();
        $other->update(['is_active'=>false]);
        $this->getJson($url.'?all_teams=1')->assertOk()->assertJsonPath('leaders.total',1);
        $this->getJson($url.'?search=Missing')->assertOk()->assertJsonCount(0,'leaders.data');
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertSee('id="team-leader-modal"',false);
    }

    public function test_duration_permissions_and_cancellation_preserve_collections(): void
    {
        $data=$this->booking();$data['collect_now']=true;
        $order=app(PanelBookingService::class)->create($data,7);$visit=$order->visits()->first();
        $url=route('admin.hourly-orders.operations',$order);
        $this->postJson($url,['action'=>'duration','reason'=>'Shorter visit','visit_ids'=>[$visit->id],'hours'=>1])->assertOk();
        $this->assertEquals(28.75,$order->fresh()->total_amount);
        $this->assertEquals(115,$order->fresh()->initial_amount);
        $this->assertEquals(1,$visit->fresh()->planned_hours);
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(false);auth('admin')->setUser($admin);
        $this->postJson($url,['action'=>'price','reason'=>'Not permitted','visit_ids'=>[$visit->id],'price'=>1])->assertForbidden();
        $this->postJson($url,['action'=>'cancel_order','reason'=>'Customer cancellation'])->assertForbidden();
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $this->postJson($url,['action'=>'cancel_order','reason'=>'Customer cancellation'])->assertOk();
        $this->assertEquals(0,$order->fresh()->total_amount);$this->assertEquals(0,$order->fresh()->paid_amount);
        $this->assertDatabaseHas('hourly_order_payments',['hourly_rental_order_id'=>$order->id,'amount'=>-115,'status'=>'paid']);
        $this->assertSame('cancelled',$order->fresh()->status);$this->assertSame(0,$order->fresh()->balance['reserved']);
    }

    public function test_order_operations_accept_an_empty_or_missing_reason(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();
        $url=route('admin.hourly-orders.operations',$order);
        $this->postJson($url,['action'=>'duration','reason'=>'','visit_ids'=>[$visit->id],'hours'=>1])->assertOk();
        $this->assertEquals(1,$visit->fresh()->planned_hours);
        $this->postJson($url,['action'=>'cancel_order'])->assertOk();
        $this->assertSame('cancelled',$order->fresh()->status);
        $this->assertDatabaseHas('hourly_order_audits',['hourly_rental_order_id'=>$order->id,'action'=>'cancel_order','reason'=>null]);
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertDontSee('id="operation-reason" name="reason" class="form-control" maxlength="2000" required',false);
    }

    public function test_quarter_hour_edit_accepts_manual_price_difference_and_rejects_stale_data(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();
        $url=route('admin.hourly-orders.operations',$order);
        $this->postJson($url,['action'=>'price','visit_ids'=>[$visit->id],'price'=>400,'reason'=>'Set price'])->assertOk();
        $data=['action'=>'duration','visit_ids'=>[$visit->id],'hours'=>4.25,'duration_price_adjustment'=>25,'expected_hours'=>4,'expected_price'=>400,'reason'=>'Extra quarter hour'];
        $this->postJson($url,$data)->assertOk();
        $this->assertEquals(425,$order->fresh()->total_amount);
        $this->assertSame('12:15',$visit->fresh()->scheduled_end_at->format('H:i'));
        $this->postJson($url,$data)->assertUnprocessable();
        unset($data['expected_hours'],$data['expected_price']);
        $data['hours']=4.1;$this->postJson($url,$data)->assertUnprocessable();
        $data['hours']=4.5;$data['duration_price_adjustment']=-500;$this->postJson($url,$data)->assertUnprocessable();
        $data['duration_price_adjustment']=10;$this->postJson($url,$data)->assertOk();
        $this->assertEquals(435,$order->fresh()->total_amount);
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertSee('id="visit-edit-modal"',false)->assertSee('id="ve-plus"',false);
    }

    public function test_duration_cap_override_is_explicit_and_recorded(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();
        HourlyRentalSettings::persist(['max_hours_per_day'=>4]);
        $url=route('admin.hourly-orders.operations',$order);
        $data=['action'=>'duration','visit_ids'=>[$visit->id],'hours'=>4.25,'reason'=>'Approved overtime'];
        $this->postJson($url,$data)->assertUnprocessable();
        $this->postJson($url,$data+['ignore_hour_caps'=>true])->assertOk();
        $audit=DB::table('hourly_order_audits')->where('visit_id',$visit->id)->latest('id')->first();
        $this->assertTrue(json_decode($audit->after,true)['duration_options']['ignore_hour_caps']);
        $this->assertEquals(4.25,$visit->fresh()->planned_hours);
    }

    public function test_ignoring_break_does_not_allow_an_actual_worker_overlap(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);$visit=$order->visits()->first();
        HourlyRentalSettings::persist(['max_visits_per_day'=>5,'max_hours_per_day'=>12,'min_rest_hours_between_visits'=>1]);
        $other=$order->replicate();$other->unit='different-unit';$other->order_number=(string)Str::uuid();$other->invoice_number=(string)Str::uuid();$other->booking_token=(string)Str::uuid();$other->save();
        $later=$visit->replicate();$later->hourly_rental_order_id=$other->id;$later->scheduled_start_at=$visit->scheduled_start_at->copy()->setTime(13,0);$later->scheduled_end_at=$later->scheduled_start_at->copy()->addHours(4);$later->save();$later->workers()->attach(1);
        $url=route('admin.hourly-orders.operations',$order);
        $data=['action'=>'duration','visit_ids'=>[$visit->id],'hours'=>4.25,'reason'=>'Shorter break'];
        $this->postJson($url,$data)->assertUnprocessable();
        $this->postJson($url,$data+['ignore_break'=>true])->assertOk();
        $data['hours']=5.25;$this->postJson($url,$data+['ignore_break'=>true,'ignore_hour_caps'=>true])->assertUnprocessable();
        $this->assertEquals(4.25,$visit->fresh()->planned_hours);
    }

    public function test_renewal_prefills_customer_without_creating_an_order(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7);
        $order->update(['status'=>'active']);
        $order->visits()->first()->update(['status'=>'completed']);
        $this->get(route('admin.hourly-orders.create',['renew'=>$order->id]))->assertOk()
            ->assertViewHas('renewal',fn($r)=>$r['id']===$order->id && $r['customer']['id']===$order->user_id && $r['item_id']===$order->hourly_package_id);
        $this->assertSame(1,HourlyRentalOrder::count());
        $this->get('/panel/orders/index/act/all?to='.now()->toDateString())->assertOk()->assertSee($order->order_number);
    }

    public function test_skipped_assignment_reserves_capacity_and_cannot_be_double_booked(): void
    {
        $data=$this->booking();$data['workers_per_visit']=2;$data['visits'][0]['worker_ids']=[null,null];
        $order=app(PanelBookingService::class)->create($data,7);
        $this->assertSame(0,DB::table('hourly_visit_workers')->count());
        $this->assertSame(2,DB::table('hourly_visit_reservations')->count());
        $this->assertSame(2,$order->visits()->first()->workers_count);
        DB::table('user_addresses')->where('id',$data['address_id'])->update(['unit'=>'different-unit']);
        $data['booking_token']=(string)Str::uuid();
        try { app(PanelBookingService::class)->create($data,7);$this->fail('Capacity must be reserved'); }
        catch (ValidationException $e) { $this->assertSame(1,HourlyRentalOrder::count()); }
    }

    public function test_wallet_coupon_and_manual_price_are_consistent_and_debited_once(): void
    {
        $data=$this->booking();$data+=['manual_total'=>100,'coupon_code'=>'SAVE'];$data['payment_method']='customer_balance';
        DB::table('hourly_coupons')->insert(['code'=>'SAVE','type'=>'percent','value'=>10,'usage_limit'=>1,'is_active'=>true]);
        $service=app(PanelBookingService::class);$order=$service->create($data,7);$service->create($data,7);
        $this->assertEquals(90,$order->fresh()->total_amount);$this->assertEquals(90,$order->fresh()->paid_amount);
        $this->assertEquals(90,$order->visits()->sum('visit_price'));
        $this->assertEquals(910,DB::table('users')->value('hourly_wallet_balance'));
        $this->assertSame(1,DB::table('hourly_wallet_transactions')->count());
        $this->assertSame(1,DB::table('hourly_coupons')->value('used_count'));
    }

    public function test_failed_payment_rolls_back_customer_address_order_and_reservations(): void
    {
        $data=$this->booking();$data['customer_mode']='new';$data['address_mode']='new';$data['payment_method']='customer_balance';
        $data['customer']=['type'=>'company','company_name'=>'شركة جديدة','email'=>'new@example.test','phone'=>'501234567','password'=>'secret-password'];
        $data['address']=['city_id'=>1,'hourly_district_id'=>DB::table('hourly_service_districts')->value('id'),'latitude'=>24.7,'longitude'=>46.7,'address'=>'عنوان جديد','building'=>'فيلا','unit'=>'1','contact_first_name'=>'عميل','contact_last_name'=>'جديد','contact_phone'=>'0501234567'];
        try { app(PanelBookingService::class)->create($data,7);$this->fail('Insufficient funds'); }
        catch (ValidationException $e) {
            $this->assertSame(1,DB::table('users')->count());$this->assertSame(1,DB::table('user_addresses')->count());
            $this->assertSame(0,HourlyRentalOrder::count());$this->assertSame(0,DB::table('hourly_visit_workers')->count());
        }
    }

    public function test_leave_and_other_customers_addresses_are_rejected(): void
    {
        $data=$this->booking();$start=$data['visits'][0]['starts_at'];
        DB::table('hourly_worker_leaves')->insert(['maid_id'=>1,'starts_at'=>$start,'ends_at'=>\Carbon\Carbon::parse($start)->addDay(),'is_active'=>true]);
        try {app(PanelBookingService::class)->create($data,7);$this->fail('Worker on leave');} catch (ValidationException $e) {$this->assertSame(0,HourlyRentalOrder::count());}
        DB::table('user_addresses')->where('id',$data['address_id'])->update(['user_id'=>2]);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);app(PanelBookingService::class)->create($data,7);
    }

    public function test_mixed_collection_records_cash_and_card_separately(): void
    {
        $data=$this->booking();$data['payment_method']='cash_and_card';$data['collect_now']=true;$data['cash_amount']=40;
        $order=app(PanelBookingService::class)->create($data,7);
        $this->assertEquals(['cash'=>40,'card_on_delivery'=>75],$order->payments()->pluck('amount','method')->toArray());
        $this->assertSame('paid',$order->fresh()->payment_status);
    }

    public function test_wizard_endpoints_are_protected_and_preview_does_not_create_records(): void
    {
        $data=$this->booking();
        $this->get('/panel/add-order')->assertOk()->assertSee('id="place-order"',false)->assertSee('id="service-items"',false)
            ->assertSee('data-customer-type="company"',false)->assertSee('name="company_name"',false);
        $this->postJson('/panel/add-order/quote',$data)->assertOk()->assertJsonPath('total',11500);
        $this->postJson('/panel/add-order/availability',$data+['date'=>substr($data['visits'][0]['starts_at'],0,10)])->assertOk();
        $this->assertSame(0,HourlyRentalOrder::count());
        foreach (app('router')->getRoutes() as $route) {
            if (!str_starts_with($route->uri(),'panel/add-order'))continue;
            foreach (['admin','admin.route.permission','feature.hourly_rental'] as $middleware) $this->assertContains($middleware,$route->gatherMiddleware());
            $this->assertArrayHasKey($route->getName(),config('hourly_permissions'));
        }
    }

    public function test_controller_stores_new_customer_and_address_only_at_confirmation_and_retry_is_safe(): void
    {
        \Illuminate\Support\Facades\Bus::fake();
        $data=$this->booking();$data['customer_mode']='new';$data['address_mode']='new';
        $data['customer']=['type'=>'individual','first_name'=>'عميل','last_name'=>'جديد','email'=>'new@example.test','phone'=>'501234567','password'=>'secret-password'];
        $data['address']=['city_id'=>1,'hourly_district_id'=>DB::table('hourly_service_districts')->value('id'),'latitude'=>24.7,'longitude'=>46.7,'address'=>'عنوان جديد','building'=>'فيلا','unit'=>'1','contact_first_name'=>'عميل','contact_last_name'=>'جديد','contact_phone'=>'0501234567'];
        $response=$this->postJson('/panel/add-order/store',$data)->assertOk();
        $this->postJson('/panel/add-order/store',$data)->assertOk()->assertJsonPath('id',$response->json('id'));
        $this->assertSame(2,DB::table('users')->count());$this->assertSame(2,DB::table('user_addresses')->count());
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret-password',DB::table('users')->where('email','new@example.test')->value('password')));
        $this->assertSame(1,HourlyRentalOrder::count());
        \Illuminate\Support\Facades\Bus::assertDispatchedTimes(\App\Jobs\SendHourlyBookingConfirmation::class,1);
    }

    public function test_map_links_never_follow_untrusted_hosts(): void
    {
        \Illuminate\Support\Facades\Http::fake(['https://maps.app.goo.gl/test'=>\Illuminate\Support\Facades\Http::response('',302,['Location'=>'http://127.0.0.1/private'])]);
        $this->postJson('/panel/add-order/map-link',['url'=>'https://maps.app.goo.gl/test'])->assertStatus(422);
        \Illuminate\Support\Facades\Http::assertSentCount(1);
        $this->postJson('/panel/add-order/map-link',['url'=>'https://www.google.com/maps/@24.7,46.7,15z'])->assertOk()->assertJsonPath('latitude',24.7);
    }

    public function test_saved_addresses_include_city_and_only_belong_to_selected_customer(): void
    {
        $first=$this->address(24.7,46.7,'الملز');
        $second=$this->address(24.71,46.71,'الروضة');
        $other=$this->address(24.72,46.72,'عنوان عميل آخر');
        DB::table('user_addresses')->where('id',$other)->update(['user_id'=>2]);
        $this->getJson('/panel/add-order/addresses?user_id=1')->assertOk()->assertJsonCount(2)
            ->assertJsonPath('0.id',$second)->assertJsonPath('1.id',$first)->assertJsonPath('0.city_name','الرياض')
            ->assertJsonMissing(['id'=>$other]);
        $this->assertSame(3,DB::table('user_addresses')->count());
        $this->assertSame(0,HourlyRentalOrder::count());
    }

    public function test_company_booking_saves_company_profile_without_individual_fields(): void
    {
        \Illuminate\Support\Facades\Bus::fake();
        $data=$this->booking();$data['customer_mode']='new';$data['address_mode']='new';
        $data['customer']=['type'=>'company','company_name'=>'شركة النظافة','company_id'=>'CR-123','email'=>'company@example.test','phone'=>'501234567','phone_code'=>'966','password'=>'secret-password','language'=>'ar','note'=>'مسؤول التواصل: أحمد',
            'first_name'=>'حقل مخفي','last_name'=>'حقل مخفي','birthdate'=>'invalid','identity'=>'hidden'];
        $data['address']=['city_id'=>1,'hourly_district_id'=>DB::table('hourly_service_districts')->value('id'),'latitude'=>24.7,'longitude'=>46.7,'address'=>'عنوان الشركة','building'=>'مكتب','unit'=>'1','contact_first_name'=>'أحمد','contact_last_name'=>'محمد','contact_phone'=>'501234567'];
        $this->postJson('/panel/add-order/store',$data)->assertOk();
        $user=DB::table('users')->where('email','company@example.test')->first();
        $this->assertSame('شركة النظافة',$user->name);
        $profile=json_decode($user->hourly_customer_profile,true);
        $this->assertSame('CR-123',$profile['company_id']);$this->assertSame('مسؤول التواصل: أحمد',$profile['note']);
        foreach (['first_name','last_name','birthdate','identity','password'] as $field) $this->assertArrayNotHasKey($field,$profile);
    }

    public function test_customer_required_fields_depend_on_type_and_phone_has_nine_digits(): void
    {
        $data=$this->booking();$data['customer_mode']='new';
        $data['customer']=['type'=>'company','email'=>'company@example.test','phone'=>'0501234567','password'=>'secret-password'];
        $this->postJson('/panel/add-order/store',$data)->assertUnprocessable()->assertJsonValidationErrors(['company_name','phone'])->assertJsonMissingValidationErrors(['first_name','last_name']);
        $data['customer']['type']='individual';$data['customer']['phone']='501234567';
        $this->postJson('/panel/add-order/store',$data)->assertUnprocessable()->assertJsonValidationErrors(['first_name','last_name'])->assertJsonMissingValidationErrors(['company_name','phone']);
        $data['customer']['phone']='500000000'; // Existing customer's stored 0500000000.
        $this->postJson('/panel/add-order/store',$data)->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->assertSame(1,DB::table('users')->count());
    }

    public function test_manual_price_requires_its_own_permission(): void
    {
        $data=$this->booking();$data['manual_total']=1;
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(false);auth('admin')->setUser($admin);
        $this->postJson('/panel/add-order/quote',$data)->assertForbidden();
        $this->postJson('/panel/add-order/store',$data)->assertForbidden();
        $this->assertSame(0,HourlyRentalOrder::count());
    }

    public function test_confirmation_retry_does_not_repeat_a_successful_channel(): void
    {
        Schema::create('notifications',function (Blueprint $t) {$t->id();$t->unsignedBigInteger('user_id');$t->string('type');$t->text('title');$t->text('description');$t->timestamps();});
        $data=$this->booking();$data['notify_email']=true;$data['notify_app']=true;
        $order=app(PanelBookingService::class)->create($data,7);
        $push=\Mockery::mock(\App\Services\Firebase\FirebasePushService::class);
        $push->shouldReceive('sendToUser')->once()->andReturn(\App\Services\Firebase\FirebasePushResult::skipped('No tokens'));
        $this->app->instance(\App\Services\Firebase\FirebasePushService::class,$push);
        $attempts=0;
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->twice()->andReturnUsing(function () use (&$attempts) {if (++$attempts===1) throw new \RuntimeException('Temporary mail failure');});
        $job=new \App\Jobs\SendHourlyBookingConfirmation($order->id);
        try {$job->handle();$this->fail('Expected mail failure');} catch (\RuntimeException $e) {$this->assertSame('Temporary mail failure',$e->getMessage());}
        $this->assertSame(1,DB::table('notifications')->count());
        $job->handle();$job->handle();
        $this->assertSame(1,DB::table('notifications')->count());
        $this->assertNotEmpty($order->fresh()->booking_metadata['email_sent_at']);
    }
    public function test_duplicate_booking_with_another_worker_is_blocked_and_original_retry_is_safe(): void
    {
        $data=$this->booking();$source=app(PanelBookingService::class)->create($data,7);
        $retry=app(PanelBookingService::class)->create($data,7);$this->assertSame($source->id,$retry->id);
        $second=$data;$second['booking_token']=(string)Str::uuid();$second['visits'][0]['worker_ids']=[2];
        $this->postJson('/panel/add-order/store',$second)->assertStatus(409)->assertJsonPath('code','duplicate_visit')
            ->assertJsonPath('duplicate.visits.0.order_id',$source->id)->assertJsonPath('duplicate.visits.0.can_add_worker',true);
        $this->assertSame(1,HourlyRentalOrder::count());$this->assertSame(1,DB::table('hourly_visits')->count());
        $this->assertSame(0,DB::table('hourly_order_payments')->count());
        $this->assertEquals(1000,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
    }

    public function test_customer_api_and_new_address_copy_cannot_bypass_duplicate_detection(): void
    {
        $data=$this->booking();$source=app(PanelBookingService::class)->create($data,7);
        $address=\App\Models\ingaz\UserAddress::findOrFail($data['address_id']);$copy=$address->replicate();$copy->save();
        auth('api')->setUser(\App\Models\ingaz\User::findOrFail(1));
        $this->postJson('/api/app/hourly/orders',['booking_token'=>(string)Str::uuid(),'hourly_package_id'=>$data['hourly_package_id'],
            'address_id'=>$copy->id,'workers_per_visit'=>1,'payment_method'=>'cash','scheduled_starts'=>[$data['visits'][0]['starts_at']]])
            ->assertStatus(409)->assertJsonPath('code','duplicate_visit')->assertJsonPath('duplicate.visits.0.order_id',$source->id);
        $this->assertSame(1,HourlyRentalOrder::count());
    }

    public function test_non_overlapping_booking_and_cancelled_source_do_not_block_new_orders(): void
    {
        $data=$this->booking();$source=app(PanelBookingService::class)->create($data,7);
        $later=$data;$later['booking_token']=(string)Str::uuid();$later['visits'][0]['worker_ids']=[2];
        $later['visits'][0]['starts_at']=\Carbon\Carbon::parse($data['visits'][0]['starts_at'])->addDay()->format('Y-m-d H:i');
        $this->assertNotSame($source->id,app(PanelBookingService::class)->create($later,7)->id);
        $source->visits()->update(['status'=>'cancelled']);$source->update(['status'=>'cancelled']);
        $new=$data;$new['booking_token']=(string)Str::uuid();$new['visits'][0]['worker_ids']=[2];
        $this->assertNotSame($source->id,app(PanelBookingService::class)->create($new,7)->id);
    }

    public function test_customer_adds_one_worker_to_only_the_existing_visit_after_price_confirmation(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $data=$this->booking(2);$source=app(PanelBookingService::class)->create($data,7);$visits=$source->visits()->orderBy('visit_number')->get();$visit=$visits[0];
        auth('api')->setUser(\App\Models\ingaz\User::findOrFail(1));
        $url='/api/app/hourly/orders/'.$source->id.'/visits/'.$visit->id.'/additional-worker';
        request()->query->set('add_worker_visit',$visit->id);
        $html=view('web.hourly._additional-worker',['order'=>$source->load('visits')])->render();
        $this->assertStringContainsString('name="confirmed"',$html);
        request()->query->remove('add_worker_visit');
        $quote=$this->getJson($url)->assertOk()->assertJsonPath('data.current_workers',1)->assertJsonPath('data.new_workers',2)->json('data');
        $body=['request_token'=>(string)Str::uuid(),'confirmed'=>1,'expected_workers'=>$quote['current_workers'],'expected_price'=>$quote['current_price'],'expected_total'=>$quote['order_total']];
        $this->postJson($url,array_merge($body,['confirmed'=>0]))->assertStatus(422);
        $this->postJson($url,array_merge($body,['expected_price'=>1]))->assertStatus(422);
        $result=$this->postJson($url,$body)->assertOk()->assertJsonPath('data.replayed',false)->json('data');
        $this->postJson($url,$body)->assertOk()->assertJsonPath('data.replayed',true);
        $this->assertSame(1,HourlyRentalOrder::count());$this->assertSame(2,DB::table('hourly_visits')->count());
        $this->assertSame(2,$visit->fresh()->workers()->count());$this->assertSame(2,(int)$visit->fresh()->workers_count);
        $this->assertEquals($quote['new_price'],$visit->fresh()->visit_price);
        $this->assertEquals($quote['new_order_total'],$source->fresh()->total_amount);
        $this->assertSame(1,(int)$visits[1]->fresh()->workers_count);
        $this->assertEquals($visits[1]->visit_price,$visits[1]->fresh()->visit_price);
        $this->assertSame(0,DB::table('hourly_order_payments')->count());
        $this->assertDatabaseHas('hourly_order_audits',['hourly_rental_order_id'=>$source->id,'visit_id'=>$visit->id,'action'=>'customer_add_worker']);
        $this->postJson($url,array_merge($body,['expected_total'=>1]))->assertStatus(422);
        DB::table('users')->insert(['id'=>2,'name'=>'Other','phone'=>'0500000099']);
        auth('api')->setUser(\App\Models\ingaz\User::findOrFail(2));
        $this->getJson($url)->assertNotFound();$this->postJson($url,$body)->assertNotFound();
    }

    public function test_customer_additional_worker_declines_when_worker_is_no_longer_available(): void
    {
        $data=$this->booking();$source=app(PanelBookingService::class)->create($data,7);$visit=$source->visits()->first();
        auth('api')->setUser(\App\Models\ingaz\User::findOrFail(1));
        $url='/api/app/hourly/orders/'.$source->id.'/visits/'.$visit->id.'/additional-worker';
        $quote=$this->getJson($url)->assertOk()->json('data');
        $maid=\App\Models\ingaz\Maid::findOrFail(2);$settings=$maid->hourly_settings;
        $settings['work_schedule']=array_fill_keys(array_keys(\App\Models\Hourly\HourlyDriver::DAYS),['mode'=>'off']);
        $maid->update(['hourly_settings'=>$settings]);
        DB::table('hourly_worker_leaves')->insert(['maid_id'=>2,'starts_at'=>$visit->scheduled_start_at,'ends_at'=>$visit->scheduled_end_at,'is_active'=>true]);
        $this->postJson($url,['request_token'=>(string)Str::uuid(),'confirmed'=>1,'expected_workers'=>1,
            'expected_price'=>$quote['current_price'],'expected_total'=>$quote['order_total']])->assertStatus(422);
        $this->assertSame(1,(int)$visit->fresh()->workers_count);
    }
    public function test_driver_residency_blocks_manual_assignment_on_task_date_and_recovers_after_renewal(): void
    {
        $order=app(PanelBookingService::class)->create($this->booking(),7); $visit=$order->visits()->firstOrFail();
        Schema::table('hourly_drivers',function(Blueprint $t){$t->json('work_schedule')->nullable();$t->date('residency_expiry_date')->nullable();});
        DB::table('hourly_drivers')->insert(['id'=>1,'name'=>'Residency driver','is_active'=>true,
            'residency_expiry_date'=>$visit->scheduled_start_at->copy()->subDay()->toDateString(),
            'work_schedule'=>json_encode(array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day']))]);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>$visit->hourly_team_id,'member_type'=>'driver','member_id'=>1,'role'=>'primary','is_active'=>true]);
        $service=app(\App\Services\Hourly\OrderManagementService::class);
        $clock=$visit->scheduled_start_at->format('H:i');
        $this->assertCount(0,$service->availableDrivers(collect([$visit]),'delivery',$clock));
        $body=['action'=>'assign_driver','reason'=>'Residency check','visit_ids'=>[$visit->id],'driver_id'=>1,'driver_role'=>'delivery','driver_time'=>$clock];
        $url=route('admin.hourly-orders.operations',$order);
        $response=$this->postJson($url,$body)->assertUnprocessable();
        $this->assertStringContainsString('تنتهي إقامته',json_encode($response->json('errors'),JSON_UNESCAPED_UNICODE));
        $this->assertNull($visit->fresh()->delivery_driver_id);
        DB::table('hourly_drivers')->where('id',1)->update(['residency_expiry_date'=>$visit->scheduled_start_at->toDateString()]);
        $this->assertCount(1,$service->availableDrivers(collect([$visit]),'delivery',$clock));
        $this->postJson($url,$body)->assertOk();
        $this->assertEquals(1,$visit->fresh()->delivery_driver_id);
    }
}
