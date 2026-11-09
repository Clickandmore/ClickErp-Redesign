<?php

namespace Tests\Feature;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClickChatWebhookTest extends TestCase
{
    use DatabaseTransactions;

    private const SECRET = 'whsec_feature_test';

    protected function setUp(): void
    {
        parent::setUp();
        config(['queue.default' => 'sync', 'cache.default' => 'array', 'services.whatsapp.driver' => 'clickchat',
            'services.whatsapp.default_country_code' => '966',
            'services.whatsapp.clickchat' => ['base_url' => 'https://chat.example.test', 'api_token' => '12|token',
                'webhook_secret' => self::SECRET, 'default_channel_account_id' => '3']]);
    }

    private function deliver(array $payload, ?string $secret = null, ?int $time = null)
    {
        $raw = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $t = $time ?? time();
        $signature = "t={$t},v1=" . hash_hmac('sha256', "{$t}.{$raw}", $secret ?? self::SECRET);

        return $this->call('POST', route('api.webhooks.clickchat'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_WEBHOOK_EVENT' => $payload['event'] ?? '',
            'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $raw);
    }

    private function event(string $event, int $id, string $direction, string $status, array $extra = []): array
    {
        return [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'message' => $extra + ['id' => $id, 'conversation_id' => 57, 'direction' => $direction, 'type' => 'text',
                'body' => 'مرحباً', 'status' => $status, 'error' => null, 'reference' => 'INV-1001',
                'media_url' => null, 'sent_at' => now()->toIso8601String()],
            'contact' => ['id' => 42, 'name' => 'Ahmed Ali', 'phone_e164' => '+966500000777'],
            'conversation' => ['id' => 57, 'status' => 'open', 'assigned_user_id' => null],
            'channel_account' => ['id' => 3, 'type' => 'whatsapp_cloud', 'name' => '+966511111111'],
        ];
    }

    public function test_bad_or_old_signature_is_rejected_and_nothing_is_saved(): void
    {
        $this->deliver($this->event('message.received', 9001, 'in', 'delivered'), 'whsec_wrong')->assertStatus(401);
        $this->deliver($this->event('message.received', 9001, 'in', 'delivered'), null, time() - 600)->assertStatus(401);

        $this->assertDatabaseMissing('whatsapp_messages', ['platform_message_id' => 9001]);
    }

    public function test_ping_is_acknowledged(): void
    {
        $this->deliver(['event' => 'test.ping'])->assertOk();
    }

    public function test_incoming_message_creates_conversation_once_even_when_delivered_twice(): void
    {
        $payload = $this->event('message.received', 9002, 'in', 'delivered', ['type' => 'image', 'body' => '',
            'media_url' => 'https://chat.example.test/media/1.jpg']);

        $this->deliver($payload)->assertOk();
        $this->deliver($payload)->assertOk();

        $this->assertSame(1, WhatsAppMessage::where('platform_message_id', 9002)->count());
        $message = WhatsAppMessage::where('platform_message_id', 9002)->first();
        $this->assertSame('inbound', $message->direction);
        $this->assertSame('clickchat', $message->provider);
        $this->assertSame('صورة', $message->body);
        $this->assertSame('https://chat.example.test/media/1.jpg', $message->media_url);

        $conversation = WhatsAppConversation::withoutBranchScope()->where('phone', '966500000777')->first();
        $this->assertSame(57, (int) $conversation->platform_conversation_id);
        $this->assertSame('Ahmed Ali', $conversation->profile_name);
        $this->assertTrue($conversation->customerServiceWindowIsOpen());
    }

    public function test_status_only_moves_forward_even_when_events_arrive_out_of_order(): void
    {
        // The status arrives before message.sent, then read before delivered.
        $this->deliver($this->event('message.status', 9003, 'out', 'read'))->assertOk();
        $this->deliver($this->event('message.sent', 9003, 'out', 'sent'))->assertOk();
        $this->deliver($this->event('message.status', 9003, 'out', 'delivered'))->assertOk();

        $message = WhatsAppMessage::where('platform_message_id', 9003)->firstOrFail();
        $this->assertSame('read', $message->status);
        $this->assertSame('INV-1001', $message->reference);
        $this->assertNotNull($message->read_at);
    }

    public function test_failed_status_keeps_the_reason(): void
    {
        $this->deliver($this->event('message.sent', 9004, 'out', 'sent'))->assertOk();
        $this->deliver($this->event('message.status', 9004, 'out', 'failed', ['error' => 'Recipient is not on WhatsApp']))->assertOk();

        $message = WhatsAppMessage::where('platform_message_id', 9004)->firstOrFail();
        $this->assertSame('failed', $message->status);
        $this->assertSame('Recipient is not on WhatsApp', $message->error_message);
    }

    public function test_send_saves_the_outbound_row_and_webhook_updates_the_same_row(): void
    {
        Http::fake(['*' => Http::response(['message_id' => 9005, 'conversation_id' => 57, 'contact_id' => 42,
            'channel_account_id' => 3, 'status' => 'sent'])]);

        $result = app(\App\Services\WhatsApp\WhatsAppMessagingService::class)
            ->sendText('0500000777', 'فاتورتك جاهزة', ['reference' => 'INV-2002']);
        $this->assertTrue($result['success']);
        $this->deliver($this->event('message.status', 9005, 'out', 'delivered'))->assertOk();

        $rows = WhatsAppMessage::where('platform_message_id', 9005)->get();
        $this->assertCount(1, $rows);
        $this->assertSame('delivered', $rows[0]->status);
        $this->assertSame('INV-2002', $rows[0]->reference);
        $this->assertDatabaseHas('outbound_message_logs', ['provider' => 'clickchat', 'provider_message_id' => '9005', 'status' => 'delivered']);
    }

    public function test_settings_store_keys_encrypted_and_keep_them_when_left_empty(): void
    {
        $section = ['driver' => 'clickchat', 'clickchat' => ['base_url' => 'https://chat.example.test/',
            'api_token' => '12|plain-token', 'webhook_secret' => 'whsec_abc', 'default_channel_account_id' => '7']];
        \App\Support\CommunicationSettings::persistSection('whatsapp', $section);

        $stored = (string) \App\Models\NewSettings::where('key', 'communication_channels')->value('value');
        $this->assertStringNotContainsString('plain-token', $stored);
        $this->assertStringNotContainsString('whsec_abc', $stored);

        $section['clickchat']['api_token'] = '';
        $section['clickchat']['webhook_secret'] = '';
        \App\Support\CommunicationSettings::persistSection('whatsapp', $section);

        $saved = \App\Support\CommunicationSettings::whatsapp()['clickchat'];
        $this->assertSame('https://chat.example.test', $saved['base_url']);
        $this->assertSame('12|plain-token', $saved['api_token']);
        $this->assertSame('whsec_abc', $saved['webhook_secret']);
        $this->assertSame('7', $saved['default_channel_account_id']);
    }
}
