<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyCategorySetting, HourlyRentalOrder, HourlyServiceCategory, HourlyServiceCategoryLog};
use App\Services\Hourly\{HourlyCategorySettings, HourlyOrderService, HourlyPaymentService, HourlySlotService};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Artisan, DB};
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

/** المرحلة 4: توريث إعدادات الفئة وتطبيقها فعليًا في التوفر والسعر وإنشاء الطلب. */
class HourlyCategorySettingsTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHourlyCategorySchema();
        Carbon::setTestNow('2030-01-06 10:00:00'); // الأحد
    }

    protected function tearDown(): void { Carbon::setTestNow(); HourlyRentalSettings::flush(); parent::tearDown(); }

    private function configure(HourlyServiceCategory $category, array $settings): void
    {
        HourlyCategorySetting::updateOrCreate(['service_category_id' => $category->id], ['settings' => $settings]);
    }

    private function book(int $itemId, array $extra = [], ?int $admin = null, ?int $address = null): HourlyRentalOrder
    {
        $category=\App\Models\Hourly\HourlyPackage::findOrFail($itemId)->serviceCategory;
        if(!$category->districts()->exists())$category->districts()->attach($this->district('حي الاختبار',24.7,46.7)->id,['is_active'=>true]);
        return app(HourlyOrderService::class)->create(1, $extra + ['hourly_package_id' => $itemId, 'address_id' => $address ?? $this->address(24.7, 46.7), 'payment_method' => 'cash'], $admin);
    }

    private function rejects(callable $call, string $contains): void
    {
        try { $call(); $this->fail('Expected rejection: '.$contains); }
        catch (ValidationException $e) { $this->assertStringContainsString($contains, implode(' ', \Illuminate\Support\Arr::flatten($e->errors()))); }
    }

    public function test_item_over_category_over_general_inheritance(): void
    {
        $settings = new HourlyCategorySettings();
        $category = $this->category();
        $inherits = $this->item($category, ['tax_rate' => null]);
        $own = $this->item($category, ['tax_rate' => 10]);

        $this->assertSame(15.0, (float) $settings->value($inherits, 'tax_rate'), 'العام');
        $this->configure($category, ['tax_rate' => 5, 'auto_assign' => ['web_app' => true], 'working_days' => [5 => ['is_on' => true], 1 => ['order_buffer_hours' => 3]]]);
        $settings = new HourlyCategorySettings();
        $this->assertSame(5.0, $settings->value($inherits, 'tax_rate'), 'الفئة');
        $this->assertSame('category', $settings->source($inherits, 'tax_rate'));
        $this->assertSame(10.0, $settings->value($own, 'tax_rate'), 'العنصر يتقدم');
        $this->assertSame('item', $settings->source($own, 'tax_rate'));
        $this->assertSame(['web_app' => true, 'panel' => false], $settings->value($inherits, 'auto_assign'), 'كل قناة ترث وحدها');
        $this->assertSame(24, $settings->value($inherits, 'reschedule_hours_before'));

        HourlyRentalSettings::persist(['closed_weekdays' => [5, 6]]);
        $days = (new HourlyCategorySettings())->value($inherits, 'working_days');
        $this->assertTrue($days[5]['is_on'], 'الفئة تفتح يومًا مغلقًا عامًا');
        $this->assertFalse($days[6]['is_on'], 'السبت يرث الإغلاق العام');
        $this->assertSame(3, $days[1]['order_buffer_hours']);
        $this->assertSame(0, $days[2]['order_buffer_hours']);
    }

    public function test_settings_form_saves_only_overrides_and_validates(): void
    {
        $category = $this->category();
        $url = route('admin.hourly-category-settings.update', $category);
        $payload = [
            'tax_rate' => '', 'allow_reschedule' => '0', 'show_packages' => 'inherit', 'package_price_display' => 'per_visit',
            'auto_assign' => ['web_app' => '1', 'panel' => 'inherit'], 'auto_complete_mode' => 'time', 'auto_complete_time' => '21:30',
            'same_day_cutoff' => '12:00', 'customer_future_days' => '14',
            'order_form_steppers_mode' => 'custom',
            'order_form_steppers' => [['key' => 'quantity', 'label' => 'الغرف', 'channels' => ['web_app'], 'min' => 1, 'max' => 4, 'scale_price' => '1'], ['key' => 'workers', 'label' => '', 'channels' => ['web_app', 'panel'], 'min' => 1, 'max' => 3, 'scale_price' => '0']],
            'working_days' => [5 => ['is_on' => '0', 'order_buffer_hours' => ''], 0 => ['is_on' => 'inherit', 'order_buffer_hours' => '2']],
        ];
        $this->put($url, $payload)->assertRedirect(route('admin.hourly-service-categories.show', $category).'#settings');
        $stored = HourlyCategorySetting::where('service_category_id', $category->id)->value('settings');
        $this->assertSame([
            'allow_reschedule' => false, 'package_price_display' => 'per_visit', 'auto_assign' => ['web_app' => true],
            'auto_complete_visits_at' => '21:30', 'customer_future_days' => 14, 'same_day_cutoff' => '12:00',
            'order_form_steppers' => [
                ['key' => 'quantity', 'label' => 'الغرف', 'channels' => ['web_app'], 'min' => 1, 'max' => 4, 'scale_price' => true],
                ['key' => 'workers', 'label' => 'workers', 'channels' => ['web_app', 'panel'], 'min' => 1, 'max' => 3, 'scale_price' => false],
            ],
            'working_days' => [0 => ['order_buffer_hours' => 2], 5 => ['is_on' => false]],
        ], $stored);
        $this->assertSame(1, HourlyServiceCategoryLog::where('section', 'settings')->count());

        $this->putJson($url, ['order_form_steppers_mode' => 'inherit', 'tax_rate' => 150])->assertJsonValidationErrors('tax_rate');
        $this->putJson($url, ['order_form_steppers_mode' => 'custom', 'order_form_steppers' => [['key' => 'workers', 'min' => 5, 'max' => 2]]])->assertJsonValidationErrors('order_form_steppers.0.max');
        $this->putJson($url, ['order_form_steppers_mode' => 'inherit', 'auto_complete_mode' => 'time', 'auto_complete_time' => '25:00'])->assertJsonValidationErrors('auto_complete_visits_at');

        $this->get(route('admin.hourly-service-categories.show', $category))->assertOk()->assertSee('name="order_form_steppers[0][key]"', false)->assertSee('value="21:30"', false);
    }

    public function test_price_tax_steppers_channels_and_confirmation_are_applied_on_order(): void
    {
        $category = $this->category();
        $district = $this->district('النرجس', 24.7, 46.7);
        $team = $this->team();
        $category->districts()->attach($district->id, ['team_id' => $team->id, 'is_active' => true]);
        $item = $this->item($category, ['tax_rate' => null, 'price_per_visit' => 105, 'visits_count' => 2, 'extra_worker_price' => 50]);

        $this->configure($category, ['tax_rate' => 5]);
        $order = $this->book($item->id);
        $this->assertEquals(210, $order->total_amount);
        $this->assertEquals(200, $order->subtotal, 'ضريبة الفئة 5%');
        $this->assertEquals(5, $order->tax_rate);
        $this->assertSame('pending_confirmation', $order->status);
        $this->assertSame($team->id, (int) $order->visits()->first()->hourly_team_id, 'فريق التغطية محفوظ حتى مع تعطيل إسناد العاملات');
        $this->assertDatabaseCount('hourly_visit_workers', 0);

        $this->configure($category, [
            'auto_assign' => ['web_app' => true], 'auto_confirm_postpaid' => ['web_app' => true],
            'order_form_steppers' => [['key' => 'quantity', 'label' => 'الغرف', 'channels' => ['web_app'], 'min' => 1, 'max' => 3, 'scale_price' => true], ['key' => 'workers', 'label' => 'العاملات', 'channels' => ['web_app'], 'min' => 1, 'max' => 2, 'scale_price' => false]],
        ]);
        $order = $this->book($item->id, ['quantity' => 2, 'workers_per_visit' => 2]);
        $this->assertEquals(420, $order->total_amount, 'الكمية تضاعف السعر، والعاملة الإضافية مجانية لأن scale_price=false');
        $this->assertSame('active', $order->status, 'تأكيد تلقائي للدفع الآجل عبر التطبيق');
        $this->assertSame($team->id, (int) $order->visits()->first()->hourly_team_id, 'إسناد تلقائي لفريق الحي');
        $this->rejects(fn () => $this->book($item->id, ['workers_per_visit' => 3]), 'العاملات يجب أن يكون بين 1 و2');
        $this->rejects(fn () => $this->book($item->id, ['quantity' => 4]), 'الغرف يجب أن يكون بين 1 و3');
        $panel = $this->book($item->id, [], 7);
        $this->assertSame('pending_confirmation', $panel->status, 'التأكيد التلقائي مفعّل للتطبيق فقط');
        $this->rejects(fn () => $this->book($item->id, ['quantity' => 2], 7), 'الكمية غير متاح');

        // show_packages / show_one_off وقائمة التطبيق
        $oneOff = $this->item($category, ['visits_count' => 1]);
        $this->configure($category, ['show_packages' => false, 'customer_worker_selection' => false]);
        $this->rejects(fn () => $this->book($item->id), 'غير معروض للعملاء');
        $this->assertNotNull($this->book($item->id, [], 7)->id, 'لوحة التحكم تحجز الباقة');
        $this->assertNotNull($this->book($oneOff->id)->id);
        $this->rejects(fn () => $this->book($oneOff->id, ['preferred_maid_id' => 5]), 'اختيار العاملة غير متاح');
        auth('api')->setUser(new \App\Models\ingaz\User(['id' => 1]));
        $ids = collect($this->getJson('/api/app/hourly/packages?city_id=1')->assertOk()->json('data'))->pluck('id')->all();
        $this->assertContains($oneOff->id, $ids);
        $this->assertNotContains($item->id, $ids);
    }

    public function test_calendar_working_days_buffer_and_session_rules_limit_slots(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['start_times' => ['09:00' => 'all', '14:00' => 'all'], 'visits_count' => 3]);
        $slots = fn (string $date, bool $dispatcher = false) => array_column(app(HourlySlotService::class)->forDate($date, 4, 0, $item->toArray(), $dispatcher), 'starts_at');

        $this->configure($category, ['customer_future_days' => 5, 'dispatcher_future_days' => 10, 'dispatcher_past_days' => 2, 'same_day_cutoff' => '09:30', 'working_days' => [3 => ['is_on' => false]]]);
        $this->assertCount(2, $slots('2030-01-07'), 'الاثنين بلا مهلة');
        $this->assertSame([], $slots('2030-01-06'), 'بعد وقت الحجز لليوم نفسه (10:00 > 09:30)');
        $this->assertSame(['2030-01-07 14:00:00'], (function () use ($category, $slots) {
            $this->configure($category, ['working_days' => [1 => ['order_buffer_hours' => 25]]]);
            return $slots('2030-01-07');
        })(), 'مهلة الاثنين 25 ساعة تحذف موعد 09:00');
        $this->configure($category, ['customer_future_days' => 5, 'dispatcher_future_days' => 10, 'dispatcher_past_days' => 2, 'working_days' => [3 => ['is_on' => false]]]);
        $this->assertSame([], $slots('2030-01-09'), 'الأربعاء ليس يوم عمل');
        $this->assertCount(2, $slots('2030-01-09') ?: array_column(app(HourlySlotService::class)->forDate('2030-01-09', 4, 0, array_merge($item->toArray(), ['ignore_closed_days' => true])), 'starts_at'));
        $this->assertSame([], $slots('2030-01-12'), 'بعد أيام العميل');
        $this->assertCount(2, $slots('2030-01-12', true), 'ضمن أيام التشغيل');
        $this->assertSame([], $slots('2030-01-17', true), 'بعد أيام التشغيل');
        $this->assertCount(2, $slots('2030-01-04', true), 'التشغيل يحجز في الماضي ضمن يومين');
        $this->assertSame([], $slots('2030-01-03', true));
        $this->assertSame([], $slots('2030-01-04'), 'العميل لا يحجز في الماضي');

        $this->configure($category, ['handpick_session_dates' => false]);
        $this->rejects(fn () => $this->book($item->id, ['scheduled_starts' => ['2030-01-07 09:00', '2030-01-08 09:00']]), 'اختيار مواعيد كل الجلسات');
        $this->assertNotNull($this->book($item->id, ['scheduled_starts' => ['2030-01-07 09:00', '2030-01-08 09:00']], 7)->id);
        $this->configure($category, []);
        $this->rejects(fn () => $this->book($item->id, ['scheduled_starts' => ['2030-01-07 09:00', '2030-01-07 14:00']]), 'أكثر من جلسة في اليوم');
        $this->configure($category, ['allow_multiple_sessions_per_day' => true]);
        $this->assertCount(2, $this->book($item->id, ['scheduled_starts' => ['2030-01-07 09:00', '2030-01-07 14:00']])->schedule_pattern);
    }

    public function test_per_visit_price_display_collects_first_visit_online(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['total_price' => 300, 'price_per_visit' => 100, 'visits_count' => 3]);
        $this->configure($category, ['package_price_display' => 'per_visit']);
        $order = $this->book($item->id, ['payment_method' => 'bank_transfer']);
        $this->assertEquals(100, $order->package_snapshot['charge_now']);
        $payments = app(HourlyPaymentService::class);
        $this->assertEquals(100, $payments->dueNow($order));
        $this->rejects(fn () => app(HourlyOrderService::class)->confirm($order, 7), 'يجب تأكيد تحصيل المبلغ');
        $payments->collect($order, ['amount' => 100, 'attachment_path' => 'proof.pdf'], 7);
        $order->refresh();
        $this->assertSame('active', $order->status, 'سداد الزيارة الأولى يفعّل الطلب');
        $this->assertSame('partial', $order->payment_status);
        $this->assertEquals(200, $payments->dueNow($order));

        $this->configure($category, []);
        $full = $this->book($item->id, ['payment_method' => 'bank_transfer']);
        $this->assertEquals(300, $payments->dueNow($full), 'إجمالي الباقة افتراضيًا');
    }

    public function test_reschedule_rules_and_auto_complete_job(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['start_times' => ['09:00' => 'all', '14:00' => 'all'], 'visits_count' => 2]);
        $this->configure($category, ['reschedule_hours_before' => 24, 'max_reschedules_per_visit' => 1]);
        $order = $this->book($item->id, ['scheduled_starts' => ['2030-01-08 09:00']]);
        $visit = $order->visits()->where('visit_number', 1)->first();
        $service = app(HourlyOrderService::class);

        $this->assertSame('2030-01-09 14:00:00', $service->reschedule($visit, '2030-01-09 14:00')->scheduled_start_at->format('Y-m-d H:i:s'));
        $this->rejects(fn () => $service->reschedule($visit->fresh(), '2030-01-10 09:00'), 'الحد الأقصى');
        $this->rejects(fn () => $service->reschedule($visit->fresh(), '2030-01-10 09:00', 7), 'الحد الأقصى'); // التشغيل مقيد بالحد أيضًا
        $this->assertSame(1, (int) $visit->fresh()->reschedule_count);
    }

    public function test_reschedule_window_disabled_category_and_auto_complete(): void
    {
        $category = $this->category();
        $item = $this->item($category, ['start_times' => ['09:00' => 'all', '14:00' => 'all']]);
        $order = $this->book($item->id, ['scheduled_starts' => ['2030-01-07 09:00']]);
        $visit = $order->visits()->first();
        $service = app(HourlyOrderService::class);
        $this->rejects(fn () => $service->reschedule($visit, '2030-01-08 09:00'), 'قبل 24 ساعة');
        $this->assertSame('2030-01-08', $service->reschedule($visit, '2030-01-08 09:00', 7)->scheduled_date->toDateString(), 'التشغيل يتجاوز مهلة الساعات');
        $this->configure($category, ['allow_reschedule' => false]);
        $this->rejects(fn () => $service->reschedule($visit->fresh(), '2030-01-09 09:00', 7), 'غير متاحة في هذه الفئة');

        // الإغلاق التلقائي: زيارات اليوم في طلبات نشطة بعد وقت الفئة فقط
        $order->update(['status' => 'active']);
        $visit->fresh()->update(['scheduled_date' => '2030-01-06', 'status' => 'scheduled']);
        Artisan::call('hourly:auto-complete-visits');
        $this->assertSame('scheduled', $visit->fresh()->status, 'لا وقت إغلاق = معطل');
        $this->configure($category, ['auto_complete_visits_at' => '11:00']);
        Artisan::call('hourly:auto-complete-visits');
        $this->assertSame('scheduled', $visit->fresh()->status, 'قبل الوقت (10:00)');
        Carbon::setTestNow('2030-01-06 11:05:00');
        Artisan::call('hourly:auto-complete-visits');
        $this->assertSame('completed', $visit->fresh()->status);
    }
}
