<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyCategorySetting, HourlyRentalOrder};
use App\Services\Hourly\HourlyBookingRules;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

class HourlyBookingOptionsTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHourlyCategorySchema();
        Carbon::setTestNow('2030-01-06 07:00:00');
        auth('api')->setUser(new \App\Models\ingaz\User(['id'=>1]));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    private function service(array $settings, array $attributes = []): array
    {
        $category=$this->category();
        $district=\App\Models\Hourly\ServiceDistrict::first() ?? $this->district('حي',24.7,46.7);
        $category->districts()->attach($district->id,['is_active'=>true]);
        HourlyCategorySetting::create(['service_category_id'=>$category->id,'settings'=>$settings]);
        $item=$this->item($category,$attributes + ['for_home'=>true,'for_company'=>true]);
        return [$item,$district,['hourly_package_id'=>$item->id,'address_id'=>$this->address(24.7,46.7),'payment_method'=>'cash']];
    }

    public function test_enabled_options_are_exposed_validated_saved_and_returned_by_customer_api(): void
    {
        [$item,,$data]=$this->service(['show_material_option'=>true,'show_service_type_field'=>true]);
        $this->getJson('/api/app/hourly/packages/'.$item->id.'/config')->assertOk()
            ->assertJsonPath('data.fields.bring_materials.type','boolean')
            ->assertJsonPath('data.fields.service_type.options.1.value','company');
        $response=$this->postJson('/api/app/hourly/orders',$data+['bring_materials'=>true,'service_type'=>'company'])->assertCreated();
        $order=HourlyRentalOrder::findOrFail($response->json('data.id'));
        $this->assertSame(['bring_materials'=>true,'service_type'=>'company'],$order->package_snapshot['booking_options']);
        $this->getJson('/api/app/hourly/orders/'.$order->id)->assertOk()
            ->assertJsonPath('data.booking_options.bring_materials',true)
            ->assertJsonPath('data.booking_options.service_type','company');
    }

    public function test_hidden_options_cannot_be_submitted_and_unsupported_service_types_are_rejected(): void
    {
        [$item,,$data]=$this->service([]);
        $this->postJson('/api/app/hourly/orders',$data+['bring_materials'=>true])->assertStatus(422);
        $this->postJson('/api/app/hourly/orders',$data+['service_type'=>'home'])->assertStatus(422);
        [$company,,$companyData]=$this->service(['show_service_type_field'=>true],['for_home'=>false]);
        $this->postJson('/api/app/hourly/orders',$companyData+['service_type'=>'home'])->assertStatus(422);
        $this->postJson('/api/app/hourly/orders',$companyData+['service_type'=>'company'])->assertCreated();
    }

    public function test_tax_and_fee_presentation_changes_without_changing_total(): void
    {
        [$item]=$this->service(['show_tax_separately'=>['web_app'=>true],'show_custom_fee_separately'=>true],
            ['price_per_visit'=>100,'transport_included'=>false,'transport_fee'=>15]);
        $url='/api/app/hourly/packages/'.$item->id.'?city_id=1';
        $response=$this->getJson($url)->assertOk()->assertJsonPath('data.pricing.total_amount',115)
            ->assertJsonPath('data.pricing.presentation.lines.1.code','transport')
            ->assertJsonPath('data.pricing.presentation.lines.1.amount',15);
        $this->assertNotNull($response->json('data.pricing.presentation.tax_line'));
        $item->update(['settings_overrides'=>['show_tax_separately'=>['web_app'=>false],'show_custom_fee_separately'=>false]]);
        $this->getJson($url)->assertOk()->assertJsonPath('data.pricing.total_amount',115)
            ->assertJsonCount(1,'data.pricing.presentation.lines')->assertJsonPath('data.pricing.presentation.tax_line',null);
    }

    public function test_hidden_time_options_support_date_only_booking_using_a_real_slot(): void
    {
        [$item,$district,$data]=$this->service(['show_time_options_app'=>false],['start_times'=>['09:00'=>'all','14:00'=>'all']]);
        $this->getJson('/api/app/hourly/packages/'.$item->id.'/config')->assertOk()->assertJsonPath('data.booking.time_selection','date');
        $this->getJson('/api/app/hourly/slots?city_id=1&hourly_package_id='.$item->id.'&district_id='.$district->id.'&date=2030-01-09')
            ->assertOk()->assertJsonPath('time_selection','date')->assertJsonCount(1,'data');
        $response=$this->postJson('/api/app/hourly/orders',$data+['scheduled_dates'=>['2030-01-09']])->assertCreated();
        $visit=HourlyRentalOrder::findOrFail($response->json('data.id'))->visits()->first();
        $this->assertSame('2030-01-09 09:00:00',$visit->scheduled_start_at->format('Y-m-d H:i:s'));
        $this->postJson('/api/app/hourly/orders/'.$visit->hourly_rental_order_id.'/visits/'.$visit->id.'/reschedule',
            ['scheduled_date'=>'2030-01-10'])->assertOk();
        $this->assertSame('2030-01-10 09:00:00',$visit->fresh()->scheduled_start_at->format('Y-m-d H:i:s'));
        $this->postJson('/api/app/hourly/orders',$data+['scheduled_dates'=>['2030-01-09'],'scheduled_starts'=>['2030-01-09 14:00']])->assertStatus(422);
    }

    public function test_date_only_booking_obeys_working_days_and_requires_hidden_time_options(): void
    {
        [,,$data]=$this->service(['show_time_options_app'=>false,'working_days'=>[3=>['is_on'=>false]]]);
        $this->postJson('/api/app/hourly/orders',$data+['scheduled_dates'=>['2030-01-09']])->assertStatus(422);
        [,,$withTimes]=$this->service(['show_time_options_app'=>true]);
        $this->postJson('/api/app/hourly/orders',$withTimes+['scheduled_dates'=>['2030-01-09']])->assertStatus(422);
    }

    public function test_catalog_uses_stepper_minimum_and_order_preserves_quantity(): void
    {
        [$item,,$data]=$this->service(['order_form_steppers'=>[
            ['key'=>'quantity','label'=>'غرف','channels'=>['web_app'],'min'=>2,'max'=>4,'scale_price'=>true],
        ]]);
        $this->getJson('/api/app/hourly/packages?city_id=1')->assertOk()
            ->assertJsonPath('data.0.pricing.quantity',2)->assertJsonPath('data.0.pricing.total_amount',230);
        $this->postJson('/api/app/hourly/orders',$data+['quantity'=>5])->assertStatus(422);
        $response=$this->postJson('/api/app/hourly/orders',$data+['quantity'=>3])->assertCreated();
        $order=HourlyRentalOrder::findOrFail($response->json('data.id'));
        $this->assertSame(3,$order->package_snapshot['quantity']);
        $this->assertEquals(345,$order->total_amount);
    }
}
