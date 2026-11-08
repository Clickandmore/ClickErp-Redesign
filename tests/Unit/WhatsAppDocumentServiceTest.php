<?php

namespace Tests\Unit;

use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\WhatsAppDocumentService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class WhatsAppDocumentServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'services.whatsapp.default_country_code' => '966',
            'services.whatsapp.phone_number_id' => '123', 'services.whatsapp.access_token' => 'test-only',
            'services.whatsapp.graph_version' => 'v23.0']);
        DB::purge('sqlite');
    }

    private function service(string $driver): WhatsAppDocumentService
    {
        $messaging = Mockery::mock(WhatsAppMessagingService::class);
        $messaging->shouldReceive('driver')->andReturn($driver);
        $messaging->shouldReceive('isConfigured')->andReturn(true);
        $messaging->shouldReceive('resolveSender')->andReturn(['instance_id' => 'instance1', 'token' => 'test-only']);
        return new WhatsAppDocumentService($messaging, app(MetaWhatsAppService::class));
    }

    public function test_meta_sends_a_document_with_a_filename_and_preserves_provider_id(): void
    {
        Http::fake(['*' => Http::response(['messages' => [['id' => 'wamid.123']]])]);
        $result = $this->service('meta')->send('0501234567', 'https://example.test/cv.pdf?signature=test', 'PAULINE-KE-00123.pdf');
        $this->assertTrue($result['success']);
        $this->assertSame('wamid.123', $result['message_id']);
        Http::assertSent(fn ($r) => $r->url() === 'https://graph.facebook.com/v23.0/123/messages'
            && $r['to'] === '966501234567' && $r['type'] === 'document'
            && $r['document']['filename'] === 'PAULINE-KE-00123.pdf'
            && $r['document']['link'] === 'https://example.test/cv.pdf?signature=test');
        Http::assertSentCount(1);
    }

    public function test_meta_does_not_treat_http_200_without_message_id_as_acceptance(): void
    {
        Http::fake(['*' => Http::response(['error' => ['message' => 'Rejected']])]);
        $this->assertFalse($this->service('meta')->send('0501234567', 'https://example.test/cv.pdf', 'cv.pdf')['success']);
        Http::assertSentCount(1);
    }

    public function test_ultramsg_posts_document_form_fields(): void
    {
        Http::fake(['*' => Http::response(['sent' => 'true', 'id' => 789])]);
        $result = $this->service('ultramsg')->send('0501234567', 'https://example.test/cv.pdf', 'KE-00123.pdf');
        $this->assertTrue($result['success']);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/instance1/messages/document')
            && $r['document'] === 'https://example.test/cv.pdf' && $r['filename'] === 'KE-00123.pdf');
        Http::assertSentCount(1);
    }

    public function test_chatwoot_uploads_the_private_snapshot_as_a_public_attachment(): void
    {
        config(['services.whatsapp.chatwoot' => ['base_url' => 'https://chat.example.test',
            'account_id' => 76, 'inbox_id' => 12, 'access_token' => 'test-only']]);
        Storage::fake('local');
        Storage::disk('local')->put('maid-cv-shares/test.pdf', '%PDF-actual-snapshot');
        Http::fakeSequence()->push(['channel_type' => 'Channel::Whatsapp'])
            ->push(['payload' => [['id' => 3, 'phone_number' => '+966501234567']]])
            ->push(['payload' => [['id' => 7, 'inbox_id' => 12, 'status' => 'open', 'can_reply' => true]]])
            ->push(['id' => 123, 'status' => 'sent']);
        $result = $this->service('chatwoot')->send('0501234567', 'https://example.test/cv.pdf', 'KE-00123.pdf',
            ['attachment_path' => 'maid-cv-shares/test.pdf']);
        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) {
            if (!str_ends_with($request->url(), '/conversations/7/messages')) return false;
            $body = $request->body();
            return str_contains($body, 'attachments[]') && str_contains($body, 'KE-00123.pdf')
                && str_contains($body, '%PDF-actual-snapshot') && str_contains($body, 'outgoing')
                && str_contains($body, 'false');
        });
        Http::assertSentCount(4);
    }

    public function test_invalid_phone_does_not_contact_meta(): void
    {
        Http::fake();
        $this->assertFalse($this->service('meta')->send('invalid', 'https://example.test/cv.pdf', 'cv.pdf')['success']);
        Http::assertNothingSent();
    }
}
