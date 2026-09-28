<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyPackage, HourlyServiceCategory, HourlyServiceCategoryLog};
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\HourlyTestCase as TestCase;

/** المرحلة 1: فئات الخدمة ونقل الفئات النصية وربط عناصر الخدمة. */
class HourlyServiceCategoriesTest extends TestCase
{
    use \Tests\Feature\Concerns\BuildsHourlyCategorySchema;

    protected function setUp(): void { parent::setUp(); $this->setUpHourlyCategorySchema(); }

    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    /** الترحيل يُعاد تشغيله بعد إدراج بيانات قديمة ليختبر النقل (وهو آمن لإعادة التشغيل). */
    private function migrate(): void { (require base_path('database/migrations/2026_09_18_000001_create_hourly_service_categories.php'))->up(); }

    private function legacyItem(string $category, string $code): int
    {
        return DB::table('hourly_packages')->insertGetId(['code' => $code, 'category' => $category, 'hours_per_visit' => 4, 'workers_per_visit' => 1, 'visits_count' => 1, 'validity_days' => 30, 'price_per_visit' => 100, 'tax_rate' => 15, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function categoryPayload(array $overrides = []): array
    {
        return $overrides + ['name_ar' => 'تنظيف منازل', 'name_en' => 'Home Cleaning', 'icon_type' => 'library', 'icon' => 'fa-broom', 'color' => '#6861ee', 'is_active' => 1];
    }

    public function test_migration_moves_text_categories_to_rows_without_losing_items(): void
    {
        $a = $this->legacyItem('تنظيف', 'A'); $b = $this->legacyItem('تنظيف', 'B'); $c = $this->legacyItem('مكاتب', 'C');
        $this->migrate();
        $this->migrate(); // إعادة التشغيل آمنة ولا تكرر الفئات
        $this->assertSame(2, HourlyServiceCategory::count());
        $cleaning = HourlyServiceCategory::where('name_ar', 'تنظيف')->firstOrFail();
        $this->assertSame([$a, $b], HourlyPackage::where('service_category_id', $cleaning->id)->orderBy('id')->pluck('id')->all());
        $this->assertNotNull(HourlyPackage::find($c)->service_category_id);
        $this->assertSame('تنظيف', HourlyPackage::find($a)->category, 'الاسم النصي القديم يبقى');
        $this->assertNotSame('', $cleaning->slug);
    }

    public function test_crud_status_order_and_audit_log(): void
    {
        $this->migrate();
        $this->post(route('admin.hourly-service-categories.store'), $this->categoryPayload())->assertRedirect();
        $first = HourlyServiceCategory::firstOrFail();
        $this->assertSame('home-cleaning', $first->slug);
        $this->postJson(route('admin.hourly-service-categories.store'), $this->categoryPayload())->assertJsonValidationErrors('name_ar');
        $this->postJson(route('admin.hourly-service-categories.store'), $this->categoryPayload(['name_ar' => 'أخرى', 'icon' => 'bad icon']))->assertJsonValidationErrors('icon');
        $this->post(route('admin.hourly-service-categories.store'), $this->categoryPayload(['name_ar' => 'مكاتب', 'name_en' => 'Offices']))->assertRedirect();
        $second = HourlyServiceCategory::where('name_ar', 'مكاتب')->firstOrFail();

        // تعديل الاسم يزامن الاسم النصي في العناصر
        $item = HourlyPackage::create(['code' => 'X', 'service_category_id' => $first->id, 'category' => 'تنظيف منازل', 'hours_per_visit' => 4, 'workers_per_visit' => 1, 'visits_count' => 1, 'validity_days' => 30, 'price_per_visit' => 100, 'tax_rate' => 15]);
        $this->put(route('admin.hourly-service-categories.update', $first), $this->categoryPayload(['name_ar' => 'تنظيف المنازل']))->assertRedirect();
        $this->assertSame('تنظيف المنازل', $item->fresh()->category);

        $this->putJson(route('admin.hourly-service-categories.status', $first), ['is_active' => false])->assertOk()->assertJsonPath('is_active', false);
        $this->assertFalse($first->fresh()->is_active);

        $this->put(route('admin.hourly-service-categories.move', [$second, 'up']));
        $this->assertSame([$second->id, $first->id], HourlyServiceCategory::ordered()->pluck('id')->all());

        $this->delete(route('admin.hourly-service-categories.destroy', $first))->assertSessionHasErrors('category');
        $this->assertNotNull($first->fresh());
        $this->delete(route('admin.hourly-service-categories.destroy', $second))->assertRedirect(route('admin.hourly-service-categories.index'));
        $this->assertSoftDeleted($second);

        $this->assertSame(['create', 'create', 'edit', 'status', 'reorder', 'delete'], HourlyServiceCategoryLog::orderBy('id')->pluck('type')->all());
        $this->assertSame(7, HourlyServiceCategoryLog::first()->admin_id);
    }

    public function test_pages_render_and_item_form_requires_category(): void
    {
        $this->migrate();
        $category = HourlyServiceCategory::create(['name_ar' => 'تنظيف', 'name_en' => 'Cleaning', 'slug' => 'cleaning', 'icon' => 'fa-broom']);
        $this->get(route('admin.hourly-service-categories.index'))->assertOk()->assertSee('تنظيف')->assertSee(route('admin.hourly-service-categories.show', $category), false);
        $this->get(route('admin.hourly-service-categories.show', $category))->assertOk()->assertSee(__('hourly.total_orders'))->assertSee(__('hourly.tab_payments'));

        foreach (['ar' => 'اسم الفئة وصورتها وأيقونتها', 'en' => 'Service name, image and icon'] as $locale => $heading) {
            app()->setLocale($locale);
            $this->get(route('admin.hourly-service-categories.show', $category))
                ->assertOk()->assertSee($heading)
                ->assertSee('dir="'.($locale === 'ar' ? 'rtl' : 'ltr').'"', false)
                ->assertSee(__('hourly_category_ui.closures'))
                ->assertSee(__('hourly_category_ui.notes_hint'))
                ->assertDontSee('hourly_category_ui.');
        }
        app()->setLocale('ar');

        $payload = ['service_item_form' => 1, 'code' => 'NEW', 'title_ar' => 'تنظيف', 'total_price' => 200, 'duration_minutes' => 240, 'hours_per_visit' => 4, 'workers_per_visit' => 1, 'visits_count' => 1, 'validity_days' => 30, 'extra_worker_price' => 0, 'extra_hour_price' => 0, 'transport_fee' => 0, 'tax_rate' => 15, 'sort_order' => 0, 'city_ids' => [1], 'is_active' => 1, 'use_general_schedule' => 1];
        $this->postJson(route('admin.hourly-packages.store'), $payload)->assertJsonValidationErrors('service_category_id');
        $this->postJson(route('admin.hourly-packages.store'), $payload + ['service_category_id' => $category->id])->assertOk();
        $item = HourlyPackage::where('code', 'NEW')->firstOrFail();
        $this->assertSame($category->id, $item->service_category_id);
        $this->assertSame('تنظيف', $item->category);

        $this->get(route('admin.hourly-packages.create', ['service_category_id' => $category->id]))->assertOk()->assertSee('name="service_category_id"', false);
        $this->get(route('admin.hourly-service-items.index', ['category' => $category->id]))->assertOk()->assertSee('NEW')->assertSee('name="service_category_id" value="'.$category->id.'"', false);
    }

    public function test_category_routes_are_gated(): void
    {
        $this->migrate();
        $names = [];
        foreach (app('router')->getRoutes() as $route) {
            if (!str_starts_with($route->getName() ?? '', 'admin.hourly-service-categories.')) continue;
            $names[] = $route->getName();
            foreach (['feature.hourly_rental', 'admin', 'admin.route.permission'] as $middleware) $this->assertContains($middleware, $route->gatherMiddleware(), $route->getName());
            $this->assertArrayHasKey($route->getName(), config('hourly_permissions'));
        }
        $this->assertContains('admin.hourly-service-categories.show', $names);
        config(['superadmin_modules.hourly_rental.default_enabled'=>false]);
        $this->get(route('admin.hourly-service-categories.index'))->assertNotFound();
    }
}
