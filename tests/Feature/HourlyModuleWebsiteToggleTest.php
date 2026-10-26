<?php

namespace Tests\Feature;

use App\Support\HourlyRentalSettings;
use App\Support\SystemModuleManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** مفتاح «العرض في الموقع الخارجي» في كارد إيجار الساعات بصفحة الموديولات. */
class HourlyModuleWebsiteToggleTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        HourlyRentalSettings::flush();
    }

    protected function tearDown(): void
    {
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    private function save(array $extra): void
    {
        $profile = SystemModuleManager::productProfileKey();
        // تُرسل الموديلات بحالتها الحالية حتى لا يغيّر الحفظ شيئًا غير المفتاح.
        $modules = array_fill_keys(SystemModuleManager::requiredModules($profile), '1');
        if (HourlyRentalSettings::isEnabled()) $modules['hourly_rental'] = '1';

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.modules.update'), [
                'current_product_profile' => $profile,
                'product_profile' => $profile,
                'default_dashboard' => SystemModuleManager::defaultDashboardKey(),
                'modules' => $modules,
            ] + $extra)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dev-superadmin.modules'));
        HourlyRentalSettings::flush();
    }

    /** @test */
    public function the_module_card_switch_hides_and_shows_the_website(): void
    {
        $this->save(['hourly_website_field' => '1']);
        $this->assertFalse(HourlyRentalSettings::all()['show_on_website']);

        $this->save(['hourly_website_field' => '1', 'hourly_show_on_website' => '1']);
        $this->assertTrue(HourlyRentalSettings::all()['show_on_website']);
    }

    /** @test */
    public function changing_the_switch_flushes_the_external_sites_cache(): void
    {
        $this->save(['hourly_website_field' => '1', 'hourly_show_on_website' => '1']);
        $version = \App\Support\ExternalSiteCacheSettings::all()['version'];

        $this->save(['hourly_website_field' => '1']);
        $this->assertSame($version + 1, \App\Support\ExternalSiteCacheSettings::all()['version']);

        // حفظ بنفس القيمة لا يُبطل كاش المواقع.
        $this->save(['hourly_website_field' => '1']);
        $this->assertSame($version + 1, \App\Support\ExternalSiteCacheSettings::all()['version']);
    }

    /** @test */
    public function saving_without_the_card_field_keeps_the_current_value(): void
    {
        HourlyRentalSettings::persist(['show_on_website' => false]);
        $this->save([]);
        $this->assertFalse(HourlyRentalSettings::all()['show_on_website']);
    }

    /** @test */
    public function the_modules_page_renders_the_switch(): void
    {
        if (! array_key_exists('hourly_rental', SystemModuleManager::modules())) {
            $this->markTestSkipped('موديول الساعات غير معرّف في هذا التثبيت.');
        }

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->get(route('dev-superadmin.modules'))
            ->assertOk()
            ->assertSee('name="hourly_show_on_website"', false)
            ->assertSee('العرض في الموقع الخارجي');
    }
}
