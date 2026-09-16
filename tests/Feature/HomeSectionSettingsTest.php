<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\HomeSectionSettingsController;
use App\Support\HomeSectionSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * إظهار/إخفاء سكاشن الصفحة الرئيسية: الافتراضي «ظاهر» حتى لا يتغير شكل أي موقع
 * مرتبط، والإخفاء يصل للمواقع عبر data.sections_visibility في /api/client/home.
 */
class HomeSectionSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (!Schema::hasTable('home_section_settings')) {
            $this->markTestSkipped('home_section_settings غير موجود — شغّل migrate ثم php artisan test:db-provision.');
        }

        HomeSectionSettings::flush();
    }

    protected function tearDown(): void
    {
        HomeSectionSettings::flush();

        parent::tearDown();
    }

    public function test_every_defined_section_is_visible_by_default(): void
    {
        DB::table('home_section_settings')->delete();
        HomeSectionSettings::flush();

        $visibility = HomeSectionSettings::all();

        $this->assertSame(array_keys(config('home_sections.sections')), array_keys($visibility));
        $this->assertNotContains(false, $visibility, 'لا يُخفى أي قسم قبل أن يُخفيه المسؤول صراحةً.');
        $this->assertTrue(HomeSectionSettings::isVisible('a_key_that_does_not_exist'));
    }

    public function test_update_hides_only_submitted_sections_and_ignores_unknown_keys(): void
    {
        HomeSectionSettings::update(['faq' => false, 'hero' => true, 'not_a_section' => false]);

        $visibility = HomeSectionSettings::all();

        $this->assertFalse($visibility['faq']);
        $this->assertTrue($visibility['hero']);
        $this->assertTrue($visibility['contact']);
        $this->assertArrayNotHasKey('not_a_section', $visibility);
        $this->assertDatabaseMissing('home_section_settings', ['key' => 'not_a_section']);
    }

    public function test_controller_leaves_sections_missing_from_the_request_unchanged(): void
    {
        HomeSectionSettings::update(['blog' => false]);

        $request = Request::create('/admin/home-sections', 'PUT', [
            'sections' => ['faq' => '0', 'hero' => '1'],
        ]);
        $request->setLaravelSession(app('session.store'));

        app(HomeSectionSettingsController::class)->update($request);

        $visibility = HomeSectionSettings::all();

        $this->assertFalse($visibility['faq']);
        $this->assertTrue($visibility['hero']);
        $this->assertFalse($visibility['blog'], 'قسم لم يُرسل في الطلب يجب ألا يتغير.');
    }

    public function test_available_sections_limit_what_the_admin_sees_and_can_change(): void
    {
        HomeSectionSettings::updateAvailableKeys(['contact', 'hero', 'not_a_section']);

        // ترتيب config محفوظ والمفاتيح غير المعرّفة تُتجاهل.
        $this->assertSame(['hero', 'contact'], HomeSectionSettings::availableKeys());
        $this->assertSame(['hero', 'contact'], array_keys(HomeSectionSettings::availableDefinitions()));

        $request = Request::create('/admin/home-sections', 'PUT', [
            'sections' => ['hero' => '0', 'faq' => '0'],
        ]);
        $request->setLaravelSession(app('session.store'));

        app(HomeSectionSettingsController::class)->update($request);

        $visibility = HomeSectionSettings::all();
        $this->assertFalse($visibility['hero']);
        $this->assertTrue($visibility['faq'], 'قسم غير موجود في تصميم الموقع لا يتغير من الشاشة.');

        HomeSectionSettings::updateAvailableKeys(null);
        $this->assertNull(HomeSectionSettings::availableKeys());
        $this->assertSame(array_keys(config('home_sections.sections')), array_keys(HomeSectionSettings::availableDefinitions()));
    }

    public function test_available_sections_route_is_developer_only(): void
    {
        $response = $this->put(route('dev-superadmin.home-sections.available'), ['available' => ['hero']]);

        $response->assertRedirect(route('superadmin.login'));
        $this->assertNotSame(['hero'], HomeSectionSettings::availableKeys());
    }

    public function test_presets_only_reference_defined_sections(): void
    {
        $defined = array_keys(config('home_sections.sections'));

        foreach (HomeSectionSettings::presets() as $key => $preset) {
            $this->assertSame([], array_values(array_diff($preset['sections'], $defined)), "القالب $key يحتوي مفتاحاً غير معرّف.");
        }
    }

    public function test_home_api_exposes_sections_visibility_without_touching_other_keys(): void
    {
        HomeSectionSettings::update(['partners' => false]);

        $response = $this->getJson('/api/client/home');

        $response->assertOk();
        $response->assertJsonPath('data.sections_visibility.partners', false);
        $response->assertJsonPath('data.sections_visibility.hero', true);
        $response->assertJsonStructure(['data' => ['sliders', 'statistics', 'about', 'nationalities', 'counts']]);
    }
}
