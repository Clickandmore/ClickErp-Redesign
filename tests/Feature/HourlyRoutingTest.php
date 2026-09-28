<?php
namespace Tests\Feature;

use Tests\HourlyTestCase as TestCase;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema};
use App\Models\Hourly\{HourlyDriver,HourlyRentalOrder,HourlyVisit};
use App\Services\Hourly\{LocationPings,PanelBookingService,RouteBoard};
use App\Support\{HourlyRentalSettings,HourlyRouteWindows};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** إدارة المسار (مهام التوصيل والاستلام لكل سائق) وتتبع GPS. */
class HourlyRoutingTest extends TestCase
{
    use BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema;

    private $team;

    protected function setUp():void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-22 07:00'));
        $this->setUpHourlyCategorySchema();$this->buildWorkers();
        Schema::create('admins',function(Blueprint $t){ $t->softDeletes();$t->id();$t->string('name');});DB::table('admins')->insert(['id'=>7,'name'=>'مشرف']);
        Schema::create('users',function(Blueprint $t){ $t->softDeletes();$t->id();$t->string('name');$t->string('email')->nullable();$t->string('phone');$t->string('phone_code')->default('966');$t->string('password')->nullable();$t->string('register_from')->nullable();$t->boolean('is_blocked')->default(false);$t->timestamps();});
        Schema::table('user_addresses',function(Blueprint $t){foreach(['name','phone','title'] as $c)$t->string($c)->nullable();});
        foreach(['2026_09_20_000001_add_hourly_booking_wizard','2026_09_21_000001_extend_hourly_order_management','2026_09_21_000002_add_hourly_visit_operations','2026_09_22_000001_create_hourly_customer_sources'] as $migration)(require base_path('database/migrations/'.$migration.'.php'))->up();
        Schema::create('hourly_drivers',function(Blueprint $t){$t->id();$t->string('name');$t->string('phone')->nullable();$t->unsignedSmallInteger('vehicle_capacity')->nullable();$t->boolean('is_active')->default(true);$t->json('work_schedule')->nullable();$t->timestamps();$t->softDeletes();});
        Schema::create('location_pings',function(Blueprint $t){$t->id();$t->string('user_type',16);$t->unsignedBigInteger('user_id');$t->decimal('lat',10,7);$t->decimal('lng',10,7);$t->decimal('accuracy',8,2)->nullable();$t->decimal('speed',6,2)->nullable();$t->unsignedSmallInteger('heading')->nullable();$t->string('app_version',30)->nullable();$t->string('device_type',40)->nullable();$t->unsignedBigInteger('city_id')->nullable();$t->dateTime('recorded_at');$t->timestamp('created_at')->nullable();});
        Schema::create('hourly_route_stops',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_visit_id');$t->string('role',10);$t->unsignedBigInteger('hourly_driver_id');$t->date('route_date');$t->unsignedInteger('display_order');$t->timestamps();$t->unique(['hourly_visit_id','role']);});
        DB::table('users')->insert(['id'=>1,'name'=>'عميل المسار','phone'=>'0500000000','email'=>'client@example.test','hourly_wallet_balance'=>1000]);
        DB::table('cities')->where('id',1)->update(['latitude'=>24.7136,'longitude'=>46.6753]);
        // مقر الفريق على بعد ~1.1 كم شمال العميل الأول.
        $this->team=$this->team('فريق الرياض');$this->team->update(['base_lat'=>24.71,'base_lng'=>46.70]);
    }
    protected function tearDown():void{Carbon::setTestNow();HourlyRentalSettings::flush();parent::tearDown();}

    private function driver(string $name,string $role='primary'):HourlyDriver
    {
        $driver=HourlyDriver::create(['name'=>$name,'is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);
        DB::table('hourly_team_members')->insert(['hourly_team_id'=>$this->team->id,'member_type'=>'driver','member_id'=>$driver->id,'role'=>$role,'is_active'=>true]);
        return $driver;
    }
    private function order(string $at,float $lat,int $worker):HourlyRentalOrder
    {
        $category=$this->category();$district=\App\Models\Hourly\ServiceDistrict::first()??$this->district('الملز',24.7,46.7);
        $category->districts()->attach($district->id,['is_active'=>true,'team_id'=>$this->team->id]);$item=$this->item($category);
        DB::table('maids')->insertOrIgnore(['id'=>$worker,'name'=>'عاملة '.$worker,'rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>today()->toDateString(),'hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])])]);
        if(!DB::table('maid_jobs')->where('maid_id',$worker)->exists())DB::table('maid_jobs')->insert(['maid_id'=>$worker,'job_type_id'=>2]);
        DB::table('contracts')->insertOrIgnore(['id'=>$worker,'maid_id'=>$worker,'status'=>'shelter']);
        if(!DB::table('contract_infos')->where('contract_id',$worker)->exists())DB::table('contract_infos')->insert(['contract_id'=>$worker,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
        if(!DB::table('hourly_team_members')->where('member_type','maid')->where('member_id',$worker)->exists())DB::table('hourly_team_members')->insert(['hourly_team_id'=>$this->team->id,'member_type'=>'maid','member_id'=>$worker,'role'=>'primary','is_active'=>true]);
        return app(PanelBookingService::class)->create(['booking_token'=>(string)Str::uuid(),'customer_mode'=>'existing','user_id'=>1,'address_mode'=>'existing','address_id'=>$this->address($lat,46.7,'الملز'),'hourly_package_id'=>$item->id,'workers_per_visit'=>1,'payment_method'=>'cash','collect_now'=>false,'all_workers'=>false,'notify_email'=>false,'notify_app'=>false,'visits'=>[['starts_at'=>$at,'worker_ids'=>[$worker]]]],7);
    }
    private function assign(HourlyVisit $visit,HourlyDriver $driver):void
    {
        $visit->update(['delivery_driver_id'=>$driver->id,'pickup_driver_id'=>$driver->id]);
    }

    public function test_each_visit_yields_delivery_and_pickup_tasks_grouped_by_driver_with_distances(): void
    {
        $ali=$this->driver('علي');
        $morning=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();
        $evening=$this->order('2026-09-22 13:00',24.72,2)->visits()->first();
        $this->assign($morning,$ali);
        $evening->update(['delivery_driver_id'=>$ali->id,'delivery_at'=>'2026-09-22 12:45:00']);
        $cancelled=$this->order('2026-09-22 16:00',24.70,3)->visits()->first();$cancelled->update(['status'=>'cancelled']);

        $board=app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[]);
        $this->assertCount(1,$board['drivers']);
        $card=$board['drivers'][0];
        // التوصيل عند البداية والاستلام عند النهاية، ووقت delivery_at المضبوط يتقدم على بداية الزيارة.
        $this->assertSame(['08:00 delivery','12:00 pickup','12:45 delivery'],array_map(fn($t)=>$t['clock'].' '.$t['role'],$card['tasks']));
        $this->assertSame('فريق الرياض',$card['team']);
        $this->assertSame('طوال اليوم',$card['hours']);
        $this->assertSame(['pending','قيد الانتظار'],$card['state']);
        // من المقر لكل مهمة: العميل الأول (24.70,46.70) على بعد ~1.11 كم من المقر (24.71,46.70).
        $this->assertEqualsWithDelta(1.11,$card['tasks'][0]['km'],0.01);
        $this->assertEqualsWithDelta(1.11,$card['tasks'][1]['km'],0.01);
        $this->assertEqualsWithDelta(1.11,$card['tasks'][2]['km'],0.01);
        $this->assertSame(['routes'=>1,'unassigned'=>0,'cancelled'=>1,'without_driver'=>1],$board['counters']);
        $this->assertSame($evening->id.'-pickup',$board['unassigned'][0]['key']);

        // من عميل إلى عميل: الاستلام من نفس العميل = 0، ثم 24.70 → 24.72 ≈ 2.22 كم.
        HourlyRentalSettings::persist(['route_client_to_client'=>true]);
        $tasks=app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[])['drivers'][0]['tasks'];
        $this->assertSame([1.11,0.0,2.22],array_map(fn($t)=>round($t['km'],2),$tasks));

        // نوافذ اليوم وفلتر السائق.
        $am=app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),['period'=>'am']);
        $this->assertSame(['08:00'],array_column($am['drivers'][0]['tasks'],'clock'));
        $custom=app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),['period'=>'custom','from'=>'12:00','to'=>'12:45']);
        $this->assertSame(['12:00'],array_column($custom['drivers'][0]['tasks'],'clock'));
        $this->assertSame([],app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),['driver_id'=>999])['drivers']);
    }

    public function test_task_states_follow_the_visit_status(): void
    {
        $ali=$this->driver('علي');
        $visit=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();$this->assign($visit,$ali);
        $states=function()use($visit){$visit->refresh();return array_map(fn($t)=>$t['state'],app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[])['drivers'][0]['tasks']);};
        $visit->update(['status'=>'en_route']);$this->assertSame(['moving','pending'],$states());
        $visit->update(['status'=>'worker_finished']);$this->assertSame(['done','ready'],$states());
        $visit->update(['status'=>'completed']);$this->assertSame(['done','done'],$states());
        $this->assertSame(['done','مكتمل'],app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[])['drivers'][0]['state']);
    }


    /** نفس الحمولة التي ترسلها الصفحة: المهام والسائق الحالي لكل منها كما رأته. */
    private function transferPayload(array $keys,int $to,array $more=[]):array
    {
        $expected=[];
        foreach($keys as $key){[$id,$role]=explode('-',$key);$driver=HourlyVisit::find($id)->{$role.'_driver_id'};$expected[$key]=$driver?(int)$driver:null;}
        return $more+['to_driver_id'=>$to,'tasks'=>$keys,'expected'=>$expected,'reason'=>'إعادة توزيع'];
    }

    public function test_transfer_moves_tasks_through_assign_driver_atomically(): void
    {
        $ali=$this->driver('علي');$omar=$this->driver('عمر','secondary');$outsider=HourlyDriver::create(['name'=>'خارج الفريق','is_active'=>true,'work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);
        $first=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();$second=$this->order('2026-09-22 13:00',24.72,2)->visits()->first();
        $this->assign($first,$ali);$this->assign($second,$ali);
        $first->update(['status'=>'in_progress']);
        $keys=[$first->id.'-pickup',$second->id.'-delivery',$second->id.'-pickup'];
        $board=app(RouteBoard::class);

        // سائق خارج فريق الزيارة: يُرفض كل النقل ولا تتحرك أي مهمة.
        try{$board->transfer($keys,$outsider->id,'نقل',7,$this->transferPayload($keys,0)['expected']);$this->fail('Driver outside the visit team must be rejected');}catch(ValidationException $e){$this->assertStringContainsString('ليس عضوًا',collect($e->errors())->flatten()->first());}
        $this->assertSame($ali->id,(int)$second->fresh()->delivery_driver_id);

        // مهمة التوصيل المنفذة لا تُنقل.
        try{$board->transfer([$first->id.'-delivery'],$omar->id,'نقل',7,[$first->id.'-delivery'=>$ali->id]);$this->fail('Done task must not move');}catch(ValidationException $e){$this->assertStringContainsString('منتهية',collect($e->errors())->flatten()->first());}

        $this->bypass();
        $payload=$this->transferPayload($keys,$omar->id,['reason'=>'إجازة مرضية']);
        $this->postJson(route('admin.hourly-routing.transfer'),$payload)->assertOk()->assertJson(['message'=>'تم نقل 3 مهمة إلى السائق الجديد.']);
        $this->assertSame([$ali->id,$omar->id],[(int)$first->fresh()->delivery_driver_id,(int)$first->fresh()->pickup_driver_id]);
        $this->assertSame([$omar->id,$omar->id],[(int)$second->fresh()->delivery_driver_id,(int)$second->fresh()->pickup_driver_id]);
        // الوقت يبقى كما كان (بداية ونهاية الزيارة) والسبب في سجل الطلب.
        $this->assertSame('2026-09-22 13:00',Carbon::parse($second->fresh()->delivery_at)->format('Y-m-d H:i'));
        $this->assertSame(3,DB::table('hourly_order_audits')->where('action','assign_driver')->whereNotNull('visit_id')->where('reason','إجازة مرضية')->count());

        // إعادة الإرسال بعد تغير التوزيع تُرفض بدل النقل مرتين.
        $this->postJson(route('admin.hourly-routing.transfer'),$payload)->assertStatus(422);
    }

    public function test_bulk_transfer_of_selected_tasks_from_several_drivers_and_unassigned(): void
    {
        $ali=$this->driver('علي');$omar=$this->driver('عمر');$sami=$this->driver('سامي','secondary');
        $first=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();$second=$this->order('2026-09-22 13:00',24.72,2)->visits()->first();
        $first->update(['delivery_driver_id'=>$ali->id]);$second->update(['delivery_driver_id'=>$omar->id]);
        // مهمتان من سائقين مختلفين ومهمة بلا سائق، دفعة واحدة إلى سامي.
        $keys=[$first->id.'-delivery',$second->id.'-delivery',$first->id.'-pickup'];
        $this->bypass();

        // الصفحة رأت سائقًا غير الموجود الآن لمهمة واحدة: لا يتحرك شيء.
        $stale=$this->transferPayload($keys,$sami->id);$stale['expected'][$second->id.'-delivery']=$ali->id;
        $this->postJson(route('admin.hourly-routing.transfer'),$stale)->assertStatus(422)->assertJsonPath('errors.hourly.0','تغيّر توزيع المهام منذ فتح الصفحة. حدّث الصفحة وأعد المحاولة.');
        $this->assertSame([$ali->id,$omar->id,0],[(int)$first->fresh()->delivery_driver_id,(int)$second->fresh()->delivery_driver_id,(int)$first->fresh()->pickup_driver_id]);

        $this->postJson(route('admin.hourly-routing.transfer'),$this->transferPayload($keys,$sami->id))->assertOk()->assertJson(['message'=>'تم نقل 3 مهمة إلى السائق الجديد.']);
        $this->assertSame([$sami->id,$sami->id,$sami->id],[(int)$first->fresh()->delivery_driver_id,(int)$first->fresh()->pickup_driver_id,(int)$second->fresh()->delivery_driver_id]);
        $board=app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[]);
        $this->assertSame(['سامي'],array_column($board['drivers'],'name'));
        $this->assertSame([$second->id.'-pickup'],array_column($board['unassigned'],'key'));
    }

    public function test_capacity_overflow_needs_confirmation_and_shows_red_on_the_card(): void
    {
        $ali=$this->driver('علي');$omar=$this->driver('عمر');
        $first=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();$second=$this->order('2026-09-22 13:00',24.72,2)->visits()->first();
        $this->assign($first,$omar);$second->update(['delivery_driver_id'=>$ali->id]);
        $omar->update(['vehicle_capacity'=>1]);
        $this->bypass();
        $payload=$this->transferPayload([$second->id.'-delivery'],$omar->id);

        // عمر لديه زيارة ومقعد واحد: التحويل يتوقف ويطلب التأكيد دون تنفيذ أي شيء.
        $this->postJson(route('admin.hourly-routing.transfer'),$payload)->assertStatus(409)
            ->assertJson(['needs_confirmation'=>true,'capacity'=>['driver'=>'عمر','date'=>'2026-09-22','assigned'=>2,'seats'=>1]]);
        $this->assertSame($ali->id,(int)$second->fresh()->delivery_driver_id);

        $this->postJson(route('admin.hourly-routing.transfer'),$payload+['confirm_capacity'=>true])->assertOk();
        $this->assertSame($omar->id,(int)$second->fresh()->delivery_driver_id);
        $card=collect(app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[])['drivers'])->firstWhere('name','عمر');
        $this->assertSame([2,1,true],[$card['assigned'],$card['seats'],$card['over']]);
        // «منفذ / الكل» عدّاد منفصل عن المقاعد.
        $this->assertSame([0,3],[$card['done'],count($card['tasks'])]);

        // تعديل المقاعد من الكرت بنفس حدود نموذج السائق.
        $this->putJson(route('admin.hourly-routing.capacity'),['driver_id'=>$omar->id,'seats'=>2])->assertStatus(422);
        $this->putJson(route('admin.hourly-routing.capacity'),['driver_id'=>$omar->id,'seats'=>4])->assertOk();
        $this->assertSame(4,(int)$omar->fresh()->vehicle_capacity);
    }

    public function test_same_driver_for_both_sides_moves_and_validates_the_other_task(): void
    {
        $ali=$this->driver('علي');$omar=$this->driver('عمر');
        $visit=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();$this->assign($visit,$ali);
        // جدول عمر ينتهي 10:00 فالاستلام 12:00 خارج جدوله.
        $omar->update(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'hours','start'=>'07:00','end'=>'10:00'])]);
        $this->bypass();
        $key=$visit->id.'-delivery';

        $this->postJson(route('admin.hourly-routing.transfer'),$this->transferPayload([$key],$omar->id,['same_driver'=>true]))->assertStatus(422);
        $this->assertSame([$ali->id,$ali->id],[(int)$visit->fresh()->delivery_driver_id,(int)$visit->fresh()->pickup_driver_id]);

        // بلا «نفس السائق» ينتقل التوصيل وحده.
        $this->postJson(route('admin.hourly-routing.transfer'),$this->transferPayload([$key],$omar->id))->assertOk();
        $this->assertSame([$omar->id,$ali->id],[(int)$visit->fresh()->delivery_driver_id,(int)$visit->fresh()->pickup_driver_id]);

        // مع جدول يغطي الطرفين: الاستلام ينتقل مع التوصيل، ولكل منهما سطر في سجل الطلب.
        $omar->update(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])]);
        $sami=$this->driver('سامي');
        $this->postJson(route('admin.hourly-routing.transfer'),$this->transferPayload([$key],$sami->id,['same_driver'=>true,'reason'=>'نفس السائق']))->assertOk()->assertJson(['message'=>'تم نقل 2 مهمة إلى السائق الجديد.']);
        $this->assertSame([$sami->id,$sami->id],[(int)$visit->fresh()->delivery_driver_id,(int)$visit->fresh()->pickup_driver_id]);
        $this->assertSame(2,DB::table('hourly_order_audits')->where('reason','نفس السائق')->where('action','assign_driver')->whereNotNull('visit_id')->count());
    }

    public function test_reorder_is_saved_and_drives_display_map_and_export(): void
    {
        $ali=$this->driver('علي');$omar=$this->driver('عمر');
        $first=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();$second=$this->order('2026-09-22 13:00',24.72,2)->visits()->first();
        $this->assign($first,$ali);$this->assign($second,$ali);
        [$d1,$p1,$d2,$p2]=[$first->id.'-delivery',$first->id.'-pickup',$second->id.'-delivery',$second->id.'-pickup'];
        $keys=fn()=>array_column(collect(app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[])['drivers'])->firstWhere('name','علي')['tasks'],'key');
        $this->assertSame([$d1,$p1,$d2,$p2],$keys());
        $this->bypass();

        $this->postJson(route('admin.hourly-routing.reorder'),['driver_id'=>$ali->id,'date'=>'2026-09-22','tasks'=>[$d2,$d1,$p1,$p2]])->assertOk()->assertJson(['order'=>[$d2,$d1,$p1,$p2]]);
        $this->assertSame([$d2,$d1,$p1,$p2],$keys());
        $this->assertSame([1,2,3,4],DB::table('hourly_route_stops')->where('hourly_driver_id',$ali->id)->orderBy('display_order')->pluck('display_order')->all());

        // ترتيب جزء ظاهر فقط (مثل نافذة بعد الظهر) يبقى داخل مواقعه في المسار الكامل.
        $this->postJson(route('admin.hourly-routing.reorder'),['driver_id'=>$ali->id,'date'=>'2026-09-22','tasks'=>[$p2,$p1]])->assertOk();
        $this->assertSame([$d2,$d1,$p2,$p1],$keys());

        // المسافة من عميل إلى عميل تتبع الترتيب: أول مهمة (العميل الثاني 24.72) من المقر 24.71.
        HourlyRentalSettings::persist(['route_client_to_client'=>true]);
        $tasks=collect(app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[])['drivers'])->firstWhere('name','علي')['tasks'];
        $this->assertEqualsWithDelta(1.11,$tasks[0]['km'],0.01);$this->assertEqualsWithDelta(2.22,$tasks[1]['km'],0.01);

        // التصدير بنفس الترتيب ومعه رقم التسلسل.
        $response=$this->get('/admin/hourly/routing/export?date=2026-09-22');
        ob_start();$response->baseResponse->sendContent();$content=ob_get_clean();
        $file=tempnam(sys_get_temp_dir(),'rt');file_put_contents($file,$content);
        $sheet=\PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet();@unlink($file);
        $this->assertSame([['1','13:00'],['2','08:00'],['3','17:00'],['4','12:00']],array_map(fn($r)=>[(string)$sheet->getCell('C'.$r)->getValue(),(string)$sheet->getCell('D'.$r)->getValue()],[2,3,4,5]));

        // مهمة تنتقل لسائق آخر يسقط ترتيبها القديم.
        $this->postJson(route('admin.hourly-routing.transfer'),$this->transferPayload([$d2],$omar->id))->assertOk();
        $this->assertFalse(DB::table('hourly_route_stops')->where('hourly_visit_id',$second->id)->where('role','delivery')->exists());
        // مهمة ليست للسائق تُرفض.
        $this->postJson(route('admin.hourly-routing.reorder'),['driver_id'=>$ali->id,'date'=>'2026-09-22','tasks'=>[$d2,$d1]])->assertStatus(422);
    }

    public function test_page_export_and_windows(): void
    {
        $ali=$this->driver('علي');$visit=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();$this->assign($visit,$ali);
        $cancelled=$this->order('2026-09-22 16:00',24.70,3)->visits()->first();
        app(\App\Services\Hourly\OrderManagementService::class)->apply($cancelled->order,['action'=>'cancel_visit','visit_ids'=>[$cancelled->id],'refund_method'=>'none','reason'=>'العميل مسافر'],7);
        $this->bypass();
        // النصوص تتبع لغة الواجهة بالكامل: عربية بلا تسميات إنجليزية، والعكس.
        $html=$this->get('/admin/hourly/routing?date=2026-09-22')->assertOk()->assertSee('عرض المسار')->assertSee('علي')->assertSee('نقل كل المهام')->assertSee('dir="rtl"',false)
            ->assertSee('زيارات مخصصة / المقاعد')->assertSee('منفذ / الكل')->assertSee('data-select-all',false)->assertSee('class="rt-handle"',false)
            // الملغاة مخفية افتراضيًا ومعها سبب الإلغاء، والروابط لملف العاملة وتفاصيل الطلب.
            ->assertSee('id="rt-cancelled" hidden',false)->assertSee('العميل مسافر')->assertSee(route('admin.hourly-maids.show',1),false)->assertSee(route('admin.hourly-orders.show',$visit->hourly_rental_order_id),false)
            ->assertDontSee('Show route')->assertDontSee('Time bands')->assertDontSee('@disabled')->getContent();
        $this->assertStringNotContainsString('class="rt-handle"',$this->get('/admin/hourly/routing?date=2026-09-22&view=bands')->getContent());
        app()->setLocale('en');
        $this->get('/admin/hourly/routing?date=2026-09-22')->assertOk()->assertSee('Show route')->assertSee('Transfer all tasks')->assertSee('Drop-off')->assertSee('All day')->assertSee('dir="ltr"',false)
            ->assertDontSee('عرض المسار')->assertDontSee('التسليم');
        app()->setLocale('ar');
        $this->get('/admin/hourly/routing?date=2026-09-22&period=custom')->assertSessionHasErrors(['from','to']);
        $this->get('/panel/orders/routing?date=2026-09-22')->assertRedirect(route('admin.hourly-routing.index',['date'=>'2026-09-22']));
        $response=$this->get('/admin/hourly/routing/export?date=2026-09-22');
        $response->assertOk();$this->assertStringContainsString('hourly-routes-2026-09-22.xlsx',$response->headers->get('content-disposition'));

        // تحذير المسافة من الإعدادات.
        HourlyRentalSettings::persist(['route_distance_warning_km'=>1]);
        $this->assertTrue(app(RouteBoard::class)->build(Carbon::parse('2026-09-22'),[])['drivers'][0]['tasks'][0]['far']);

        $this->putJson(route('admin.hourly-routing.windows'),['windows'=>[['name'=>'الصباح الباكر','from'=>'06:00','to'=>'09:00']]])->assertOk();
        $this->assertSame([['name'=>'الصباح الباكر','from'=>'06:00','to'=>'09:00']],HourlyRouteWindows::all());
        $this->putJson(route('admin.hourly-routing.windows'),['windows'=>[['name'=>'خطأ','from'=>'10:00','to'=>'09:00']]])->assertStatus(422);
        $this->assertSame([],HourlyRouteWindows::normalize([['name'=>'','from'=>'06:00','to'=>'09:00'],['name'=>'x','from'=>'6','to'=>'09:00']]));
    }

    public function test_activity_status_device_details_customers_status_now_and_trail(): void
    {
        $ali=$this->driver('علي');
        $visit=$this->order('2026-09-22 08:00',24.70,1)->visits()->first();$this->assign($visit,$ali);
        $this->withoutMiddleware(\App\Http\Middleware\EnsureHourlyDriverAccess::class);
        $this->actingAs($ali,'hourly_driver_api');
        $this->postJson('/api/app/hourly/driver/location',['app_version'=>'2.4.1','device_type'=>'android','points'=>[
            ['lat'=>24.70,'lng'=>46.70,'recorded_at'=>'2026-09-22 06:20:00'],
            ['lat'=>24.71,'lng'=>46.68,'accuracy'=>12,'speed'=>42.5,'heading'=>90,'recorded_at'=>'2026-09-22 06:30:00'],
            ['lat'=>24.72,'lng'=>46.68,'recorded_at'=>'2026-09-20 06:58:00'],
        ]])->assertOk()->assertJson(['data'=>['saved'=>2,'tracking'=>['enabled'=>true,'interval_seconds'=>10]]]);
        $this->assertSame(1,(int)DB::table('location_pings')->value('city_id'));
        // العاملة أرسلت قبل 90 دقيقة، والسائق قبل 30.
        app(LocationPings::class)->record('maid',1,[['lat'=>21.5,'lng'=>39.2,'recorded_at'=>'2026-09-22 05:30:00']]);

        $points=collect(app(LocationPings::class)->latest(Carbon::parse('2026-09-22'),['types'=>['maid','driver']]));
        $driver=$points->firstWhere('type','driver');
        $this->assertSame(['علي',24.71,true,42.5,90,'2.4.1','android'],[$driver['name'],$driver['lat'],$driver['active'],$driver['speed'],$driver['heading'],$driver['app_version'],$driver['device_type']]);
        // «نشط» = آخر تحديث خلال 60 دقيقة افتراضيًا.
        $this->assertSame([false,null],[$points->firstWhere('type','maid')['active'],$points->firstWhere('type','maid')['city']]);
        $this->assertSame(['driver'],array_column(app(LocationPings::class)->latest(Carbon::parse('2026-09-22'),['types'=>['maid','driver'],'status'=>'active']),'type'));
        HourlyRentalSettings::persist(['tracking_active_minutes'=>20]);
        $this->assertFalse(collect(app(LocationPings::class)->latest(Carbon::parse('2026-09-22'),['types'=>['driver']]))->first()['active']);
        HourlyRentalSettings::persist(['tracking_active_minutes'=>60]);

        // العملاء من عنوان الطلب: من له زيارة اليوم مفعّل، ومن ليس له زيارة اليوم رمادي.
        DB::table('users')->insert(['id'=>2,'name'=>'عميل بلا زيارة','phone'=>'0500000002']);
        $later=$this->order('2026-09-25 08:00',24.75,2);$later->update(['user_id'=>2,'status'=>'active']);
        $customers=collect(app(LocationPings::class)->latest(Carbon::parse('2026-09-22'),['types'=>['customer']]))->keyBy('id');
        $this->assertSame([true,'address',24.70],[$customers[1]['active'],$customers[1]['source'],$customers[1]['lat']]);
        $this->assertSame([false,24.75],[$customers[2]['active'],$customers[2]['lat']]);

        $this->bypass();
        // الحالة الآن للسائق: قبل الموعد لا حالية والتالية التوصيل؛ بعد 08:00 التوصيل حالية والاستلام تالٍ.
        $this->getJson(route('admin.hourly-gps.status',['type'=>'driver','id'=>$ali->id]))->assertOk()->assertJsonPath('current',null)->assertJsonPath('next.task','التسليم')->assertJsonPath('next.time','08:00');
        Carbon::setTestNow(Carbon::parse('2026-09-22 09:00'));
        $this->getJson(route('admin.hourly-gps.status',['type'=>'driver','id'=>$ali->id]))->assertJsonPath('current.time','08:00')->assertJsonPath('next.task','الاستلام');
        $this->getJson(route('admin.hourly-gps.status',['type'=>'maid','id'=>1]))->assertJsonPath('current.visit_number',$visit->visit_number)->assertJsonPath('current.customer','عميل المسار');

        // مسار يوم سابق: النقاط بالترتيب الزمني.
        $this->getJson(route('admin.hourly-gps.trail',['type'=>'driver','id'=>$ali->id,'date'=>'2026-09-22']))->assertOk()
            ->assertJsonPath('points.0.at','06:20')->assertJsonPath('points.1.at','06:30')->assertJsonCount(2,'points');
        $this->getJson(route('admin.hourly-gps.data',['date'=>'2026-09-21']))->assertJsonPath('past',true)->assertJsonPath('live',false);
        $this->get('/admin/hourly/gps-tracking')->assertOk()->assertSee('نوع المستخدم')->assertSee('id="map"',false)->assertDontSee('admin.layout')->assertSee('markercluster',false)->assertSee('data-refresh="5"',false);
        HourlyRentalSettings::persist(['tracking_interval_seconds'=>5]);
        $this->postJson('/api/app/hourly/driver/location',['lat'=>24.7,'lng'=>46.7])->assertOk()->assertJsonPath('data.tracking.interval_seconds',5);
        HourlyRentalSettings::persist(['tracking_interval_seconds'=>60]);
        $this->assertSame(60,HourlyRentalSettings::trackingConfig()['interval_seconds']);

        HourlyRentalSettings::persist(['tracking_enabled'=>false]);
        $this->postJson('/api/app/hourly/driver/location',['lat'=>24.7,'lng'=>46.7])->assertStatus(403)->assertJsonPath('data.tracking.enabled',false);
    }

    public function test_socket_auth_requires_gps_permission_and_only_signs_the_gps_channel():void
    {
        \App\Support\HourlyWebsocketSettings::persist(\App\Support\HourlyWebsocketSettings::prepare([
            'enabled'=>true,'key'=>'test-key','secret'=>'test-secret','app_id'=>'test-id']));
        $url=route('admin.hourly-gps.socket-auth');
        $this->postJson($url,['socket_id'=>'123.456','channel_name'=>'private-hourly-gps'])
            ->assertOk()->assertJsonPath('auth','test-key:'.hash_hmac('sha256','123.456:private-hourly-gps','test-secret'));
        $this->postJson($url,['socket_id'=>'123.456','channel_name'=>'private-other'])->assertUnprocessable();
        $this->assertArrayNotHasKey('secret',app(\App\Services\Hourly\TrackingSocket::class)->browserConfig());
        $admin=\Mockery::mock(\App\Models\auth\Admin::class)->makePartial();$admin->id=7;
        $admin->shouldReceive('can')->andReturn(false);auth('admin')->setUser($admin);
        $this->postJson($url,['socket_id'=>'123.456','channel_name'=>'private-hourly-gps'])->assertForbidden();
    }

    public function test_location_socket_notification_waits_for_commit_and_skips_rollback():void
    {
        $socket=\Mockery::mock(\App\Services\Hourly\TrackingSocket::class);
        $socket->shouldReceive('enabled')->andReturn(true);
        $sent=0;$socket->shouldReceive('changed')->once()->andReturnUsing(function()use(&$sent){$sent++;});
        $this->app->instance(\App\Services\Hourly\TrackingSocket::class,$socket);
        DB::beginTransaction();
        app(LocationPings::class)->record('driver',1,[['lat'=>24.7,'lng'=>46.7]]);
        $this->assertSame(0,$sent);DB::rollBack();$this->assertSame(0,$sent);
        DB::beginTransaction();
        app(LocationPings::class)->record('driver',1,[['lat'=>24.7,'lng'=>46.7]]);
        $this->assertSame(0,$sent);DB::commit();$this->assertSame(1,$sent);
    }

    public function test_socket_provider_failure_does_not_lose_the_saved_location():void
    {
        $client=\Mockery::mock(\Pusher\Pusher::class);
        $client->shouldReceive('trigger')->once()->andThrow(new \RuntimeException('Transport unavailable'));
        $socket=\Mockery::mock(\App\Services\Hourly\TrackingSocket::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $socket->shouldReceive('enabled')->andReturn(true);
        $socket->shouldReceive('client')->andReturn($client);
        $this->app->instance(\App\Services\Hourly\TrackingSocket::class,$socket);
        $this->assertSame(1,app(LocationPings::class)->record('driver',1,[['lat'=>24.7,'lng'=>46.7]]));
        $this->assertDatabaseHas('location_pings',['user_type'=>'driver','user_id'=>1,'lat'=>24.7]);
    }

    private function bypass():void
    {
        $this->withoutMiddleware([\App\Http\Middleware\Authenticate::class, \App\Http\Middleware\EnsureAdminRoutePermission::class, \App\Http\Middleware\Admin::class]);
    }
}
