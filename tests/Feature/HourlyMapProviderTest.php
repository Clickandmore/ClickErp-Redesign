<?php

namespace Tests\Feature;

use App\Support\HourlyRentalSettings;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

class HourlyMapProviderTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    protected function setUp(): void { parent::setUp(); $this->setUpHourlyCategorySchema(); }
    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    public function test_free_provider_never_exposes_or_loads_saved_google_key(): void
    {
        config(['services.google_maps.browser_key'=>'environment-test-key']);
        HourlyRentalSettings::persist(['map_provider'=>'osm','google_maps_browser_key'=>'saved-test-key']);
        $html=view('admin.hourly._map-provider')->render();
        $this->assertStringContainsString('"provider":"osm"',$html);
        $this->assertStringContainsString('"key":""',$html);
        $this->assertStringNotContainsString('saved-test-key',$html);
        $this->assertStringNotContainsString('environment-test-key',$html);
    }

    public function test_shared_maps_follow_saved_provider_and_key_precedence(): void
    {
        config(['services.google_maps.browser_key'=>'environment-test-key']);
        HourlyRentalSettings::persist(['map_provider'=>'google','google_maps_browser_key'=>'saved-test-key']);
        $html=view('admin.hourly._map-provider')->render();
        $this->assertStringContainsString('"provider":"google"',$html);
        $this->assertStringContainsString('"key":"saved-test-key"',$html);
        HourlyRentalSettings::persist(['google_maps_browser_key'=>'']);
        $this->assertSame('',HourlyRentalSettings::googleMapsKey());
        config(['services.google_maps.browser_key'=>'']);
        $this->assertSame('osm',HourlyRentalSettings::mapProvider());
    }
}
