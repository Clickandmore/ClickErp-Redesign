<?php
namespace Tests\Feature;

use App\Models\Hourly\{HourlyDriver, HourlyRentalOrder, WorkerDailyMovement};
use App\Services\Hourly\{PanelBookingService, ShelterPresence};
use App\Services\Rental\RentalMaidQuery;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Support\Str;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\HourlyTestCase as TestCase;

/**
 * ربط الإيواء بالساعات: الخروج المؤقت للزيارة يُقرأ لحظيًا ويُسجَّل في سجل الحركة،
 * ولا يمس إطلاقًا حالة الخروج التعاقدية في contract_infos.
 */
class HourlyShelterPresenceTest extends TestCase
{
    use BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHourlyCategorySchema();
        $this->buildWorkers();
        Schema::create('admins', function (Blueprint $t) { $t->softDeletes(); $t->id(); $t->string('name'); });
        DB::table('admins')->insert(['id' => 7, 'name' => 'مشرف']);
        Schema::create('users', function (Blueprint $t) { $t->softDeletes();
            $t->id(); $t->string('name'); $t->string('email')->nullable(); $t->string('phone');
            $t->string('phone_code')->default('966'); $t->string('password')->nullable();
            $t->string('register_from')->nullable(); $t->boolean('is_blocked')->default(false); $t->timestamps();
        });
        Schema::table('user_addresses', function (Blueprint $t) { foreach (['name', 'phone', 'title'] as $c) $t->string($c)->nullable(); });
        foreach (['2026_09_20_000001_add_hourly_booking_wizard', '2026_09_21_000001_extend_hourly_order_management',
            '2026_09_21_000002_add_hourly_visit_operations', '2026_09_22_000001_create_hourly_customer_sources',
            '2026_09_28_000001_create_worker_daily_movements_table'] as $migration) {
            (require base_path('database/migrations/'.$migration.'.php'))->up();
        }
        DB::table('users')->insert(['id' => 1, 'name' => 'عميل الإيواء', 'phone' => '0500000000']);
        Schema::create('hourly_drivers', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->boolean('is_active')->default(true); $t->json('work_schedule')->nullable(); $t->timestamps(); $t->softDeletes();
        });
    }

    protected function tearDown(): void { \Carbon\Carbon::setTestNow(); HourlyRentalSettings::flush(); parent::tearDown(); }

    private function order(): HourlyRentalOrder
    {
        $category = $this->category();
        $district = \App\Models\Hourly\ServiceDistrict::first() ?? $this->district('الملز', 24.7, 46.7);
        $team = $this->team('فريق الإيواء');
        $category->districts()->attach($district->id, ['is_active' => true, 'team_id' => $team->id]);
        $item = $this->item($category);
        DB::table('maids')->insertOrIgnore(['id' => 1, 'name' => 'عاملة الإيواء', 'rental_mode' => 'hourly', 'rental_source' => 'outside',
            'arrival_date' => today()->toDateString(),
            'hourly_settings' => json_encode(['work_schedule' => array_fill_keys(array_keys(HourlyDriver::DAYS), ['mode' => 'all_day'])])]);
        if (!DB::table('maid_jobs')->where('maid_id', 1)->exists()) DB::table('maid_jobs')->insert(['maid_id' => 1, 'job_type_id' => 2]);
        DB::table('contracts')->insertOrIgnore(['id' => 1, 'maid_id' => 1, 'status' => 'shelter']);
        if (!DB::table('contract_infos')->where('contract_id', 1)->exists()) {
            DB::table('contract_infos')->insert(['contract_id' => 1, 'shelter_id' => 9, 'is_outside_shelter' => false, 'wants_to_work' => 'yes']);
        }
        DB::table('hourly_team_members')->insert(['hourly_team_id' => $team->id, 'member_type' => 'maid', 'member_id' => 1, 'role' => 'primary', 'is_active' => true]);

        return app(PanelBookingService::class)->create(['booking_token' => (string) Str::uuid(), 'customer_mode' => 'existing', 'user_id' => 1,
            'address_mode' => 'existing', 'address_id' => $this->address(24.7, 46.7, 'الملز'), 'hourly_package_id' => $item->id,
            'workers_per_visit' => 1, 'payment_method' => 'cash', 'collect_now' => false, 'all_workers' => false,
            'notify_email' => false, 'notify_app' => false,
            'visits' => [['starts_at' => now()->addDays(2)->format('Y-m-d').' 08:00', 'worker_ids' => [1]]]], 7);
    }

    private function presence(): ShelterPresence
    {
        $presence = app(ShelterPresence::class);
        $presence->flush();

        return $presence;
    }

    public function test_worker_counts_as_out_only_while_the_visit_is_running(): void
    {
        $visit = $this->order()->visits()->first();

        $this->assertFalse($this->presence()->isOut(1), 'الزيارة مجدولة ولم تبدأ، فالعاملة داخل الإيواء.');

        foreach (ShelterPresence::OUT_STATUSES as $status) {
            $visit->update(['status' => $status]);
            $this->assertTrue($this->presence()->isOut(1), 'الحالة '.$status.' تعني أن العاملة خارج الإيواء.');
        }

        $visit->update(['status' => 'completed']);
        $this->assertSame([], $this->presence()->outNow(), 'بعد الإكمال تعود العاملة إلى الإيواء.');
    }

    public function test_movement_log_opens_and_closes_without_touching_the_shelter_contract(): void
    {
        $visit = $this->order()->visits()->first();
        $before = DB::table('contract_infos')->where('contract_id', 1)->first();

        app(ShelterPresence::class)->open($visit, 'driver', 3);
        app(ShelterPresence::class)->open($visit, 'driver', 3); // إعادة الإرسال لا تُكرّر الصف

        $movements = WorkerDailyMovement::where('reference_id', $visit->id)->get();
        $this->assertCount(1, $movements);
        $this->assertSame(WorkerDailyMovement::STATUS_OUT, $movements->first()->status);
        $this->assertNull($movements->first()->in_at);
        // مكان الإيواء يُقرأ من عقد العاملة لا من الإعداد الافتراضي.
        $this->assertSame(9, (int) $movements->first()->shelter_place_id);

        app(ShelterPresence::class)->close($visit, 'driver', 3);
        $movement = WorkerDailyMovement::where('reference_id', $visit->id)->first();
        $this->assertNotNull($movement->in_at);
        $this->assertSame(WorkerDailyMovement::STATUS_RETURNED, $movement->status);

        $after = DB::table('contract_infos')->where('contract_id', 1)->first();
        $this->assertEquals($before->is_outside_shelter, $after->is_outside_shelter, 'حالة الخروج التعاقدية لا تُمس.');
        $this->assertEquals($before->outside_shelter_reason ?? null, $after->outside_shelter_reason ?? null);
    }

    public function test_upcoming_visits_are_counted_for_the_shelter_badge(): void
    {
        $visit = $this->order()->visits()->first();
        $this->assertSame(1, $this->presence()->upcomingFor(1));

        $visit->update(['status' => 'cancelled']);
        $this->assertSame(0, $this->presence()->upcomingFor(1));
    }

    public function test_a_worker_on_an_active_monthly_rental_is_not_offered_for_hourly_visits(): void
    {
        $this->order();
        $rentalTypeId = RentalMaidQuery::rentalJobTypeId();
        $query = fn () => RentalMaidQuery::applyNoActiveRental(
            \App\Models\ingaz\Maid::query()->whereHas('jobTypes', fn ($q) => $q->where('job_types.id', $rentalTypeId))
        );

        $this->assertTrue($query()->whereKey(1)->exists());

        DB::table('rental_contracts')->insert(['id' => 1, 'maid_id' => 1, 'status' => 'active']);

        $this->assertFalse($query()->whereKey(1)->exists(), 'عقد إيجار شهري نشط يمنع ترشيحها للساعات.');
    }

    public function test_presence_is_silent_when_the_shelter_link_is_off(): void
    {
        $visit = $this->order()->visits()->first();
        $visit->update(['status' => 'delivered']);
        $this->assertTrue($this->presence()->isOut(1));

        HourlyRentalSettings::persist(['link_shelter' => false] + HourlyRentalSettings::all(), 7);
        HourlyRentalSettings::flush();

        $this->assertFalse(ShelterPresence::linked());
        $this->assertSame([], $this->presence()->outNow());
    }

    public function test_delay_stages_and_manual_return_keep_physical_presence_consistent(): void
    {
        $order = $this->order();
        $order->update(['status' => 'active']);
        $visit = $order->visits()->first();
        $visit->update(['status'=>'in_progress', 'scheduled_end_at'=>now()->subMinutes(10)]);
        $this->presence()->open($visit, 'driver', 3);
        $this->assertSame('ended', \App\Services\Hourly\VisitDelay::state($visit)['stage']);
        $visit->update(['scheduled_end_at'=>now()->subMinutes(30)]);
        $this->assertSame('overdue', \App\Services\Hourly\VisitDelay::state($visit)['stage']);
        $visit->update(['scheduled_end_at'=>now()->subMinutes(90)]);
        $this->assertSame('escalated', \App\Services\Hourly\VisitDelay::state($visit)['stage']);
        $this->assertTrue($this->presence()->isOut(1));
        $manager = app(\App\Services\Hourly\OrderManagementService::class);
        try {
            $manager->apply($order, ['action'=>'complete', 'visit_ids'=>[$visit->id], 'reason'=>'test'], 7);
            $this->fail('Ordinary completion must reject a worker outside shelter.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertSame('in_progress', $visit->fresh()->status);
        }
        $manager->apply($order, ['action'=>'confirm_return', 'visit_ids'=>[$visit->id], 'reason'=>'تم التحقق من الوصول'], 7);
        $this->assertSame('completed', $visit->fresh()->status);
        $this->assertSame('completed', $order->fresh()->status);
        $movement = WorkerDailyMovement::where('reference_id',$visit->id)->firstOrFail();
        $this->assertNotNull($movement->in_at);
        $this->assertSame('admin', $movement->in_by_type);
        $this->assertSame(7, (int)$movement->in_by_id);
        $this->assertFalse($this->presence()->isOut(1));
        $this->assertNull(\App\Services\Hourly\VisitDelay::state($visit->fresh()));
        $this->assertTrue(DB::table('hourly_order_audits')->where('visit_id',$visit->id)->where('action','confirm_return')->exists());
    }

    public function test_delay_notifications_retry_failures_deduplicate_and_include_previous_days(): void
    {
        (require base_path('database/migrations/2026_10_14_000001_create_hourly_visit_delay_notices.php'))->up();
        $order = $this->order();
        $order->update(['status'=>'active']);
        $visit = $order->visits()->first();
        $visit->update(['status'=>'in_progress','scheduled_end_at'=>now()->subDay()]);
        $this->presence()->open($visit, 'driver', 3);
        $success = new \App\Services\Firebase\FirebasePushResult();
        $success->addSuccess();
        $push = \Mockery::mock(\App\Services\Hourly\StaffPushService::class);
        $push->shouldReceive('sendToStaff')->with('worker', 1, \Mockery::any(), \Mockery::any(), \Mockery::on(fn($data)=>$data['stage']==='escalated'))
            ->twice()->andReturn(\App\Services\Firebase\FirebasePushResult::skipped('offline'), $success);
        $this->app->instance(\App\Services\Hourly\StaffPushService::class, $push);
        \Illuminate\Support\Facades\Artisan::call('hourly:visit-delays');
        $this->assertNull(DB::table('hourly_visit_delay_notices')->value('sent_at'));
        \Illuminate\Support\Facades\Artisan::call('hourly:visit-delays');
        \Illuminate\Support\Facades\Artisan::call('hourly:visit-delays');
        $this->assertSame(1, DB::table('hourly_visit_delay_notices')->count());
        $this->assertNotNull(DB::table('hourly_visit_delay_notices')->value('sent_at'));
        $this->assertSame('overdue', WorkerDailyMovement::where('reference_id',$visit->id)->value('status'));
        $this->assertSame('in_progress', $visit->fresh()->status);
        $this->assertTrue($this->presence()->isOut(1));
    }

    public function test_auto_completion_never_closes_a_worker_outside_shelter(): void
    {
        $order = $this->order();
        $order->update(['status'=>'active']);
        \App\Models\Hourly\HourlyCategorySetting::create(['service_category_id'=>$order->service_category_id,'settings'=>['auto_complete_visits_at'=>'00:00']]);
        $visit = $order->visits()->first();
        $visit->update(['status'=>'in_progress','scheduled_date'=>today()]);
        $this->presence()->open($visit, 'driver', 3);
        \Illuminate\Support\Facades\Artisan::call('hourly:auto-complete-visits');
        $this->assertSame('in_progress', $visit->fresh()->status);
        // An inconsistent status must not override an open physical movement either.
        $visit->update(['status'=>'scheduled']);
        \Illuminate\Support\Facades\Artisan::call('hourly:auto-complete-visits');
        $this->assertSame('scheduled', $visit->fresh()->status);
        $this->assertNull(WorkerDailyMovement::where('reference_id',$visit->id)->value('in_at'));
    }

    public function test_worker_list_badge_shows_actual_phase_and_delay_thresholds(): void
    {
        \Carbon\Carbon::setTestNow('2030-01-06 12:00:00');
        $badge = fn($status, $end) => view('admin.hourly_maids._current-visit-status', [
            'trip' => ['status'=>$status, 'until'=>$end],
        ])->render();
        foreach (['en_route'=>'في الطريق إلى العميل', 'delivered'=>'لدى العميل',
            'in_progress'=>'تؤدي الخدمة', 'worker_finished'=>'بانتظار الاستلام',
            'picked_up'=>'في طريق العودة للإيواء'] as $status=>$label) {
            $html = $badge($status, '2030-01-06 13:00:00');
            $this->assertStringContainsString($label, $html);
            $this->assertStringNotContainsString('دقيقة بعد الموعد', $html);
        }
        $this->assertStringContainsString('انتهى وقت الخدمة', $badge('in_progress', '2030-01-06 12:00:00'));
        $html = $badge('worker_finished', '2030-01-06 11:30:00');
        $this->assertStringContainsString('متأخرة عن الاستلام', $html);
        $this->assertStringContainsString('30 دقيقة بعد الموعد', $html);
        $this->assertStringContainsString('متأخرة عن العودة', $badge('picked_up', '2030-01-06 11:30:00'));
        $html = $badge('picked_up', '2030-01-06 10:30:00');
        $this->assertStringContainsString('في طريق العودة للإيواء', $html);
        $this->assertStringContainsString('تصعيد التأخير', $html);
        $this->assertStringContainsString('mh-now-escalated', $html);
    }

    public function test_old_open_visit_keeps_return_unconfirmed_without_blocking_a_non_overlapping_booking(): void
    {
        \Carbon\Carbon::setTestNow('2030-01-20 12:00:00');
        $order=$this->order(); $visit=$order->visits()->firstOrFail();
        $visit->update(['status'=>'in_progress','scheduled_start_at'=>'2030-01-03 08:00:00','scheduled_end_at'=>'2030-01-03 12:00:00']);
        $worker=\App\Models\ingaz\Maid::findOrFail(1);
        $assignment=app(\App\Services\Hourly\HourlyWorkerAssignment::class);
        $this->assertTrue($assignment->available($worker,'2030-01-22 08:00:00','2030-01-22 12:00:00',$order->package_snapshot));
        $trip=$this->presence()->inVisitNow()[1];
        $this->assertSame($visit->id,$trip['visit_id']);
        $html=view('admin.hourly_maids._current-visit-status',['trip'=>$trip])->render();
        $this->assertStringContainsString('انتهى موعد الزيارة — العودة غير مؤكدة',$html);
        $reference=view('admin.hourly_maids._visit-reference',['trip'=>$trip])->render();
        $this->assertStringContainsString('مرجع #'.$visit->id,$reference);
        $this->assertStringContainsString($order->order_number,$reference);
        $this->assertStringContainsString('2030-01-03 12:00',$reference);
        $this->assertSame('in_progress',$visit->fresh()->status);
    }

    public function test_daily_past_visit_reminder_refreshes_without_completing_visits_or_recording_returns(): void
    {
        \Carbon\Carbon::setTestNow('2030-01-20 12:00:00');
        $order=$this->order(); $visit=$order->visits()->firstOrFail();
        $visit->update(['status'=>'in_progress','scheduled_start_at'=>'2030-01-03 08:00:00','scheduled_end_at'=>'2030-01-03 12:00:00']);
        $this->presence()->open($visit,'driver',3);
        $second=$visit->replicate(); $second->visit_number=2; $second->status='scheduled'; $second->save();
        $key=\App\Console\Commands\HourlyPastVisitReminder::CACHE_KEY;
        \Illuminate\Support\Facades\Artisan::call('hourly:past-visit-reminder');
        $this->assertSame(2,\Illuminate\Support\Facades\Cache::get($key)['count']);
        $html=view('admin.hourly._past-visits-reminder')->render();
        $this->assertStringContainsString('توجد 2 زيارة',$html);
        $this->assertStringContainsString(e(route('admin.hourly-visits.past',['from'=>'','to'=>''])),$html);
        \Illuminate\Support\Facades\Artisan::call('hourly:past-visit-reminder');
        $this->assertSame(2,\Illuminate\Support\Facades\Cache::get($key)['count']);
        $this->assertSame('in_progress',$visit->fresh()->status);
        $this->assertNull(WorkerDailyMovement::where('reference_id',$visit->id)->value('in_at'));
        $second->update(['status'=>'cancelled_by_client']);
        \Illuminate\Support\Facades\Artisan::call('hourly:past-visit-reminder');
        $this->assertSame(1,\Illuminate\Support\Facades\Cache::get($key)['count']);
        $order->update(['status'=>'cancelled']);
        \Illuminate\Support\Facades\Artisan::call('hourly:past-visit-reminder');
        $this->assertNull(\Illuminate\Support\Facades\Cache::get($key));
    }
}
