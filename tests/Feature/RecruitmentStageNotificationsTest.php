<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\RecruitmentContractStageStep;
use App\Models\RecruitmentContractStepCompletion;
use App\Models\RecruitmentStageNotification;
use App\Services\ingaz\RecruitmentContractStageStepService;
use App\Services\ingaz\RecruitmentStageNotifier;
use App\Services\TeqnyatSmsService;
use App\Support\RecruitmentStageNotificationSettings as Settings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RecruitmentStageNotificationsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringEndsWith('_testing', DB::connection()->getDatabaseName());
    }

    private function contract(string $status = 'msaned'): Contract
    {
        $client = \App\Models\ingaz\User::create([
            'name' => 'عميل الاختبار', 'email' => uniqid('customer').'@example.test',
            'phone' => '5'.random_int(10000000, 99999999), 'phone_code' => '+966',
            'password' => bcrypt('test'), 'client_type' => 'person',
            'national_num' => (string) random_int(1000000000, 1999999999),
        ]);
        return Contract::create(['type' => 'recruitment', 'status' => $status, 'user_id' => $client->id]);
    }

    private function enable(): void
    {
        Settings::save(['enabled' => true, 'stages' => ['msaned' => ['sms' => true, 'email' => true],
            'tafwed' => ['sms' => true, 'email' => true]]]);
    }

    public function test_channels_are_independent_and_events_are_deduplicated(): void
    {
        $this->enable();
        $contract = $this->contract();
        $contract->user->email = null;
        $notifier = app(RecruitmentStageNotifier::class);
        $notifier->stageChanged($contract, 'new');
        $notifier->stageChanged($contract, 'new');
        $rows = RecruitmentStageNotification::where('contract_id', $contract->id)->get()->keyBy('channel');
        $this->assertCount(2, $rows);
        $this->assertSame('pending', $rows['sms']->status);
        $this->assertSame('skipped', $rows['email']->status);
        $this->assertStringContainsString('عميل الاختبار', $rows['sms']->message);
        $this->assertStringNotContainsString('{', $rows['sms']->message);
    }

    public function test_disabled_and_backward_changes_do_not_create_messages(): void
    {
        Settings::save([]);
        $contract = $this->contract();
        $notifier = app(RecruitmentStageNotifier::class);
        $notifier->stageChanged($contract, 'new');
        $this->enable();
        $notifier->stageChanged($contract, 'tafwed');
        $notifier->stageChanged($contract, 'msaned');
        $this->assertSame(0, RecruitmentStageNotification::where('contract_id', $contract->id)->count());
    }

    public function test_contract_update_records_notification_without_sending(): void
    {
        Mail::fake();
        $this->enable();
        $contract = $this->contract('new');
        $contract->update(['status' => 'msaned']);
        $this->assertSame(2, RecruitmentStageNotification::where('contract_id', $contract->id)->count());
        Mail::assertNothingSent();
    }

    public function test_stage_change_sends_right_after_commit_without_scheduler(): void
    {
        $this->enable();
        $contract = $this->contract('new');
        Mail::fake();
        $this->mock(TeqnyatSmsService::class)->shouldReceive('sendMessage')->once()->andReturn(['status' => 'sent']);

        $contract->update(['status' => 'msaned']);
        $rows = RecruitmentStageNotification::where('contract_id', $contract->id)->get();
        $this->assertSame(['pending'], $rows->pluck('status')->unique()->values()->all(), 'لا إرسال قبل نجاح الحفظ');

        // معاملة الاختبار لا تُعتمد أبدًا؛ نحاكي اعتمادها ثم نهاية الطلب
        app('db.transactions')->getTransactions()->last()->executeCallbacks();
        $this->app->terminate();

        $this->assertSame(['sent'], $rows->map(fn ($row) => $row->fresh()->status)->unique()->values()->all());
    }

    public function test_rolled_back_update_leaves_no_notification(): void
    {
        $this->enable();
        $contract = $this->contract('new');
        DB::beginTransaction();
        $contract->update(['status' => 'msaned']);
        DB::rollBack();
        $this->assertSame(0, RecruitmentStageNotification::where('contract_id', $contract->id)->count());
        $this->assertSame('new', $contract->fresh()->status);
    }

    public function test_optional_steps_do_not_block_stage_and_arrival_has_no_steps(): void
    {
        $step = RecruitmentContractStageStep::create(['stage' => 'msaned', 'name_ar' => 'اختيارية',
            'is_active' => true, 'is_required' => false]);
        $contract = $this->contract();
        $service = app(RecruitmentContractStageStepService::class);
        $this->assertSame($step->id, $service->currentStep($contract)?->id);
        $service->assertStageComplete($contract);
        RecruitmentContractStageStep::create(['stage' => 'arrival', 'name_ar' => 'خطوة قديمة', 'is_active' => true]);
        $contract->status = 'arrival';
        $this->assertCount(0, $service->applicableSteps($contract));
    }

    public function test_existing_contract_keeps_steps_when_settings_change(): void
    {
        $step = RecruitmentContractStageStep::create(['stage' => 'msaned', 'name_ar' => 'الاسم الأصلي',
            'is_active' => true, 'is_required' => true]);
        $old = $this->contract();
        RecruitmentContractStageStepService::freezeExistingContracts();
        $step->update(['name_ar' => 'اسم معدل', 'is_required' => false]);
        $new = $this->contract();
        $service = app(RecruitmentContractStageStepService::class);
        $oldStep = $service->applicableSteps($old->fresh())->firstWhere('id', $step->id);
        $newStep = $service->applicableSteps($new)->firstWhere('id', $step->id);
        $this->assertSame('الاسم الأصلي', $oldStep->name_ar);
        $this->assertTrue($oldStep->is_required);
        $this->assertSame('اسم معدل', $newStep->name_ar);
        $this->assertFalse($newStep->is_required);
    }

    public function test_step_reopening_does_not_send_duplicate_message(): void
    {
        $this->enable();
        $step = RecruitmentContractStageStep::create(['stage' => 'msaned', 'name_ar' => 'خطوة',
            'is_active' => true, 'notify_email' => true]);
        $contract = $this->contract();
        $service = app(RecruitmentContractStageStepService::class);
        $service->completeMany($contract, 'msaned', [['id' => $step->id, 'completed_at' => '2026-01-01']], null);
        $service->reopenFrom($contract, $step);
        $service->completeMany($contract, 'msaned', [['id' => $step->id, 'completed_at' => '2026-01-02']], null);
        $this->assertSame(1, RecruitmentStageNotification::where('contract_id', $contract->id)->count());
    }

    public function test_delivery_claim_prevents_duplicate_sms(): void
    {
        $this->enable();
        $contract = $this->contract();
        $notifier = app(RecruitmentStageNotifier::class);
        $notifier->stageChanged($contract, 'new');
        $sms = RecruitmentStageNotification::where('contract_id', $contract->id)->where('channel', 'sms')->firstOrFail();
        $this->mock(TeqnyatSmsService::class)->shouldReceive('sendMessage')->once()->andReturn(['status' => 'sent']);
        $notifier->deliver($sms);
        $notifier->deliver($sms);
        $this->assertSame('sent', $sms->fresh()->status);
        $this->assertNotNull($sms->fresh()->sent_at);
    }

    public function test_email_failure_does_not_prevent_sms(): void
    {
        $this->enable();
        $contract = $this->contract();
        $notifier = app(RecruitmentStageNotifier::class);
        $notifier->stageChanged($contract, 'new');
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('test mail failure'));
        $this->mock(TeqnyatSmsService::class)->shouldReceive('sendMessage')->once()->andReturn(['status' => 'sent']);
        $rows = RecruitmentStageNotification::where('contract_id', $contract->id)->get()->keyBy('channel');
        $notifier->deliver($rows['email']);
        $notifier->deliver($rows['sms']);
        $this->assertSame('failed', $rows['email']->fresh()->status);
        $this->assertSame('sent', $rows['sms']->fresh()->status);
    }

    public function test_queued_message_is_cancelled_if_stage_was_rolled_back(): void
    {
        $this->enable();
        $contract = $this->contract();
        $notifier = app(RecruitmentStageNotifier::class);
        $notifier->stageChanged($contract, 'new');
        $row = RecruitmentStageNotification::where('contract_id', $contract->id)->where('channel', 'sms')->firstOrFail();
        $contract->updateQuietly(['status' => 'new']);
        $this->mock(TeqnyatSmsService::class)->shouldNotReceive('sendMessage');
        $notifier->deliver($row);
        $this->assertSame('cancelled', $row->fresh()->status);
    }

    public function test_queued_message_is_cancelled_if_contact_changes(): void
    {
        $this->enable();
        $contract = $this->contract();
        $notifier = app(RecruitmentStageNotifier::class);
        $notifier->stageChanged($contract, 'new');
        $row = RecruitmentStageNotification::where('contract_id', $contract->id)->where('channel', 'email')->firstOrFail();
        $contract->user->update(['email' => uniqid('changed').'@example.test']);
        Mail::shouldReceive('raw')->never();
        $notifier->deliver($row);
        $this->assertSame('cancelled', $row->fresh()->status);
    }

    public function test_settings_reject_steps_for_arrival(): void
    {
        $admin = \App\Models\auth\Admin::factory()->create();
        $admin->givePermissionTo(\App\Models\Permission::firstOrCreate([
            'name' => \App\PermKeys::SETTINGS_CONTROL, 'guard_name' => 'admin',
        ]));
        $this->actingAs($admin, 'admin');
        $request = \Illuminate\Http\Request::create('/', 'PUT', [
            'labels' => \App\Support\RecruitmentContractStageLabels::defaults(),
            'order_labels' => \App\Support\RecruitmentOrderStatusLabels::defaults(),
            'steps' => [['stage' => 'arrival', 'name_ar' => 'غير مسموح']],
        ]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(\App\Http\Controllers\ingaz\RecruitmentContractStageLabelController::class)->update($request);
    }

    /** المسار الكامل: صفحة الإعدادات → الحفظ عبر المسار الحقيقي → تغيير مرحلة العقد → أمر الإرسال المجدول. */
    public function test_settings_page_save_then_stage_change_delivers_messages(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        $admin = \App\Models\auth\Admin::factory()->global()->create();
        foreach ([\App\PermKeys::SETTINGS_VIEW, \App\PermKeys::SETTINGS_CONTROL] as $perm) {
            $admin->givePermissionTo(\App\Models\Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'admin']));
        }
        $this->actingAs($admin, 'admin');
        $labels = \App\Support\RecruitmentContractStageLabels::labels();

        $html = $this->get(route('recruitment-contract-stages.edit'))->assertOk()->getContent();
        $default = str_replace(['{step_name}', '{stage_name}'], ['', $labels['tafwed']['ar']], Settings::TEMPLATE);
        $this->assertStringContainsString(e($default), $html);
        $this->assertStringNotContainsString('@continue', $html);

        $this->put(route('recruitment-contract-stages.update'), [
            'labels' => $labels,
            'order_labels' => \App\Support\RecruitmentOrderStatusLabels::labels(),
            'notifications' => ['enabled' => '1', 'stages' => [
                'msaned' => ['sms' => '1', 'email' => '1', 'template' => ''],
                'tafwed' => ['sms' => '0', 'email' => '1', 'template' => 'مرحبا {client_name}، عقدك {contract_number} في {stage_name}'],
                'taffez' => ['sms' => '0', 'email' => '0', 'template' => ''],
            ]],
        ])->assertRedirect(route('recruitment-contract-stages.edit'))->assertSessionHasNoErrors();

        $contract = $this->contract('new');
        $contract->update(['status' => 'msaned']);
        $contract->update(['status' => 'tafwed']);
        $contract->update(['status' => 'taffez']);

        $rows = RecruitmentStageNotification::where('contract_id', $contract->id)->orderBy('id')->get();
        $this->assertSame(['msaned:sms', 'msaned:email', 'tafwed:email'],
            $rows->map(fn ($row) => substr($row->event_key, 6).':'.$row->channel)->all());
        $this->assertSame(strtr(str_replace('{step_name}', '', Settings::TEMPLATE), [
            '{client_name}' => 'عميل الاختبار', '{contract_number}' => $contract->id, '{stage_name}' => $labels['msaned']['ar'],
        ]), $rows[0]->message);
        $this->assertSame("مرحبا عميل الاختبار، عقدك {$contract->id} في {$labels['tafwed']['ar']}", $rows[2]->message);

        Mail::fake();
        $this->mock(TeqnyatSmsService::class)->shouldReceive('sendMessage')->once()
            ->withArgs(fn ($phone, $message) => $phone === $contract->user->phone && $message === $rows[0]->message)
            ->andReturn(['status' => 'sent']);
        $this->artisan('recruitment:send-stage-notifications')->assertExitCode(0);

        $this->assertSame(['sent'], $rows->map(fn ($row) => $row->fresh()->status)->unique()->values()->all());
    }

    public function test_notification_settings_partial_renders_saved_channels(): void
    {
        $html = view('admin.ingaz.recruitment_contract_stage_labels._notifications', [
            'labels' => \App\Support\RecruitmentContractStageLabels::defaults(),
            'notificationSettings' => ['enabled' => true, 'stages' => ['arrival' => ['email' => true]]],
        ])->render();
        $this->assertMatchesRegularExpression('/name="notifications\[stages\]\[arrival\]\[email\]"[^>]+checked/s', $html);
        $this->assertStringNotContainsString('@continue', $html);
    }
}
