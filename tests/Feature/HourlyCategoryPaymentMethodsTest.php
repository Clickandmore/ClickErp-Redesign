<?php

namespace Tests\Feature;

use App\Models\Hourly\HourlyServiceCategoryLog;
use App\Services\Hourly\{HourlyBookingRules, HourlyOrderService};
use App\Support\HourlyRentalSettings;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

/** المرحلة 5: طرق الدفع لكل فئة وقناة. */
class HourlyCategoryPaymentMethodsTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    protected function setUp(): void { parent::setUp(); $this->setUpHourlyCategorySchema(); }
    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    private function payload(array $off): array
    {
        $methods = [];
        foreach (HourlyBookingRules::PAYMENT_METHODS as $method) {
            foreach (['web_app', 'employee', 'branch'] as $channel) $methods[$method][$channel] = in_array("$method.$channel", $off, true) ? '0' : '1';
        }
        return ['methods' => $methods];
    }

    public function test_category_payment_methods_are_enforced_per_channel(): void
    {
        $category = $this->category();
        $other = $this->category();
        $item = $this->item($category);
        $otherItem = $this->item($other);
        $district=$this->district('حي الاختبار',24.7,46.7);
        $category->districts()->attach($district->id,['is_active'=>true]);$other->districts()->attach($district->id,['is_active'=>true]);
        $rules = app(HourlyBookingRules::class);
        $book = fn ($itemId, string $method, ?int $admin = null) => app(HourlyOrderService::class)->create(1, ['hourly_package_id' => $itemId, 'address_id' => $this->address(24.7, 46.7), 'payment_method' => $method], $admin);

        // بلا إعداد: كل الطرق مسموحة (ترث)
        $this->assertSame(['moyasar','bank_transfer','cash'], $rules->paymentMethods($item, 'web_app'));

        $this->put(route('admin.hourly-category-payments.update', $category), $this->payload(['moyasar.employee', 'cash.web_app']))
            ->assertRedirect(route('admin.hourly-service-categories.show', $category).'#payments');

        $this->assertSame(['moyasar', 'wallet', 'bank_transfer'], $rules->paymentMethods($item, 'web_app'));
        $this->assertSame(['bank_transfer', 'cash'], $rules->paymentMethods($item, 'employee'));
        $this->assertSame(['moyasar', 'bank_transfer', 'cash'], $rules->paymentMethods($item, 'branch'));

        $this->assertNotNull($book($item->id, 'cash', 7)->id, 'النقدي مسموح للموظف');
        $this->assertNotNull($book($item->id, 'moyasar')->id, 'ميسر مسموح للتطبيق');
        foreach ([[$item->id, 'cash', null], [$item->id, 'moyasar', 7]] as [$id, $method, $admin]) {
            try { $book($id, $method, $admin); $this->fail("$method should be rejected"); }
            catch (ValidationException $e) { $this->assertSame('طريقة الدفع غير متاحة.', $e->errors()['hourly'][0]); }
        }
        $this->assertNotNull($book($otherItem->id, 'cash')->id, 'فئة أخرى ترث الطرق العامة الآمنة');

        $this->get(route('admin.hourly-service-categories.show', $category))->assertOk()
            ->assertSee('name="methods[moyasar][employee]"', false)
            ->assertSee(__('hourly.channel_branch'));
        $this->putJson(route('admin.hourly-category-payments.update', $category), ['methods' => ['cash' => ['web_app' => 'maybe']]])->assertJsonValidationErrors('methods.cash.web_app');
        $this->assertSame(1, HourlyServiceCategoryLog::where('section', 'payments')->count());
    }
}
