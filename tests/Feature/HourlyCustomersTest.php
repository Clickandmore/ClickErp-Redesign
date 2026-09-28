<?php
namespace Tests\Feature;

use Tests\HourlyTestCase as TestCase;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema};
use App\Models\Hourly\{HourlyCustomerProfile,HourlyDriver,HourlyRentalOrder};
use App\Models\ingaz\User;
use App\Services\Hourly\{CustomerDirectory,PanelBookingService};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

/** عملاء الساعات: نفس جدول العملاء، بصفحات إنشاء وتعديل وملف خاصة بالساعات وبيانات إضافية في hourly_customer_profiles. */
class HourlyCustomersTest extends TestCase
{
    use BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema;

    protected function setUp():void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-09-22 07:00'));
        $this->setUpHourlyCategorySchema();$this->buildWorkers();
        config(['superadmin_modules.hourly_rental.default_enabled'=>true,'activitylog.enabled'=>false]);
        Schema::create('admins',function(Blueprint $t){ $t->softDeletes();$t->id();$t->string('name');});DB::table('admins')->insert([['id'=>7,'name'=>'مشرف'],['id'=>8,'name'=>'محاسب']]);
        Schema::create('users',function(Blueprint $t){ $t->softDeletes();$t->id();$t->string('name');$t->string('email')->nullable();$t->string('phone')->unique();$t->string('phone_code')->default('966');$t->string('password');
            $t->string('register_from')->default('web');$t->boolean('is_blocked')->default(false);$t->string('national_num',50)->nullable();$t->string('phone2',50)->nullable();$t->date('birthdate')->nullable();
            $t->string('job')->nullable();$t->unsignedBigInteger('created_by')->nullable();$t->timestamps();});
        Schema::table('user_addresses',function(Blueprint $t){foreach(['name','phone','title'] as $c)$t->string($c)->nullable();});
        foreach(['2026_09_20_000001_add_hourly_booking_wizard','2026_09_21_000001_extend_hourly_order_management','2026_09_21_000002_add_hourly_visit_operations','2026_09_22_000001_create_hourly_customer_sources','2026_09_26_000001_create_hourly_customer_profiles','2026_09_27_000001_extend_hourly_customers'] as $migration)(require base_path('database/migrations/'.$migration.'.php'))->up();
        Schema::create('hourly_drivers',function(Blueprint $t){$t->id();$t->string('name');$t->boolean('is_active')->default(true);$t->json('work_schedule')->nullable();$t->timestamps();$t->softDeletes();});
        // عميل من نظام الاستقدام بلا ملف ساعات، وجواله بالصيغة القديمة.
        DB::table('users')->insert(['id'=>1,'name'=>'سارة أحمد العتيبي','phone'=>'0555000111','email'=>'sara@example.test','password'=>'hash','job'=>'معلمة','is_blocked'=>0,'created_at'=>now()->subYear(),'updated_at'=>now()->subYear()]);
        DB::table('maids')->insertOrIgnore(['id'=>5,'name'=>'عاملة التحصيل','rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>today()->toDateString()]);
    }
    protected function tearDown():void{Carbon::setTestNow();HourlyRentalSettings::flush();parent::tearDown();}

    private function payload(array $more=[]):array
    {
        return $more+['customer_type'=>'individual','first_name'=>'نورة','last_name'=>'القحطاني','phone'=>'512345678','email'=>'noura@example.test','language'=>'en',
            'identity_type'=>'national_id','national_num'=>'1098765432','birthdate'=>'1995-01-10','iban'=>'SA03 8000 0000 6080 1016 7519','marketing_source'=>'instagram','note'=>'تفضّل الصباح'];
    }

    /** طلب ساعات عبر معالج الحجز لعميل موجود (نقدي بلا تحصيل). */
    private function order(int $userId=1,string $at='2026-09-23 08:00'):HourlyRentalOrder
    {
        $category=$this->category();$district=\App\Models\Hourly\ServiceDistrict::first()??$this->district('الملز',24.7,46.7);
        $team=\App\Models\Hourly\HourlyTeam::first()??$this->team('فريق');
        $category->districts()->attach($district->id,['is_active'=>true,'team_id'=>$team->id]);$item=$this->item($category);
        DB::table('maids')->insertOrIgnore(['id'=>1,'name'=>'عاملة 1','rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>today()->toDateString(),'hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])])]);
        if(!DB::table('maid_jobs')->where('maid_id',1)->exists())DB::table('maid_jobs')->insert(['maid_id'=>1,'job_type_id'=>2]);
        DB::table('contracts')->insertOrIgnore(['id'=>1,'maid_id'=>1,'status'=>'shelter']);
        if(!DB::table('contract_infos')->where('contract_id',1)->exists())DB::table('contract_infos')->insert(['contract_id'=>1,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
        if(!DB::table('hourly_team_members')->where('member_id',1)->exists())DB::table('hourly_team_members')->insert(['hourly_team_id'=>$team->id,'member_type'=>'maid','member_id'=>1,'role'=>'primary','is_active'=>true]);
        $address=DB::table('user_addresses')->insertGetId(['user_id'=>$userId,'city_id'=>1,'address'=>'عنوان','latitude'=>24.7,'longitude'=>46.7,'district'=>'الملز','created_at'=>now(),'updated_at'=>now()]);
        return app(PanelBookingService::class)->create(['booking_token'=>(string)Str::uuid(),'customer_mode'=>'existing','user_id'=>$userId,'address_mode'=>'existing','address_id'=>$address,'hourly_package_id'=>$item->id,'workers_per_visit'=>1,'payment_method'=>'cash','collect_now'=>false,'all_workers'=>false,'notify_email'=>false,'notify_app'=>false,'visits'=>[['starts_at'=>$at,'worker_ids'=>[1]]]],7);
    }

    public function test_create_page_builds_a_shared_customer_with_an_hourly_profile(): void
    {
        $this->get('/admin/hourly/customers/create')->assertOk()->assertSee('عميل ساعات جديد')->assertSee('رقم الآيبان (IBAN)')->assertDontSee('@selected');
        $this->post(route('admin.hourly-customers.store'),$this->payload())->assertRedirect();
        $user=User::where('phone','512345678')->firstOrFail();
        $this->assertSame(['نورة القحطاني','966','admin',7,'1098765432','1995-01-10'],[$user->name,$user->phone_code,$user->register_from,(int)$user->created_by,$user->national_num,substr((string)$user->birthdate,0,10)]);
        $profile=HourlyCustomerProfile::where('user_id',$user->id)->firstOrFail();
        // الآيبان يُحفظ بلا مسافات، والبيانات الخاصة بالساعات في الملف لا في users.
        $this->assertSame(['individual','نورة','القحطاني','SA0380000000608010167519','en','instagram','national_id'],[$profile->customer_type,$profile->first_name,$profile->last_name,$profile->iban,$profile->language,$profile->marketing_source,$profile->identity_type]);

        // الجوال مسجل بأي صيغة، وصيغة الجوال والآيبان.
        $this->post(route('admin.hourly-customers.store'),$this->payload(['phone'=>'555000111','email'=>'x@example.test']))->assertSessionHasErrors(['phone'=>'رقم الجوال مسجل لعميل آخر.']);
        $this->post(route('admin.hourly-customers.store'),$this->payload(['phone'=>'0512','email'=>'y@example.test']))->assertSessionHasErrors('phone');
        $this->post(route('admin.hourly-customers.store'),$this->payload(['phone'=>'598765432','email'=>'z@example.test','iban'=>'GB00123']))->assertSessionHasErrors('iban');

        // شركة: الاسم من اسم الشركة، وحقول الفرد لا تُحفظ.
        $this->post(route('admin.hourly-customers.store'),['customer_type'=>'company','company_name'=>'شركة الندى','company_id'=>'1010101010','first_name'=>'تجاهل','phone'=>'598765432','language'=>'ar'])->assertRedirect();
        $company=User::where('phone','598765432')->firstOrFail();
        $this->assertSame('شركة الندى',$company->name);
        $profile=HourlyCustomerProfile::where('user_id',$company->id)->first();
        $this->assertSame(['company',null,'1010101010'],[$profile->customer_type,$profile->first_name,$profile->company_id]);
    }

    public function test_existing_customer_is_completed_from_the_hourly_edit_page_without_touching_other_fields(): void
    {
        // عميل من نظام آخر: الاسم الأول والأخير يُقترحان من اسمه، وتنبيه لإكمال بيانات الساعات.
        $this->get('/admin/hourly/customers/1')->assertOk()->assertSee('value="سارة"',false)->assertSee('value="أحمد العتيبي"',false)->assertSee('أكمل بيانات الساعات');
        // الجوال القديم (05…) يُقبل ويُحفظ بصيغة دخول التطبيق 5XXXXXXXX، وكلمة المرور لا تتغير ما دام حقلها فارغًا.
        $this->put(route('admin.hourly-customers.update',1),$this->payload(['first_name'=>'سارة','last_name'=>'العتيبي','phone'=>'0555000111','email'=>'sara@example.test','new_password'=>'']))->assertRedirect(route('admin.hourly-customers.show',1));
        $user=User::find(1);
        $this->assertSame(['سارة العتيبي','555000111','hash','معلمة',0],[$user->name,$user->phone,$user->password,$user->job,(int)$user->is_blocked]);
        $this->assertSame('SA0380000000608010167519',HourlyCustomerProfile::where('user_id',1)->value('iban'));
        // تغيير الجوال يلزمه الصيغة الجديدة، ولا يُسمح بتمرير كلمة مرور من صفحة التعديل.
        $this->put(route('admin.hourly-customers.update',1),$this->payload(['phone'=>'0512']))->assertSessionHasErrors('phone');
        $this->put(route('admin.hourly-customers.update',1),$this->payload(['phone'=>'555000111','email'=>'sara@example.test','password'=>'NewPass123']))->assertSessionHasErrors('password');
        // «كلمة مرور جديدة»: تُطلب مطابقة التأكيد، وتُحفظ مشفّرة.
        $this->put(route('admin.hourly-customers.update',1),$this->payload(['phone'=>'555000111','email'=>'sara@example.test','new_password'=>'NewPass123','new_password_confirmation'=>'Other']))->assertSessionHasErrors('new_password');
        $this->put(route('admin.hourly-customers.update',1),$this->payload(['phone'=>'555000111','email'=>'sara@example.test','new_password'=>'NewPass123','new_password_confirmation'=>'NewPass123']))->assertSessionHasNoErrors();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewPass123',User::find(1)->password));
    }

    /** يفعّل سجل النشاط لاختبار تسجيل العمليات الحساسة. */
    private function enableActivityLog(): void
    {
        Schema::create('activity_log',function(Blueprint $t){$t->id();$t->string('log_name')->nullable();$t->text('description');$t->nullableMorphs('subject');$t->nullableMorphs('causer');
            $t->text('properties')->nullable();$t->string('event')->nullable();$t->uuid('batch_uuid')->nullable();$t->timestamps();});
        config(['activitylog.enabled'=>true]);app(\Spatie\Activitylog\ActivityLogStatus::class)->enable();
    }
    private function events(): array{return DB::table('activity_log')->where('log_name','hourly_customers')->orderBy('id')->pluck('event')->all();}

    public function test_phone_normalization_on_save(): void
    {
        foreach(["+966 \u{2066}+966 55 298 7588\u{2069}"=>'552987588','+966 966538270564'=>'538270564','0552987588'=>'552987588','00966552987588'=>'552987588','٠٥٥٢٩٨٧٥٨٨'=>'552987588','552987588'=>'552987588',
            '+44 7700 900123'=>null,'0112345678'=>null,''=>null] as $raw=>$expected)$this->assertSame($expected,\App\Support\PhoneNumber::saudiMobile((string)$raw),'phone: '.$raw);

        // الحفظ من صفحة الساعات يطبّع الجوال، وكشف التكرار يطابق كل الصيغ القديمة.
        $user=app(CustomerDirectory::class)->save(null,$this->payload(['phone'=>'+966 0512 345 678']),7);
        $this->assertSame('512345678',$user->phone);
        $this->post(route('admin.hourly-customers.store'),$this->payload(['phone'=>'+966555000111','email'=>'q@example.test']))->assertSessionHasErrors(['phone'=>'رقم الجوال مسجل لعميل آخر.']);


    }

    public function test_balance_adjustment_requires_a_reason_and_is_ledgered_and_logged(): void
    {
        $this->enableActivityLog();
        $this->post(route('admin.hourly-customers.adjust',1),['direction'=>'increase','amount'=>100])->assertSessionHasErrors('reason');
        $this->post(route('admin.hourly-customers.adjust',1),['direction'=>'sideways','amount'=>100,'reason'=>'تعويض'])->assertSessionHasErrors('direction');
        $this->post(route('admin.hourly-customers.adjust',1),['direction'=>'increase','amount'=>100,'reason'=>'تعويض زيارة متأخرة'])->assertRedirect(route('admin.hourly-customers.show',[1,'balance']))->assertSessionHasNoErrors();
        // الخصم قد يجعل الرصيد سالبًا (مديونية).
        $this->post(route('admin.hourly-customers.adjust',1),['direction'=>'decrease','amount'=>150,'reason'=>'خصم تلفيات'])->assertSessionHasNoErrors();
        $this->assertEquals(-50,User::find(1)->hourly_wallet_balance);
        $rows=DB::table('hourly_wallet_transactions')->where('user_id',1)->orderBy('id')->get();
        $this->assertSame([['adjustment',100.0,100.0,'تعويض زيارة متأخرة'],['adjustment',-150.0,-50.0,'خصم تلفيات']],$rows->map(fn($r)=>[$r->type,(float)$r->amount,(float)$r->balance_after,$r->description])->all());
        $this->assertSame(['balance_adjusted','balance_adjusted'],$this->events());
        $this->get('/admin/hourly/customers/1/balance')->assertOk()->assertSee('تسوية يدوية')->assertSee('خصم تلفيات');
        $this->get('/admin/hourly/customers/1/logs')->assertOk()->assertSee('تعديل رصيد يدوي');
    }

    public function test_delete_is_blocked_by_open_orders_or_balance_and_restore_brings_the_profile_back(): void
    {
        $this->enableActivityLog();
        // طلب قائم يمنع الحذف مع ذكر السبب.
        $order=$this->order(1);
        $this->get('/admin/hourly/customers/1')->assertOk()->assertSee('لا يمكن الحذف الآن:')->assertSee('لديه 1 طلب قائم.');
        $this->delete(route('admin.hourly-customers.destroy',1))->assertSessionHasErrors(['delete'=>'لديه 1 طلب قائم.']);
        // رصيد غير صفري يمنعه أيضًا.
        DB::table('hourly_rental_orders')->where('id',$order->id)->update(['status'=>'completed']);
        DB::table('users')->where('id',1)->update(['hourly_wallet_balance'=>20]);
        $this->delete(route('admin.hourly-customers.destroy',1))->assertSessionHasErrors('delete');
        $this->assertNull(HourlyCustomerProfile::onlyTrashed()->where('user_id',1)->first());

        DB::table('users')->where('id',1)->update(['hourly_wallet_balance'=>0]);
        $this->delete(route('admin.hourly-customers.destroy',1),['reason'=>'طلب العميل'])->assertRedirect(route('admin.hourly-customers.index'))->assertSessionHasNoErrors();
        $profile=HourlyCustomerProfile::onlyTrashed()->where('user_id',1)->firstOrFail();
        $this->assertSame(7,(int)$profile->deleted_by);
        // سجل العميل المشترك باقٍ، ويختفي من القائمة إلا مع «إظهار المحذوفة».
        $this->assertNotNull(User::find(1));
        $directory=app(CustomerDirectory::class);
        $this->assertNotContains(1,$directory->query([])->pluck('users.id')->all());
        $this->assertContains(1,$directory->query(['show_deleted'=>1])->pluck('users.id')->all());
        $this->get('/admin/hourly/customers?show_deleted=1')->assertOk()->assertSee('is-deleted',false)->assertSee('استعادة');
        $this->get('/admin/hourly/customers/1')->assertOk()->assertSee('هذا العميل محذوف من قسم الساعات');
        $this->delete(route('admin.hourly-customers.destroy',1))->assertSessionHasErrors(['delete'=>'العميل محذوف بالفعل.']);

        $this->put(route('admin.hourly-customers.restore',1))->assertRedirect(route('admin.hourly-customers.show',1))->assertSessionHasNoErrors();
        $this->assertFalse(HourlyCustomerProfile::where('user_id',1)->firstOrFail()->trashed());
        $this->put(route('admin.hourly-customers.restore',1))->assertSessionHasErrors('restore');
        $this->assertSame(['hourly_deleted','hourly_restored'],array_values(array_intersect($this->events(),['hourly_deleted','hourly_restored'])));
    }

    public function test_merge_moves_hourly_data_to_the_primary_and_soft_deletes_the_other(): void
    {
        $this->enableActivityLog();
        // حساب مكرر لنفس العميل بجوال بصيغة أخرى، عليه طلب ورصيد وعنوان ومفضلة.
        DB::table('users')->insert(['id'=>30,'name'=>'سارة (مكرر)','phone'=>'+966 555000111 ','email'=>null,'national_num'=>'1011223344','password'=>'x','hourly_wallet_balance'=>75,'created_at'=>now(),'updated_at'=>now()]);
        $dupOrder=$this->order(30);
        DB::table('hourly_wallet_transactions')->insert(['user_id'=>30,'type'=>'deposit','amount'=>75,'balance_after'=>75,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('hourly_customer_favorites')->insert(['user_id'=>30,'favorable_type'=>'maid','favorable_id'=>1,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('users')->where('id',1)->update(['hourly_wallet_balance'=>25]);

        $groups=app(\App\Services\Hourly\MergeCustomersService::class)->groups();
        $this->assertSame([[1,30]],$groups->map(fn($g)=>$g['members']->pluck('id')->all())->all());
        $this->assertSame(['phone'],$groups[0]['reasons']);
        $this->get('/admin/hourly/customers/duplicates')->assertOk()->assertSee('نفس الجوال')->assertSee('سارة (مكرر)');
        $this->get('/admin/hourly/customers/merge?ids[]=1&ids[]=30')->assertOk()->assertSee('تأكيد الدمج');
        $this->get('/admin/hourly/customers/1/merge')->assertOk()->assertSee('فتح شاشة الدمج');

        $this->post(route('admin.hourly-customers.merge'),['primary_id'=>1,'merge_ids'=>[1]])->assertSessionHasErrors();
        $this->post(route('admin.hourly-customers.merge'),['primary_id'=>1,'merge_ids'=>[30],'reason'=>'نفس العميل'])->assertRedirect(route('admin.hourly-customers.show',[1,'merge']))->assertSessionHasNoErrors();

        $this->assertSame(1,(int)$dupOrder->fresh()->user_id);
        $this->assertSame(0,DB::table('user_addresses')->where('user_id',30)->count());
        $this->assertSame(1,DB::table('hourly_customer_favorites')->where('user_id',1)->count());
        $this->assertEquals(100,User::find(1)->hourly_wallet_balance);
        $this->assertEquals(0,User::find(30)->hourly_wallet_balance);
        $this->assertSame('merge',DB::table('hourly_wallet_transactions')->where('user_id',1)->orderByDesc('id')->value('type'));
        // الناقص في الأساسي يُكمل من المدموج، والمدموج يُحذف من قسم الساعات ولا يُستعاد.
        $this->assertSame('1011223344',User::find(1)->national_num);
        $merged=HourlyCustomerProfile::onlyTrashed()->where('user_id',30)->firstOrFail();
        $this->assertSame(1,(int)$merged->merged_into_id);
        $this->assertSame(1,DB::table('hourly_customer_merges')->where(['primary_user_id'=>1,'merged_user_id'=>30,'reason'=>'نفس العميل'])->count());
        $this->assertContains('customers_merged',$this->events());
        $this->put(route('admin.hourly-customers.restore',30))->assertSessionHasErrors(['restore'=>'هذا الحساب دُمج في #1 ولا يُستعاد.']);
        $this->post(route('admin.hourly-customers.merge'),['primary_id'=>1,'merge_ids'=>[30]])->assertSessionHasErrors('merge');
        $this->assertTrue(app(\App\Services\Hourly\MergeCustomersService::class)->groups()->isEmpty());
        $this->get('/admin/hourly/customers/1/merge')->assertOk()->assertSee('دُمج منه #30');
    }

    public function test_groups_payment_term_and_new_tabs(): void
    {
        $this->post(route('admin.hourly-customer-groups.store'),['name'=>'VIP','discount_percent'=>10,'is_active'=>1])->assertSessionHasNoErrors();
        $this->post(route('admin.hourly-customer-groups.store'),['name'=>'مؤرشفة'])->assertSessionHasNoErrors();
        $vip=\App\Models\Hourly\HourlyCustomerGroup::where('name','VIP')->firstOrFail();
        $archived=\App\Models\Hourly\HourlyCustomerGroup::where('name','مؤرشفة')->firstOrFail();
        $this->assertFalse((bool)$archived->is_active);
        $this->get('/admin/hourly/customer-groups')->assertOk()->assertSee('VIP')->assertSee('غير نشطة');

        // المجموعة غير النشطة لا تُختار، ومدة الدفع من القائمة.
        $this->put(route('admin.hourly-customers.update',1),$this->payload(['phone'=>'555000111','email'=>'sara@example.test','group_id'=>$archived->id]))->assertSessionHasErrors('group_id');
        $this->put(route('admin.hourly-customers.update',1),$this->payload(['phone'=>'555000111','email'=>'sara@example.test','payment_term'=>'yearly']))->assertSessionHasErrors('payment_term');
        $this->put(route('admin.hourly-customers.update',1),$this->payload(['phone'=>'555000111','email'=>'sara@example.test','group_id'=>$vip->id,'payment_term'=>'monthly']))->assertSessionHasNoErrors();
        $this->assertSame([$vip->id,'monthly'],[(int)HourlyCustomerProfile::where('user_id',1)->value('group_id'),HourlyCustomerProfile::where('user_id',1)->value('payment_term')]);
        $directory=app(CustomerDirectory::class);
        $this->assertSame([1],$directory->query(['group_id'=>(string)$vip->id])->pluck('users.id')->all());

        // مدة الدفع المؤجلة تُقرأ عند إنشاء الطلب: لا يُشترط سداد قبل التفعيل، وتاريخ الاستحقاق شهر بعد أول زيارة.
        $order=$this->order(1);
        $this->assertSame(['monthly','2026-10-23'],[$order->booking_metadata['payment_term']??null,$order->booking_metadata['payment_due_date']??null]);
        $this->assertEquals(0,$order->package_snapshot['charge_now']);

        // الترتيب: المفضلة أولًا.
        DB::table('maids')->insertOrIgnore(['id'=>2,'name'=>'عاملة 2','rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>today()->toDateString()]);
        $this->post(route('admin.hourly-customers.favorites.store',1),['favorable_type'=>'maid','favorable_id'=>2])->assertSessionHasNoErrors();
        $maids=collect([(object)['id'=>1],(object)['id'=>2],(object)['id'=>5]]);
        $this->assertSame([2,1,5],$directory->favoritesFirst($maids,1)->pluck('id')->all());

        foreach(['calendar'=>'السبت','favorites'=>'عاملة 2','documents'=>'رفع بطاقة هوية','ratings'=>'لا توجد تقييمات','report'=>'الرصيد الافتتاحي','merge'=>'لا توجد حسابات مكررة لهذا العميل.'] as $tab=>$text)
            $this->get('/admin/hourly/customers/1/'.$tab)->assertOk()->assertSee($text);
        $this->get('/admin/hourly/customers/1/calendar?view=week&date=2026-09-23')->assertOk()->assertSee($order->visits()->first()->visit_number);
        $this->get('/admin/hourly/customers/1')->assertOk()->assertSee('مكتمل')->assertSee('قيد الانتظار')->assertSee('مجموعة العضوية')->assertSee('VIP');
        $xlsx=$this->get(route('admin.hourly-customers.statement',[1,'format'=>'xlsx','from'=>'2026-09-01','to'=>'2026-09-30']));
        $xlsx->assertOk();$this->assertStringContainsString('statement-1-20260901-20260930.xlsx',$xlsx->headers->get('content-disposition'));
    }

    public function test_list_filters_balances_and_export(): void
    {
        $directory=app(CustomerDirectory::class);
        $noura=$directory->save(null,$this->payload(),7);
        $company=$directory->save(null,['customer_type'=>'company','company_name'=>'شركة الندى','phone'=>'598765432','language'=>'ar','marketing_source'=>'google'],7);
        DB::table('users')->where('id',1)->update(['hourly_wallet_balance'=>120]);
        DB::table('users')->where('id',$company->id)->update(['hourly_wallet_balance'=>-45.5]);
        $old=$this->order(1);DB::table('hourly_rental_orders')->where('id',$old->id)->update(['created_at'=>now()->subMonths(3)]);
        $this->order($noura->id,'2026-09-24 08:00');
        $ids=fn(array $f)=>$directory->query($f)->orderBy('users.id')->pluck('users.id')->all();

        $this->assertSame([1],$ids(['balance'=>'positive']));
        $this->assertSame([$company->id],$ids(['balance'=>'negative']));
        $this->assertSame([1,$company->id],$ids(['balance'=>'non_zero']));
        $this->assertSame([$company->id],$ids(['type'=>'company']));
        $this->assertSame([1,$noura->id],$ids(['type'=>'individual']));
        $this->assertSame([$company->id],$ids(['activity'=>'no_orders']));
        $this->assertSame([1],$ids(['activity'=>'inactive_2m']));
        $this->assertSame([$noura->id],$ids(['marketing_source'=>'instagram']));
        $this->assertSame([1],$ids(['search'=>'سارة']));
        $this->assertSame(['positive'=>120.0,'negative'=>-45.5],$directory->balances($directory->query([])));

        $this->get('/admin/hourly/customers?balance=negative')->assertOk()->assertSee('شركة الندى')->assertDontSee('سارة أحمد')->assertSee('قائمة العملاء')->assertSee('-45.50');
        $this->get('/panel/customer?type=company')->assertRedirect(route('admin.hourly-customers.index',['type'=>'company']));
        $response=$this->get('/admin/hourly/customers/export');
        $response->assertOk();$this->assertStringContainsString('hourly-customers.xlsx',$response->headers->get('content-disposition'));
        app()->setLocale('en');
        $this->get('/admin/hourly/customers')->assertOk()->assertSee('Customer list')->assertSee('dir="ltr"',false)->assertDontSee('قائمة العملاء');
        app()->setLocale('ar');
    }

    public function test_add_balance_to_wallet_or_as_an_order_payment(): void
    {
        // رصيد في المحفظة بلا ربط: يرتفع الرصيد وتُسجل الحركة بمن استلم المبلغ والضريبة والوصف.
        $this->post(route('admin.hourly-customers.deposit',1),['mode'=>'wallet','collected_by_type'=>'admin','collected_by_id'=>8,'amount'=>250,'vat_rate'=>15,'payment_method'=>'cash','paid_at'=>'2026-09-22T06:30','description'=>'<b>دفعة</b> مقدمة'])->assertRedirect();
        $this->assertEquals(250,User::find(1)->hourly_wallet_balance);
        $row=DB::table('hourly_wallet_transactions')->where('user_id',1)->first();
        $this->assertSame(['deposit','cash','admin',8,'دفعة مقدمة',250.0],[$row->type,$row->payment_method,$row->collected_by_type,(int)$row->collected_by_id,$row->description,(float)$row->balance_after]);

        // دفعة على طلب: تمر بالتحصيل القائم، ولا تمس المحفظة، وتُنسب للعاملة التي استلمتها.
        $order=$this->order(1);
        $this->post(route('admin.hourly-customers.deposit',1),['mode'=>'order','order_id'=>$order->id,'collected_by_type'=>'maid','collected_by_id'=>5,'amount'=>50,'vat_rate'=>15,'payment_method'=>'cash','paid_at'=>'2026-09-22T06:45'])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('receipt_url');
        $this->assertEquals(50,$order->fresh()->paid_amount);
        $this->assertEquals(250,User::find(1)->hourly_wallet_balance);
        $payment=DB::table('hourly_order_payments')->where('hourly_rental_order_id',$order->id)->first();
        $this->assertSame(['maid',5],[$payment->collected_by_type,(int)$payment->collected_by_id]);
        $this->get(route('admin.hourly-orders.receipt',[$order->id,$payment->id]))->assertOk()->assertSee('سند قبض')->assertSee('50.00');
        // أكثر من المتبقي يُرفض، وطلب عميل آخر لا يُقبل.
        $this->post(route('admin.hourly-customers.deposit',1),['mode'=>'order','order_id'=>$order->id,'collected_by_type'=>'admin','collected_by_id'=>7,'amount'=>99999,'vat_rate'=>15,'payment_method'=>'cash','paid_at'=>'2026-09-22T06:45'])->assertSessionHasErrors();
        $other=app(CustomerDirectory::class)->save(null,$this->payload(),7);
        $this->post(route('admin.hourly-customers.deposit',$other->id),['mode'=>'order','order_id'=>$order->id,'collected_by_type'=>'admin','collected_by_id'=>7,'amount'=>10,'vat_rate'=>15,'payment_method'=>'cash','paid_at'=>'2026-09-22T06:45'])->assertSessionHasErrors('order_id');

        $this->get('/admin/hourly/customers/1/balance')->assertOk()->assertSee('إيداع')->assertSee('دفعة مقدمة')->assertSee('محاسب')->assertSee('عاملة التحصيل');
        $this->get('/admin/hourly/customers/1')->assertOk()->assertSee('250.00')->assertSee('إضافة رصيد أو دفعة');
    }

    public function test_tabs_verification_addresses_and_wizard_profile(): void
    {
        $order=$this->order(1);
        foreach(['orders'=>'#'.$order->order_number,'visits'=>'مجدولة','addresses'=>'إضافة عنوان','logs'=>'لا توجد سجلات.'] as $tab=>$text)$this->get('/admin/hourly/customers/1/'.$tab)->assertOk()->assertSee($text);
        $this->get('/admin/hourly/customers/1/unknown')->assertNotFound();

        $this->putJson(route('admin.hourly-customers.verify',1),['verified'=>true])->assertOk()->assertJson(['verified'=>true]);
        $this->assertNotNull(HourlyCustomerProfile::where('user_id',1)->value('verified_at'));
        $this->assertSame([1],app(CustomerDirectory::class)->query(['verified'=>'1'])->pluck('users.id')->all());

        // العنوان: الحي يجب أن يتبع المدينة، وأعمدة العنوان القديمة تُملأ للأنظمة الأخرى.
        $district=\App\Models\Hourly\ServiceDistrict::first();
        $this->post(route('admin.hourly-customers.addresses.store',1),['city_id'=>2,'hourly_district_id'=>$district->id,'address'=>'حي آخر','latitude'=>21.5,'longitude'=>39.2])->assertSessionHasErrors('hourly_district_id');
        $this->post(route('admin.hourly-customers.addresses.store',1),['city_id'=>1,'hourly_district_id'=>$district->id,'address'=>'شارع العليا','building'=>'12','contact_first_name'=>'منى','contact_last_name'=>'سعد','contact_phone'=>'0501112223','latitude'=>24.71,'longitude'=>46.68])
            ->assertRedirect(route('admin.hourly-customers.show',[1,'addresses']));
        $address=DB::table('user_addresses')->where('address','شارع العليا')->first();
        $this->assertSame(['منى سعد','0501112223','12',$district->name],[$address->name,$address->phone,$address->title,$address->district]);
        $this->put(route('admin.hourly-customers.addresses.update',[1,$address->id]),['city_id'=>1,'address'=>'شارع العليا 2','latitude'=>24.72,'longitude'=>46.69])->assertRedirect();
        $this->assertSame('شارع العليا 2',DB::table('user_addresses')->where('id',$address->id)->value('address'));

        // عميل جديد من معالج الحجز يحصل على ملف ساعات أيضًا.
        $category=\App\Models\Hourly\HourlyServiceCategory::first();$item=\App\Models\Hourly\HourlyPackage::first();
        app(PanelBookingService::class)->create(['booking_token'=>(string)Str::uuid(),'customer_mode'=>'new','customer'=>['type'=>'company','company_name'=>'مؤسسة الضحى','company_id'=>'7001','email'=>'d@example.test','phone'=>'533333333','password'=>'Secret123','language'=>'ar'],
            'address_mode'=>'new','address'=>['city_id'=>1,'hourly_district_id'=>$district->id,'building'=>'1','unit'=>'2','address'=>'عنوان','contact_first_name'=>'أ','contact_last_name'=>'ب','contact_phone'=>'0500000000','latitude'=>24.7,'longitude'=>46.7],
            'hourly_package_id'=>$item->id,'workers_per_visit'=>1,'payment_method'=>'cash','collect_now'=>false,'all_workers'=>false,'notify_email'=>false,'notify_app'=>false,'visits'=>[['starts_at'=>'2026-09-24 08:00','worker_ids'=>[1]]]],7);
        $created=HourlyCustomerProfile::where('user_id',User::where('phone','533333333')->value('id'))->firstOrFail();
        $this->assertSame(['company','7001','مؤسسة الضحى'],[$created->customer_type,$created->company_id,$created->company_name]);
    }
}
