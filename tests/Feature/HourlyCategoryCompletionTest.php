<?php
namespace Tests\Feature;
use Tests\HourlyTestCase as TestCase;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use App\Models\Hourly\{HourlyCategorySetting,HourlyPackage,HourlyRentalOrder};
use App\Services\Hourly\{HourlyCategorySettings,HourlyOrderService};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;
class HourlyCategoryCompletionTest extends TestCase {
    use BuildsHourlyCategorySchema;
    protected function setUp(): void { parent::setUp();$this->setUpHourlyCategorySchema();auth('api')->setUser(new \App\Models\ingaz\User(['id'=>1])); }
    protected function tearDown():void {HourlyRentalSettings::flush();parent::tearDown();}
    private function covered(): array {
        $category=$this->category();$district=$this->district('حي',24.7,46.7);$category->districts()->attach($district->id,['is_active'=>true]);$item=$this->item($category,['visits_count'=>2]);
        return [$category,$district,$item,['hourly_package_id'=>$item->id,'address_id'=>$this->address(24.7,46.7),'payment_method'=>'cash']];
    }
    public function test_http_quantity_is_used_and_branch_cannot_be_spoofed_by_customer():void {
        [$category,$district,$item,$data]=$this->covered();
        HourlyCategorySetting::create(['service_category_id'=>$category->id,'settings'=>['order_form_steppers'=>[['key'=>'quantity','label'=>'عدد','channels'=>['web_app','panel'],'min'=>1,'max'=>3,'scale_price'=>true]]]]);
        $response=$this->postJson('/api/app/hourly/orders',$data+['quantity'=>2])->assertCreated();$order=HourlyRentalOrder::findOrFail($response->json('data.id'));$this->assertEquals(460,$order->total_amount);
        $this->postJson('/api/app/hourly/orders',$data+['quantity'=>4])->assertStatus(422);
        DB::table('hourly_category_payment_methods')->insert(['service_category_id'=>$category->id,'payment_method'=>'cash','web_app'=>false,'employee'=>true,'branch'=>true]);
        $this->postJson('/api/app/hourly/orders',$data+['payment_channel'=>'branch'])->assertStatus(422);
        DB::table('hourly_category_payment_methods')->where('service_category_id',$category->id)->update(['branch'=>false]);
        try{app(HourlyOrderService::class)->create(1,$data+['payment_channel'=>'branch'],7);$this->fail('Branch must reject cash');}catch(\Illuminate\Validation\ValidationException $e){$this->assertStringContainsString('طريقة الدفع',$e->errors()['hourly'][0]);}
    }
    public function test_removing_last_district_blocks_order_and_slots():void {
        [$category,$district,$item,$data]=$this->covered();$category->districts()->detach();
        $this->postJson('/api/app/hourly/orders',$data)->assertStatus(422);
        $this->getJson('/api/app/hourly/slots?city_id=1&hourly_package_id='.$item->id.'&district_id='.$district->id.'&date='.now()->addDays(3)->toDateString())->assertOk()->assertJsonPath('data',[]);
    }
    public function test_overrides_channels_days_and_empty_values_inherit():void {
        [$category,$district,$item]=$this->covered();
        HourlyRentalSettings::persist(['service_defaults'=>['customer_future_days'=>90,'auto_assign'=>['panel'=>true]]]);
        HourlyCategorySetting::create(['service_category_id'=>$category->id,'settings'=>['customer_future_days'=>30,'auto_assign'=>['web_app'=>true],'working_days'=>[1=>['is_on'=>false,'order_buffer_hours'=>2]]]]);
        $item->settings_overrides=['customer_future_days'=>7,'auto_assign'=>['web_app'=>false,'panel'=>null],'working_days'=>[1=>['is_on'=>true]]];$item->save();
        $s=new HourlyCategorySettings();$this->assertEquals(7,$s->value($item,'customer_future_days'));$this->assertSame(['web_app'=>false,'panel'=>true],$s->value($item,'auto_assign'));$this->assertSame(['is_on'=>true,'order_buffer_hours'=>2],$s->value($item,'working_days')[1]);
        $this->get(route('admin.hourly-service-items.settings.edit',$item))->assertOk();$this->get(route('admin.hourly-service-defaults.edit'))->assertOk();
        $this->put(route('admin.hourly-service-items.settings.update',$item),['order_form_steppers_mode'=>'inherit','customer_future_days'=>''])->assertRedirect();
        $this->assertEquals(30,(new HourlyCategorySettings())->value($item->fresh(),'customer_future_days'));
    }
    public function test_notes_show_on_category_and_item_api_and_reorder():void {
        [$category,$district,$item]=$this->covered();$this->post(route('admin.hourly-category-notes.store',$category),['text_ar'=>'أحضر المفتاح','text_en'=>'Bring the key'])->assertRedirect();
        $this->get(route('admin.hourly-service-categories.show',$category))->assertOk()->assertSee('أحضر المفتاح')->assertSee('معاينة الملاحظات');
        $this->withHeader('lang','ar')->getJson('/api/app/hourly/packages/'.$item->id.'/config')->assertOk()->assertJsonPath('data.notes.0.text','أحضر المفتاح');
        $this->getJson(route('admin.hourly-booking-config',$item))->assertOk()->assertJsonPath('data.districts.0.id',$district->id);
        $note=$category->notes()->first();$this->putJson(route('admin.hourly-category-notes.reorder',$category),['ids'=>[$note->id]])->assertOk();
    }
    public function test_reschedule_cannot_overlap_or_add_second_session_to_same_day():void {
        [$category,$district,$item,$data]=$this->covered();$day=now()->addDays(3)->format('Y-m-d');$other=now()->addDays(4)->format('Y-m-d');
        $order=app(HourlyOrderService::class)->create(1,$data+['scheduled_starts'=>[$day.' 08:00',$other.' 08:00']]);$visit=$order->visits()->orderByDesc('id')->first();
        $url='/api/app/hourly/orders/'.$order->id.'/visits/'.$visit->id.'/reschedule';
        $this->postJson($url,['scheduled_start'=>$day.' 08:00'])->assertStatus(422);
        $this->assertSame($other,$visit->fresh()->scheduled_start_at->toDateString());
    }
}
