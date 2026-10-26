<?php

namespace Tests\Feature;

use App\Models\RentalContract;
use App\Models\ingaz\User;
use App\Models\ingaz\Notification;
use App\Services\ConfiguredNotificationAlertService;
use App\Services\RentalContractExpiryReminderService;
use App\Support\NotificationAlertSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalAlertEligibilityTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2040, 6, 10)->setTime(9, 0));
        Http::fake();
        $rules = NotificationAlertSettings::all();
        foreach ($rules as &$rule) $rule['enabled'] = false;
        unset($rule);
        foreach (['rent_contract_expiry', 'rent_contract_warranty'] as $key) {
            $rules[$key] = array_replace($rules[$key], ['enabled' => true, 'channels' => ['notification'],
                'recipient' => 'client', 'lead_time_value' => 5, 'lead_time_unit' => 'days',
                'repeat_every_value' => 0, 'template_id' => null]);
        }
        NotificationAlertSettings::persist($rules);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function contract(string $status, bool $closed = false): RentalContract
    {
        $user = User::withoutEvents(fn () => User::create(['name' => 'Rental alert eligibility', 'phone' => Str::random(20)]));
        $id = DB::table('rental_contracts')->insertGetId(['user_id' => $user->id, 'status' => $status,
            'closed_at' => $closed ? now()->subDay() : null,
            'warranty_status' => 'active', 'warranty_end_date' => now()->addDays(2)->toDateString()]);
        DB::table('rental_contract_infos')->insert(['rental_contract_id' => $id, 'ingaz_to' => now()->addDays(2)->toDateString()]);
        return RentalContract::findOrFail($id);
    }

    public function test_general_alert_job_excludes_cancelled_completed_and_closed_contracts_on_all_runs(): void
    {
        $eligible = [];
        foreach (['active', 'maid_delivered', 'contract_signed'] as $status) $eligible[] = $this->contract($status);
        $excluded = [];
        foreach (['cancelled', 'completed', 'frozen', 'suspended', 'new'] as $status) $excluded[] = $this->contract($status);
        $excluded[] = $this->contract('active', true);
        $service = app(ConfiguredNotificationAlertService::class);
        $result = $service->run();
        $this->assertSame(6, $result['sent']);
        foreach ($eligible as $contract) {
            $this->assertSame(2, Notification::where('user_id', $contract->user_id)->where('type', 'like', 'configured_alert:rent_contract_%')->count());
        }
        foreach ($excluded as $contract) $this->assertFalse(Notification::where('user_id', $contract->user_id)->exists());
        $this->assertSame(0, $service->run()['sent']);
        Http::assertNothingSent();
    }

    public function test_dedicated_sender_rechecks_cancellation_after_a_contract_was_loaded(): void
    {
        $contract = $this->contract('active');
        DB::table('rental_contracts')->where('id', $contract->id)->update(['status' => 'cancelled']);
        $this->assertNull(app(RentalContractExpiryReminderService::class)->createAndSendReminder($contract));
        $this->assertFalse($contract->expiryReminders()->exists());
        Http::assertNothingSent();
    }
}
