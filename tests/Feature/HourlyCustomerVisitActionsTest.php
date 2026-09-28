<?php

namespace Tests\Feature;

use App\Models\Hourly\HourlyRentalOrder;
use App\Models\ingaz\User;
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Tests\Feature\Concerns\BuildsHourlyCategorySchema;
use Tests\HourlyTestCase as TestCase;

/**
 * إجراءات العميل الثلاثة على زيارة مشتراة: الإلغاء، الساعة الإضافية، التقييم.
 * (api/app/hourly/orders/{order}/visits/{visit}/{cancel|extra-hour|rate})
 */
class HourlyCustomerVisitActionsTest extends TestCase
{
    use BuildsHourlyCategorySchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHourlyCategorySchema();
        Carbon::setTestNow(Carbon::parse('2030-01-06 07:00:00'));
        Schema::create('users', function (Blueprint $t) { $t->softDeletes(); $t->id(); $t->string('name'); });
        DB::table('users')->insert([['id' => 1, 'name' => 'عميل'], ['id' => 2, 'name' => 'عميل آخر']]);
        Schema::table('user_addresses', function (Blueprint $t) { foreach (['title','name','phone','notes'] as $field) $t->string($field)->default(''); });
        Schema::table('hourly_rental_orders', function (Blueprint $t) { $t->uuid('booking_token')->nullable()->unique(); $t->json('booking_metadata')->nullable(); });
        // سجلات التدقيق والتقييمات وأعمدة التشغيل (is_free / pickup_at) تأتي من ترحيل 2026_09_21.
        (require base_path('database/migrations/2026_09_21_000001_extend_hourly_order_management.php'))->up();
        // رمز التسليم وحالة العاملة في الزيارة يأتيان مع ترحيل تطبيق الفريق الميداني.
        (require base_path('database/migrations/2026_09_28_000001_create_hourly_staff_app_tables.php'))->up();
        auth('api')->setUser(new User(['id' => 1]));
        $this->withHeader('lang', 'ar');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    /** طلب مدفوع الشكل: 4 زيارات ×4 ساعات بسعر 180 للزيارة، أولاها مجدولة 2030-01-07 08:00. */
    private function order(): array
    {
        // كل طلب في حي مستقل بإحداثيات مستقلة، فلا تتقاطع مضلعات الأحياء بين طلبين.
        static $n = 0; $n++;
        $lat = 24.7 + $n / 10;
        $category = $this->category(['name_ar' => 'تنظيف عام '.$n]);
        $district = $this->district('الصحافة '.$n, $lat, 46.7);
        $category->districts()->attach($district->id, ['is_active' => true]);
        $item = $this->item($category, ['visits_count' => 4, 'total_price' => 720, 'extra_hour_price' => 60, 'start_times' => ['08:00' => 'all', '14:00' => 'all']]);
        $order = $this->postJson('/api/app/hourly/orders', ['hourly_package_id' => $item->id, 'address_id' => $this->address($lat, 46.7),
            'payment_method' => 'moyasar', 'workers_per_visit' => 1, 'scheduled_starts' => ['2030-01-07 08:00']])->assertCreated()->json('data');
        return [$order, $order['visits'][0]['id'], $order['visits'][1]['id']];
    }

    private function url(array $order, int $visit, string $action): string
    {
        return '/api/app/hourly/orders/'.$order['id'].'/visits/'.$visit.'/'.$action;
    }

    public function test_visit_payload_exposes_the_three_actions_and_the_cancellation_policy(): void
    {
        [$order, $scheduled, $unscheduled] = $this->order();
        $data = $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()
            ->assertJsonPath('data.visits.0.can_cancel', true)
            ->assertJsonPath('data.visits.0.can_request_extra_hour', true)
            ->assertJsonPath('data.visits.0.can_rate', false)
            ->assertJsonPath('data.visits.0.rating', null)
            ->assertJsonPath('data.visits.0.extra_hours', 0)
            ->assertJsonPath('data.visits.0.extra_hour_price', 60)
            ->assertJsonPath('data.visits.0.cancellation_policy.window_hours', 12)
            ->assertJsonPath('data.visits.0.cancellation_policy.fee_percent', 50)
            // 25 ساعة قبل الموعد: خارج نافذة الـ12 ساعة، فالإلغاء مجاني.
            ->assertJsonPath('data.visits.0.cancellation_policy.fee_if_cancelled_now', 0)
            ->assertJsonPath('data.visits.1.can_cancel', false)
            ->assertJsonPath('data.visits.1.can_request_extra_hour', false)->json('data');
        $this->assertSame('2030-01-06T20:00:00+03:00', $data['visits'][0]['cancellation_policy']['free_until']);
        $this->assertSame($scheduled, $data['visits'][0]['id']);
        $this->assertSame($unscheduled, $data['visits'][1]['id']);
    }

    public function test_cancel_is_free_outside_the_window_and_charges_the_fee_inside_it(): void
    {
        [$order, $visit] = $this->order();
        $this->postJson($this->url($order, $visit, 'cancel'))->assertOk()
            ->assertJsonPath('data.status', 'cancelled_by_client')
            ->assertJsonPath('data.cancellation_fee', 0)
            ->assertJsonPath('data.can_cancel', false);
        // الزيارة الملغاة تُحتسب مستهلكة، وإجمالي الطلب يعود لثلاث زيارات فقط.
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()
            ->assertJsonPath('data.balance.consumed', 1)->assertJsonPath('data.remaining_visits', 3)
            ->assertJsonPath('data.total_amount', 540);

        [$second, , $unscheduled] = $this->order();
        $this->postJson($this->url($second, $unscheduled, 'schedule'), ['scheduled_start' => '2030-01-08 08:00'])->assertOk();
        Carbon::setTestNow(Carbon::parse('2030-01-08 02:00:00'));
        $this->postJson($this->url($second, $unscheduled, 'cancel'))->assertOk()
            ->assertJsonPath('data.status', 'cancelled_by_client')->assertJsonPath('data.cancellation_fee', 90);
        $this->assertDatabaseHas('hourly_rental_orders', ['id' => $second['id'], 'total_amount' => 630.0]);
    }

    public function test_cancel_is_idempotent_and_refuses_closed_or_unscheduled_visits(): void
    {
        [$order, $visit, $unscheduled] = $this->order();
        $this->postJson($this->url($order, $visit, 'cancel'))->assertOk();
        // إعادة الإرسال لا تُسجّل إلغاءً ثانيًا ولا رسومًا ثانية.
        $this->postJson($this->url($order, $visit, 'cancel'))->assertOk()->assertJsonPath('data.status', 'cancelled_by_client');
        $this->assertSame(1, DB::table('hourly_order_audits')->where('action', 'customer_cancel_visit')->count());
        $this->assertSame(540.0, (float) HourlyRentalOrder::find($order['id'])->total_amount);

        $this->postJson($this->url($order, $unscheduled, 'cancel'))->assertStatus(422)
            ->assertJsonPath('message', 'الإلغاء متاح للزيارات المجدولة فقط.');
        HourlyRentalOrder::find($order['id'])->visits()->where('id', $unscheduled)->update(['status' => 'completed']);
        $this->postJson($this->url($order, $unscheduled, 'cancel'))->assertStatus(422)
            ->assertJsonPath('message', 'الزيارة منتهية أو ملغاة ولا يمكن إلغاؤها.');
        auth('api')->setUser(new User(['id' => 2]));
        $this->postJson($this->url($order, $visit, 'cancel'))->assertNotFound();
    }

    public function test_cancelling_every_visit_closes_the_order(): void
    {
        [$order, $visit] = $this->order();
        HourlyRentalOrder::find($order['id'])->visits()->where('id', '!=', $visit)->update(['status' => 'expired']);
        $this->postJson($this->url($order, $visit, 'cancel'))->assertOk();
        $this->assertSame('cancelled', HourlyRentalOrder::find($order['id'])->status);
        $this->assertDatabaseHas('hourly_order_status_logs', ['hourly_rental_order_id' => $order['id'], 'to_status' => 'cancelled', 'actor_type' => 'client']);
    }

    public function test_extra_hour_extends_the_visit_and_prices_it_from_the_package(): void
    {
        [$order, $visit] = $this->order();
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 2])->assertOk()
            ->assertJsonPath('data.planned_hours', 6)->assertJsonPath('data.visit_price', 300)
            ->assertJsonPath('data.extra_hours', 2)
            ->assertJsonPath('data.scheduled_end_at', '2030-01-07T14:00:00+03:00')
            ->assertJsonPath('data.extra_hour.hours', 2)->assertJsonPath('data.extra_hour.amount', 120)
            ->assertJsonPath('data.extra_hour.amount_due', 840)
            ->assertJsonPath('data.extra_hour.payment_status', 'unpaid');
        $this->assertDatabaseHas('hourly_rental_orders', ['id' => $order['id'], 'total_amount' => 840.0]);
        $this->assertDatabaseHas('hourly_visits', ['id' => $visit, 'planned_hours' => 6, 'scheduled_end_at' => '2030-01-07 14:00:00', 'is_free' => false]);
    }

    public function test_extra_hour_falls_back_to_the_visit_hour_price_when_the_package_has_none(): void
    {
        [$order, $visit] = $this->order();
        HourlyRentalOrder::find($order['id'])->update(['extra_hour_price' => 0]);
        // 180 ر.س / 4 ساعات = 45 ر.س للساعة.
        $this->postJson($this->url($order, $visit, 'extra-hour'))->assertOk()
            ->assertJsonPath('data.extra_hour.amount', 45)->assertJsonPath('data.visit_price', 225);
    }

    public function test_extra_hour_is_not_charged_twice_for_a_repeated_request(): void
    {
        [$order, $visit] = $this->order();
        $token = (string) Str::uuid();
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 1, 'request_token' => $token])->assertOk()->assertJsonPath('data.planned_hours', 5);
        // نفس الرمز = نفس النتيجة بلا تمديد ثانٍ.
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 1, 'request_token' => $token])->assertOk()
            ->assertJsonPath('data.planned_hours', 5)->assertJsonPath('data.extra_hour.amount', 60);
        // بلا رمز: طلب مطابق خلال دقيقتين يُعتبر تكرارًا للمسة مزدوجة.
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 1])->assertOk()->assertJsonPath('data.planned_hours', 5);
        $this->assertSame(1, DB::table('hourly_order_audits')->where('action', 'customer_extra_hour')->count());
        // بعد دقيقتين يُنفَّذ طلب جديد فعلًا.
        Carbon::setTestNow(Carbon::parse('2030-01-06 07:05:00'));
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 1])->assertOk()->assertJsonPath('data.planned_hours', 6);
        $this->assertSame(2, DB::table('hourly_order_audits')->where('action', 'customer_extra_hour')->count());
    }

    public function test_extra_hour_respects_the_visit_day_the_hour_cap_and_the_visit_state(): void
    {
        [$order, $visit, $unscheduled] = $this->order();
        $this->postJson($this->url($order, $unscheduled, 'extra-hour'))->assertStatus(422)
            ->assertJsonPath('message', 'طلب الساعة الإضافية متاح للزيارات المجدولة أو الجارية فقط.');
        // 6 ساعات هي أقصى ما يُطلب في المرة الواحدة.
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 9])->assertStatus(422)
            ->assertJsonPath('message', 'يجب أن تكون قيمة عدد الساعات بين 1 و 6.');
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 6])->assertOk()->assertJsonPath('data.planned_hours', 10);
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 3])->assertStatus(422)
            ->assertJsonPath('message', 'إجمالي ساعات الزيارة لا يتجاوز 12 ساعة.');
        // زيارة تنتهي 22:00 لا تقبل تمديدًا يتجاوز منتصف الليل.
        HourlyRentalOrder::find($order['id'])->visits()->where('id', $visit)->update(['planned_hours' => 4, 'scheduled_start_at' => '2030-01-07 18:00:00', 'scheduled_end_at' => '2030-01-07 22:00:00']);
        $this->postJson($this->url($order, $visit, 'extra-hour'), ['hours' => 3])->assertStatus(422)
            ->assertJsonPath('message', 'الساعة الإضافية تتجاوز يوم الزيارة.');
        // بعد انتهاء الزيارة لا تمديد.
        Carbon::setTestNow(Carbon::parse('2030-01-07 23:00:00'));
        $this->postJson($this->url($order, $visit, 'extra-hour'))->assertStatus(422)
            ->assertJsonPath('message', 'انتهى وقت الزيارة؛ لا يمكن إضافة ساعات إليها.');
    }

    public function test_rating_needs_a_completed_visit_with_workers_and_updates_in_place(): void
    {
        [$order, $visit] = $this->order();
        $this->postJson($this->url($order, $visit, 'rate'), ['rating' => 5])->assertStatus(422)
            ->assertJsonPath('message', 'التقييم متاح بعد انتهاء الزيارة فقط.');
        HourlyRentalOrder::find($order['id'])->visits()->where('id', $visit)->update(['status' => 'completed']);
        $this->postJson($this->url($order, $visit, 'rate'), ['rating' => 5])->assertStatus(422)
            ->assertJsonPath('message', 'لا توجد عاملة مسجلة على هذه الزيارة لتقييمها.');
        DB::table('hourly_visit_workers')->insert(['hourly_visit_id' => $visit, 'maid_id' => 9, 'created_at' => now(), 'updated_at' => now()]);

        $this->postJson($this->url($order, $visit, 'rate'), ['rating' => 5, 'note' => 'خدمة ممتازة'])->assertOk()
            ->assertJsonPath('data.rating.value', 5)->assertJsonPath('data.rating.comment', 'خدمة ممتازة')
            ->assertJsonPath('data.rating.workers', 1)->assertJsonPath('data.can_rate', false);
        $this->assertDatabaseHas('hourly_worker_reviews', ['visit_id' => $visit, 'maid_id' => 9, 'user_id' => 1, 'rating' => 5, 'comment' => 'خدمة ممتازة']);
        // إعادة التقييم تُحدّث الصف نفسه ولا تضيف صفًا ثانيًا.
        $this->postJson($this->url($order, $visit, 'rate'), ['rating' => 3, 'comment' => 'تأخير'])->assertOk()
            ->assertJsonPath('data.rating.value', 3)->assertJsonPath('data.rating.comment', 'تأخير');
        $this->assertSame(1, DB::table('hourly_worker_reviews')->count());
        $this->postJson($this->url($order, $visit, 'rate'), ['rating' => 9])->assertStatus(422);
        auth('api')->setUser(new User(['id' => 2]));
        $this->postJson($this->url($order, $visit, 'rate'), ['rating' => 4])->assertNotFound();
    }

    public function test_customer_sees_the_delivery_code_and_a_readable_status_through_the_field_lifecycle(): void
    {
        [$order, $visit] = $this->order();
        $code = DB::table('hourly_visits')->where('id', $visit)->value('delivery_code');
        $this->assertMatchesRegularExpression('/^\d{4}$/', (string) $code);
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()
            ->assertJsonPath('data.visits.0.delivery_code', $code)
            ->assertJsonPath('data.visits.0.display_status', 'scheduled');
        // في الطريق ⇒ on_the_way، والرمز ما زال معروضًا.
        HourlyRentalOrder::find($order['id'])->visits()->where('id', $visit)->update(['status' => 'en_route']);
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()
            ->assertJsonPath('data.visits.0.display_status', 'on_the_way')->assertJsonPath('data.visits.0.delivery_code', $code);
        // بعد التسليم يختفي الرمز وتصبح الزيارة «جارية»، وتقبل ساعة إضافية.
        HourlyRentalOrder::find($order['id'])->visits()->where('id', $visit)->update(['status' => 'delivered']);
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()
            ->assertJsonPath('data.visits.0.display_status', 'in_progress')
            ->assertJsonPath('data.visits.0.delivery_code', null)
            ->assertJsonPath('data.visits.0.can_request_extra_hour', true)
            ->assertJsonPath('data.visits.0.can_cancel', false);
        $this->postJson($this->url($order, $visit, 'extra-hour'))->assertOk()->assertJsonPath('data.extra_hours', 1);
        // «انتهيت» من العاملة ⇒ العميل يرى «مكتملة» ويستطيع التقييم (حسم الفجوة ف٢).
        HourlyRentalOrder::find($order['id'])->visits()->where('id', $visit)->update(['status' => 'worker_finished']);
        DB::table('hourly_visit_workers')->insert(['hourly_visit_id' => $visit, 'maid_id' => 4, 'created_at' => now(), 'updated_at' => now()]);
        $this->getJson('/api/app/hourly/orders/'.$order['id'])->assertOk()
            ->assertJsonPath('data.visits.0.display_status', 'completed')->assertJsonPath('data.visits.0.can_rate', true);
        $this->postJson($this->url($order, $visit, 'rate'), ['rating' => 4])->assertOk()->assertJsonPath('data.rating.value', 4);
    }

    public function test_actions_are_hidden_when_the_module_is_off(): void
    {
        [$order, $visit] = $this->order();
        config(['superadmin_modules.hourly_rental.default_enabled'=>false]);
        foreach (['cancel', 'extra-hour', 'rate'] as $action) $this->postJson($this->url($order, $visit, $action), ['rating' => 5])->assertNotFound();
    }

}
