<?php

namespace Tests\Feature;

use App\Models\OutboundMessageLog;
use App\Models\NewSettings;
use App\Models\Permission;
use App\Models\auth\Admin;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\WhatsAppWebhookService;
use App\Support\OutboundMessageRecorder as Recorder;
use App\Support\SmsGateway;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OutboundMessageAuditTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.whatsapp.driver' => 'meta', 'services.whatsapp.phone_number_id' => 'test-phone', 'services.whatsapp.access_token' => 'test-secret',
            'services.whatsapp.webhook_verify_token' => 'test-verify', 'services.whatsapp.app_secret' => 'test-app']);
    }

    private function smsSettings(bool $enabled = true): void
    {
        NewSettings::updateOrCreate(['key' => 'communication_channels'], ['value' => json_encode(['sms' => [
            'provider' => 'taqnyat', 'gateway_status' => $enabled ? 'on' : 'off', 'sender' => 'TEST',
            'taqnyat' => ['base_url' => 'https://sms.example.test', 'bearer_token' => 'secret-token'],
        ]])]);
    }

    public function test_sms_acceptance_failure_and_disabled_attempts_are_logged_without_changing_return_values(): void
    {
        $this->smsSettings();
        Http::fake(['sms.example.test/*' => Http::sequence()->push(['statusCode' => 201, 'messageId' => 'sms-1'])->push(['message' => 'Balance exhausted'], 402)]);
        $this->assertNotNull(SmsGateway::send('966501234567', 'Audit successful SMS'));
        $this->assertNull(SmsGateway::send('966501234567', 'Audit failed SMS'));
        $this->assertDatabaseHas('outbound_message_logs', ['message' => 'Audit successful SMS', 'status' => 'accepted', 'channel' => 'sms']);
        $this->assertDatabaseHas('outbound_message_logs', ['message' => 'Audit failed SMS', 'status' => 'failed', 'http_status' => 402, 'error' => 'Balance exhausted']);
        $this->smsSettings(false);
        $this->assertNull(SmsGateway::send('966501234567', 'Audit disabled SMS'));
        $this->assertDatabaseHas('outbound_message_logs', ['message' => 'Audit disabled SMS', 'status' => 'skipped']);
        Http::assertSentCount(2);
    }

    public function test_otp_and_sensitive_provider_echo_are_redacted(): void
    {
        $this->smsSettings();
        Http::fake(['sms.example.test/*' => Http::response(['message' => 'Your OTP 739281 rejected', 'api_key' => 'secret-token'], 400)]);
        SmsGateway::send('966501234567', 'Your OTP is 739281', '739281', 'verification');
        $log = OutboundMessageLog::latest('id')->firstOrFail();
        $this->assertSame('verification', $log->purpose);
        $this->assertSame('failed', $log->status);
        $this->assertStringNotContainsString('739281', $log->toJson());
        $this->assertStringNotContainsString('secret-token', $log->toJson());
    }

    public function test_whatsapp_is_logged_once_and_webhook_updates_delivery_outside_inbox(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.audit-test']]])]);
        $result = app(\App\Services\WhatsApp\WhatsAppMessagingService::class)->sendText('0501234567', 'Audit WhatsApp', ['source' => 'rental_contract_expiry_reminder', 'contract_id' => 123]);
        $this->assertTrue($result['success']);
        $this->assertSame(1, OutboundMessageLog::where('provider_message_id', 'wamid.audit-test')->count());
        app(WhatsAppWebhookService::class)->handle(['entry' => [['changes' => [['value' => ['statuses' => [['id' => 'wamid.audit-test', 'status' => 'delivered', 'timestamp' => now()->timestamp]]]]]]]]);
        $log = OutboundMessageLog::where('provider_message_id', 'wamid.audit-test')->firstOrFail();
        $this->assertSame('delivered', $log->status);
        $this->assertNotNull($log->delivered_at);
        Recorder::whatsAppStatus('wamid.audit-test', 'sent', []);
        $this->assertSame('delivered', $log->fresh()->status);
    }

    public function test_whatsapp_template_and_configuration_failures_are_logged(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template rejected']], 400)]);
        app(MetaWhatsAppService::class)->sendTemplate('0501234567', 'audit_template', 'ar', ['Customer']);
        $this->assertDatabaseHas('outbound_message_logs', ['channel' => 'whatsapp', 'status' => 'failed', 'error' => 'Template rejected']);
        config(['services.whatsapp.access_token' => '']);
        app(MetaWhatsAppService::class)->sendText('0501234567', 'Audit unconfigured WhatsApp');
        $this->assertDatabaseHas('outbound_message_logs', ['message' => 'Audit unconfigured WhatsApp', 'status' => 'skipped']);
    }

    public function test_mail_transport_logs_success_and_redacts_password_reset_links(): void
    {
        Mail::mailer('array')->raw('Audit email body', fn ($mail) => $mail->to('audit@example.test')->subject('Audit email'));
        $this->assertDatabaseHas('outbound_message_logs', ['channel' => 'email', 'recipient' => 'audit@example.test', 'status' => 'skipped']);
        Mail::mailer('array')->raw('Reset password https://example.test/reset/secret-reset-token', fn ($mail) => $mail->to('audit@example.test')->subject('Reset password'));
        $log = OutboundMessageLog::latest('id')->firstOrFail();
        $this->assertSame('verification', $log->purpose);
        $this->assertStringNotContainsString('secret-reset-token', $log->toJson());
    }

    public function test_mail_transport_records_exceptions_and_preserves_them(): void
    {
        $transport = \Mockery::mock(\Swift_Transport::class);
        $transport->shouldReceive('send')->once()->andThrow(new \RuntimeException('SMTP connection refused'));
        $mail = (new \Swift_Message('Audit exception'))->setTo('failed@example.test')->setBody('Audit body');
        try { (new \App\Support\LoggingMailTransport($transport))->send($mail); $this->fail('Expected transport error'); }
        catch (\RuntimeException $e) { $this->assertSame('SMTP connection refused', $e->getMessage()); }
        $this->assertDatabaseHas('outbound_message_logs', ['recipient' => 'failed@example.test', 'status' => 'failed', 'error' => 'SMTP connection refused']);
    }

    public function test_in_app_notifications_are_audited_but_sms_history_does_not_duplicate_sends(): void
    {
        $before = OutboundMessageLog::count();
        \App\Models\ingaz\Notification::create(['title' => 'Audit notification', 'description' => 'Audit body', 'type' => 'general']);
        $this->assertSame($before + 1, OutboundMessageLog::count());
        $this->assertDatabaseHas('outbound_message_logs', ['channel' => 'notification', 'status' => 'delivered']);
        \App\Models\ingaz\Notification::create(['title' => 'Audit SMS history', 'description' => 'Audit body', 'type' => 'sms']);
        $this->assertSame($before + 1, OutboundMessageLog::count());
    }

    public function test_push_disabled_attempts_are_logged_without_exposing_device_tokens(): void
    {
        config(['firebase.enabled' => false]);
        app(\App\Services\Firebase\FirebasePushService::class)->sendToTokens(['device-secret-token'], 'Audit push', 'Audit push body');
        $log = OutboundMessageLog::latest('id')->firstOrFail();
        $this->assertSame('push', $log->channel);
        $this->assertSame('skipped', $log->status);
        $this->assertStringNotContainsString('device-secret-token', $log->toJson());
    }

    public function test_permission_is_required_and_filters_apply_to_list_and_counts(): void
    {
        $actor = Admin::factory()->global()->create();
        $this->actingAs($actor, 'admin');
        $this->get(route('messages.index'))->assertForbidden();
        $actor->givePermissionTo(Permission::firstOrCreate(['name' => \App\PermKeys::NOTIFICATIONS_CONTROL, 'guard_name' => 'admin']));
        $this->withoutExceptionHandling();
        $log = Recorder::begin('sms', '966509999999', 'Unique filtered audit message', 'test-provider', ['source' => 'audit_filter_test', 'contract_id' => 98765]);
        Recorder::finish($log, 'failed', ['error_message' => '<script>alert(1)</script>']);
        Recorder::finish(Recorder::begin('whatsapp', '966508888888', 'Excluded message', 'test-provider', ['source' => 'audit_filter_test']), 'accepted');
        $response = $this->get(route('messages.index', ['type' => 'sms', 'status' => 'failed', 'source' => 'audit_filter_test', 'contract_id' => 98765]));
        $response->assertOk()->assertViewHas('logs', fn ($logs) => $logs->total() === 1 && $logs->first()->id === $log->id);
        $response->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_mixed_sms_batch_result_is_classified_per_recipient(): void
    {
        $result = ['accepted' => ['966501111111'], 'rejected' => ['966502222222']];
        $this->assertTrue(Recorder::smsAccepted($result, '966501111111'));
        $this->assertFalse(Recorder::smsAccepted($result, '966502222222'));
        $this->assertFalse(Recorder::smsAccepted('{"success":"false"}'));
    }

    public function test_branch_filters_cannot_reveal_another_branch_or_its_details(): void
    {
        $this->enableBranchesModule();
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $actor = $this->lockToOwnBranch($this->branchAdmin($own));
        $actor->givePermissionTo(Permission::firstOrCreate(['name' => \App\PermKeys::NOTIFICATIONS_CONTROL, 'guard_name' => 'admin']));
        Recorder::finish(Recorder::begin('sms', 'branch-own', 'Own branch message', 'audit-test', ['source' => 'branch_audit_test', 'branch_id' => $own->id]), 'accepted');
        Recorder::finish(Recorder::begin('sms', 'branch-other', 'Private other branch message', 'audit-test', ['source' => 'branch_audit_test', 'branch_id' => $other->id]), 'failed');
        $this->actingAs($actor, 'admin');
        $this->get(route('messages.index', ['source' => 'branch_audit_test']))->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 1)->assertDontSee('Private other branch message');
        $this->get(route('messages.index', ['source' => 'branch_audit_test', 'branch_id' => $other->id]))->assertOk()
            ->assertViewHas('logs', fn ($logs) => $logs->total() === 0);
    }

    public function test_history_backfill_is_idempotent_and_does_not_invent_sms_success(): void
    {
        $id = \Illuminate\Support\Facades\DB::table('notifications')->insertGetId([
            'title' => json_encode(['ar' => 'Old SMS']), 'description' => json_encode(['ar' => 'Old message']),
            'type' => 'sms', 'created_at' => '1899-01-01 00:00:00', 'updated_at' => '1899-01-01 00:00:00',
        ]);
        Http::fake();
        $this->artisan('messages:backfill-logs', ['--before' => '1900-01-01'])->assertSuccessful();
        $this->artisan('messages:backfill-logs', ['--before' => '1900-01-01'])->assertSuccessful();
        $this->assertSame(1, OutboundMessageLog::where('legacy_key', 'notifications:' . $id)->count());
        $this->assertDatabaseHas('outbound_message_logs', ['legacy_key' => 'notifications:' . $id, 'status' => 'unknown']);
        Http::assertNothingSent();
    }
}
