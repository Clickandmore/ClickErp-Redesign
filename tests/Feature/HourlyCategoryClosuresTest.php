<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyCategoryClosure, HourlyServiceCategoryLog};
use App\Services\Hourly\{HourlyOrderService, HourlySlotService};
use App\Support\HourlyRentalSettings;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

/** المرحلة 3: الإغلاقات المؤقتة للفئة واستثناؤها من التوفر. */
class HourlyCategoryClosuresTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    protected function setUp(): void { parent::setUp(); $this->setUpHourlyCategorySchema(); }
    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    public function test_closures_remove_slots_per_district_unless_item_ignores_closed_days(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['start_times' => ['09:00' => 'all', '14:00' => 'all']]);
        $north = $this->district('الشمال', 24.8, 46.6); $south = $this->district('الجنوب', 24.6, 46.6);
        $category->districts()->attach([$north->id => ['team_id' => null, 'is_active' => true], $south->id => ['team_id' => null, 'is_active' => true]]);
        $date = now()->addDays(3)->toDateString();
        $slots = fn (array $extra = []) => array_column(app(HourlySlotService::class)->forDate($date, 4, 0, array_merge($item->toArray(), $extra)), 'starts_at');

        $this->assertCount(2, $slots());
        // إغلاق صباحي لحي الشمال فقط
        HourlyCategoryClosure::create(['service_category_id' => $category->id, 'starts_at' => $date.' 08:00', 'ends_at' => $date.' 12:00', 'district_ids' => [$north->id]]);
        $this->assertSame([$date.' 14:00:00'], $slots(['service_district_id' => $north->id]));
        $this->assertCount(2, $slots(['service_district_id' => $south->id]));
        // إغلاق الكل يتقاطع مع زيارة الساعة 14:00 (تنتهي 18:00)
        HourlyCategoryClosure::create(['service_category_id' => $category->id, 'starts_at' => $date.' 17:00', 'ends_at' => $date.' 23:00']);
        $this->assertSame([$date.' 09:00:00'], $slots(['service_district_id' => $south->id]));
        $this->assertSame([], $slots(['service_district_id' => $north->id]));
        // العنصر الذي يتجاهل أيام الإغلاق
        $this->assertCount(2, $slots(['service_district_id' => $north->id, 'ignore_closed_days' => true]));
        // إغلاق فئة أخرى لا يؤثر
        $other = $this->category();
        HourlyCategoryClosure::create(['service_category_id' => $other->id, 'starts_at' => $date.' 00:00', 'ends_at' => $date.' 23:59']);
        $this->assertSame([$date.' 09:00:00'], $slots(['service_district_id' => $south->id]));

        // إنشاء الطلب يرفض الموعد المغلق للحي، ويقبله للحي المفتوح
        $book = fn (int $address) => app(HourlyOrderService::class)->create(1, ['hourly_package_id' => $item->id, 'address_id' => $address, 'payment_method' => 'cash', 'scheduled_starts' => [$date.' 09:00']]);
        $this->assertNotNull($book($this->address(24.6, 46.6))->id);
        $this->expectException(ValidationException::class);
        $book($this->address(24.8, 46.6));
    }

    public function test_admin_adds_lists_deletes_and_clears_closures(): void
    {
        $category = $this->category();
        $district = $this->district('الشمال', 24.8, 46.6);
        $outside = $this->district('خارج الفئة', 24.5, 46.6);
        $category->districts()->attach($district->id);
        $store = route('admin.hourly-category-closures.store', $category);

        $this->post($store, ['starts_at' => '2030-01-01T08:00', 'ends_at' => '2030-01-02T08:00', 'district_ids' => [$district->id], 'reason' => 'صيانة'])->assertRedirect(route('admin.hourly-service-categories.show', $category).'#operation');
        $this->postJson($store, ['starts_at' => '2030-01-02T08:00', 'ends_at' => '2030-01-01T08:00'])->assertJsonValidationErrors('ends_at');
        $this->postJson($store, ['starts_at' => '2030-01-01T08:00', 'ends_at' => '2030-01-02T08:00', 'district_ids' => [$outside->id]])->assertJsonValidationErrors('district_ids.0');
        $this->post($store, ['starts_at' => '2030-02-01T00:00', 'ends_at' => '2030-02-03T00:00'])->assertRedirect();
        $this->assertSame([[$district->id], null], $category->closures()->orderBy('id')->pluck('district_ids')->all());

        $this->get(route('admin.hourly-service-categories.show', $category))->assertOk()->assertSee('صيانة')->assertSee('كل الأحياء')->assertSee('مسح كل الإغلاقات');

        $first = $category->closures()->orderBy('id')->first();
        $otherCategory = $this->category();
        $this->delete(route('admin.hourly-category-closures.destroy', [$otherCategory, $first]))->assertNotFound();
        $this->delete(route('admin.hourly-category-closures.destroy', [$category, $first]))->assertRedirect();
        $this->assertSame(1, $category->closures()->count());
        $this->delete(route('admin.hourly-category-closures.clear', $category))->assertRedirect();
        $this->assertSame(0, $category->closures()->count());
        $this->assertSame(['add', 'add', 'remove', 'clear'], HourlyServiceCategoryLog::where('section', 'closures')->orderBy('id')->pluck('type')->all());
    }
}
