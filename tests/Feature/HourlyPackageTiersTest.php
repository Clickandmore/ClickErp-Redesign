<?php
namespace Tests\Feature;
use Tests\HourlyTestCase as TestCase;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use App\Models\Hourly\{HourlyPackageTier,HourlyPackage,HourlyCategorySetting};
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Database\Schema\Blueprint;
class HourlyPackageTiersTest extends TestCase {
    use BuildsHourlyCategorySchema;
    protected function setUp():void{parent::setUp();$this->setUpHourlyCategorySchema();}
    protected function tearDown():void{HourlyRentalSettings::flush();parent::tearDown();}
    private function payload(array $extra=[]):array{return $extra+['service_item_form'=>1,'code'=>uniqid('NEW'),'service_category_id'=>$this->category()->id,'title_ar'=>'عنصر','total_price'=>200,'duration_minutes'=>240,'hours_per_visit'=>4,'workers_per_visit'=>1,'validity_days'=>90,'extra_worker_price'=>0,'extra_hour_price'=>0,'transport_fee'=>0,'sort_order'=>0,'city_ids'=>[1],'is_active'=>1,'show_on_app'=>1,'use_general_schedule'=>1];}
    public function test_one_off_is_normalized_and_recurring_defaults_can_be_overridden():void{
        $data=$this->payload(['recurring_months'=>3,'visits_count'=>12]);$this->postJson(route('admin.hourly-packages.store'),$data)->assertOk();$item=HourlyPackage::where('code',$data['code'])->firstOrFail();$this->assertEquals(1,$item->visits_count);$this->assertEquals(0,$item->recurring_months);
        foreach(['weekly_3'=>24,'biweekly'=>4,'monthly'=>2] as $code=>$visits){$data=$this->payload(['package_tier_id'=>HourlyPackageTier::where('code',$code)->value('id'),'recurring_months'=>2]);$this->postJson(route('admin.hourly-packages.store'),$data)->assertOk();$this->assertEquals($visits,HourlyPackage::where('code',$data['code'])->value('visits_count'));}
        $data=$this->payload(['package_tier_id'=>HourlyPackageTier::where('code','weekly_1')->value('id'),'recurring_months'=>0]);$this->postJson(route('admin.hourly-packages.store'),$data)->assertJsonValidationErrors('recurring_months');
        $data['recurring_months']=1;$data['visits_count']=7;$this->postJson(route('admin.hourly-packages.store'),$data)->assertOk();$this->assertEquals(7,HourlyPackage::where('code',$data['code'])->value('visits_count'));
    }
    public function test_management_code_is_immutable_and_seed_is_idempotent():void{
        $tier=HourlyPackageTier::first();$this->get(route('admin.hourly-package-tiers.index'))->assertOk();
        $data=['name_ar'=>'اسم جديد','name_en'=>'New','is_active'=>0,'sort_order'=>99];$this->putJson(route('admin.hourly-package-tiers.update',$tier),$data+['code'=>'changed'])->assertJsonValidationErrors('code');
        $this->put(route('admin.hourly-package-tiers.update',$tier),$data)->assertRedirect();(new \Database\Seeders\HourlyPackageTiersSeeder)->run();$this->assertEquals(10,HourlyPackageTier::count());$this->assertFalse($tier->fresh()->is_active);$this->assertSame('اسم جديد',$tier->fresh()->name_ar);
    }
    public function test_public_tiers_respect_city_category_visibility_and_active_status():void{
        $category=$this->category();$tier=HourlyPackageTier::where('code','weekly_2')->first();$item=$this->item($category,['package_tier_id'=>$tier->id,'visits_count'=>8]);
        $url='/api/app/hourly/package-tiers?city_id=1&service_category_id='.$category->id;$this->getJson($url)->assertOk()->assertJsonPath('data.0.code','weekly_2');
        $this->getJson(str_replace('city_id=1','city_id=2',$url))->assertOk()->assertJsonPath('data',[]);
        HourlyCategorySetting::create(['service_category_id'=>$category->id,'settings'=>['show_packages'=>false]]);$this->getJson($url)->assertOk()->assertJsonPath('data',[]);
        HourlyCategorySetting::where('service_category_id',$category->id)->delete();$tier->update(['is_active'=>false]);$this->getJson($url)->assertOk()->assertJsonPath('data',[]);
    }
    public function test_migration_preserves_unknown_legacy_values_and_visit_counts():void{
        config(['database.connections.tier_legacy'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''],'database.default'=>'tier_legacy']);
        Schema::create('hourly_packages',function(Blueprint $t){$t->id();$t->string('package_type')->nullable();$t->integer('visits_count');$t->integer('recurring_months');});
        DB::table('hourly_packages')->insert([['package_type'=>'ONE-OFF','visits_count'=>1,'recurring_months'=>0],['package_type'=>'Custom','visits_count'=>6,'recurring_months'=>2],['package_type'=>'Custom','visits_count'=>9,'recurring_months'=>3]]);
        (require base_path('database/migrations/2026_09_18_000008_create_hourly_package_tiers.php'))->up();
        $this->assertEquals([1,6,9],DB::table('hourly_packages')->orderBy('id')->pluck('visits_count')->all());$this->assertEquals(0,DB::table('hourly_packages')->whereNull('package_tier_id')->count());
    }
}
