<?php
namespace Tests\Feature;
use Tests\HourlyTestCase as TestCase;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema};
use App\Models\Hourly\{HourlyCategorySetting,HourlyDriver};
use App\Services\Hourly\{HourlyOrderService,HourlyWorkerAssignment};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\DB;
class HourlyWorkerAssignmentTest extends TestCase {
    use BuildsHourlyCategorySchema,BuildsHourlyWorkerSchema;
    protected function setUp():void {parent::setUp();$this->setUpHourlyCategorySchema();$this->buildWorkers();}
    protected function tearDown():void {HourlyRentalSettings::flush();parent::tearDown();}
    public function test_assignment_requires_ready_team_worker_and_prevents_double_booking():void {
        $category=$this->category();$district=$this->district('District',24.7,46.7);$team=$this->team();
        $category->districts()->attach($district->id,['is_active'=>true,'team_id'=>$team->id]);$item=$this->item($category);
        HourlyCategorySetting::create(['service_category_id'=>$category->id,'settings'=>['auto_assign'=>['web_app'=>true],'customer_worker_selection'=>true,'assign_by_product_attribute'=>true]]);
        DB::table('nationalities')->insert([['id'=>1],['id'=>2]]);$item->nationalities()->attach(1);
        foreach([1,2,3] as $id){
            DB::table('maids')->insert(['id'=>$id,'name'=>'Worker '.$id,'nationality_id'=>$id===3?2:1,'rental_mode'=>'hourly','rental_source'=>'outside','arrival_date'=>$id===2?null:now()->toDateString(),'hourly_settings'=>json_encode(['work_schedule'=>array_fill_keys(array_keys(HourlyDriver::DAYS),['mode'=>'all_day'])])]);
            DB::table('maid_jobs')->insert(['maid_id'=>$id,'job_type_id'=>2]);
            DB::table('contracts')->insert(['id'=>$id,'maid_id'=>$id,'status'=>'shelter']);
            DB::table('contract_infos')->insert(['contract_id'=>$id,'shelter_id'=>1,'is_outside_shelter'=>false,'wants_to_work'=>'yes']);
            DB::table('hourly_team_members')->insert(['hourly_team_id'=>$team->id,'member_type'=>'maid','member_id'=>$id,'role'=>'primary','is_active'=>true]);
        }
        $this->assertSame([1],app(HourlyWorkerAssignment::class)->candidates($item,$team->id,true)->pluck('maids.id')->all());
        $root=\App\Models\Hourly\HourlyProductAttribute::create(['service_category_id'=>$category->id,'name_ar'=>'Size','code'=>'size']);
        $attribute=\App\Models\Hourly\HourlyProductAttribute::create(['service_category_id'=>$category->id,'parent_id'=>$root->id,'name_ar'=>'Studio','code'=>'studio']);
        $item->update(['attribute_id'=>$attribute->id]);
        $this->assertSame([],app(HourlyWorkerAssignment::class)->candidates($item,$team->id,true)->pluck('maids.id')->all());
        \App\Models\ingaz\Maid::findOrFail(1)->productAttributes()->attach($attribute->id);
        $this->assertSame([1],app(HourlyWorkerAssignment::class)->candidates($item,$team->id,true)->pluck('maids.id')->all());
        $worker=\App\Models\ingaz\Maid::findOrFail(1);$workerSettings=$worker->hourly_settings;
        $worker->forceFill(['hourly_settings'=>$workerSettings+['service_category_ids'=>[]]])->save();
        $this->assertSame([],app(HourlyWorkerAssignment::class)->candidates($item,$team->id,true)->pluck('maids.id')->all());
        $worker->forceFill(['hourly_settings'=>$workerSettings+['service_category_ids'=>[$category->id]]])->save();
        $this->assertSame([1],app(HourlyWorkerAssignment::class)->candidates($item,$team->id,true)->pluck('maids.id')->all());
        $data=['hourly_package_id'=>$item->id,'address_id'=>$this->address(24.7,46.7),'payment_method'=>'cash','preferred_maid_id'=>1,'scheduled_starts'=>[now()->addDays(3)->format('Y-m-d').' 08:00']];
        $order=app(HourlyOrderService::class)->create(1,$data);
        $this->assertDatabaseHas('hourly_visit_workers',['hourly_visit_id'=>$order->visits()->first()->id,'maid_id'=>1]);
        try{app(HourlyOrderService::class)->create(1,$data);$this->fail('Duplicate assignment must be rejected');}catch(\Illuminate\Validation\ValidationException $e){$this->assertArrayHasKey('hourly',$e->errors());}
        $this->assertSame(1,DB::table('hourly_rental_orders')->count());
        $this->assertSame(1,DB::table('hourly_visit_workers')->count());
        $this->getJson('/api/app/hourly/slots?city_id=1&hourly_package_id='.$item->id.'&district_id='.$district->id.'&date='.now()->addDays(3)->toDateString())->assertOk()->assertJsonPath('data',[]);
    }
}
