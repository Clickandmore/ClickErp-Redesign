<?php

namespace Tests\Unit;

use App\Services\WhatsApp\ChatwootWhatsAppService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatwootWhatsAppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'services.whatsapp.driver' => 'chatwoot',
            'services.whatsapp.default_country_code' => '966',
            'services.whatsapp.chatwoot' => ['base_url' => 'https://chat.example.test',
                'account_id' => 76, 'inbox_id' => 12, 'access_token' => 'test-secret']]);
    }

    public function test_connection_test_only_reads_inboxes(): void
    {
        Http::fake(['*' => Http::response(['payload' => [['id' => 12, 'name' => 'Support', 'channel_type' => 'Channel::Whatsapp']]])]);
        $result = app(ChatwootWhatsAppService::class)->connectionStatus();
        $this->assertTrue($result['success']);
        $this->assertSame(12, $result['inboxes'][0]['id']);
        Http::assertSent(fn ($r) => $r->method() === 'GET' && $r->url() === 'https://chat.example.test/api/v1/accounts/76/inboxes'
            && $r->hasHeader('api_access_token', 'test-secret'));
        Http::assertSentCount(1);
    }

    public function test_existing_contact_uses_only_the_selected_inbox_and_sends_publicly(): void
    {
        Http::fakeSequence()->push(['channel_type' => 'Channel::Whatsapp'])
            ->push(['payload' => [['id' => 3, 'phone_number' => '+966501234567']]])
            ->push(['payload' => [['id' => 99, 'inbox_id' => 44, 'status' => 'open', 'can_reply' => true], ['id' => 7, 'inbox_id' => 12, 'status' => 'open', 'can_reply' => true]]])
            ->push(['id' => 123, 'status' => 'sent']);
        $result = app(WhatsAppMessagingService::class)->sendText('0501234567', 'Hello');
        $this->assertTrue($result['success']);
        $this->assertSame('123', $result['message_id']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/conversations/7/messages')
            && $r['private'] === false && $r['message_type'] === 'outgoing' && $r['content'] === 'Hello');
        Http::assertSentCount(4);
    }

    public function test_new_contact_and_conversation_sequence_with_template(): void
    {
        Http::fakeSequence()->push(['channel_type' => 'Channel::Whatsapp'])
            ->push(['payload' => []])
            ->push(['payload' => ['contact' => ['id' => 3, 'contact_inboxes' => [['source_id' => 'source-3', 'inbox' => ['id' => 12]]]]]])
            ->push(['payload' => []])->push(['id' => 7])->push(['id' => 123]);
        $params = ['name' => 'hello', 'language' => 'ar', 'category' => 'UTILITY'];
        $this->assertTrue(app(ChatwootWhatsAppService::class)->sendText('0501234567', 'Hello', ['template_params' => $params])['success']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/contacts') && $r['phone_number'] === '+966501234567' && $r['inbox_id'] === 12);
        Http::assertSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/conversations') && $r['source_id'] === 'source-3' && $r['contact_id'] === 3);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/messages') && $r['template_params'] === $params);
    }

    public function test_failure_is_not_retried_and_response_secrets_are_not_exposed(): void
    {
        Http::fakeSequence()->push(['channel_type' => 'Channel::Whatsapp'])
            ->push(['payload' => [['id' => 3, 'phone_number' => '+966501234567']]])
            ->push(['payload' => [['id' => 7, 'inbox_id' => 12, 'status' => 'open', 'can_reply' => true]]])
            ->push(['error' => 'test-secret'], 429);
        $result = app(ChatwootWhatsAppService::class)->sendText('0501234567', 'Hello');
        $this->assertFalse($result['success']);
        $this->assertStringNotContainsString('test-secret', json_encode($result));
        Http::assertSentCount(4);
    }

    public function test_invalid_phone_or_missing_inbox_never_calls_provider(): void
    {
        Http::fake();
        $service = app(ChatwootWhatsAppService::class);
        $this->assertFalse($service->sendText('invalid', 'Hello')['success']);
        config(['services.whatsapp.chatwoot.inbox_id' => null]);
        $this->assertFalse($service->sendText('0501234567', 'Hello', ['template_params' => ['name' => 'hello']])['success']);
        Http::assertNothingSent();
    }

    public function test_wrong_channel_never_creates_contacts_or_messages(): void
    {
        Http::fakeSequence()->push(['channel_type' => 'Channel::Email']);
        $this->assertFalse(app(ChatwootWhatsAppService::class)->sendText('0501234567', 'Hello', ['template_params' => ['name' => 'hello']])['success']);
        Http::assertSentCount(1);
    }

    public function test_settings_encrypt_and_decrypt_chatwoot_token(): void
    {
        $class = new \ReflectionClass(\App\Support\CommunicationSettings::class);
        $encrypt = $class->getMethod('encryptSecretsForStorage');
        $decrypt = $class->getMethod('decryptSecretsFromStorage');
        $encrypt->setAccessible(true);
        $decrypt->setAccessible(true);
        $data = ['whatsapp' => ['chatwoot' => ['access_token' => 'private-token']]];
        $stored = $encrypt->invoke(null, $data);
        $this->assertStringStartsWith('encrypted:', data_get($stored, 'whatsapp.chatwoot.access_token'));
        $this->assertStringNotContainsString('private-token', json_encode($stored));
        $this->assertSame('private-token', data_get($decrypt->invoke(null, $stored), 'whatsapp.chatwoot.access_token'));
    }

    public function test_closed_window_blocks_free_text_before_message_post(): void
    {
        Http::fakeSequence()->push(['channel_type' => 'Channel::Whatsapp'])
            ->push(['payload' => [['id' => 3, 'phone_number' => '+966501234567']]])
            ->push(['payload' => [['id' => 7, 'inbox_id' => 12, 'status' => 'open', 'can_reply' => false]]]);
        $this->assertFalse(app(ChatwootWhatsAppService::class)->sendText('0501234567', 'Hello')['success']);
        Http::assertSentCount(3);
    }

    public function test_approved_template_is_rendered_and_requires_variables(): void
    {
        Http::fake(['*' => Http::response(['message_templates' => [
            ['name' => 'welcome', 'language' => 'ar', 'category' => 'UTILITY', 'status' => 'APPROVED',
                'components' => [
                    ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Welcome'],
                    ['type' => 'BODY', 'text' => 'Hello {{1}}'],
                    ['type' => 'FOOTER', 'text' => 'Company'],
                ]],
            ['name' => 'pending', 'status' => 'PENDING'],
            ['name' => 'media', 'language' => 'ar', 'category' => 'UTILITY', 'status' => 'APPROVED',
                'components' => [['type' => 'HEADER', 'format' => 'IMAGE'], ['type' => 'BODY', 'text' => 'Hi']]],
            ['name' => 'header_variable', 'language' => 'ar', 'category' => 'UTILITY', 'status' => 'APPROVED',
                'components' => [['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Hi {{name}}'], ['type' => 'BODY', 'text' => 'Welcome']]],
        ]])]);
        $service = app(ChatwootWhatsAppService::class);
        $this->assertCount(1, $service->templates());
        $prepared = $service->prepareTemplate('welcome|ar', ['1' => 'Ahmed']);
        $this->assertSame('Hello Ahmed', $prepared['message']);
        $this->assertSame('Ahmed', $prepared['template_params']['processed_params']['body'][1]);
        $this->expectException(\RuntimeException::class);
        $service->prepareTemplate('welcome|ar', []);
    }

    public function test_existing_contact_is_linked_before_creating_conversation(): void
    {
        Http::fakeSequence()->push(['channel_type' => 'Channel::Whatsapp'])
            ->push(['payload' => [['id' => 3, 'phone_number' => '+966501234567']]])
            ->push(['payload' => []])->push(['source_id' => 'linked-source'])
            ->push(['id' => 7])->push(['id' => 123]);
        $this->assertTrue(app(ChatwootWhatsAppService::class)->sendText('0501234567', 'Hello', ['template_params' => ['name' => 'hello']])['success']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/contacts/3/contact_inboxes') && $r['inbox_id'] === 12);
        Http::assertSentCount(6);
    }
}
