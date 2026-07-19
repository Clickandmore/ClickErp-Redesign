<?php

namespace Tests\Unit;

use App\Services\WhatsApp\MetaWhatsAppService;
use App\Support\CommunicationSettings;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaWhatsAppServiceTest extends TestCase
{
    private MetaWhatsAppService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.graph_version' => 'v23.0',
            'services.whatsapp.phone_number_id' => '123456789',
            'services.whatsapp.access_token' => 'test-access-token',
            'services.whatsapp.webhook_verify_token' => 'verify-token',
            'services.whatsapp.app_secret' => 'test-app-secret',
            'services.whatsapp.default_country_code' => '966',
        ]);

        $this->service = app(MetaWhatsAppService::class);
    }

    public function test_it_verifies_meta_webhook_signatures(): void
    {
        $payload = '{"object":"whatsapp_business_account"}';
        $signature = 'sha256=' . hash_hmac('sha256', $payload, 'test-app-secret');

        $this->assertTrue($this->service->verifyWebhookSignature($payload, $signature));
        $this->assertFalse($this->service->verifyWebhookSignature($payload, 'sha256=invalid'));
        $this->assertFalse($this->service->verifyWebhookSignature($payload, null));
    }

    public function test_it_normalizes_saudi_and_international_numbers(): void
    {
        $this->assertSame('966501234567', $this->service->normalizePhone('050 123 4567'));
        $this->assertSame('966501234567', $this->service->normalizePhone('+966 50 123 4567'));
        $this->assertSame('201001234567', $this->service->normalizePhone('+20 100 123 4567'));
        $this->assertNull($this->service->normalizePhone('not-a-phone'));
    }

    public function test_it_sends_text_and_returns_the_meta_message_id(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [['id' => 'wamid.test-message']],
            ], 200),
        ]);

        $result = $this->service->sendText('0501234567', 'مرحبا');

        $this->assertTrue($result['success']);
        $this->assertSame('wamid.test-message', $result['message_id']);
        $this->assertSame('966501234567', $result['normalized_to']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v23.0/123456789/messages'
                && $request['to'] === '966501234567'
                && data_get($request->data(), 'text.body') === 'مرحبا'
                && $request->hasHeader('Authorization', 'Bearer test-access-token');
        });
    }

    public function test_it_can_test_the_saved_meta_connection(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response([
                'id' => '123456789',
                'display_phone_number' => '+966 50 123 4567',
                'verified_name' => 'Click ERP',
                'quality_rating' => 'GREEN',
            ], 200),
        ]);

        $result = $this->service->connectionStatus();

        $this->assertTrue($result['success']);
        $this->assertSame('Click ERP', data_get($result, 'response.verified_name'));
    }

    public function test_communication_secrets_are_encrypted_for_storage_and_restored(): void
    {
        $settings = [
            'email' => ['password' => 'mail-secret'],
            'sms' => [
                'api_key' => 'sms-secret',
                'taqnyat' => ['bearer_token' => 'taqnyat-secret'],
            ],
            'whatsapp' => [
                'meta' => [
                    'access_token' => 'meta-token',
                    'webhook_verify_token' => 'verify-token',
                    'app_secret' => 'app-secret',
                ],
                'ultramsg' => [
                    'instances' => [['token' => 'ultra-token']],
                ],
            ],
        ];

        $encrypt = new \ReflectionMethod(CommunicationSettings::class, 'encryptSecretsForStorage');
        $encrypt->setAccessible(true);
        $decrypt = new \ReflectionMethod(CommunicationSettings::class, 'decryptSecretsFromStorage');
        $decrypt->setAccessible(true);

        $encrypted = $encrypt->invoke(null, $settings);

        $this->assertStringStartsWith('encrypted:', data_get($encrypted, 'whatsapp.meta.access_token'));
        $this->assertStringNotContainsString('meta-token', json_encode($encrypted));
        $this->assertSame($settings, $decrypt->invoke(null, $encrypted));
    }
}
