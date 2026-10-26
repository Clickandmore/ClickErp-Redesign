<?php

namespace Tests\Feature;

use App\Models\ingaz\User;
use App\Models\RentalContractExpiryReminder;
use App\Services\RentalContractExpiryReminderService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalContractExpiryReminderCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2040, 6, 10)->setTime(9, 0));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function contract(int $days, string $status = 'active'): int
    {
        $user = User::withoutEvents(fn () => User::create(['name' => 'Reminder test', 'phone' => Str::random(20)]));
        $id = DB::table('rental_contracts')->insertGetId(['user_id' => $user->id, 'status' => $status]);
        DB::table('rental_contract_infos')->insert(['rental_contract_id' => $id, 'ingaz_to' => now()->addDays($days)->toDateString()]);
        return $id;
    }

    private function reminder(int $id, array $attributes = []): void
    {
        DB::table('rental_contract_expiry_reminders')->insert(array_merge([
            'rental_contract_id' => $id, 'token' => Str::random(64),
            'notification_type' => 'advance', 'expires_on' => DB::table('rental_contract_infos')->where('rental_contract_id', $id)->value('ingaz_to'),
            'sms_sent' => false, 'whatsapp_sent' => false,
        ], $attributes));
    }

    private function service(): \Mockery\MockInterface
    {
        $service = \Mockery::mock(RentalContractExpiryReminderService::class);
        $service->shouldReceive('isEnabled')->andReturn(true);
        $service->shouldReceive('reminderDays')->andReturn(5);
        $this->app->instance(RentalContractExpiryReminderService::class, $service);
        return $service;
    }

    public function test_failed_reminders_are_retried_but_successful_or_answered_are_excluded(): void
    {
        $failed = $this->contract(5);
        $this->reminder($failed, ['send_error' => 'Provider unavailable']);
        $sent = $this->contract(5);
        $this->reminder($sent, ['sms_sent' => true]);
        $answered = $this->contract(5);
        $this->reminder($answered, ['responded_at' => now()]);
        $this->service()->shouldReceive('createAndSendReminder')->once()
            ->withArgs(fn ($contract, $type) => $contract->id === $failed && $type === 'advance')
            ->andReturn(new RentalContractExpiryReminder(['sms_sent' => true]));
        $this->artisan('rental-contracts:send-expiry-reminders', ['--type' => 'advance'])->assertSuccessful();
    }

    public function test_missed_advance_run_is_caught_up_without_expired_closed_or_future_contracts(): void
    {
        $eligible = $this->contract(2);
        $this->contract(-1);
        $this->contract(6);
        $this->contract(2, 'completed');
        $this->service()->shouldReceive('createAndSendReminder')->once()
            ->withArgs(fn ($contract, $type) => $contract->id === $eligible && $type === 'advance')->andReturnNull();
        $this->artisan('rental-contracts:send-expiry-reminders', ['--type' => 'advance'])->assertSuccessful();
    }

    public function test_extension_has_a_new_reminder_even_when_old_end_date_was_sent(): void
    {
        $id = $this->contract(3);
        $this->reminder($id, ['expires_on' => now()->subDays(7)->toDateString(), 'sms_sent' => true]);
        $this->service()->shouldReceive('createAndSendReminder')->once()
            ->withArgs(fn ($contract, $type) => $contract->id === $id && $type === 'advance')->andReturnNull();
        $this->artisan('rental-contracts:send-expiry-reminders', ['--type' => 'advance'])->assertSuccessful();
    }

    public function test_dry_run_does_not_send_or_create_reminders(): void
    {
        $id = $this->contract(5);
        $service = $this->service();
        $service->shouldReceive('channel')->andReturn('sms');
        $service->shouldNotReceive('createAndSendReminder');
        $this->artisan('rental-contracts:send-expiry-reminders', ['--type' => 'advance', '--dry-run' => true])
            ->expectsOutput('Eligible advance: contract #'.$id.'; expires '.now()->addDays(5)->toDateString().'; client record: yes; phone present: yes')->assertSuccessful();
        $this->assertDatabaseMissing('rental_contract_expiry_reminders', ['rental_contract_id' => $id]);
    }

    public function test_expired_reminders_only_target_today_and_retry_the_failed_attempt(): void
    {
        $id = $this->contract(0);
        $this->reminder($id, ['notification_type' => 'expired', 'send_error' => 'Provider unavailable']);
        $this->contract(-1);
        $this->contract(1);
        $this->service()->shouldReceive('createAndSendReminder')->once()
            ->withArgs(fn ($contract, $type) => $contract->id === $id && $type === 'expired')->andReturnNull();
        $this->artisan('rental-contracts:send-expiry-reminders', ['--type' => 'expired'])->assertSuccessful();
    }
}
