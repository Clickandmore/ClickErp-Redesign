<?php

namespace Tests\Feature;

use App\Models\Hourly\{HourlyDriver, HourlyRentalOrder};
use App\Services\Hourly\{PanelBookingService, RenewalCounter, RenewalSearch};
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\HourlyTestCase as TestCase;

/** معايير قبول التجديد: الربط، تعبئة المعالج، منع التجديد المكرر، وحالة التجديد في القائمة والعداد. */
class HourlyRenewalFlowTest extends TestCase
{
    use BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema;

    protected function setUp(): void
    {
        parent::setUp(); $this->setUpHourlyCategorySchema(); $this->buildWorkers();
        Schema::create('admins', fn (Blueprint $t) => [ $t->softDeletes(),$t->id(), $t->string('name')->nullable()]); DB::table('admins')->insert(['id' => 7, 'name' => 'مشرف']);
        Schema::create('users', function (Blueprint $t) { $t->softDeletes();
            $t->id(); $t->string('name'); $t->string('email')->nullable()->unique(); $t->string('phone')->unique();
            $t->string('phone_code')->default('966'); $t->string('password')->nullable(); $t->string('register_from')->nullable();
            $t->boolean('is_blocked')->default(false); $t->timestamps();
        });
        Schema::table('user_addresses', function (Blueprint $t) { foreach (['name', 'phone', 'title'] as $column) $t->string($column)->nullable(); });
        foreach (['2026_09_20_000001_add_hourly_booking_wizard', '2026_09_21_000001_extend_hourly_order_management', '2026_09_21_000002_add_hourly_visit_operations', '2026_09_23_000001_link_hourly_order_renewals'] as $migration) {
            (require base_path('database/migrations/'.$migration.'.php'))->up();
        }
        DB::table('users')->insert(['id' => 1, 'name' => 'عميل', 'email' => 'client@example.test', 'phone' => '0500000000', 'hourly_wallet_balance' => 1000]);
        Schema::create('hourly_drivers', fn (Blueprint $t) => [$t->id(), $t->string('name'), $t->boolean('is_active')->default(true), $t->softDeletes()]);
        \Illuminate\Support\Facades\Bus::fake();
    }

    protected function tearDown(): void { HourlyRentalSettings::flush(); parent::tearDown(); }

    /** طلب أصلي بزيارتين (بعد 3 و4 أيام الساعة 08:00) تخدمه العاملة 1، يُدفع نقدًا. */
    private function source(): array
    {
        $category = $this->category(); $district = $this->district('الملز', 24.7, 46.7); $team = $this->team();
        $category->districts()->attach($district->id, ['is_active' => true, 'team_id' => $team->id]);
        $item = $this->item($category, ['visits_count' => 2, 'package_tier_id' => \App\Models\Hourly\HourlyPackageTier::where('code', 'weekly_1')->value('id')]);
        foreach ([1, 2] as $id) {
            DB::table('maids')->insert(['id' => $id, 'name' => 'عاملة '.$id, 'rental_mode' => 'hourly', 'rental_source' => 'outside', 'arrival_date' => now()->toDateString(),
                'hourly_settings' => json_encode(['work_schedule' => array_fill_keys(array_keys(HourlyDriver::DAYS), ['mode' => 'all_day'])])]);
            DB::table('maid_jobs')->insert(['maid_id' => $id, 'job_type_id' => 2]);
            DB::table('contracts')->insert(['id' => $id, 'maid_id' => $id, 'status' => 'shelter']);
            DB::table('contract_infos')->insert(['contract_id' => $id, 'shelter_id' => 1, 'is_outside_shelter' => false, 'wants_to_work' => 'yes']);
            DB::table('hourly_team_members')->insert(['hourly_team_id' => $team->id, 'member_type' => 'maid', 'member_id' => $id, 'role' => 'primary', 'is_active' => true]);
        }
        $data = ['booking_token' => (string) Str::uuid(), 'customer_mode' => 'existing', 'user_id' => 1, 'address_mode' => 'existing',
            'address_id' => $this->address(24.7, 46.7, 'الملز'), 'hourly_package_id' => $item->id, 'workers_per_visit' => 1,
            'payment_method' => 'cash', 'collect_now' => false, 'all_workers' => false, 'notify_email' => false, 'notify_app' => false,
            'visits' => [['starts_at' => now()->addDays(3)->format('Y-m-d').' 08:00', 'worker_ids' => [1]], ['starts_at' => now()->addDays(4)->format('Y-m-d').' 08:00', 'worker_ids' => [1]]]];

        $source=app(PanelBookingService::class)->create($data, 7);
        $source->update(['status'=>'active']);
        $source->visits()->orderBy('scheduled_start_at')->first()->update(['status'=>'completed']);
        return [$source, $data];
    }

    /** طلب تجديد عبر نقطة الحفظ بنفس بيانات الطلب الأصلي ومواعيده المقترحة. */
    private function renewalPayload(array $data, HourlyRentalOrder $source, array $starts): array
    {
        return array_merge($data, ['booking_token' => (string) Str::uuid(), 'renewed_from_order_id' => $source->id,
            'visits' => array_map(fn ($start) => ['starts_at' => $start, 'worker_ids' => [1]], $starts)]);
    }

    private function actingWithoutRenewAgain(): void
    {
        $admin = \Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id = 7; $admin->name = 'مشرف';
        $admin->shouldReceive('can')->andReturnUsing(fn ($permission) => $permission !== 'hourly_orders_renew_again');
        auth('admin')->setUser($admin);
    }

    public function test_wizard_prefills_the_old_order_and_proposes_dates_after_the_contract_ends(): void
    {
        [$source] = $this->source();
        $expected = [now()->addDays(10)->format('Y-m-d').' 08:00', now()->addDays(11)->format('Y-m-d').' 08:00'];

        $this->get(route('admin.hourly-orders.create', ['renew' => $source->id]))->assertOk()
            ->assertSee('تجديد للطلب')->assertSee($source->order_number)
            ->assertSee('التجديد لنفس العميل تلقائيًا.')->assertSee('id="customer-selection"  hidden inert ',false)
            ->assertViewHas('renewal', function ($r) use ($source, $expected) {
                return (int)$r['customer']['id'] === (int)$source->user_id && $r['item_id'] === $source->hourly_package_id && $r['workers'] === 1 && (float) $r['hours'] === 4.0
                    && $r['payment_method'] === 'cash' && $r['preferred_workers'] === [['id' => 1, 'name' => 'عاملة 1']]
                    && $r['contract_end'] === now()->addDays(4)->toDateString()
                    // أول يوم بنفس النمط بعد انتهاء العقد القديم: نفس اليومين والوقت بعد أسبوع.
                    && $r['visits'] === $expected && $r['renewed_by'] === null;
            });
    }

    public function test_renewal_links_both_orders_shows_renewed_and_leaves_not_renewed_filter_and_counter(): void
    {
        [$source, $data] = $this->source();
        $this->assertSame(1, app(RenewalCounter::class)->get(true));
        $starts = app(\App\Services\Hourly\RenewalPlanner::class)->for($source)['visits'];

        $id = $this->postJson('/panel/add-order/store', $this->renewalPayload($data, $source, $starts))->assertOk()->json('id');
        $renewal = HourlyRentalOrder::findOrFail($id);
        $this->assertSame($source->id, (int) $renewal->renewed_from_order_id);
        $this->assertTrue($source->fresh()->renewedBy->is($renewal));
        $this->assertTrue($renewal->renewedFrom->is($source));
        $this->assertSame('تم التجديد بالطلب #'.$renewal->order_number, DB::table('hourly_order_audits')->where('hourly_rental_order_id', $source->id)->where('action', 'renewed_to')->value('reason'));
        $this->assertSame('تجديد للطلب #'.$source->order_number, DB::table('hourly_order_audits')->where('hourly_rental_order_id', $renewal->id)->where('action', 'renewed_from')->value('reason'));

        $this->get('/admin/hourly/renewals?status=d30')->assertOk()->assertSee('تم التجديد')
            ->assertSee(route('admin.hourly-orders.show', $renewal), false)->assertSee('#'.$renewal->order_number);
        $ids = fn (string $renewalStatus) => app(RenewalSearch::class)->query(['status' => 'd30', 'renewal' => $renewalStatus])->pluck('hourly_rental_orders.id')->all();
        $this->assertNotContains($source->id, $ids('not_renewed'));
        $this->assertSame([$source->id], $ids('renewed'));
        // مسح العداد عند التجديد: يُعاد حسابه فيخرج العقد المجدَّد (والتجديد نفسه ينتهي بعد 11 يومًا).
        $this->assertSame(0, app(RenewalCounter::class)->get());
    }

    public function test_second_renewal_is_rejected_unless_the_admin_has_the_permission(): void
    {
        [$source, $data] = $this->source();
        $starts = app(\App\Services\Hourly\RenewalPlanner::class)->for($source)['visits'];
        $first = $this->postJson('/panel/add-order/store', $this->renewalPayload($data, $source, $starts))->assertOk()->json('id');
        $second = $this->renewalPayload($data, $source, [now()->addDays(17)->format('Y-m-d').' 08:00', now()->addDays(18)->format('Y-m-d').' 08:00']);

        $this->actingWithoutRenewAgain();
        $this->get(route('admin.hourly-orders.create', ['renew' => $source->id]))->assertOk()
            ->assertViewHas('renewal', null)->assertViewHas('renewalBlocked', fn ($r) => $r['renewed_by']['id'] === $first)
            ->assertSee(route('admin.hourly-orders.show', $first), false);
        $this->postJson('/panel/add-order/store', $second)->assertStatus(422)->assertJsonFragment(['هذا الطلب جُدِّد سابقًا بالطلب #'.HourlyRentalOrder::find($first)->order_number.'. تجديده مرة أخرى يتطلب صلاحية «تجديد طلب سبق تجديده».']);
        $this->assertSame(2, HourlyRentalOrder::count());

        $this->setUpAdminWithAllPermissions();
        $third = $this->postJson('/panel/add-order/store', $second)->assertOk()->json('id');
        $this->assertSame($source->id, (int) HourlyRentalOrder::find($third)->renewed_from_order_id);
        $this->assertStringContainsString('تجديد إضافي', DB::table('hourly_order_audits')->where('hourly_rental_order_id', $source->id)->where('action', 'renewed_to')->orderByDesc('id')->value('reason'));

        // ولا يُقبل ربط تجديد بطلب عميل آخر.
        DB::table('users')->insert(['id' => 2, 'name' => 'آخر', 'email' => 'other@example.test', 'phone' => '0511111111']);
        $address = DB::table('user_addresses')->insertGetId(['user_id' => 2, 'city_id' => 1, 'address' => 'عنوان آخر', 'latitude' => 24.7, 'longitude' => 46.7, 'district' => 'الملز', 'created_at' => now(), 'updated_at' => now()]);
        $foreign = array_merge($second, ['booking_token' => (string) Str::uuid(), 'user_id' => 2, 'address_id' => $address]);
        $this->postJson('/panel/add-order/store', $foreign)->assertStatus(422)->assertJsonFragment(['طلب التجديد لا يخص العميل المختار.']);
    }

    public function test_renewal_requires_a_performed_visit_on_both_open_and_save(): void
    {
        [$source,$data]=$this->source();
        $source->visits()->update(['status'=>'scheduled']);
        $starts=app(\App\Services\Hourly\RenewalPlanner::class)->for($source)['visits'];
        foreach(['scheduled','no_show'] as $status) {
            $source->visits()->update(['status'=>$status]);
            $this->get(route('admin.hourly-orders.create',['renew'=>$source->id]))
                ->assertRedirect(route('admin.hourly-orders.show',$source))->assertSessionHasErrors('hourly');
            $this->postJson('/panel/add-order/store',$this->renewalPayload($data,$source,$starts))
                ->assertStatus(422)->assertJsonFragment(['لا يمكن التجديد قبل تنفيذ أول زيارة؛ يمكنك تعديل الحجز الحالي أو إلغاؤه.']);
        }
        $this->assertSame(1,HourlyRentalOrder::count());
    }

    public function test_renewal_window_uses_configured_days_and_allows_ended_contracts(): void
    {
        [$source,$data]=$this->source();
        $policy=app(\App\Services\Hourly\RenewalEligibility::class);
        HourlyRentalSettings::persist(['renewal_reminder_days'=>2]);
        $this->assertNotNull($policy->reason($source));
        $this->get(route('admin.hourly-orders.create',['renew'=>$source->id]))
            ->assertRedirect(route('admin.hourly-orders.show',$source));
        $starts=app(\App\Services\Hourly\RenewalPlanner::class)->for($source)['visits'];
        $this->postJson('/panel/add-order/store',$this->renewalPayload($data,$source,$starts))->assertStatus(422);
        HourlyRentalSettings::persist(['renewal_reminder_days'=>4]);
        $this->assertNull($policy->reason($source));
        $source->visits()->update(['scheduled_start_at'=>now()->subDay()]);
        $this->assertNull($policy->reason($source));
        $source->update(['status'=>'pending_confirmation']);
        $this->assertNotNull($policy->reason($source));
        $source->update(['status'=>'cancelled']);
        $this->assertNotNull($policy->reason($source));
    }
    private function setUpAdminWithAllPermissions(): void
    {
        $admin = \Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id = 7; $admin->name = 'مشرف'; $admin->shouldReceive('can')->andReturn(true);
        auth('admin')->setUser($admin);
    }
}
