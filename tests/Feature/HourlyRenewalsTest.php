<?php

namespace Tests\Feature;

use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\{ContractPeriod, RenewalSearch};
use App\Support\HourlyRentalSettings;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\HourlyTestCase as TestCase;

/** بحث التجديدات ومدة العقد والتذكير على مخطط SQLite مصغّر في الذاكرة. */
class HourlyRenewalsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.hourly_renewals' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'database.default' => 'hourly_renewals', 'superadmin_modules.hourly_rental.default_enabled' => true, 'activitylog.enabled' => false]);
        Carbon::setTestNow('2026-09-21 09:00:00');
        Schema::create('new_settings', fn (Blueprint $t) => [$t->id(), $t->string('key'), $t->text('value'), $t->timestamps()]);
        Schema::create('users', fn (Blueprint $t) => [ $t->softDeletes(),$t->id(), $t->string('name'), $t->string('email')->nullable(), $t->string('phone')->nullable()]);
        Schema::create('hourly_service_categories', fn (Blueprint $t) => [$t->id(), $t->string('name_ar'), $t->string('name_en')->nullable(), $t->softDeletes()]);
        Schema::create('hourly_rental_orders', function (Blueprint $t) {
            $t->id(); $t->string('order_number'); $t->unsignedBigInteger('user_id'); $t->unsignedBigInteger('service_category_id')->default(1); $t->string('status')->default('active');
            $t->dateTime('starts_at')->nullable(); $t->unsignedBigInteger('renewed_from_order_id')->nullable(); $t->decimal('price_per_visit', 12, 2)->default(100); $t->decimal('total_amount', 12, 2)->default(400); $t->text('address_text')->nullable(); $t->timestamps();
        });
        Schema::create('hourly_visits', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('hourly_rental_order_id'), $t->string('status'), $t->boolean('is_free')->default(false), $t->dateTime('scheduled_start_at')->nullable(), $t->unsignedBigInteger('hourly_team_id')->nullable(), $t->timestamps()]);
        Schema::create('hourly_order_installments', fn (Blueprint $t) => [$t->id(), $t->unsignedBigInteger('hourly_rental_order_id'), $t->decimal('amount', 12, 2), $t->decimal('paid_amount', 12, 2)->default(0), $t->date('due_date')]);
        Schema::create('hourly_visit_notices', function (Blueprint $t) {
            $t->id(); $t->uuid('batch_id'); $t->unsignedBigInteger('user_id'); $t->string('channel'); $t->string('title'); $t->text('body'); $t->string('status')->default('pending'); $t->text('error')->nullable(); $t->timestamps(); $t->unique(['batch_id', 'user_id', 'channel']);
        });
        DB::table('users')->insert([['id' => 1, 'name' => 'عميل بريد', 'email' => 'client@example.test'], ['id' => 2, 'name' => 'عميل بلا بريد', 'email' => null], ['id' => 3, 'name' => 'عميل جدّد', 'email' => 'renewed@example.test']]);
        DB::table('hourly_service_categories')->insert(['id' => 1, 'name_ar' => 'تنظيف منزلي']);
        HourlyRentalSettings::flush();
        HourlyRentalSettings::persist(['is_enabled' => true, 'renewal_reminder_days' => 7]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    /** طلب بزياراته: [الحالة، بعد كم يوم من اليوم، مجانية؟]. */
    private function order(int $id, array $visits, array $attributes = []): void
    {
        DB::table('hourly_rental_orders')->insert($attributes + ['id' => $id, 'order_number' => 'HR-'.$id, 'user_id' => 1, 'starts_at' => '2026-09-01 08:00:00']);
        foreach ($visits as $visit) {
            [$status, $days, $free] = $visit + [2 => false];
            DB::table('hourly_visits')->insert(['hourly_rental_order_id' => $id, 'status' => $status, 'is_free' => $free, 'scheduled_start_at' => today()->addDays($days)->setTime(10, 0), 'hourly_team_id' => array_key_exists($id, $teams = [3 => 9, 8 => null]) ? $teams[$id] : 1]);
        }
    }

    private function ids(array $filters): array
    {
        return app(RenewalSearch::class)->query($filters)->pluck('hourly_rental_orders.id')->sort()->values()->all();
    }

    public function test_contract_end_is_the_last_counted_visit_and_presets_filter_by_it(): void
    {
        $this->order(1, [['completed', -3], ['scheduled', 0]]);            // تنتهي اليوم
        $this->order(2, [['scheduled', 4], ['cancelled', 20]]);            // الملغاة لا تمدّ العقد: تنتهي بعد 4 أيام
        $this->order(3, [['completed', -2], ['completed', 9]]);            // بعد 9 أيام وكل زياراتها منجزة (فريق 9)
        $this->order(4, [['scheduled', 1]], ['status' => 'cancelled']);    // طلب ملغى: لا يظهر أبدًا
        $this->order(5, [['scheduled', 2]]);                               // جُدِّد بالطلب 6
        $this->order(6, [['scheduled', 40]], ['renewed_from_order_id' => 5]);
        $this->order(7, [['completed', -5]]);                              // انتهى سابقًا
        $this->order(8, [['scheduled', 3]]);                               // بدون فريق

        $this->assertSame([1], $this->ids(['status' => 'today']));
        $this->assertSame([1, 2, 5, 8], $this->ids(['status' => 'd5']));
        $this->assertSame([1, 2, 8], $this->ids(['status' => 'd5', 'renewal' => 'not_renewed']));
        $this->assertSame([5], $this->ids(['status' => 'd5', 'renewal' => 'renewed']));
        $this->assertSame([1, 2, 3, 5, 6, 8], $this->ids(['status' => 'custom', 'expiry_from' => '2026-09-21']));
        $this->assertSame([7], $this->ids(['status' => 'custom', 'expiry_to' => '2026-09-20']));

        $this->assertSame([3], $this->ids(['status' => 'd10', 'report' => 'done']));
        $this->assertSame([1, 2, 5, 8], $this->ids(['status' => 'd10', 'report' => 'open']));
        // الفريق متعدد القيم مع «بدون فريق».
        $this->assertSame([3], $this->ids(['status' => 'd10', 'team_ids' => ['9']]));
        $this->assertSame([8], $this->ids(['status' => 'd10', 'team_ids' => [RenewalSearch::NO_TEAM]]));
        $this->assertSame([3, 8], $this->ids(['status' => 'd10', 'team_ids' => ['9', RenewalSearch::NO_TEAM]]));
        $this->assertSame([2], $this->ids(['status' => 'd10', 'order_number' => '#HR-2']));

        DB::table('hourly_order_installments')->insert([
            ['hourly_rental_order_id' => 1, 'amount' => 100, 'paid_amount' => 100, 'due_date' => '2026-09-01'],
            ['hourly_rental_order_id' => 2, 'amount' => 100, 'paid_amount' => 0, 'due_date' => '2026-09-10'],
        ]);
        $this->assertSame([1, 2], $this->ids(['status' => 'd10', 'installment' => 'has']));
        $this->assertSame([2], $this->ids(['status' => 'd10', 'installment' => 'overdue']));
        // العداد: غير المجدَّدة فقط التي تنتهي خلال 10 أيام (5 مجدَّد فيُستبعد).
        $this->assertSame(4, app(RenewalSearch::class)->dueCount(10));
    }

    public function test_active_contract_filters_ignore_cancelled_visits_and_follow_the_free_visit_setting(): void
    {
        $this->order(1, [['completed', -5], ['scheduled', 5]]);             // نشط اليوم
        $this->order(2, [['scheduled', 2], ['scheduled', 6]]);              // يبدأ بعد يومين
        $this->order(3, [['completed', -10], ['cancelled', 5]]);            // الملغاة لا تمدّه: انتهى قبل 10 أيام
        $this->order(4, [['completed', -3], ['scheduled', 8, true]]);       // زيارة مجانية بعد 8 أيام
        $this->order(5, [['completed', -1], ['scheduled', 1]], ['status' => 'cancelled']);

        $this->assertSame([1], $this->ids(['status' => 'active_all']));
        $this->assertSame([1, 2], $this->ids(['status' => 'active_on', 'active_on' => '2026-09-24']));
        $this->assertSame([3], $this->ids(['status' => 'active_on', 'active_on' => '2026-09-11']));
        $this->assertSame([], $this->ids(['status' => 'active_on', 'active_on' => '2026-10-10']));
        $this->assertSame('2026-09-18', app(ContractPeriod::class)->endOf(HourlyRentalOrder::find(4))->toDateString());

        // عند احتساب المجانية يمتد العقد 4 إلى زيارته المجانية فيصبح نشطًا اليوم.
        HourlyRentalSettings::persist(['renewal_count_free_visits' => true]);
        $this->assertSame([1, 4], $this->ids(['status' => 'active_all']));
        $this->assertSame('2026-09-29', app(ContractPeriod::class)->endOf(HourlyRentalOrder::find(4))->toDateString());
        // الملغاة لا تُحسب حتى مع احتساب المجانية.
        $this->assertSame('2026-09-11', app(ContractPeriod::class)->endOf(HourlyRentalOrder::find(3))->toDateString());
    }

    public function test_reminders_skip_renewed_contracts_are_queued_once_and_can_be_disabled(): void
    {
        $this->order(1, [['scheduled', 3]]);
        $this->order(2, [['scheduled', 5]], ['user_id' => 2]);
        $this->order(3, [['completed', 2]]);                                 // لا زيارات متبقية: لا تذكير
        $this->order(4, [['scheduled', 12]]);                                // خارج نافذة 7 أيام
        $this->order(5, [['scheduled', 4]], ['user_id' => 3]);               // تم تجديده: لا تذكير
        $this->order(6, [['scheduled', 30]], ['user_id' => 3, 'renewed_from_order_id' => 5]);

        $this->artisan('hourly:renewal-reminders')->assertExitCode(0);
        $this->artisan('hourly:renewal-reminders')->assertExitCode(0);

        $notices = DB::table('hourly_visit_notices')->orderBy('id')->get();
        $this->assertSame([[1, 'app'], [1, 'email'], [2, 'app']], $notices->map(fn ($n) => [(int) $n->user_id, $n->channel])->all());
        $this->assertSame(0, DB::table('hourly_visit_notices')->where('user_id', 3)->count());
        $this->assertSame('تذكير بتجديد الباقة', $notices[0]->title);
        $this->assertStringContainsString('HR-1', $notices[0]->body);
        // العداد: 1 و2 و3 (غير مجدَّدة وتنتهي خلال 7 أيام)؛ 5 مجدَّد فلا يُعد.
        $this->assertSame(['count' => 3, 'date' => '2026-09-21'], json_decode(DB::table('new_settings')->where('key', 'hourly_renewals_due_count')->value('value'), true));

        DB::table('hourly_visit_notices')->delete();
        HourlyRentalSettings::persist(['renewal_reminder_days' => 0]);
        $this->artisan('hourly:renewal-reminders')->assertExitCode(0);
        $this->assertSame(0, DB::table('hourly_visit_notices')->count());
    }
}
