<?php

namespace Tests\Feature;

use App\Support\HourlyRentalSettings;
use Illuminate\Support\ViewErrorBag;
use Tests\HourlyTestCase as TestCase;

class HourlyRentalSettingsViewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.connections.hourly_settings_view' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'hourly_settings_view',
        ]);
        \Illuminate\Support\Facades\Schema::create('new_settings', function (\Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value');
            $table->timestamps();
        });
    }

    public function test_settings_render_form_and_conditionals_without_literal_blade(): void
    {
        app('view')->getFinder()->prependLocation(base_path('tests/Fixtures/hourly-views'));
        config(['services.google_maps.browser_key'=>'']);
        $data = [
            'hourly'=>array_replace(HourlyRentalSettings::defaults(), ['is_enabled'=>true,'city_ids'=>[1]]),
            'readiness'=>[['done'=>true,'label'=>'مرحلة جاهزة'],['done'=>false,'label'=>'مرحلة قادمة']],
            'blockers'=>[], 'shelters'=>collect(),
            'cities'=>collect([(object)['id'=>1,'name_ar'=>'الرياض','name_en'=>'Riyadh']]),
            'errors'=>new ViewErrorBag(),
        ];
        $html = view('admin.hourly_rental_settings.index',$data)->render();
        foreach (['{{','@if','@endif','@csrf','@method','@php','@endphp'] as $literal) {
            $this->assertStringNotContainsString($literal,$html);
        }
        $this->assertStringContainsString('1/2',$html);
        $this->assertStringContainsString('name="_token"',$html);
        $this->assertStringContainsString('name="_method" value="PUT"',$html);
        $this->assertStringContainsString('name="map_provider"',$html);
        $this->assertStringContainsString('name="staff_app_driver_stop_rate"',$html);
        $this->assertStringContainsString('name="staff_app_worker_hour_rate"',$html);
        $this->assertStringContainsString('name="staff_app_stop_radius_meters"',$html);
        $this->assertStringContainsString('name="staff_app_driver_acceptance"',$html);
        $this->assertStringNotContainsString('لا يمكن إطفاء الموديل الآن.',$html);
        $data['blockers']=['توجد زيارة جارية'];
        $html = view('admin.hourly_rental_settings.index',$data)->render();
        $this->assertStringContainsString('توجد زيارة جارية',$html);
    }
}
