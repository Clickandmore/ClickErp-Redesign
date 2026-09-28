<?php
namespace Tests\Feature;
use Tests\HourlyTestCase as TestCase;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema};
use App\Models\Hourly\HourlyProductAttribute;
use App\Services\Hourly\{HourlyBookingRules,MaidSettingsService};
use App\Support\HourlyRentalSettings;
use Illuminate\Http\Request;
class HourlyProductAttributesTest extends TestCase {
    use BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema;
    protected function setUp():void{parent::setUp();$this->setUpHourlyCategorySchema();}
    protected function tearDown():void{HourlyRentalSettings::flush();parent::tearDown();}
    private function option($category){$root=HourlyProductAttribute::create(['service_category_id'=>$category->id,'name_ar'=>'حجم السكن','code'=>'size','is_active'=>true]);return HourlyProductAttribute::create(['service_category_id'=>$category->id,'parent_id'=>$root->id,'name_ar'=>'استوديو','code'=>'studio','price_modifier'=>25,'is_active'=>true]);}
    public function test_tree_is_scoped_audited_and_rejects_foreign_or_child_parents():void{
        $category=$this->category();$option=$this->option($category);$data=['name_ar'=>'غرفة','code'=>'room','parent_id'=>$option->parent_id,'sort_order'=>2,'is_active'=>1];
        $this->post(route('admin.hourly-attributes.store',$category),$data)->assertRedirect();
        $this->postJson(route('admin.hourly-attributes.store',$category),array_replace($data,['code'=>'invalid','parent_id'=>$option->id]))->assertJsonValidationErrors('parent_id');
        $other=$this->category();$this->postJson(route('admin.hourly-attributes.store',$other),$data)->assertJsonValidationErrors('parent_id');
        $this->putJson(route('admin.hourly-attributes.update',[$other,$option]),$data)->assertNotFound();
        $this->get(route('admin.hourly-service-categories.show',$category))->assertOk()->assertSee('حجم السكن')->assertSee('استوديو');
        $this->assertDatabaseHas('hourly_service_category_logs',['service_category_id'=>$category->id,'section'=>'attributes']);
    }
    public function test_attribute_price_options_city_scope_and_inactive_parent():void{
        $category=$this->category();$option=$this->option($category);$item=$this->item($category,['attribute_id'=>$option->id]);
        $quote=app(HourlyBookingRules::class)->quote($item,1,1,false);$this->assertSame(14000,$quote['total']);
        $url='/api/app/hourly/packages/'.$item->id.'/config';$this->getJson($url.'?city_id=1')->assertOk()->assertJsonPath('data.attributes.0.options.0.items.0.total',140);
        $this->getJson($url.'?city_id=2')->assertOk()->assertJsonPath('data.attributes',[]);
        $this->get(route('admin.hourly-packages.edit',$item))->assertOk()->assertSee('attribute_id')->assertSee('استوديو');
        $option->parent->update(['is_active'=>false]);$this->getJson($url)->assertNotFound();
    }
    public function test_maid_attributes_sync_clear_and_reject_roots():void{
        $this->buildWorkers();$category=$this->category();$option=$this->option($category);$maid=\App\Models\ingaz\Maid::create(['name'=>'Worker']);
        $payload=['type_id'=>2,'rental_mode'=>'hourly','hourly'=>['requires_driver'=>1,'max_orders_per_hour'=>1,'account_enabled'=>0,'login_access'=>'both','attributes_present'=>1,'attribute_ids'=>[$option->id],'work_schedule'=>array_fill_keys(array_keys(\App\Models\Hourly\HourlyDriver::DAYS),['mode'=>'off'])]];
        $service=app(MaidSettingsService::class);$service->save($maid,$service->validate(new Request($payload),$maid));$this->assertEquals([$option->id],$maid->productAttributes()->pluck('hourly_product_attributes.id')->all());
        $this->assertStringContainsString('استوديو',view('admin.ingaz.maids._hourly-fields',['obj'=>$maid->fresh()])->render());
        unset($payload['hourly']['attribute_ids']);$service->save($maid,$service->validate(new Request($payload),$maid));$this->assertSame(0,$maid->productAttributes()->count());
        $payload['hourly']['attribute_ids']=[$option->parent_id];$this->expectException(\Illuminate\Validation\ValidationException::class);$service->validate(new Request($payload),$maid);
    }
    public function test_order_rejects_mismatched_attribute_and_snapshots_modified_price():void{
        $category=$this->category();$option=$this->option($category);$item=$this->item($category,['attribute_id'=>$option->id]);$district=$this->district('District',24.7,46.7);$category->districts()->attach($district->id,['is_active'=>true]);
        $data=['hourly_package_id'=>$item->id,'attribute_id'=>$option->id,'address_id'=>$this->address(24.7,46.7),'payment_method'=>'cash'];
        $order=app(\App\Services\Hourly\HourlyOrderService::class)->create(1,$data,7);$this->assertEquals(140,$order->total_amount);
        $data['attribute_id']=$option->parent_id;$this->expectException(\Illuminate\Validation\ValidationException::class);app(\App\Services\Hourly\HourlyOrderService::class)->create(1,$data,7);
    }
    public function test_legacy_brand_migration_keeps_items_and_groups_duplicates():void{
        config(['database.connections.attribute_legacy'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'database.default'=>'attribute_legacy']);
        \Illuminate\Support\Facades\Schema::create('hourly_service_categories',function(\Illuminate\Database\Schema\Blueprint $t){$t->id();});
        \Illuminate\Support\Facades\Schema::create('hourly_packages',function(\Illuminate\Database\Schema\Blueprint $t){$t->id();$t->unsignedBigInteger('service_category_id');$t->string('brand')->nullable();});
        \Illuminate\Support\Facades\DB::table('hourly_service_categories')->insert(['id'=>1]);
        \Illuminate\Support\Facades\DB::table('hourly_packages')->insert([['service_category_id'=>1,'brand'=>'Studio'],['service_category_id'=>1,'brand'=>'Studio'],['service_category_id'=>1,'brand'=>null]]);
        (require base_path('database/migrations/2026_09_18_000009_create_hourly_product_attributes.php'))->up();
        $items=\Illuminate\Support\Facades\DB::table('hourly_packages')->orderBy('id')->get();$this->assertCount(3,$items);$this->assertNotNull($items[0]->attribute_id);$this->assertSame($items[0]->attribute_id,$items[1]->attribute_id);$this->assertNull($items[2]->attribute_id);
    }
}
