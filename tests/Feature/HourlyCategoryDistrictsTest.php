<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyRentalOrder, HourlyServiceCategoryLog};
use App\Services\Hourly\HourlyOrderService;
use App\Support\HourlyRentalSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

/** المرحلة 2: مناطق خدمة الفئة ورفض الحجز خارجها. */
class HourlyCategoryDistrictsTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    protected function setUp(): void { parent::setUp(); $this->setUpHourlyCategorySchema(); }
    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    private function book(int $itemId, int $addressId, array $extra = []): HourlyRentalOrder
    {
        return app(HourlyOrderService::class)->create(1, $extra + ['hourly_package_id' => $itemId, 'address_id' => $addressId, 'payment_method' => 'cash']);
    }

    private function rejects(callable $call, string $contains): void
    {
        try { $call(); $this->fail('Expected rejection: '.$contains); }
        catch (ValidationException $e) { $this->assertStringContainsString($contains, implode(' ', $e->errors()['hourly'] ?? [])); }
    }

    public function test_booking_requires_an_active_linked_district_once_coverage_is_defined(): void
    {
        $category = $this->category();
        $item = $this->item($category);
        $narjis = $this->district('النرجس', 24.80, 46.60);
        $malqa = $this->district('الملقا', 24.70, 46.60);
        $yasmin = $this->district('الياسمين', 24.60, 46.60);

        // لا مناطق معرّفة: يبقى الحجز على نطاق المدينة كما كان قبل الترحيل
        $this->rejects(fn()=> $this->book($item->id, $this->address(24.80, 46.60)), 'لا توجد مناطق');

        $team = $this->team();
        $category->districts()->attach([$narjis->id => ['team_id' => $team->id, 'is_active' => true], $malqa->id => ['team_id' => null, 'is_active' => false]]);

        $order = $this->book($item->id, $this->address(24.801, 46.601));
        $this->assertSame($narjis->id, (int) $order->service_district_id);
        $this->assertSame($category->id, (int) $order->service_category_id);

        $this->rejects(fn () => $this->book($item->id, $this->address(24.70, 46.60)), 'متوقفة مؤقتًا');
        $this->rejects(fn () => $this->book($item->id, $this->address(24.60, 46.60)), 'خارج مناطق خدمة');
        $this->rejects(fn () => $this->book($item->id, $this->address(25.50, 47.50)), 'تعذر تحديد حي');

        // بلا حدود مرسومة: يُطابق اسم الحي في العنوان، أو يُحدد صراحة
        DB::table('hourly_service_districts')->where('id', $narjis->id)->update(['polygon' => null]);
        $this->assertSame($narjis->id, (int) $this->book($item->id, $this->address(30, 50, 'حي النرجس'))->service_district_id);
        $this->assertSame($narjis->id, (int) $this->book($item->id, $this->address(30, 50), ['district_id' => $narjis->id])->service_district_id);
        $this->rejects(fn () => $this->book($item->id, $this->address(30, 50), ['district_id' => $yasmin->id]), 'خارج مناطق خدمة');

        // حي متوقف في الدليل أو فئة متوقفة
        $narjis->update(['is_active' => false]);
        $this->rejects(fn () => $this->book($item->id, $this->address(30, 50, 'النرجس')), 'متوقفة مؤقتًا');
        $narjis->update(['is_active' => true]);
        $category->update(['is_active' => false]);
        $this->rejects(fn () => $this->book($item->id, $this->address(30, 50, 'النرجس')), __('hourly.category_inactive'));
    }

    public function test_admin_manages_category_districts_individually_and_in_bulk(): void
    {
        $category = $this->category();
        $a = $this->district('النرجس', 24.8, 46.6); $b = $this->district('الملقا', 24.7, 46.6); $c = $this->district('الياسمين', 24.6, 46.6);
        $jeddah = $this->district('الروضة', 21.5, 39.2, 2);
        $team = $this->team('فريق الشمال'); $other = $this->team('فريق الجنوب');
        $show = route('admin.hourly-service-categories.show', $category);

        $this->post(route('admin.hourly-category-districts.store', $category), ['city_id' => 1, 'district_ids' => [$a->id, $b->id], 'team_id' => $team->id, 'is_active' => 1])->assertRedirect($show.'#districts');
        $this->assertSame(2, $category->districts()->count());
        $this->postJson(route('admin.hourly-category-districts.store', $category), ['city_id' => 1, 'district_ids' => [$a->id]])->assertJsonValidationErrors('district_ids');
        $this->postJson(route('admin.hourly-category-districts.store', $category), ['city_id' => 1, 'district_ids' => [$jeddah->id]])->assertJsonValidationErrors('district_ids.0');

        $this->putJson(route('admin.hourly-category-districts.update', [$category, $a]), ['is_active' => false])->assertOk()->assertJsonPath('is_active', false)->assertJsonPath('team', 'فريق الشمال');
        $this->putJson(route('admin.hourly-category-districts.update', [$category, $c]), ['is_active' => true])->assertNotFound();

        $this->get($show)->assertOk()->assertSee('الرياض')->assertSee('النرجس')->assertSee('فريق الشمال')->assertSee('data-count="inactive">1<', false);

        $this->put(route('admin.hourly-category-districts.bulk', $category), ['city_id' => 1, 'action' => 'activate'])->assertRedirect();
        $this->assertSame(2, $category->districts()->wherePivot('is_active', true)->count());
        $this->putJson(route('admin.hourly-category-districts.bulk', $category), ['city_id' => 1, 'action' => 'team'])->assertJsonValidationErrors('team_id');
        $this->put(route('admin.hourly-category-districts.bulk', $category), ['city_id' => 1, 'action' => 'team', 'team_id' => $other->id])->assertRedirect();
        $this->assertSame([$other->id, $other->id], $category->districts()->pluck('team_id')->map(fn ($v) => (int) $v)->all());

        $this->delete(route('admin.hourly-category-districts.destroy', [$category, $b]))->assertRedirect();
        $this->assertSame([$a->id], $category->districts()->pluck('hourly_service_districts.id')->all());
        $category->districts()->attach($jeddah->id);
        $this->delete(route('admin.hourly-category-districts.destroy-city', [$category, 1]))->assertRedirect();
        $this->assertSame([$jeddah->id], $category->districts()->pluck('hourly_service_districts.id')->all(), 'حذف الكل يخص المدينة فقط');

        $this->assertSame(['add', 'edit', 'bulk_activate', 'bulk_team', 'remove', 'remove_all'], HourlyServiceCategoryLog::where('section', 'districts')->orderBy('id')->pluck('type')->all());
    }
}
