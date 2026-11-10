<?php

namespace Tests\Unit;

use App\Services\WhatsApp\ClickChatMessageStore;
use App\Services\WhatsApp\ClickChatWhatsAppService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ClickChatWhatsAppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.whatsapp.driver' => 'clickchat',
            'services.whatsapp.default_country_code' => '966',
            'services.whatsapp.clickchat' => ['base_url' => 'https://chat.example.test', 'api_token' => '12|secret-token',
                'webhook_secret' => 'whsec_test', 'default_channel_account_id' => '3', 'auto_retry' => false]]);
        $this->app->instance(\App\Services\WhatsApp\ClickChatTemplateBinding::class,
            Mockery::mock(\App\Services\WhatsApp\ClickChatTemplateBinding::class)->shouldReceive('resolve')->andReturn(null)->getMock());
        // Saving is covered by the webhook feature test; here only the HTTP contract matters.
        $this->app->instance(ClickChatMessageStore::class, Mockery::mock(ClickChatMessageStore::class)->shouldIgnoreMissing());
    }

    public function test_messaging_service_routes_text_to_the_platform_with_default_sender_and_reference(): void
    {
        Http::fake(['*' => Http::response(['message_id' => 812, 'conversation_id' => 57, 'contact_id' => 42,
            'channel_account_id' => 3, 'status' => 'sent', 'error' => null])]);

        $result = app(WhatsAppMessagingService::class)->sendText('0500000000', 'فاتورتك جاهزة', ['name' => 'Ahmed', 'reference' => 'INV-1001']);

        $this->assertTrue($result['success']);
        $this->assertSame('812', $result['message_id']);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r->url() === 'https://chat.example.test/api/v1/messages/send'
            && $r->hasHeader('Authorization', 'Bearer 12|secret-token') && $r->hasHeader('Accept', 'application/json')
            && $r['to'] === '+966500000000' && $r['channel_account_id'] === 3 && $r['body'] === 'فاتورتك جاهزة'
            && $r['reference'] === 'INV-1001' && $r['name'] === 'Ahmed' && !isset($r['template_name']));
        Http::assertSentCount(1);
    }

    public function test_sender_key_overrides_default_and_template_replaces_body(): void
    {
        Http::fake(['*' => Http::response(['message_id' => 9, 'status' => 'sent'])]);

        app(ClickChatWhatsAppService::class)->sendText('+966500000000', 'ignored', ['sender_key' => '7',
            'clickchat_template' => ['name' => 'invoice_ready', 'language' => 'ar', 'vars' => ['أحمد', 1001]]]);

        Http::assertSent(fn ($r) => $r['channel_account_id'] === 7 && $r['template_name'] === 'invoice_ready'
            && $r['template_language'] === 'ar' && $r['template_vars'] === ['أحمد', '1001'] && !isset($r['body']));
    }

    public function test_closed_window_and_auth_errors_are_reported_without_retry(): void
    {
        Http::fakeSequence()
            ->push(['error' => 'WhatsApp 24-hour session is closed.', 'window_closed' => true], 422)
            ->push(['message' => 'Unauthenticated.'], 401)
            ->push([], 429);
        $service = app(ClickChatWhatsAppService::class);

        $closed = $service->sendText('0500000000', 'Hi');
        $this->assertFalse($closed['success']);
        $this->assertSame('window_closed', $closed['error']);
        $this->assertTrue($closed['window_closed']);

        $this->assertSame('auth_error', $service->sendText('0500000000', 'Hi')['error']);
        $this->assertSame('rate_limited', $service->sendText('0500000000', 'Hi')['error']);
        Http::assertSentCount(3);
    }

    public function test_failed_send_with_message_id_keeps_the_id(): void
    {
        Http::fake(['*' => Http::response(['message_id' => 15, 'status' => 'failed', 'error' => 'not on WhatsApp'], 422)]);

        $result = app(ClickChatWhatsAppService::class)->sendText('0500000000', 'Hi');

        $this->assertFalse($result['success']);
        $this->assertSame('send_failed', $result['error']);
        $this->assertSame('15', $result['message_id']);
        $this->assertStringContainsString('not on WhatsApp', $result['response']['message']);
    }

    public function test_connection_test_lists_whatsapp_numbers_and_explains_401(): void
    {
        Http::fakeSequence()
            ->push(['data' => [
                ['id' => 3, 'channel' => 'whatsapp', 'type' => 'whatsapp_cloud', 'name' => '+966511111111', 'status' => 'active'],
                ['id' => 7, 'channel' => 'whatsapp', 'type' => 'whatsapp_qr', 'name' => '+966522222222', 'status' => 'active'],
                ['id' => 9, 'channel' => 'sms', 'type' => 'sms', 'name' => 'SMS', 'status' => 'active'],
            ]])
            ->push(['message' => 'Unauthenticated.'], 401);
        $service = app(ClickChatWhatsAppService::class);

        $ok = $service->connectionStatus();
        $this->assertTrue($ok['success']);
        $this->assertSame([3, 7], array_column($ok['channels'], 'id'));
        $this->assertSame(['3', '7'], array_column(app(WhatsAppMessagingService::class)->availableSenders(), 'key'));

        $bad = $service->connectionStatus();
        $this->assertFalse($bad['success']);
        $this->assertStringContainsString('401', $bad['response']['message']);
    }

    public function test_signature_must_match_raw_body_and_be_recent(): void
    {
        $service = app(ClickChatWhatsAppService::class);
        $body = '{"event":"test.ping"}';
        $now = time();
        $sign = fn (int $t, string $raw, string $secret = 'whsec_test') => "t={$t},v1=" . hash_hmac('sha256', "{$t}.{$raw}", $secret);

        $this->assertTrue($service->verifySignature($body, $sign($now, $body)));
        $this->assertFalse($service->verifySignature($body . ' ', $sign($now, $body)));
        $this->assertFalse($service->verifySignature($body, $sign($now, $body, 'whsec_other')));
        $this->assertFalse($service->verifySignature($body, $sign($now - 301, $body)));
        $this->assertFalse($service->verifySignature($body, null));
    }

    public function test_bound_contract_is_sent_as_a_template_for_the_selected_number(): void
    {
        $this->app->instance(\App\Services\WhatsApp\ClickChatTemplateBinding::class,
            Mockery::mock(\App\Services\WhatsApp\ClickChatTemplateBinding::class)->shouldReceive('resolve')->once()
                ->andReturn(['name' => 'contract_update', 'language' => 'ar', 'vars' => ['Ali', '42']])->getMock());
        Http::fake([
            '*/channels' => Http::response(['data' => [['id' => 3, 'type' => 'whatsapp_cloud', 'channel' => 'whatsapp']]]),
            '*/templates*' => Http::response(['data' => [['name' => 'contract_update', 'language' => 'ar', 'category' => 'UTILITY', 'body' => 'Hi {{1}} {{2}}', 'variables' => 2]]]),
            '*/messages/send' => Http::response(['message_id' => 42, 'status' => 'sent']),
        ]);
        $this->assertTrue(app(ClickChatWhatsAppService::class)->sendText('0500000000', 'contract update')['success']);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/templates?channel_account_id=3'));
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/messages/send') && $r['template_name'] === 'contract_update'
            && $r['template_vars'] === ['Ali', '42'] && !isset($r['body']));
    }

    public function test_pending_bound_template_blocks_the_message(): void
    {
        $this->app->instance(\App\Services\WhatsApp\ClickChatTemplateBinding::class,
            Mockery::mock(\App\Services\WhatsApp\ClickChatTemplateBinding::class)->shouldReceive('resolve')
                ->andReturn(['name' => 'pending_update', 'language' => 'ar', 'vars' => []])->getMock());
        Http::fake(['*/channels' => Http::response(['data' => [['id' => 3, 'type' => 'whatsapp_cloud']]]),
            '*/templates*' => Http::response(['data' => []])]);
        $result = app(ClickChatWhatsAppService::class)->sendText('0500000000', 'update');
        $this->assertFalse($result['success']);
        $this->assertSame('template_binding_error', $result['error']);
        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/messages/send'));
    }

    public function test_creation_is_submitted_to_live_chat(): void
    {
        Http::fake(['*/templates' => Http::response(['data' => ['id' => 1, 'status' => 'PENDING']], 201)]);
        $this->assertTrue(app(ClickChatWhatsAppService::class)->create([
            'name' => 'contract_update', 'language' => 'ar', 'category' => 'UTILITY', 'body' => 'Updated {{1}}', 'examples' => ['42'],
        ])['success']);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && $r->url() === 'https://chat.example.test/api/v1/templates'
            && $r['channel_account_id'] === 3 && $r['examples'] === ['42']);
    }

    public function test_status_snapshot_reads_all_states_for_the_selected_number(): void
    {
        Http::fake(['*/templates*' => Http::response(['data' => [['name' => 'contract_update', 'language' => 'ar', 'status' => 'PENDING']],
            'meta' => ['includes_all_statuses' => true]])]);
        $result = app(ClickChatWhatsAppService::class)->templateStatusSnapshot();
        $this->assertTrue($result['success']);
        $this->assertSame('PENDING', $result['templates'][0]['status']);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'channel_account_id=3') && str_contains($r->url(), 'include_status=1'));
    }

    public function test_old_api_and_outage_cannot_be_mistaken_for_a_missing_template(): void
    {
        Http::fakeSequence()->push(['data' => []])->push(['message' => 'Unavailable'], 503);
        $service = app(ClickChatWhatsAppService::class);
        $this->assertSame('api_outdated', $service->templateStatusSnapshot()['error']);
        $this->assertSame('unavailable', $service->templateStatusSnapshot()['error']);
    }

    public function test_status_snapshot_distinguishes_authentication_and_permissions(): void
    {
        Http::fakeSequence()->push([], 401)->push([], 403)->push([], 404)->push([], 422);
        $service = app(ClickChatWhatsAppService::class);
        foreach (['auth_error', 'forbidden', 'api_missing', 'invalid_sender'] as $error) {
            $this->assertSame($error, $service->templateStatusSnapshot()['error']);
        }
    }

    public function test_status_badges_do_not_contact_clickchat_for_another_provider(): void
    {
        config(['services.whatsapp.driver' => 'meta']);
        Http::fake();
        $this->assertSame('other_provider', app(\App\Services\WhatsApp\ClickChatTemplateStatus::class)->snapshot()['error']);
        Http::assertNothingSent();
    }

    public function test_not_configured_does_not_call_the_platform(): void
    {
        config(['services.whatsapp.clickchat.api_token' => '']);
        Http::fake();

        $this->assertFalse(app(WhatsAppMessagingService::class)->isConfigured());
        $this->assertSame('not_configured', app(ClickChatWhatsAppService::class)->sendText('0500000000', 'Hi')['error']);
        Http::assertNothingSent();
    }
}
