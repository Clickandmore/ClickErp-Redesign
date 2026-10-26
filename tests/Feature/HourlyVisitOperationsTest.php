<?php
namespace Tests\Feature;

use Tests\HourlyTestCase as TestCase;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema};
use App\Models\Hourly\{HourlyDriver,HourlyRentalOrder};
use App\Services\Hourly\{PanelBookingService,VisitBatchService,VisitSearch};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HourlyVisitOperationsTest extends TestCase
{
    use BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema;
    protected function setUp():void
    {
        parent::setUp();$this->setUpHourlyCategorySchema();$this->buildWorkers();
        Schema::create('admins',function(Blueprint $t){ $t->softDeletes();$t->id();$t->string('name');});DB::table('admins')->insert(['id'=>7,'name'=>'مشرف']);
        Schema::create('users',function(Blueprint $t){ $t->softDeletes();$t->id();$t->string('name');$t->string('email')->nullable();$t->string('phone');$t->string('phone_code')->default('966');$t->string('password')->nullable();$t->string('register_from')->nullable();$t->boolean('is_blocked')->default(false);$t->timestamps();});
        Schema::table('user_addresses',function(Blueprint $t){foreach(['name','phone','title'] as $c)$t->string($c)->nullable();});
        foreach(['2026_09_20_000001_add_hourly_booking_wizard','2026_09_21_000001_extend_hourly_order_management','2026_09_21_000002_add_hourly_visit_operations','2026_09_22_000001_create_hourly_customer_sources','2026_09_29_000001_add_deep_link_ids_to_hourly_visit_notices'] as $migration)(require base_path('database/migrations/'.$migration.'.php'))->up();
        DB::table('users')->insert(['id'=>1,'name'=>'عميل التشغيل','phone'=>'0500000000','email'=>'client@example.test','hourly_wallet_balance'=>1000]);
        Schema::create('hourly_drivers',function(Blueprint $t){$t->id();$t->string('name');$t->boolean('is_active')->default(true);$t->json('work_schedule')->nullable();$t->timestamps();$t->softDeletes();});
    }
    protected function tearDown():void{\Carbon\Carbon::setTestNow();HourlyRentalSettings::flush();parent::tearDown();}
    private function order(int $days=3):HourlyRentalOrder
    {
        $category=$this->category();$district=\App\Models\Hourly\ServiceDistrict::first()??$this->district('الملز',24.7,46.7);$team=$this->team('فريق '.$days);
        $category->districts()->attach($district->id,['is_active'=>true,'team_id'=>$team->id]);$item=$this->item($category);
        for($id=1;$id<=2;$id++){
            DB::table('maids')->insertOrIgnore(['id'=>$id,'name'=>'عاملة '.$id,'rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>today()->toDateString(),'hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])])]);
            if(!DB::table('maid_jobs')->where('maid_id',$id)->exists())DB::table('maid_jobs')->insert(['maid_id'=>$id,'job_type_id'=>2]);
            DB::table('contracts')->insertOrIgnore(['id'=>$id,'maid_id'=>$id,'status'=>'shelter']);
            if(!DB::table('contract_infos')->where('contract_id',$id)->exists())DB::table('contract_infos')->insert(['contract_id'=>$id,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
            DB::table('hourly_team_members')->insert(['hourly_team_id'=>$team->id,'member_type'=>'maid','member_id'=>$id,'role'=>'primary','is_active'=>true]);
        }
        return app(PanelBookingService::class)->create(['booking_token'=>(string)Str::uuid(),'customer_mode'=>'existing','user_id'=>1,'address_mode'=>'existing','address_id'=>$this->address(24.7,46.7,'الملز'),'hourly_package_id'=>$item->id,'workers_per_visit'=>1,'payment_method'=>'cash','collect_now'=>false,'all_workers'=>false,'notify_email'=>false,'notify_app'=>false,'visits'=>[['starts_at'=>now()->addDays($days)->format('Y-m-d').' 08:00','worker_ids'=>[1]]]],7);
    }
    private function payload(string $action,array $ids,array $more=[]):array{return $more+['batch_id'=>(string)Str::uuid(),'action'=>$action,'visit_ids'=>$ids,'reason'=>'اختبار التشغيل'];}
    public function test_order_driver_choices_follow_schedule_membership_and_conflicts():void
    {
        $order=$this->order();$visit=$order->visits()->first();
        $driver=HourlyDriver::create(['name'=>'Available driver','is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'hours','start'=>'09:00','end'=>'18:00'])]);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>$visit->hourly_team_id,'member_type'=>'driver','member_id'=>$driver->id,'role'=>'primary','is_active'=>true]);
        $url=route('admin.hourly-orders.available-drivers',$order);
        $query=['visit_ids'=>[$visit->id],'driver_role'=>'delivery','driver_time'=>'08:00'];
        $this->getJson($url.'?'.http_build_query($query))->assertOk()->assertJsonCount(0,'drivers');
        $query['driver_role']='pickup';$query['driver_time']='12:00';
        $this->getJson($url.'?'.http_build_query($query))->assertOk()->assertJsonPath('drivers.0.id',$driver->id);
        $other=$visit->replicate();$other->visit_number=2;$other->delivery_driver_id=$driver->id;$other->delivery_at=$visit->scheduled_end_at->copy()->addMinutes(15);$other->save();
        $this->getJson($url.'?'.http_build_query($query))->assertOk()->assertJsonCount(0,'drivers');
        $other->update(['status'=>'cancelled']);
        $this->getJson($url.'?'.http_build_query($query))->assertOk()->assertJsonCount(1,'drivers');
        DB::table('hourly_team_members')->where('member_type','driver')->update(['is_active'=>false]);
        $this->getJson($url.'?'.http_build_query($query))->assertOk()->assertJsonCount(0,'drivers');
    }
    public function test_pages_filters_aliases_and_assignment_counts():void
    {
        $a=$this->order();$b=$this->order(4);$first=$a->visits()->first();$second=$b->visits()->first();$second->workers()->detach();
        $search=app(VisitSearch::class);
        $this->assertEquals([$second->id],$search->query([],'unassigned')->pluck('id')->all());
        $this->assertEquals([$first->id],$search->query(['status'=>'assigned'])->pluck('id')->all());
        $this->assertEquals(2,$search->query(['search'=>'عميل التشغيل'])->count());
        $first->update(['scheduled_end_at'=>now()->subHour(),'scheduled_start_at'=>now()->subHours(5)]);
        $this->assertEquals([$first->id],$search->query([],'past')->pluck('id')->all());
        foreach(['date','customer','workers','leader','driver','price'] as $sort)$this->assertCount(2,$search->sort($search->query([]),['sort'=>$sort])->get());
        foreach(['','/unassigned','/past-uncompleted'] as $suffix)$this->get('/admin/hourly/visits'.$suffix.'?from=&to=')->assertOk()->assertSee('مرشحات الزيارات')->assertDontSee('@selected');
        $this->get('/admin/hourly/visits?from=&to=&per_page=50')->assertOk()->assertSee('value="50" selected',false);
        $this->get('/panel/orders/visit-list')->assertRedirect(route('admin.hourly-visits.index'));
    }
    public function test_bulk_edit_is_atomic_and_idempotent():void
    {
        $a=$this->order();$b=$this->order(4);$ids=[$a->visits()->first()->id,$b->visits()->first()->id];$service=app(VisitBatchService::class);
        $payload=$this->payload('price',$ids,['price'=>200]);
        $first=$service->run($payload,7);$this->assertSame($first,$service->run($payload,7));
        $this->assertEquals(200,$a->fresh()->total_amount);$this->assertEquals(200,$b->fresh()->total_amount);
        $b->update(['status'=>'completed']);
        try{$service->run($this->payload('price',$ids,['price'=>300]),7);$this->fail('Closed order must reject entire batch');}catch(ValidationException $e){$this->assertNotEmpty($e->errors());}
        $this->assertEquals(200,$a->fresh()->total_amount);$this->assertSame(1,DB::table('hourly_visit_batches')->count());
    }
    public function test_collection_groups_customer_receipts_prevents_duplicates_and_overpayment():void
    {
        $a=$this->order();$b=$this->order(4);$ids=[$a->visits()->first()->id,$b->visits()->first()->id];$service=app(VisitBatchService::class);
        $payload=$this->payload('collect',$ids,['method'=>'cash','amount'=>150,'paid_at'=>now()->format('Y-m-d\TH:i'),'send_receipt'=>true]);
        $result=$service->run($payload,7);$service->run($payload,7);
        $this->assertCount(1,$result['receipts']);$this->assertEquals(150,$result['receipts'][0]['amount']);
        $this->assertEquals(150,DB::table('hourly_order_payments')->sum('amount'));
        $this->assertSame(1,DB::table('hourly_visit_notices')->count());
        // إشعار يجمع طلبين وزيارتين: لا وجهة مفردة، فيفتح التطبيق قائمة الطلبات.
        $notice=DB::table('hourly_visit_notices')->first();
        $this->assertSame([null,null],[$notice->order_id,$notice->visit_id]);
        $this->get('/admin/hourly/visits/receipts/'.$result['receipts'][0]['id'])->assertOk()->assertSee('150.00');
        $this->expectException(ValidationException::class);$service->run($this->payload('collect',$ids,['method'=>'cash','amount'=>999999,'paid_at'=>now()->format('Y-m-d\TH:i')]),7);
    }
    public function test_single_visit_notice_carries_order_and_visit_ids_for_the_app_deep_link():void
    {
        $order=$this->order();$visit=$order->visits()->first();
        app(VisitBatchService::class)->run($this->payload('note',[$visit->id],['notify_app'=>true]),7);
        $notice=DB::table('hourly_visit_notices')->where('channel','app')->first();
        $this->assertSame([(int)$order->id,(int)$visit->id],[(int)$notice->order_id,(int)$notice->visit_id]);
    }
    public function test_cancellation_refunds_only_paid_excess_once_to_wallet():void
    {
        $order=$this->order();$id=$order->visits()->first()->id;$service=app(VisitBatchService::class);$total=$order->total_amount;
        $service->run($this->payload('collect',[$id],['method'=>'cash','amount'=>$total,'paid_at'=>now()->format('Y-m-d\TH:i')]),7);
        $payload=$this->payload('cancel_visit',[$id],['refund_method'=>'wallet']);$service->run($payload,7);$service->run($payload,7);
        $this->assertEquals(1000+$total,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $this->assertEquals(0,$order->fresh()->paid_amount);$this->assertEquals(0,DB::table('hourly_order_payments')->sum('amount'));
        $this->assertSame(1,DB::table('hourly_wallet_transactions')->where('amount','>',0)->count());
    }
    public function test_auto_driver_and_offer_claim_use_existing_availability_rules():void
    {
        $order=$this->order();$visit=$order->visits()->first();$service=app(VisitBatchService::class);
        $driver=HourlyDriver::create(['name'=>'سائق','is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>$visit->hourly_team_id,'member_type'=>'driver','member_id'=>$driver->id,'role'=>'primary','is_active'=>true]);
        $service->run($this->payload('auto_driver',[$visit->id],['driver_direction'=>'together']),7);
        $this->assertEquals($driver->id,$visit->fresh()->delivery_driver_id);$this->assertEquals($driver->id,$visit->fresh()->pickup_driver_id);
        $visit->workers()->detach();$service->run($this->payload('offer',[$visit->id],['offered'=>true]),7);
        auth('hourly_maid_api')->setUser(\App\Models\Hourly\HourlyMaidAccount::findOrFail(1));
        $controller=app(\App\Http\Controllers\Hourly\WorkerVisitOffersController::class);$request=\Illuminate\Http\Request::create('/api/app/hourly/maid/offers');
        $this->assertCount(1,$controller->index($request)->getData(true)['data']);
        $controller->claim($request,$visit->id);$this->assertSame(1,$visit->workers()->count());$this->assertFalse((bool)$visit->fresh()->offered_to_workers);
        $this->expectException(ValidationException::class);$controller->claim($request,$visit->id);
    }

    public function test_worker_offers_are_filtered_before_pagination():void
    {
        $order=$this->order();$visit=$order->visits()->first();$visit->workers()->detach();
        $visit->update(['offered_to_workers'=>true]);
        $attributes=$visit->getAttributes();
        unset($attributes['id']);
        for($i=25;$i>=1;$i--){
            $copy=$attributes;
            $copy['visit_number']=$i+1;
            $copy['hourly_team_id']=999999;
            $copy['scheduled_start_at']=$visit->scheduled_start_at->copy()->subMinutes($i);
            $copy['scheduled_end_at']=$visit->scheduled_end_at->copy()->subMinutes($i);
            $copy['offered_to_workers']=true;
            DB::table('hourly_visits')->insert($copy);
        }
        auth('hourly_maid_api')->setUser(\App\Models\Hourly\HourlyMaidAccount::findOrFail(1));
        $request=\Illuminate\Http\Request::create('/api/worker/offers');
        $response=app(\App\Http\Controllers\Hourly\WorkerVisitOffersController::class)->index($request)->getData(true);
        $this->assertSame([$visit->id],array_column($response['data'],'id'));
        $this->assertNull($response['next_page_url']);
    }
    public function test_completed_unpaid_visit_can_be_collected():void
    {
        $order=$this->order();$visit=$order->visits()->first();
        $order->update(['status'=>'completed']);$visit->update(['status'=>'completed']);
        app(VisitBatchService::class)->run($this->payload('collect',[$visit->id],['method'=>'cash','amount'=>$order->total_amount,'paid_at'=>now()->format('Y-m-d\TH:i')]),7);
        $this->assertEquals($order->total_amount,$order->fresh()->paid_amount);
    }
    public function test_cancellation_without_refund_preserves_charge():void
    {
        $order=$this->order();$visit=$order->visits()->first();$total=$order->total_amount;
        app(VisitBatchService::class)->run($this->payload('cancel_visit',[$visit->id],['refund_method'=>'none']),7);
        $this->assertEquals($total,$order->fresh()->total_amount);
        $this->assertEquals($visit->visit_price,$visit->fresh()->cancellation_fee);
        $this->assertEquals(1000,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
    }
    public function test_financial_permission_is_checked_even_when_operations_permission_is_present():void
    {
        $order=$this->order();$admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturnUsing(fn($p)=>in_array($p,['hourly_orders_view','hourly_orders_create']));auth('admin')->setUser($admin);
        $this->postJson('/admin/hourly/visits/batch',$this->payload('collect',[$order->visits()->first()->id],['method'=>'cash','amount'=>10,'paid_at'=>now()->format('Y-m-d\TH:i')]))->assertForbidden();
        $this->postJson('/admin/hourly/visits/batch',$this->payload('cancel_visit',[$order->visits()->first()->id],['refund_method'=>'wallet']))->assertForbidden();
        $this->assertSame(0,DB::table('hourly_visit_batches')->count());
    }

    public function test_multiple_filters_null_values_and_date_limit():void
    {
        $a=$this->order();$b=$this->order(4);$va=$a->visits()->first();$vb=$b->visits()->first();$vb->workers()->detach();
        $vb->update(['team_leader_id'=>null,'hourly_team_id'=>null,'status'=>'cancelled']);
        $search=app(VisitSearch::class);
        $this->assertEquals([$va->id],$search->query([])->pluck('id')->all());
        $this->assertCount(2,$search->query(['status'=>['scheduled','cancelled']])->get());
        $this->assertEquals([$vb->id],$search->query(['show_cancelled'=>1,'worker_id'=>['none'],'team_id'=>['none']])->pluck('id')->all());
        foreach(['admin_id'=>[7,'none'],'city_id'=>[1,'none'],'customer_source'=>['none','admin'],'start_time'=>['08:00','09:30']] as $key=>$values)$this->assertCount(2,$search->query(['show_cancelled'=>1,$key=>$values])->get(),$key);
        $this->assertCount(2,$search->query(['show_cancelled'=>1,'team_id'=>[$va->hourly_team_id,'none'],'worker_id'=>[1,'none'],'driver_id'=>['none'],'admin_id'=>[7,'none'],'city_id'=>[1,'none'],'service_id'=>[$a->service_category_id,$b->service_category_id],'customer_source'=>['none','admin'],'start_time'=>['08:00','09:30']])->get());
        $url='/admin/hourly/visits?'.http_build_query(['from'=>'','to'=>'','team_id'=>[$va->hourly_team_id,'none'],'status'=>['scheduled','cancelled']]);
        $this->get($url)->assertOk()->assertSee('غير مخصص')->assertSee('ملغاة');
        $this->getJson('/admin/hourly/visits?from=2026-01-01&to=2026-02-01')->assertUnprocessable()->assertJsonValidationErrors('to');
        $this->get('/admin/hourly/visits?from=2026-01-01&to=2026-01-31')->assertOk();
        $this->getJson('/admin/hourly/visits?from=2026-01-01')->assertUnprocessable()->assertJsonValidationErrors('to');
    }

    public function test_existing_visit_can_be_staffed_before_start_but_not_at_or_after_start():void
    {
        \Carbon\Carbon::setTestNow('2026-09-19 07:00:00');
        $order=$this->order();$visit=$order->visits()->first();
        $visit->workers()->detach();
        $snapshot=$order->package_snapshot;$snapshot['min_notice_hours']=24;$order->update(['package_snapshot'=>$snapshot]);
        \Carbon\Carbon::setTestNow('2026-09-22 07:59:00');
        $this->postJson('/admin/hourly/visits/batch',$this->payload('assign_workers',[$visit->id],['worker_ids'=>[2]]))->assertOk();
        $this->assertEquals([2],$visit->workers()->pluck('maids.id')->all());
        $visit->workers()->detach();
        $this->postJson('/admin/hourly/visits/batch',$this->payload('assign_all',[$visit->id]))->assertOk();
        $this->assertSame(1,$visit->workers()->count());
        $this->postJson('/admin/hourly/visits/batch',$this->payload('start_time',[$visit->id],['start_time'=>'09:00']))->assertUnprocessable();
        $this->assertSame('08:00',$visit->fresh()->scheduled_start_at->format('H:i'));
        $assigned=$visit->workers()->pluck('maids.id')->all();
        foreach(['08:00:00','09:00:00','13:01:00'] as $clock){
            \Carbon\Carbon::setTestNow('2026-09-22 '.$clock);
            foreach(['assign_workers'=>['worker_ids'=>[2]],'assign_all'=>[]] as $action=>$extra){
                $response=$this->postJson('/admin/hourly/visits/batch',$this->payload($action,[$visit->id],$extra));
                $response->assertUnprocessable();
                $this->assertStringContainsString('تعيين العاملات متاح قبل بدء موعد الزيارة فقط',$response->json('errors.hourly.0'));
                $this->assertEquals($assigned,$visit->workers()->pluck('maids.id')->all());
            }
        }
    }

    public function test_auto_assignment_repairs_a_legacy_visit_without_a_team():void
    {
        $order=$this->order();$visit=$order->visits()->first();$teamId=$visit->hourly_team_id;
        $visit->workers()->detach();
        DB::table('hourly_visit_reservations')->where('hourly_visit_id',$visit->id)->delete();
        $visit->update(['hourly_team_id'=>null]);

        $this->postJson('/admin/hourly/visits/batch',$this->payload('assign_all',[$visit->id]))->assertOk();

        $this->assertEquals($teamId,$visit->fresh()->hourly_team_id);
        $this->assertSame(1,$visit->workers()->count());
    }

    public function test_manual_assignment_replacement_conflict_and_removal_are_audited():void
    {
        $order=$this->order();$visit=$order->visits()->first();
        $this->getJson('/admin/hourly/visits/'.$visit->id.'/workers')->assertOk()->assertJsonCount(2,'workers');
        $this->postJson('/admin/hourly/visits/batch',$this->payload('assign_workers',[$visit->id],['worker_ids'=>[2],'expected_worker_ids'=>[1]]))->assertOk();
        $this->assertEquals([2],$visit->workers()->pluck('maids.id')->all());
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>$visit->id,'action'=>'assign_workers']);
        $this->postJson('/admin/hourly/visits/batch',$this->payload('assign_workers',[$visit->id],['worker_ids'=>[1,2]]))->assertUnprocessable();
        $other=$this->order(4);$conflict=$other->visits()->first();$conflict->update(['scheduled_start_at'=>$visit->scheduled_start_at,'scheduled_end_at'=>$visit->scheduled_end_at]);
        $conflict->workers()->sync([1]);
        $this->postJson('/admin/hourly/visits/batch',$this->payload('assign_workers',[$visit->id],['worker_ids'=>[1]]))->assertUnprocessable();
        $this->assertEquals([2],$visit->workers()->pluck('maids.id')->all());
        $this->postJson('/admin/hourly/visits/batch',$this->payload('unassign_workers',[$visit->id]))->assertOk();
        $this->assertSame(0,$visit->workers()->count());
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>$visit->id,'action'=>'unassign_workers']);
        $this->assertEquals($order->total_amount,$order->fresh()->total_amount);
    }

    public function test_order_worker_picker_lists_and_assigns_workers_without_changing_the_price():void
    {
        $order=$this->order();$visit=$order->visits()->first();
        $url='/admin/hourly/orders/'.$order->id.'/visits/'.$visit->id.'/available-workers?selection=1';
        $this->getJson($url)->assertOk()->assertJsonCount(2,'workers')->assertJsonPath('selected.0',1)->assertJsonPath('required',1);
        $this->postJson('/admin/hourly/orders/'.$order->id.'/operations',[
            'action'=>'assign_workers','visit_ids'=>[$visit->id],'worker_ids'=>[2],'expected_worker_ids'=>[1],
        ])->assertOk();
        $this->assertEquals([2],$visit->workers()->pluck('maids.id')->all());
        $this->assertEquals($order->total_amount,$order->fresh()->total_amount);
        $this->postJson('/admin/hourly/orders/'.$order->id.'/operations',[
            'action'=>'assign_workers','visit_ids'=>[$visit->id],'worker_ids'=>[1,2],
        ])->assertUnprocessable();
        $visit->update(['scheduled_start_at'=>now()->subHour(),'scheduled_end_at'=>now()->addHour()]);
        $this->getJson($url)->assertUnprocessable();
    }

    public function test_latest_is_chosen_before_filtering_and_visit_pages_render():void
    {
        $order=$this->order();$order->update(['status'=>'active']);$first=$order->visits()->first();$last=$first->replicate();$last->visit_number=2;$last->scheduled_start_at=$first->scheduled_start_at->copy()->addDays(5);$last->scheduled_end_at=$first->scheduled_end_at->copy()->addDays(5);$last->save();
        $search=app(VisitSearch::class);$this->assertEquals([$last->id],$search->query([],'latest')->pluck('id')->all());
        $this->assertCount(0,$search->query(['from'=>$first->scheduled_start_at->toDateString(),'to'=>$first->scheduled_start_at->toDateString()],'latest')->get());
        $this->get('/admin/hourly/visits/latest')->assertOk()->assertSee('آخر الزيارات للطلب');
        foreach(['','/payments','/invoice'] as $suffix)$this->get('/admin/hourly/visits/'.$first->id.$suffix)->assertOk();
    }

    public function test_new_collection_is_allocated_to_selected_visit_not_first_visit():void
    {
        $order=$this->order();$first=$order->visits()->first();$last=$first->replicate();$last->visit_number=2;$last->scheduled_start_at=$first->scheduled_start_at->copy()->addDay();$last->scheduled_end_at=$first->scheduled_end_at->copy()->addDay();$last->save();$order->update(['total_amount'=>$order->total_amount*2]);
        app(VisitBatchService::class)->run($this->payload('collect',[$last->id],['method'=>'cash','amount'=>50,'paid_at'=>now()->format('Y-m-d\TH:i')]),7);
        $allocation=app(\App\Services\Hourly\VisitPaymentAllocation::class)->forOrder($order->fresh());
        $this->assertEquals(0,$allocation[$first->id]['paid']);$this->assertEquals(50,$allocation[$last->id]['paid']);
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>$last->id,'action'=>'bulk_collect']);
    }

    public function test_driver_conflict_is_rejected_and_only_selected_task_is_removed():void
    {
        $a=$this->order();$b=$this->order(4);$visit=$a->visits()->first();$other=$b->visits()->first();
        $driver=HourlyDriver::create(['name'=>'سائق الاختبار','is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>$visit->hourly_team_id,'member_type'=>'driver','member_id'=>$driver->id,'role'=>'primary','is_active'=>true]);
        $visit->update(['delivery_driver_id'=>$driver->id,'pickup_driver_id'=>$driver->id]);
        $this->postJson('/admin/hourly/visits/batch',$this->payload('unassign_driver',[$visit->id],['driver_role'=>'delivery']))->assertOk();
        $this->assertNull($visit->fresh()->delivery_driver_id);$this->assertEquals($driver->id,$visit->fresh()->pickup_driver_id);
        $other->update(['delivery_driver_id'=>$driver->id,'delivery_at'=>$visit->scheduled_start_at]);
        $this->postJson('/admin/hourly/visits/batch',$this->payload('assign_driver',[$visit->id],['driver_id'=>$driver->id,'driver_role'=>'delivery','driver_time'=>'08:00']))->assertUnprocessable()->assertSee('30');
        $this->assertNull($visit->fresh()->delivery_driver_id);
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>$visit->id,'action'=>'unassign_driver']);
    }

    public function test_assignment_endpoints_explain_why_suspended_workers_are_excluded():void
    {
        $order=$this->order();$visit=$order->visits()->first();
        DB::table('maids')->where('id',2)->update(['hourly_is_suspended'=>true]);
        foreach(['/admin/hourly/visits/'.$visit->id.'/workers',route('admin.hourly-orders.available-workers',[$order,$visit->id])] as $url){
            $data=$this->getJson($url)->assertOk()->json();
            $this->assertNotContains(2,array_column($data['workers'],'id'));
            $excluded=collect($data['excluded_workers'])->firstWhere('id',2);
            $this->assertNotNull($excluded);
            $this->assertContains('العاملة موقوفة عن العمل بنظام الساعات.',$excluded['reasons']);
        }
    }
    public function test_manual_assignment_accepts_multiple_workers_without_changing_price():void
    {
        $order=$this->order();$visit=$order->visits()->first();$visit->update(['workers_count'=>2]);
        $this->postJson('/admin/hourly/visits/batch',$this->payload('assign_workers',[$visit->id],['worker_ids'=>[1,2]]))->assertOk();
        $this->assertEquals([1,2],$visit->workers()->orderBy('maids.id')->pluck('maids.id')->all());
        $this->assertEquals($order->total_amount,$order->fresh()->total_amount);
        $this->get('/admin/hourly/visits?from=&to=')->assertViewHas('assigned',1);
        $this->postJson('/admin/hourly/visits/batch',$this->payload('unassign_workers',[$visit->id]))->assertOk();
        $this->get('/admin/hourly/visits?from=&to=')->assertViewHas('assigned',0);
    }

    public function test_read_only_user_cannot_assign_or_remove_workers_or_drivers():void
    {
        $order=$this->order();$visit=$order->visits()->first();
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturnUsing(fn($p)=>$p==='hourly_orders_view');auth('admin')->setUser($admin);
        $this->getJson('/admin/hourly/visits/'.$visit->id.'/workers')->assertForbidden();
        foreach(['assign_workers'=>['worker_ids'=>[2]],'unassign_workers'=>[],'unassign_driver'=>['driver_role'=>'pickup']] as $action=>$extra)$this->postJson('/admin/hourly/visits/batch',$this->payload($action,[$visit->id],$extra))->assertForbidden();
        $this->assertEquals([1],$visit->workers()->pluck('maids.id')->all());
        $this->assertSame(0,DB::table('hourly_visit_batches')->count());
    }

    public function test_free_visit_copies_latest_non_cancelled_assignments_and_preserves_totals():void
    {
        $order=$this->order();$first=$order->visits()->first();
        $last=$first->replicate();$last->visit_number=2;$last->scheduled_start_at=$first->scheduled_start_at->copy()->addDay();$last->scheduled_end_at=$last->scheduled_start_at->copy()->addHours(2);$last->planned_hours=2;$last->save();$last->workers()->sync([2]);
        $driver=HourlyDriver::create(['name'=>'سائق النسخ','is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);
        $last->update(['delivery_driver_id'=>$driver->id,'pickup_driver_id'=>$driver->id,'team_leader_id'=>$driver->id,'delivery_at'=>$last->scheduled_start_at->copy()->subMinutes(20),'pickup_at'=>$last->scheduled_end_at->copy()->addMinutes(10)]);
        $cancelled=$last->replicate();$cancelled->visit_number=3;$cancelled->scheduled_start_at=$last->scheduled_start_at->copy()->addDay();$cancelled->status='cancelled_by_client';$cancelled->save();$cancelled->workers()->sync([1]);
        $before=$order->fresh()->only(['total_amount','paid_amount','subtotal','tax_amount','payment_status']);
        $start=now()->addDays(7)->format('Y-m-d').'T08:00';
        $this->postJson('/admin/hourly/orders/'.$order->id.'/operations',['action'=>'free_visit','starts_at'=>$start,'reason'=>'تعويض العميل'])->assertOk()->assertJsonPath('warning',null);
        $free=$order->visits()->orderByDesc('id')->first();
        $this->assertTrue($free->is_free);$this->assertEquals(0,$free->visit_price);$this->assertEquals(4,$free->visit_number);
        $this->assertEquals([2],$free->workers()->pluck('maids.id')->all());$this->assertEquals($order->hours_per_visit,$free->planned_hours);$this->assertEquals($order->workers_per_visit,$free->workers_count);
        $this->assertEquals($driver->id,$free->delivery_driver_id);$this->assertEquals($driver->id,$free->pickup_driver_id);$this->assertEquals($driver->id,$free->team_leader_id);
        $this->assertEquals('07:40',\Carbon\Carbon::parse($free->delivery_at)->format('H:i'));$this->assertEquals('12:10',\Carbon\Carbon::parse($free->pickup_at)->format('H:i'));
        $this->assertEquals($before,$order->fresh()->only(array_keys($before)));
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>$free->id,'action'=>'free_visit','reason'=>'تعويض العميل']);
        $this->get('/admin/hourly/orders/'.$order->id)->assertOk()->assertSee('زيارة مجانية')->assertViewHas('averageVisitPrice',115);
        $this->get('/admin/hourly/visits?from=&to=')->assertOk()->assertSee('زيارة مجانية');
        $this->get('/admin/hourly/visits/'.$free->id.'/invoice')->assertOk()->assertSee('زيارة مجانية');
        $this->assertStringContainsString('زيارة مجانية',view('admin.hourly.invoice',['order'=>$order->fresh()->load(['visits','user','serviceCategory']),'invoiceLogo'=>null,'printSettings'=>null,'settings'=>null])->render());
    }

    public function test_free_visit_worker_conflict_is_named_and_fallback_is_explicit():void
    {
        $a=$this->order();$b=$this->order(6);$conflict=$b->visits()->first();$source=$a->visits()->first();
        $driver=HourlyDriver::create(['name'=>'سائق','is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);
        $source->update(['delivery_driver_id'=>$driver->id,'pickup_driver_id'=>$driver->id,'team_leader_id'=>$driver->id]);
        $url='/admin/hourly/orders/'.$a->id.'/operations';$data=['action'=>'free_visit','starts_at'=>$conflict->scheduled_start_at->format('Y-m-d\TH:i'),'reason'=>'تعويض'];
        $this->postJson($url,$data)->assertUnprocessable()->assertJsonFragment(['hourly'=>['العاملة عاملة 1 لديها زيارة #1 (مرجع #'.$conflict->id.') يوم '.$conflict->scheduled_start_at->format('d/m/Y').' من 08:00 إلى 12:00؛ يوجد تعارض مع الموعد أو مهلة التنقل.']]);
        $this->assertSame(1,$a->visits()->count());
        $response=$this->postJson($url,$data+['allow_unassigned'=>true])->assertOk();$this->assertStringContainsString('بدون تعيين',$response->json('warning'));
        $free=$a->visits()->orderByDesc('id')->first();$this->assertSame(0,$free->workers()->count());$this->assertNull($free->delivery_driver_id);$this->assertNull($free->pickup_driver_id);$this->assertNull($free->team_leader_id);
        $this->assertSame(0,DB::table('hourly_visit_reservations')->where('hourly_visit_id',$free->id)->count());
    }

    public function test_free_visit_driver_conflict_is_named_and_not_bypassed_by_worker_fallback():void
    {
        $a=$this->order();$b=$this->order(6);$source=$a->visits()->first();$conflict=$b->visits()->first();$conflict->workers()->sync([2]);
        DB::table('hourly_visit_reservations')->where('hourly_visit_id',$conflict->id)->delete();
        $driver=HourlyDriver::create(['name'=>'Mahmoud','is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);
        $source->update(['delivery_driver_id'=>$driver->id]);$conflict->update(['delivery_driver_id'=>$driver->id,'delivery_at'=>$conflict->scheduled_start_at->copy()->subMinutes(15)]);
        $response=$this->postJson('/admin/hourly/orders/'.$a->id.'/operations',['action'=>'free_visit','starts_at'=>$conflict->scheduled_start_at->format('Y-m-d\TH:i'),'allow_unassigned'=>true,'reason'=>'تعويض']);
        $response->assertUnprocessable();$this->assertStringContainsString('Mahmoud',$response->json('errors.hourly.0'));$this->assertStringContainsString('مرجع #'.$conflict->id,$response->json('errors.hourly.0'));
        $this->assertSame(1,$a->visits()->count());
    }

    public function test_free_visit_slot_errors_notice_override_and_quarter_hours():void
    {
        \Carbon\Carbon::setTestNow('2026-09-21 07:00:00');$order=$this->order();$url='/admin/hourly/orders/'.$order->id.'/operations';
        $snapshot=$order->package_snapshot;$snapshot['min_notice_hours']=24;$order->update(['package_snapshot'=>$snapshot]);
        $base=['action'=>'free_visit','reason'=>'اختبار المواعيد'];
        $response=$this->postJson($url,$base+['starts_at'=>'2026-09-21T08:00']);$response->assertUnprocessable();$this->assertStringContainsString('أقل مهلة حجز 24 ساعة',$response->json('errors.hourly.0'));
        $response=$this->postJson($url,$base+['starts_at'=>'2026-09-26T03:00']);$response->assertUnprocessable();$this->assertStringContainsString('فترات العمل',$response->json('errors.hourly.0'));$this->assertStringContainsString(HourlyRentalSettings::get('working_start'),$response->json('errors.hourly.0'));
        $response=$this->postJson($url,$base+['starts_at'=>$order->expires_at->copy()->addDay()->format('Y-m-d').'T08:00']);$response->assertUnprocessable();$this->assertStringContainsString('الباقة صالحة حتى',$response->json('errors.hourly.0'));$this->assertStringContainsString($order->expires_at->format('d/m/Y'),$response->json('errors.hourly.0'));
        $this->postJson($url,$base+['starts_at'=>'2026-09-26T08:10'])->assertUnprocessable();
        $this->postJson($url,$base+['starts_at'=>'2026-09-20T08:00','ignore_notice'=>true])->assertUnprocessable();
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturnUsing(fn($p)=>$p!=='hourly_orders_notice_override');auth('admin')->setUser($admin);
        $this->postJson($url,$base+['starts_at'=>'2026-09-21T08:00','ignore_notice'=>true])->assertForbidden();
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $this->postJson($url,$base+['starts_at'=>'2026-09-21T08:00','ignore_notice'=>true])->assertOk();
        $this->assertSame(2,$order->visits()->count());
    }

    public function test_free_visit_uses_current_item_start_times_instead_of_order_snapshot():void
    {
        $order=$this->order();$source=$order->visits()->first();$sourceStart=$source->scheduled_start_at->toDateTimeString();
        $snapshot=$order->package_snapshot;$snapshot['start_times']=['08:00'=>'all'];$order->update(['package_snapshot'=>$snapshot]);
        $item=\App\Models\Hourly\HourlyPackage::findOrFail($order->hourly_package_id);
        $item->update(['start_times'=>['11:00'=>'dispatch','12:00'=>'off'],'duration_minutes'=>60]);
        $url='/admin/hourly/orders/'.$order->id.'/operations';$date=now()->addDays(6)->format('Y-m-d');
        $data=['action'=>'free_visit','reason'=>'المواعيد الحالية'];
        foreach(['08:00','12:00'] as $time){
            $response=$this->postJson($url,$data+['starts_at'=>$date.'T'.$time]);$response->assertUnprocessable();
            $this->assertStringContainsString('11:00',$response->json('errors.hourly.0'));
        }
        $this->assertSame(1,$order->visits()->count());
        $this->postJson($url,$data+['starts_at'=>$date.'T11:00'])->assertOk();
        $free=$order->visits()->orderByDesc('id')->first();
        $this->assertEquals($order->hours_per_visit,$free->planned_hours);
        $this->assertEquals($order->workers_per_visit,$free->workers_count);
        $this->assertSame($snapshot,$order->fresh()->package_snapshot);
        $this->assertSame($sourceStart,$source->fresh()->scheduled_start_at->toDateTimeString());
    }

    public function test_free_visit_uses_general_schedule_when_current_item_start_times_are_null():void
    {
        $order=$this->order();$snapshot=$order->package_snapshot;$snapshot['start_times']=['03:00'=>'all'];$order->update(['package_snapshot'=>$snapshot]);
        \App\Models\Hourly\HourlyPackage::findOrFail($order->hourly_package_id)->update(['start_times'=>null]);
        $url='/admin/hourly/orders/'.$order->id.'/operations';$date=now()->addDays(6)->format('Y-m-d');
        $data=['action'=>'free_visit','reason'=>'مواعيد التشغيل العامة'];
        $this->postJson($url,$data+['starts_at'=>$date.'T03:00'])->assertUnprocessable();
        $this->postJson($url,$data+['starts_at'=>$date.'T08:00'])->assertOk();
        $this->assertSame(2,$order->visits()->count());
    }

    public function test_free_visit_paid_allocation_remains_zero_and_existing_allocation_is_preserved():void
    {
        $order=$this->order();$visit=$order->visits()->first();
        app(VisitBatchService::class)->run($this->payload('collect',[$visit->id],['method'=>'cash','amount'=>50,'paid_at'=>now()->format('Y-m-d\TH:i')]),7);
        $this->postJson('/admin/hourly/orders/'.$order->id.'/operations',['action'=>'free_visit','starts_at'=>now()->addDays(6)->format('Y-m-d').'T08:00','reason'=>'تعويض'])->assertOk();
        $free=$order->visits()->orderByDesc('id')->first();$allocations=app(\App\Services\Hourly\VisitPaymentAllocation::class)->forOrder($order->fresh());
        $this->assertEquals(50,$allocations[$visit->id]['paid']);$this->assertEquals(0,$allocations[$free->id]['paid']);$this->assertEquals(65,$order->fresh()->total_amount-$order->fresh()->paid_amount);
        $this->get('/admin/hourly/orders/'.$order->id)->assertOk()->assertViewHas('visitAllocations',fn($rows)=>$rows[$free->id]['paid']===0 && $rows[$visit->id]['paid']===50);
    }

    public function test_free_visit_same_order_conflict_can_be_saved_only_as_explicit_unassigned_placeholder():void
    {
        $order=$this->order();$source=$order->visits()->first();$url='/admin/hourly/orders/'.$order->id.'/operations';
        $data=['action'=>'free_visit','starts_at'=>$source->scheduled_start_at->format('Y-m-d\TH:i'),'reason'=>'تعيين لاحق'];
        $response=$this->postJson($url,$data)->assertUnprocessable();$this->assertStringContainsString('عاملة 1',$response->json('errors.hourly.0'));
        $this->postJson($url,$data+['allow_unassigned'=>true])->assertOk();
        $free=$order->visits()->orderByDesc('id')->first();$this->assertSame(0,$free->workers()->count());$this->assertEquals(0,$free->visit_price);
    }

    public function test_free_visit_honours_worker_travel_buffer_and_default_delivery_offset():void
    {
        $order=$this->order();$source=$order->visits()->first();$other=$this->order(6);$conflict=$other->visits()->first();
        $snapshot=$order->package_snapshot;$snapshot['break_minutes']=30;$order->update(['package_snapshot'=>$snapshot]);
        $data=['action'=>'free_visit','starts_at'=>$conflict->scheduled_end_at->format('Y-m-d').'T12:15','reason'=>'اختبار مهلة التنقل'];
        HourlyRentalSettings::persist(['slot_minutes'=>15]);
        $response=$this->postJson('/admin/hourly/orders/'.$order->id.'/operations',$data)->assertUnprocessable();$this->assertStringContainsString('مهلة التنقل',$response->json('errors.hourly.0'));
        $driver=HourlyDriver::create(['name'=>'سائق بلا وقت مسجل','is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);$source->update(['delivery_driver_id'=>$driver->id]);
        $data['starts_at']=now()->addDays(8)->format('Y-m-d').'T08:15';
        $this->postJson('/admin/hourly/orders/'.$order->id.'/operations',$data)->assertOk();
        $free=$order->visits()->orderByDesc('id')->first();$this->assertSame('08:00',\Carbon\Carbon::parse($free->delivery_at)->format('H:i'));
    }

    public function test_missed_visit_can_be_rebooked_by_admin_without_consuming_other_visits(): void
    {
        $order=$this->order();$order->update(['status'=>'active']);$visit=$order->visits()->first();
        $visit->update(['scheduled_date'=>today()->subDays(2),'scheduled_start_at'=>today()->subDays(2)->setTime(8,0),
            'scheduled_end_at'=>today()->subDays(2)->setTime(12,0),'delivery_driver_id'=>99,'pickup_driver_id'=>99]);
        $other=$visit->replicate();$other->visit_number=2;$other->scheduled_date=today()->addDays(6);
        $other->scheduled_start_at=today()->addDays(6)->setTime(8,0);$other->scheduled_end_at=today()->addDays(6)->setTime(12,0);
        $other->delivery_driver_id=null;$other->pickup_driver_id=null;$other->save();
        $beforeOther=$other->getAttributes();$price=$visit->visit_price;
        if(!Schema::hasTable('hourly_route_stops'))Schema::create('hourly_route_stops',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_visit_id');});
        DB::table('hourly_route_stops')->insert(['hourly_visit_id'=>$visit->id]);
        $this->assertTrue($visit->fresh()->is_missed);
        $this->assertSame('missed',$visit->fresh()->display_status);
        $customer = app(\App\Services\Hourly\CustomerHourlyData::class)->visit($visit->fresh(), $order->fresh());
        $this->assertTrue($customer['is_missed']);$this->assertSame('missed',$customer['display_status']);
        $this->assertFalse($customer['can_reschedule']);$this->assertFalse($customer['can_cancel']);
        $this->assertNull($customer['delivery_code']);
        $this->assertTrue(app(VisitSearch::class)->query(['status'=>['missed']])->whereKey($visit->id)->exists());
        $this->get('/admin/hourly/visits?from=&to=')->assertOk()->assertSee('فات موعدها')->assertSee('إعادة جدولة الزيارة الفائتة');
        $manager=app(\App\Services\Hourly\OrderManagementService::class);
        foreach([['action'=>'reschedule','new_date'=>today()->addDays(4)->toDateString(),'reason'=>''],
            ['action'=>'complete','reason'=>'اختبار'],
            ['action'=>'reschedule','new_date'=>today()->subDay()->toDateString(),'reason'=>'اختبار'],
            ['action'=>'reschedule','new_date'=>today()->addDays(6)->toDateString(),'reason'=>'اختبار']] as $invalid){
            try{$manager->apply($order,$invalid+['visit_ids'=>[$visit->id]],7);$this->fail('Missed visit must require an explicit valid resolution.');}
            catch(ValidationException $e){$this->assertTrue($visit->fresh()->is_missed);}
        }
        app(VisitBatchService::class)->run($this->payload('reschedule',[$visit->id],['new_date'=>today()->addDays(4)->toDateString()]),7);
        $after=$visit->fresh();
        $this->assertFalse($after->is_missed);$this->assertSame('scheduled',$after->display_status);
        $this->assertSame(1,(int)$after->reschedule_count);$this->assertEquals($price,$after->visit_price);
        $this->assertNull($after->delivery_driver_id);$this->assertNull($after->pickup_driver_id);
        $this->assertSame(0,DB::table('hourly_route_stops')->where('hourly_visit_id',$visit->id)->count());
        $this->assertEquals($beforeOther,$other->fresh()->getAttributes());
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>$visit->id,'action'=>'reschedule','admin_id'=>7,'reason'=>'اختبار التشغيل']);
    }

    public function test_missed_visit_cancellation_uses_existing_refund_and_audit_flow(): void
    {
        $order=$this->order();$order->update(['status'=>'active','paid_amount'=>100]);$visit=$order->visits()->first();
        $visit->update(['scheduled_date'=>today()->subDay(),'scheduled_start_at'=>today()->subDay()->setTime(8,0),'scheduled_end_at'=>today()->subDay()->setTime(12,0)]);
        app(VisitBatchService::class)->run($this->payload('cancel_visit',[$visit->id],['refund_method'=>'wallet']),7);
        $this->assertSame('cancelled',$visit->fresh()->status);
        $this->assertSame('cancelled',$order->fresh()->status);
        $this->assertEquals(1100,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $this->assertDatabaseHas('hourly_order_audits',['visit_id'=>$visit->id,'action'=>'cancel_visit','reason'=>'اختبار التشغيل']);
    }

    public function test_order_screen_cancellation_refunds_cash_payment_to_wallet_once(): void
    {
        $order=$this->order();$visit=$order->visits()->first();
        $visit->update(['visit_price'=>200]);$order->update(['status'=>'active','total_amount'=>200,'paid_amount'=>200]);
        $order->payments()->create(['user_id'=>1,'public_id'=>(string)Str::uuid(),'method'=>'cash','amount'=>200,'status'=>'paid','paid_at'=>now()]);
        app(\App\Services\Hourly\OrderManagementService::class)->apply($order,['action'=>'cancel_visit','visit_ids'=>[$visit->id],'reason'=>'إلغاء مجاني'],7);
        $this->assertEquals(1200,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $this->assertEquals(0,$order->fresh()->paid_amount);$this->assertEquals(0,$order->fresh()->total_amount);
        $this->assertSame('refunded',$order->fresh()->payment_status);
        $this->assertEquals(200,$order->payments()->where('amount',200)->value('amount'));
        $retry=app(\App\Services\Hourly\OrderManagementService::class)->apply($order,['action'=>'refund','refund_method'=>'wallet','reason'=>'إعادة المحاولة'],7);
        $this->assertEquals(0,$retry['refunded_amount']);
        $this->assertSame(1,$order->payments()->where('amount','<',0)->count());
        app(\App\Services\Hourly\HourlyPaymentService::class)->recalculate($order->fresh(),'admin',7);
        $this->assertSame('refunded',$order->fresh()->payment_status);
    }

    public function test_old_cancelled_order_has_refund_action_and_requires_financial_permission(): void
    {
        $order=$this->order();$order->update(['status'=>'cancelled','total_amount'=>0,'paid_amount'=>200]);
        $order->visits()->update(['status'=>'cancelled','cancellation_fee'=>0]);
        $order->payments()->create(['user_id'=>1,'public_id'=>(string)Str::uuid(),'method'=>'cash','amount'=>200,'status'=>'paid','paid_at'=>now()]);
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;
        $admin->shouldReceive('can')->andReturnUsing(fn($p)=>in_array($p,['hourly_orders_view','hourly_orders_create']));auth('admin')->setUser($admin);
        $payload=['action'=>'refund','refund_method'=>'wallet','reason'=>'تسوية إلغاء سابق'];
        $url=route('admin.hourly-orders.operations',$order);
        $this->postJson($url,$payload)->assertForbidden();
        $this->assertEquals(1000,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;$admin->shouldReceive('can')->andReturn(true);auth('admin')->setUser($admin);
        $this->get(route('admin.hourly-orders.show',$order))->assertOk()->assertSee('استرداد المبلغ المستحق');
        $this->postJson($url,$payload)->assertOk()->assertJsonPath('refunded_amount',200);
        $this->postJson($url,$payload)->assertOk()->assertJsonPath('refunded_amount',0);
        $this->assertEquals(1200,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $this->assertDatabaseHas('hourly_order_audits',['hourly_rental_order_id'=>$order->id,'action'=>'refund','reason'=>'تسوية إلغاء سابق']);
    }

    public function test_external_refund_requires_confirmation_and_does_not_credit_wallet(): void
    {
        $order=$this->order();$order->update(['status'=>'cancelled','total_amount'=>0,'paid_amount'=>200]);
        $service=app(\App\Services\Hourly\OrderRefundService::class);
        try{$service->refund($order,'bank_transfer','حوالة',7,'admin',7);$this->fail('External payout must be confirmed.');}
        catch(ValidationException $e){$this->assertEquals(200,$order->fresh()->paid_amount);}
        $this->assertEquals(200,$service->refund($order,'bank_transfer','حوالة رقم 123',7,'admin',7,true));
        $this->assertEquals(1000,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $this->assertEquals(0,$order->fresh()->paid_amount);
        $this->assertEquals(0,$service->refund($order,'wallet','طلب مكرر',7));
    }

    public function test_pending_payment_blocks_refund_and_rolls_back_cancellation(): void
    {
        $order=$this->order();$visit=$order->visits()->first();$visit->update(['visit_price'=>200]);
        $order->update(['status'=>'active','total_amount'=>200,'paid_amount'=>200]);
        $order->payments()->create(['user_id'=>1,'public_id'=>(string)Str::uuid(),'method'=>'cash','amount'=>200,'status'=>'paid']);
        $order->payments()->create(['user_id'=>1,'public_id'=>(string)Str::uuid(),'method'=>'bank_transfer','amount'=>50,'status'=>'pending_review']);
        try{app(\App\Services\Hourly\OrderManagementService::class)->apply($order,
            ['action'=>'cancel_visit','visit_ids'=>[$visit->id],'reason'=>'إلغاء مجاني'],7);$this->fail('Pending money must be settled before refund.');}
        catch(ValidationException $e){$this->assertSame('scheduled',$visit->fresh()->status);}
        $this->assertEquals(200,$order->fresh()->total_amount);$this->assertEquals(200,$order->fresh()->paid_amount);
        $this->assertEquals(1000,DB::table('users')->where('id',1)->value('hourly_wallet_balance'));
        $this->assertSame(0,$order->payments()->where('amount','<',0)->count());
    }

    public function test_paid_visit_hides_collection_and_reports_actual_remaining(): void
    {
        if(!Schema::hasColumn('hourly_visit_workers','status'))Schema::table('hourly_visit_workers',fn(Blueprint $t)=>$t->string('status')->nullable());
        $order=$this->order();$visit=$order->visits()->first();$service=app(VisitBatchService::class);
        $this->get('/admin/hourly/visits')->assertOk()->assertSee('data-single="collect"',false);
        $data=['method'=>'cash','paid_at'=>now()->format('Y-m-d\TH:i')];
        $service->run($this->payload('collect',[$visit->id],$data+['amount'=>50]),7);
        $left=(float)$order->total_amount-50;
        try{
            $service->run($this->payload('collect',[$visit->id],$data+['amount'=>$left+1]),7);
            $this->fail('Overpayment accepted');
        }catch(ValidationException $e){$this->assertStringContainsString(number_format($left,2).' ر.س',json_encode($e->errors(),JSON_UNESCAPED_UNICODE));}
        $service->run($this->payload('collect',[$visit->id],$data+['amount'=>$left]),7);
        $this->get('/admin/hourly/visits')->assertOk()->assertDontSee('data-single="collect"',false)->assertSee('سجل الدفعات');
        try{
            $service->run($this->payload('collect',[$visit->id],$data+['amount'=>1]),7);
            $this->fail('Duplicate payment accepted');
        }catch(ValidationException $e){$this->assertStringContainsString('مسددة بالكامل',json_encode($e->errors(),JSON_UNESCAPED_UNICODE));}
        $this->assertEquals($order->total_amount,$order->fresh()->paid_amount);
        $this->assertEquals($order->total_amount,DB::table('hourly_order_payments')->sum('amount'));
    }
}
