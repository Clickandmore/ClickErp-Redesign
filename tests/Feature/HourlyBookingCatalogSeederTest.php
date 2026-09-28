<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyPackage, HourlyServiceCategory};
use App\Services\Hourly\PanelBookingService;
use Database\Seeders\HourlyBookingCatalogSeeder;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

class HourlyBookingCatalogSeederTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    public function test_starter_quotes_scale_with_workers_and_correction_preserves_custom_prices(): void
    {
        $this->setUpHourlyCategorySchema();
        $this->seed(HourlyBookingCatalogSeeder::class);
        $service=app(PanelBookingService::class);
        $single=HourlyPackage::where('code','STARTER-HOURLY-CLEANING-1')->firstOrFail();
        $bundle=HourlyPackage::where('code','STARTER-HOURLY-CLEANING-4')->firstOrFail();
        $this->assertSame(40000,$service->quote($single,['workers_per_visit'=>2])['total']);
        $this->assertSame(144000,$service->quote($bundle,['workers_per_visit'=>2])['total']);
        $single->update(['extra_worker_price'=>100]);
        $bundle->update(['extra_worker_price'=>75]);
        $this->seed(\Database\Seeders\FixHourlyStarterWorkerPricesSeeder::class);
        $this->assertSame(40000,$service->quote($single->fresh(),['workers_per_visit'=>2])['total']);
        $this->assertEquals(75,$bundle->fresh()->extra_worker_price);
        $this->seed(\Database\Seeders\FixHourlyStarterWorkerPricesSeeder::class);
        $this->assertEquals(200,$single->fresh()->extra_worker_price);
    }

    public function test_catalog_reuses_existing_category_and_preserves_edits_on_rerun(): void
    {
        $this->setUpHourlyCategorySchema();
        $category=$this->category(['name_ar'=>'خدمة كل ساعة','slug'=>'existing-hourly']);
        $district=$this->district('النرجس',24.85,46.68);
        $category->districts()->attach($district->id,['is_active'=>true]);
        $this->seed(HourlyBookingCatalogSeeder::class);
        $this->assertSame(4,HourlyServiceCategory::count());
        $this->assertSame(8,HourlyPackage::count());
        foreach (HourlyPackage::all() as $item) {
            $this->assertCount(2,$item->translations);
            [$covered]=app(PanelBookingService::class)->coverage($item,['city_id'=>1,'hourly_district_id'=>$district->id]);
            $this->assertSame($district->id,$covered->id);
        }
        $item=HourlyPackage::first();
        $item->update(['price_per_visit'=>321,'is_active'=>false]);
        $category->districts()->updateExistingPivot($district->id,['is_active'=>false]);
        $this->seed(HourlyBookingCatalogSeeder::class);
        $this->assertSame(4,HourlyServiceCategory::count());
        $this->assertSame(8,HourlyPackage::count());
        $this->assertEquals(321,$item->fresh()->price_per_visit);
        $this->assertFalse($item->fresh()->is_active);
        $this->assertEquals(0,$category->districts()->first()->pivot->is_active);
    }
}
