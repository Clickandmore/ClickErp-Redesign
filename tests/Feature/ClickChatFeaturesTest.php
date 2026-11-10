<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\OutboundMessageLog;
use App\Models\Permission;
use App\Models\WhatsAppOptOut;
use App\Models\WhatsAppSendRetry;
use App\Models\auth\Admin;
use App\Services\WhatsApp\ClickChatMessageStore;
use App\Services\WhatsApp\ClickChatWhatsAppService;
use App\Services\WhatsApp\WhatsAppDocumentService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\CommunicationSettings;
use App\Support\OutboundMessageRecorder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClickChatFeaturesTest extends TestCase
{
    use DatabaseTransactions;

    private const PHONE = '966500000888';

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.whatsapp.driver' => 'clickchat', 'services.whatsapp.default_country_code' => '966',
            'services.whatsapp.clickchat' => ['base_url' => 'https://chat.example.test', 'api_token' => '12|token',
                'webhook_secret' => 'whsec_x', 'default_channel_account_id' => '3', 'branch_senders' => [],
                'fallback' => 'none', 'auto_retry' => true, 'alert_admin_ids' => []],
            'services.whatsapp.opt_out' => ['enabled' => true, 'stop_keywords' => 'إيقاف, stop', 'start_keywords' => 'اشتراك']]);
        RateLimiter::clear('clickchat-send:' . sha1('12|token'));
    }

    private function sent(): array
    {
        return ['message_id' => random_int(100000, 999999), 'conversation_id' => 57, 'contact_id' => 42, 'channel_account_id' => 3, 'status' => 'sent'];
    }

    public function test_branch_number_is_used_unless_a_sender_is_chosen(): void
    {
        config(['services.whatsapp.clickchat.branch_senders' => ['5' => '9']]);
        $service = app(ClickChatWhatsAppService::class);

        $this->assertSame(9, $service->channelAccountId(['branch_id' => 5]));
        $this->assertSame(7, $service->channelAccountId(['branch_id' => 5, 'sender_key' => '7']));
        $this->assertSame(3, $service->channelAccountId(['branch_id' => 6]));
    }

    public function test_refused_send_is_retried_later_and_the_log_follows(): void
    {
        Http::fakeSequence()->push(['message' => 'Too Many Attempts.'], 429)->push($this->sent());

        $result = app(WhatsAppMessagingService::class)->sendText(self::PHONE, 'Retry me please', ['reference' => 'INV-77']);
        $this->assertFalse($result['success']);
        $this->assertTrue($result['retry_scheduled']);
        $retry = WhatsAppSendRetry::latest('id')->firstOrFail();
        $this->assertSame('pending', $retry->status);
        $this->assertSame('pending', OutboundMessageLog::withoutBranchScope()->find($retry->outbound_message_log_id)->status);

        // Not due yet, then due.
        $this->artisan('whatsapp:clickchat-retry')->assertSuccessful();
        $this->assertSame('pending', $retry->fresh()->status);
        $retry->update(['next_attempt_at' => now()->subSecond()]);
        $this->artisan('whatsapp:clickchat-retry')->assertSuccessful();

        $this->assertSame('sent', $retry->fresh()->status);
        $this->assertSame('accepted', OutboundMessageLog::withoutBranchScope()->find($retry->outbound_message_log_id)->status);
        Http::assertSent(fn ($r) => $r['reference'] === 'INV-77');
        Http::assertSentCount(2);
    }

    public function test_login_codes_are_never_kept_for_a_retry(): void
    {
        Http::fake(['*' => Http::response([], 503)]);

        $result = app(WhatsAppMessagingService::class)->sendText(self::PHONE, 'Your code is 4821', ['purpose' => 'verification']);

        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('retry_scheduled', $result);
        $this->assertSame(0, WhatsAppSendRetry::where('message', 'like', '%4821%')->count());
    }

    public function test_closed_window_falls_back_to_ultramsg_when_chosen(): void
    {
        CommunicationSettings::persistSection('whatsapp', ['driver' => 'clickchat',
            'clickchat' => ['base_url' => 'https://chat.example.test', 'api_token' => '12|token', 'fallback' => 'ultramsg', 'alert_admin_ids' => []],
            'ultramsg' => ['api_url' => 'https://ultra.example.test', 'default_instance_key' => 'u1',
                'instances' => [['key' => 'u1', 'name' => 'Backup', 'instance_id' => 'instance9', 'token' => 'ultra-token', 'enabled' => true]]]]);
        Http::fake([
            'chat.example.test/*' => Http::response(['error' => 'WhatsApp 24-hour session is closed.', 'window_closed' => true], 422),
            'ultra.example.test/*' => Http::response(['sent' => 'true', 'id' => 55]),
        ]);

        $result = app(WhatsAppMessagingService::class)->sendText(self::PHONE, 'Contract reminder');

        $this->assertTrue($result['success']);
        $this->assertSame('ultramsg', $result['fallback_provider']);
        $this->assertSame('window_closed', $result['clickchat_error']);
        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://ultra.example.test/instance9/messages/chat') && $r['body'] === 'Contract reminder');
    }

    public function test_stop_word_blocks_automated_messages_but_not_codes_or_inbox_replies(): void
    {
        Http::fake(['*' => Http::response($this->sent())]);
        app(ClickChatMessageStore::class)->handleEvent(['event' => 'message.received',
            'message' => ['id' => 990001, 'direction' => 'in', 'type' => 'text', 'body' => ' إيقاف ', 'status' => 'delivered'],
            'contact' => ['name' => 'Customer', 'phone_e164' => '+' . self::PHONE], 'conversation' => ['id' => 57]]);
        $this->assertTrue(WhatsAppOptOut::where('phone', self::PHONE)->exists());

        $blocked = app(WhatsAppMessagingService::class)->sendText(self::PHONE, 'Promo');
        $this->assertSame('opted_out', $blocked['error']);
        Http::assertNothingSent();
        $this->assertDatabaseHas('outbound_message_logs', ['id' => $blocked['audit_log_id'], 'status' => 'skipped']);

        $this->assertTrue(app(WhatsAppMessagingService::class)->sendText(self::PHONE, 'Code 1234', ['purpose' => 'verification'])['success']);
        $this->assertTrue(app(ClickChatWhatsAppService::class)->sendText(self::PHONE, 'Hi', ['source' => 'whatsapp_inbox'])['success']);

        app(ClickChatMessageStore::class)->handleEvent(['event' => 'message.received',
            'message' => ['id' => 990002, 'direction' => 'in', 'type' => 'text', 'body' => 'اشتراك', 'status' => 'delivered'],
            'contact' => ['phone_e164' => '+' . self::PHONE], 'conversation' => ['id' => 57]]);
        $this->assertFalse(WhatsAppOptOut::where('phone', self::PHONE)->exists());
    }

    public function test_a_sentence_containing_a_stop_word_is_not_an_opt_out(): void
    {
        app(ClickChatMessageStore::class)->handleEvent(['event' => 'message.received',
            'message' => ['id' => 990003, 'direction' => 'in', 'type' => 'text', 'body' => 'لا أريد إيقاف العقد', 'status' => 'delivered'],
            'contact' => ['phone_e164' => '+' . self::PHONE], 'conversation' => ['id' => 57]]);

        $this->assertFalse(WhatsAppOptOut::where('phone', self::PHONE)->exists());
    }

    public function test_a_local_file_is_sent_as_a_real_attachment(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('shares/cv-1.pdf', '%PDF-1.4 test');
        Http::fake(['*' => Http::response($this->sent())]);

        $result = app(WhatsAppDocumentService::class)->send(self::PHONE, 'https://erp.test/signed', 'CV-1.pdf', ['attachment_path' => 'shares/cv-1.pdf']);

        $this->assertTrue($result['success']);
        Http::assertSent(fn ($r) => $r->isMultipart()
            && collect($r->data())->contains(fn ($part) => ($part['name'] ?? '') === 'file' && ($part['filename'] ?? '') === 'CV-1.pdf'));
    }

    public function test_templates_are_listed_and_filled_by_number(): void
    {
        Http::fake(['*' => Http::response(['data' => [
            ['name' => 'invoice_ready', 'language' => 'ar', 'category' => 'UTILITY', 'body' => 'مرحباً {{1}}، فاتورتك {{2}} جاهزة', 'variables' => 2, 'sendable_by_api' => true],
            ['name' => 'with_image', 'language' => 'ar', 'category' => 'UTILITY', 'body' => 'x', 'variables' => 0, 'sendable_by_api' => false],
        ]])]);
        $service = app(ClickChatWhatsAppService::class);

        $this->assertSame(['invoice_ready'], array_column($service->templates(), 'name'));
        $prepared = $service->prepareTemplate('invoice_ready|ar', [1 => 'أحمد', 2 => 'INV-1']);
        $this->assertSame('مرحباً أحمد، فاتورتك INV-1 جاهزة', $prepared['message']);
        $this->assertSame(['أحمد', 'INV-1'], $prepared['clickchat_template']['vars']);

        $this->expectException(\RuntimeException::class);
        $service->prepareTemplate('invoice_ready|ar', [1 => 'أحمد']);
    }

    public function test_rejected_key_alerts_the_chosen_admins_once(): void
    {
        $admin = Admin::factory()->global()->create();
        config(['services.whatsapp.clickchat.alert_admin_ids' => [$admin->id]]);
        Cache::forget('clickchat-alert:credentials');
        Http::fake(['*' => Http::response(['message' => 'Unauthenticated.'], 401)]);

        app(WhatsAppMessagingService::class)->sendText(self::PHONE, 'One');
        app(WhatsAppMessagingService::class)->sendText(self::PHONE, 'Two');

        $this->assertSame(1, AdminNotification::where('admin_id', $admin->id)->where('type', 'whatsapp_clickchat')->count());
    }

    public function test_health_check_alerts_when_a_sending_number_is_offline(): void
    {
        $admin = Admin::factory()->global()->create();
        config(['services.whatsapp.clickchat.alert_admin_ids' => [$admin->id]]);
        Http::fake(['*/channels' => Http::response(['data' => [
            ['id' => 3, 'channel' => 'whatsapp', 'type' => 'whatsapp_qr', 'name' => '+966511111111', 'status' => 'disconnected'],
        ]])]);

        $this->artisan('whatsapp:clickchat-sync-statuses')->assertSuccessful();

        $this->assertTrue(AdminNotification::where('admin_id', $admin->id)->where('title', 'like', '%غير متصل%')->exists());
    }

    public function test_a_failed_message_can_be_resent_once_from_the_log(): void
    {
        $actor = Admin::factory()->global()->create();
        $actor->givePermissionTo(Permission::firstOrCreate(['name' => \App\PermKeys::NOTIFICATIONS_CONTROL, 'guard_name' => 'admin']));
        $log = OutboundMessageRecorder::begin('whatsapp', self::PHONE, 'Please resend me', 'clickchat', ['source' => 'resend_test', 'contract_id' => 5]);
        OutboundMessageRecorder::finish($log, 'failed', ['error_message' => 'temporary']);
        $code = OutboundMessageRecorder::begin('whatsapp', self::PHONE, 'Your verification code 9911', 'clickchat', ['purpose' => 'verification']);
        OutboundMessageRecorder::finish($code, 'failed', []);
        Http::fake(['*' => Http::response($this->sent())]);

        $this->actingAs($actor, 'admin')->post(route('messages.resend', $log->id))->assertRedirect()->assertSessionHas('success');
        $this->actingAs($actor, 'admin')->post(route('messages.resend', $log->id))->assertSessionHas('error');
        $this->actingAs($actor, 'admin')->post(route('messages.resend', $code->id))->assertSessionHas('error');

        Http::assertSentCount(1);
        $this->assertNotEmpty($log->fresh()->context['resent_at']);
        $this->assertDatabaseHas('outbound_message_logs', ['message' => 'Please resend me', 'status' => 'accepted', 'provider' => 'clickchat']);
    }
}
